<?php

namespace App\Http\Controllers\Portal;

use App\Enums\WorkModel;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Portal de carreiras público da empresa (renderizado com SSR).
 */
class JobBoardController extends Controller
{
    public function index(Request $request, Company $company): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'cidade' => ['nullable', 'integer'],
            'modelo' => ['nullable', Rule::enum(WorkModel::class)],
        ]);

        $term = trim($filters['q'] ?? '');

        $jobs = $company->jobs()
            ->open()
            ->with('city')
            ->when($term !== '', fn ($query) => $query->search($term))
            ->when($filters['cidade'] ?? null, fn ($query, int $cityId) => $query->where('city_id', $cityId))
            ->when($filters['modelo'] ?? null, fn ($query, string $workModel) => $query->where('work_model', $workModel))
            ->orderByDesc('published_at')
            ->paginate(12, ['id', 'company_id', 'city_id', 'title', 'slug', 'work_model', 'employment_type', 'published_at'])
            ->withQueryString();

        $cities = City::query()
            ->whereIn('id', $company->jobs()->open()->whereNotNull('city_id')->select('city_id'))
            ->orderBy('name')
            ->get();

        return Inertia::render('Portal/Index', [
            'company' => $this->publicCompany($company),
            'jobs' => $jobs,
            'filters' => $filters,
            'cities' => $cities->map(fn (City $city): array => ['value' => $city->id, 'label' => $city->label()]),
            'workModels' => WorkModel::options(),
        ]);
    }

    public function show(Request $request, Company $company, Job $job): Response
    {
        abort_unless($job->isOpen(), 404);

        $candidate = $request->user('candidate');

        return Inertia::render('Portal/Show', [
            'company' => $this->publicCompany($company),
            'job' => $job->load('city')->makeHidden(['company_id', 'questions']),
            'questionsCount' => count($job->questions),
            'alreadyApplied' => $candidate !== null && $job->applications()->where('candidate_id', $candidate->id)->exists(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function publicCompany(Company $company): array
    {
        return [
            'name' => $company->displayName(),
            'slug' => $company->slug,
            'about' => $company->about,
        ];
    }
}
