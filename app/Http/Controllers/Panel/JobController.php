<?php

namespace App\Http\Controllers\Panel;

use App\Enums\ApplicationStatus;
use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\WorkModel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\JobRequest;
use App\Http\Resources\KanbanCardResource;
use App\Models\Application;
use App\Models\Job;
use App\Models\JobStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    public const KANBAN_PAGE_SIZE = 20;

    public function index(Request $request): Response
    {
        $request->validate(['status' => ['nullable', Rule::enum(JobStatus::class)]]);

        $jobs = Job::query()
            ->with('city')
            ->withCount([
                'applications',
                'applications as active_applications_count' => fn ($query) => $query->where('status', ApplicationStatus::Active),
            ])
            ->when($request->query('status'), fn ($query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Panel/Jobs/Index', [
            'jobs' => $jobs,
            'filters' => ['status' => $request->query('status')],
            'statuses' => JobStatus::options(),
            'canCreate' => $request->user()->can('create', Job::class),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Job::class);

        return Inertia::render('Panel/Jobs/Form', [
            'job' => null,
            ...$this->formOptions(),
        ]);
    }

    public function store(JobRequest $request): RedirectResponse
    {
        Gate::authorize('create', Job::class);

        $job = Job::create($request->jobAttributes());

        return to_route('panel.jobs.stages.index', $job)
            ->with('success', 'Vaga criada como rascunho com as etapas padrão. Ajuste as etapas e publique quando estiver pronta.');
    }

    /**
     * Kanban de candidaturas da vaga.
     */
    public function show(Request $request, Job $job): Response
    {
        Gate::authorize('view', $job);

        $includeRejected = $request->boolean('reprovados');

        $stages = $job->stages()
            ->withCount(['applications' => fn ($query) => $query->when(! $includeRejected, fn ($query) => $query->where('status', ApplicationStatus::Active))])
            ->get();

        $job->load('city');

        return Inertia::render('Panel/Jobs/Kanban', [
            'job' => $job,
            'includeRejected' => $includeRejected,
            'canManage' => $request->user()->can('update', $job),
            'columns' => $stages->map(fn (JobStage $stage): array => [
                'stage' => $stage->only('id', 'name', 'position'),
                'count' => $stage->applications_count,
                ...$this->kanbanPage($stage, $includeRejected),
            ]),
        ]);
    }

    /**
     * Próxima página (por cursor) de uma coluna do kanban.
     */
    public function column(Request $request, Job $job, JobStage $stage): array
    {
        Gate::authorize('view', $job);

        return $this->kanbanPage($stage, $request->boolean('reprovados'));
    }

    public function edit(Job $job): Response
    {
        Gate::authorize('update', $job);

        return Inertia::render('Panel/Jobs/Form', [
            'job' => $job->load('city'),
            ...$this->formOptions(),
        ]);
    }

    public function update(JobRequest $request, Job $job): RedirectResponse
    {
        Gate::authorize('update', $job);

        $job->update($request->jobAttributes());

        return to_route('panel.jobs.index')->with('success', 'Vaga atualizada.');
    }

    public function publish(Job $job): RedirectResponse
    {
        Gate::authorize('update', $job);

        $job->publish();

        return back()->with('success', 'Vaga publicada no portal de carreiras.');
    }

    public function pause(Job $job): RedirectResponse
    {
        Gate::authorize('update', $job);

        $job->pause();

        return back()->with('success', 'Vaga pausada. Ela não aparece mais no portal.');
    }

    public function destroy(Job $job): RedirectResponse
    {
        Gate::authorize('delete', $job);

        $job->delete();

        return to_route('panel.jobs.index')->with('success', 'Vaga excluída.');
    }

    /**
     * @return array{data: mixed, next_cursor: string|null}
     */
    private function kanbanPage(JobStage $stage, bool $includeRejected): array
    {
        $page = Application::query()
            ->where('job_stage_id', $stage->id)
            ->when(! $includeRejected, fn ($query) => $query->where('status', ApplicationStatus::Active))
            ->with('candidate:id,name,email')
            ->orderByDesc('id')
            ->cursorPaginate(self::KANBAN_PAGE_SIZE, ['id', 'candidate_id', 'job_stage_id', 'status', 'created_at', 'stage_entered_at']);

        return [
            'data' => KanbanCardResource::collection($page->items())->resolve(),
            'next_cursor' => $page->nextCursor()?->encode(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'workModels' => WorkModel::options(),
            'employmentTypes' => EmploymentType::options(),
        ];
    }
}
