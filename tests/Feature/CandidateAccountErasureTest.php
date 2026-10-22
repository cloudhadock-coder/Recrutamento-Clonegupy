<?php

use App\Models\Application;
use App\Models\ApplicationEvent;
use App\Models\Candidate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->candidate = Candidate::factory()->create(['name' => 'Joana Silva', 'linkedin_url' => 'https://linkedin.com/in/joana']);

    Storage::disk('local')->put('resumes/1/joana.pdf', '%PDF-1.4');

    $this->application = Application::factory()->for($this->candidate)->create([
        'resume_path' => 'resumes/1/joana.pdf',
        'answers' => ['q_salario' => 'R$ 8.000'],
    ]);
    $this->application->forceFill(['resume_text' => 'Joana Silva - Desenvolvedora'])->save();

    ApplicationEvent::create([
        'company_id' => $this->application->company_id,
        'application_id' => $this->application->id,
        'type' => 'applied',
        'to_stage_id' => $this->application->job_stage_id,
    ]);
});

test('candidate anonymizes the account keeping only anonymous application records', function () {
    $this->actingAs($this->candidate, 'candidate')
        ->delete(route('candidate.account.destroy'), ['mode' => 'anonymize', 'password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest('candidate');

    expect($this->candidate->fresh())
        ->name->toBe('Candidato anonimizado')
        ->email->toEndWith('@anonimizado.invalid')
        ->phone->toBeNull()
        ->linkedin_url->toBeNull()
        ->anonymized_at->not->toBeNull();

    $application = $this->application->fresh();
    expect($application)
        ->resume_path->toBeNull()
        ->resume_text->toBeNull()
        ->answers->toBeNull()
        ->consent_at->not->toBeNull()
        ->and($application->events()->count())->toBe(1);

    Storage::disk('local')->assertMissing('resumes/1/joana.pdf');
});

test('candidate deletes the account and all applications', function () {
    $this->actingAs($this->candidate, 'candidate')
        ->delete(route('candidate.account.destroy'), ['mode' => 'delete', 'password' => 'password'])
        ->assertRedirect('/');

    $this->assertGuest('candidate');

    expect(Candidate::find($this->candidate->id))->toBeNull()
        ->and(Application::count())->toBe(0)
        ->and(ApplicationEvent::count())->toBe(0);

    Storage::disk('local')->assertMissing('resumes/1/joana.pdf');
});

test('account erasure requires the current password', function () {
    $this->actingAs($this->candidate, 'candidate')
        ->delete(route('candidate.account.destroy'), ['mode' => 'delete', 'password' => 'errada'])
        ->assertSessionHasErrors('password');

    expect($this->candidate->fresh())->not->toBeNull();
});

test('anonymized candidates no longer receive e-mails', function () {
    Mail::fake();

    $this->actingAs($this->candidate, 'candidate')
        ->delete(route('candidate.account.destroy'), ['mode' => 'anonymize', 'password' => 'password']);

    $recruiter = recruiter($this->application->company);
    $nextStage = $this->application->job->stages()->skip(1)->first();

    $this->actingAs($recruiter)
        ->patchJson(route('panel.applications.move', $this->application), ['job_stage_id' => $nextStage->id])
        ->assertOk();

    Mail::assertNothingQueued();
});
