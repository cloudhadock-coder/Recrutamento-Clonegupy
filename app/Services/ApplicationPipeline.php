<?php

namespace App\Services;

use App\Enums\ApplicationEventType;
use App\Enums\ApplicationStatus;
use App\Mail\ApplicationRejected;
use App\Mail\ApplicationStageChanged;
use App\Models\Application;
use App\Models\JobStage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * Movimentações de uma candidatura no processo seletivo. Toda movimentação gera um
 * registro em application_events e, quando o candidato precisa saber, um e-mail em fila.
 */
class ApplicationPipeline
{
    /**
     * @throws ValidationException
     */
    public function moveToStage(Application $application, JobStage $stage, User $recruiter): Application
    {
        if ($stage->job_id !== $application->job_id) {
            throw ValidationException::withMessages(['job_stage_id' => 'A etapa não pertence a esta vaga.']);
        }

        return DB::transaction(function () use ($application, $stage, $recruiter): Application {
            $application = $this->lock($application);

            if ($application->isRejected()) {
                throw ValidationException::withMessages(['job_stage_id' => 'Reative a candidatura antes de movê-la.']);
            }

            if ($application->job_stage_id === $stage->id) {
                return $application;
            }

            $fromStageId = $application->job_stage_id;

            $application->update(['job_stage_id' => $stage->id, 'stage_entered_at' => now()]);

            $this->record($application, ApplicationEventType::StageChanged, $recruiter, $fromStageId, $stage->id);

            $application->setRelation('stage', $stage);
            $this->notify($application, new ApplicationStageChanged($application));

            return $application;
        });
    }

    public function reject(Application $application, User $recruiter, ?string $reason = null): Application
    {
        return DB::transaction(function () use ($application, $recruiter, $reason): Application {
            $application = $this->lock($application);

            if ($application->isRejected()) {
                return $application;
            }

            $application->forceFill([
                'status' => ApplicationStatus::Rejected,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->record($application, ApplicationEventType::Rejected, $recruiter, $application->job_stage_id, null, ['reason' => $reason]);

            $this->notify($application, new ApplicationRejected($application));

            return $application;
        });
    }

    public function reactivate(Application $application, User $recruiter): Application
    {
        return DB::transaction(function () use ($application, $recruiter): Application {
            $application = $this->lock($application);

            if (! $application->isRejected()) {
                return $application;
            }

            $application->forceFill([
                'status' => ApplicationStatus::Active,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            $this->record($application, ApplicationEventType::Reactivated, $recruiter, null, $application->job_stage_id);

            return $application;
        });
    }

    /**
     * Relê a candidatura com lock de linha para serializar movimentações concorrentes.
     */
    private function lock(Application $application): Application
    {
        return Application::query()->whereKey($application->getKey())->lockForUpdate()->firstOrFail();
    }

    /**
     * @param  array<string, mixed>|null  $meta
     */
    private function record(Application $application, ApplicationEventType $type, User $recruiter, ?int $fromStageId, ?int $toStageId, ?array $meta = null): void
    {
        $application->events()->create([
            'company_id' => $application->company_id,
            'user_id' => $recruiter->id,
            'type' => $type,
            'from_stage_id' => $fromStageId,
            'to_stage_id' => $toStageId,
            'meta' => $meta,
        ]);
    }

    private function notify(Application $application, ApplicationStageChanged|ApplicationRejected $mailable): void
    {
        $application->loadMissing(['candidate', 'job.company', 'stage']);

        if ($application->candidate->isAnonymized()) {
            return;
        }

        Mail::to($application->candidate)->queue($mailable);
    }
}
