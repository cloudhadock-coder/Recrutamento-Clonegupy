<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ApplicationEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreApplicationRequest;
use App\Jobs\ExtractResumeText;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Fluxo de candidatura: currículo em PDF, perguntas da vaga e consentimento LGPD.
 */
class ApplyController extends Controller
{
    public const DUPLICATE_MESSAGE = 'Você já se candidatou a esta vaga.';

    public function create(Request $request, Company $company, Job $job): Response|RedirectResponse
    {
        abort_unless($job->isOpen(), 404);

        if ($this->hasApplied($request->user('candidate'), $job)) {
            return to_route('portal.jobs.show', [$company, $job])->with('error', self::DUPLICATE_MESSAGE);
        }

        return Inertia::render('Portal/Apply', [
            'company' => ['name' => $company->displayName(), 'slug' => $company->slug],
            'job' => $job->only('id', 'title', 'slug', 'questions'),
            'consentVersion' => Application::CONSENT_VERSION,
        ]);
    }

    public function store(StoreApplicationRequest $request, Company $company, Job $job): RedirectResponse
    {
        abort_unless($job->isOpen(), 404);

        /** @var Candidate $candidate */
        $candidate = $request->user('candidate');

        if ($this->hasApplied($candidate, $job)) {
            throw ValidationException::withMessages(['application' => self::DUPLICATE_MESSAGE]);
        }

        $disk = Storage::disk(config('filesystems.resumes'));
        $resume = $request->file('resume');
        $resumePath = $resume->store("resumes/{$company->id}", config('filesystems.resumes'));

        try {
            $application = DB::transaction(function () use ($request, $candidate, $job, $resume, $resumePath): Application {
                $firstStage = $job->stages()->firstOrFail();

                $application = Application::create([
                    'company_id' => $job->company_id,
                    'job_id' => $job->id,
                    'candidate_id' => $candidate->id,
                    'job_stage_id' => $firstStage->id,
                    'answers' => $request->answers(),
                    'resume_path' => $resumePath,
                    'resume_original_name' => mb_substr($resume->getClientOriginalName(), 0, 255),
                    'consent_at' => now(),
                    'consent_version' => Application::CONSENT_VERSION,
                    'consent_ip' => $request->ip(),
                    'stage_entered_at' => now(),
                ]);

                $application->events()->create([
                    'company_id' => $job->company_id,
                    'type' => ApplicationEventType::Applied,
                    'to_stage_id' => $firstStage->id,
                ]);

                return $application;
            });
        } catch (UniqueConstraintViolationException) {
            // Duas requisições simultâneas: a constraint unique (job_id, candidate_id) barra a segunda.
            $disk->delete($resumePath);

            throw ValidationException::withMessages(['application' => self::DUPLICATE_MESSAGE]);
        }

        ExtractResumeText::dispatch($application);

        return to_route('candidate.applications.index')
            ->with('success', "Candidatura para \"{$job->title}\" enviada! Você receberá atualizações por e-mail.");
    }

    private function hasApplied(?Candidate $candidate, Job $job): bool
    {
        return $candidate !== null && $job->applications()->where('candidate_id', $candidate->id)->exists();
    }
}
