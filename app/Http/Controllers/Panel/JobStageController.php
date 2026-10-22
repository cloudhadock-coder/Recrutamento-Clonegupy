<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\JobStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Edição das etapas do pipeline de uma vaga.
 */
class JobStageController extends Controller
{
    public function index(Job $job): Response
    {
        Gate::authorize('update', $job);

        return Inertia::render('Panel/Jobs/Stages', [
            'job' => $job->only('id', 'title', 'status'),
            'stages' => $job->stages()->withCount('applications')->get(),
        ]);
    }

    public function store(Request $request, Job $job): RedirectResponse
    {
        Gate::authorize('update', $job);

        $validated = $request->validate(['name' => ['required', 'string', 'max:60']], attributes: ['name' => 'nome da etapa']);

        $job->stages()->create([
            'company_id' => $job->company_id,
            'name' => $validated['name'],
            'position' => (int) $job->stages()->max('position') + 1,
        ]);

        return back()->with('success', 'Etapa adicionada.');
    }

    public function update(Request $request, Job $job, JobStage $stage): RedirectResponse
    {
        Gate::authorize('update', $job);

        $stage->update($request->validate(['name' => ['required', 'string', 'max:60']], attributes: ['name' => 'nome da etapa']));

        return back()->with('success', 'Etapa renomeada.');
    }

    public function reorder(Request $request, Job $job): RedirectResponse
    {
        Gate::authorize('update', $job);

        $stageIds = $job->stages()->pluck('id');

        $validated = $request->validate([
            'stage_ids' => ['required', 'array', 'size:'.$stageIds->count()],
            'stage_ids.*' => ['integer', 'distinct', Rule::in($stageIds)],
        ]);

        DB::transaction(function () use ($validated): void {
            foreach ($validated['stage_ids'] as $position => $stageId) {
                JobStage::query()->whereKey($stageId)->update(['position' => $position]);
            }
        });

        return back()->with('success', 'Ordem das etapas atualizada.');
    }

    public function destroy(Job $job, JobStage $stage): RedirectResponse
    {
        Gate::authorize('update', $job);

        if ($job->stages()->count() <= 1) {
            throw ValidationException::withMessages(['stage' => 'A vaga precisa de pelo menos uma etapa.']);
        }

        if ($stage->applications()->exists()) {
            throw ValidationException::withMessages(['stage' => "Mova as candidaturas da etapa \"{$stage->name}\" antes de excluí-la."]);
        }

        $stage->delete();

        return back()->with('success', 'Etapa excluída.');
    }
}
