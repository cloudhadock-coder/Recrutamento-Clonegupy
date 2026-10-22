<?php

namespace App\Http\Controllers\Panel;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Job;
use App\Models\JobStage;
use App\Services\ApplicationPipeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function __construct(private ApplicationPipeline $pipeline) {}

    /**
     * Todas as candidaturas da empresa, com paginação por cursor.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'job' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::enum(ApplicationStatus::class)],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $applications = Application::query()
            ->with(['candidate:id,name,email', 'job:id,title', 'stage:id,name'])
            ->when($filters['job'] ?? null, fn ($query, int $jobId) => $query->where('job_id', $jobId))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, fn ($query, string $term) => $query->whereHas(
                'candidate',
                fn ($query) => $query->where('name', 'ilike', "%{$term}%")->orWhere('email', 'ilike', "%{$term}%"),
            ))
            ->orderByDesc('id')
            ->cursorPaginate(25, ['id', 'job_id', 'candidate_id', 'job_stage_id', 'status', 'created_at'])
            ->withQueryString();

        return Inertia::render('Panel/Applications/Index', [
            'applications' => $applications,
            'filters' => $filters,
            'jobs' => Job::query()->latest('id')->get(['id', 'title']),
            'statuses' => ApplicationStatus::options(),
        ]);
    }

    public function show(Request $request, Application $application): Response
    {
        Gate::authorize('view', $application);

        $application->load([
            'candidate.city',
            'job.stages',
            'stage',
            'events' => fn ($query) => $query->orderByDesc('id'),
            'events.user:id,name',
            'events.fromStage:id,name',
            'events.toStage:id,name',
        ])->makeVisible('resume_text');

        return Inertia::render('Panel/Applications/Show', [
            'application' => $application,
            'hasResume' => $application->resume_path !== null,
            'canManage' => $request->user()->can('update', $application),
        ]);
    }

    public function resume(Application $application): StreamedResponse
    {
        Gate::authorize('view', $application);

        abort_if($application->resume_path === null, 404);

        return Storage::disk(config('filesystems.resumes'))->download(
            $application->resume_path,
            $application->resume_original_name ?? "curriculo-{$application->id}.pdf",
        );
    }

    /**
     * Mudança de etapa (drag-and-drop do kanban).
     */
    public function move(Request $request, Application $application): JsonResponse|RedirectResponse
    {
        Gate::authorize('update', $application);

        $validated = $request->validate(['job_stage_id' => ['required', 'integer']]);

        $stage = JobStage::query()->findOrFail($validated['job_stage_id']);

        $application = $this->pipeline->moveToStage($application, $stage, $request->user());

        if ($request->expectsJson() && ! $request->header('X-Inertia')) {
            return response()->json(['id' => $application->id, 'job_stage_id' => $application->job_stage_id]);
        }

        return back()->with('success', "Candidatura movida para \"{$stage->name}\".");
    }

    public function reject(Request $request, Application $application): RedirectResponse
    {
        Gate::authorize('update', $application);

        $validated = $request->validate(['reason' => ['nullable', 'string', 'max:255']], attributes: ['reason' => 'motivo']);

        $this->pipeline->reject($application, $request->user(), $validated['reason'] ?? null);

        return back()->with('success', 'Candidatura reprovada. O candidato será avisado por e-mail.');
    }

    public function reactivate(Request $request, Application $application): RedirectResponse
    {
        Gate::authorize('update', $application);

        $this->pipeline->reactivate($application, $request->user());

        return back()->with('success', 'Candidatura reativada.');
    }
}
