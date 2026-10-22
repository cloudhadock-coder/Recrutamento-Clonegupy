<?php

use App\Enums\ApplicationEventType;
use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Mail\ApplicationRejected;
use App\Mail\ApplicationStageChanged;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    $this->job = Job::factory()->published()->create();
    $this->recruiter = recruiter($this->job->company);
    $this->application = Application::factory()->create(['job_id' => $this->job->id]);
    $this->stages = $this->job->stages()->get();
});

test('moving a card to another stage updates the application and records an event', function () {
    $target = $this->stages[2];

    $this->actingAs($this->recruiter)
        ->patchJson(route('panel.applications.move', $this->application), ['job_stage_id' => $target->id])
        ->assertOk()
        ->assertJson(['id' => $this->application->id, 'job_stage_id' => $target->id]);

    expect($this->application->fresh()->job_stage_id)->toBe($target->id);

    expect($this->application->events()->sole())
        ->type->toBe(ApplicationEventType::StageChanged)
        ->from_stage_id->toBe($this->stages[0]->id)
        ->to_stage_id->toBe($target->id)
        ->user_id->toBe($this->recruiter->id)
        ->company_id->toBe($this->job->company_id);
});

test('stage change queues an e-mail to the candidate', function () {
    $this->actingAs($this->recruiter)
        ->patchJson(route('panel.applications.move', $this->application), ['job_stage_id' => $this->stages[1]->id])
        ->assertOk();

    Mail::assertQueued(ApplicationStageChanged::class, fn (ApplicationStageChanged $mail) => $mail->hasTo($this->application->candidate->email)
        && $mail->application->stage->is($this->stages[1]));
    Mail::assertNotQueued(ApplicationRejected::class);
});

test('each movement is recorded in the history', function () {
    foreach ([1, 2, 1] as $index) {
        $this->actingAs($this->recruiter)
            ->patchJson(route('panel.applications.move', $this->application), ['job_stage_id' => $this->stages[$index]->id])
            ->assertOk();
    }

    expect($this->application->events()->orderBy('id')->pluck('to_stage_id')->all())
        ->toBe([$this->stages[1]->id, $this->stages[2]->id, $this->stages[1]->id]);
    Mail::assertQueuedCount(3);
});

test('dropping a card on its current stage does nothing', function () {
    $this->actingAs($this->recruiter)
        ->patchJson(route('panel.applications.move', $this->application), ['job_stage_id' => $this->stages[0]->id])
        ->assertOk();

    expect($this->application->events()->count())->toBe(0);
    Mail::assertNothingQueued();
});

test('cannot move to a stage of another job of the same company', function () {
    $otherJob = Job::factory()->published()->for($this->job->company)->create();

    $this->actingAs($this->recruiter)
        ->patchJson(route('panel.applications.move', $this->application), ['job_stage_id' => $otherJob->stages()->first()->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('job_stage_id');

    expect($this->application->fresh()->job_stage_id)->toBe($this->stages[0]->id);
});

test('managers have read-only access to the pipeline', function () {
    $manager = recruiter($this->job->company, UserRole::Manager);

    $this->actingAs($manager)->get(route('panel.jobs.show', $this->job))->assertOk();

    $this->actingAs($manager)
        ->patchJson(route('panel.applications.move', $this->application), ['job_stage_id' => $this->stages[1]->id])
        ->assertForbidden();

    expect($this->application->events()->count())->toBe(0);
});

test('rejecting an application records the event and queues an e-mail', function () {
    $this->actingAs($this->recruiter)
        ->post(route('panel.applications.reject', $this->application), ['reason' => 'Perfil sem experiência exigida'])
        ->assertRedirect();

    expect($this->application->fresh())
        ->status->toBe(ApplicationStatus::Rejected)
        ->rejected_at->not->toBeNull();

    expect($this->application->events()->sole())
        ->type->toBe(ApplicationEventType::Rejected)
        ->meta->toBe(['reason' => 'Perfil sem experiência exigida']);

    Mail::assertQueued(ApplicationRejected::class, fn (ApplicationRejected $mail) => $mail->hasTo($this->application->candidate->email));
});

test('rejected applications must be reactivated before moving', function () {
    $this->actingAs($this->recruiter)->post(route('panel.applications.reject', $this->application));

    $this->actingAs($this->recruiter)
        ->patchJson(route('panel.applications.move', $this->application), ['job_stage_id' => $this->stages[1]->id])
        ->assertUnprocessable();

    $this->actingAs($this->recruiter)->post(route('panel.applications.reactivate', $this->application))->assertRedirect();

    $this->actingAs($this->recruiter)
        ->patchJson(route('panel.applications.move', $this->application), ['job_stage_id' => $this->stages[1]->id])
        ->assertOk();

    expect($this->application->events()->orderBy('id')->pluck('type')->all())->toBe([
        ApplicationEventType::Rejected,
        ApplicationEventType::Reactivated,
        ApplicationEventType::StageChanged,
    ]);
});

test('stage change e-mail renders with the stage name', function () {
    $this->application->load(['candidate', 'job.company'])->setRelation('stage', $this->stages[3]);

    (new ApplicationStageChanged($this->application))
        ->assertSeeInHtml($this->stages[3]->name)
        ->assertSeeInHtml($this->job->title)
        ->assertHasSubject("Sua candidatura para {$this->job->title} avançou");
});
