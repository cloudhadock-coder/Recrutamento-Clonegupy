<?php

use App\Enums\ApplicationEventType;
use App\Jobs\ExtractResumeText;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Job;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();

    $this->job = Job::factory()->published()->withQuestions()->create();
    $this->candidate = Candidate::factory()->create();
    $this->applyUrl = route('portal.apply.store', [$this->job->company->slug, $this->job->slug]);
});

function validApplication(array $overrides = []): array
{
    return [
        'resume' => UploadedFile::fake()->create('meu-curriculo.pdf', 120, 'application/pdf'),
        'answers' => ['q_salario' => 'R$ 5.000', 'q_inicio' => 'sim'],
        'consent' => true,
        ...$overrides,
    ];
}

test('candidate applies with resume, answers and LGPD consent', function () {
    $this->freezeSecond();

    $this->actingAs($this->candidate, 'candidate')
        ->post($this->applyUrl, validApplication())
        ->assertRedirect(route('candidate.applications.index'))
        ->assertSessionHasNoErrors();

    $application = Application::sole();
    $firstStage = $this->job->stages()->first();

    expect($application)
        ->candidate_id->toBe($this->candidate->id)
        ->company_id->toBe($this->job->company_id)
        ->job_stage_id->toBe($firstStage->id)
        ->answers->toEqual(['q_salario' => 'R$ 5.000', 'q_inicio' => 'sim'])
        ->resume_original_name->toBe('meu-curriculo.pdf')
        ->consent_at->toEqual(now())
        ->consent_version->toBe(Application::CONSENT_VERSION);

    Storage::disk('local')->assertExists($application->resume_path);

    expect($application->events()->sole())
        ->type->toBe(ApplicationEventType::Applied)
        ->to_stage_id->toBe($firstStage->id);

    Queue::assertPushed(ExtractResumeText::class, fn (ExtractResumeText $job) => $job->application->is($application));
});

test('the same candidate cannot apply twice to the same job', function () {
    $this->actingAs($this->candidate, 'candidate')->post($this->applyUrl, validApplication())->assertSessionHasNoErrors();

    $this->actingAs($this->candidate, 'candidate')
        ->post($this->applyUrl, validApplication())
        ->assertSessionHasErrors(['application' => 'Você já se candidatou a esta vaga.']);

    expect(Application::count())->toBe(1);
    expect(Storage::disk('local')->allFiles())->toHaveCount(1);
});

test('apply page redirects back when the candidate already applied', function () {
    Application::factory()->for($this->candidate)->create(['job_id' => $this->job->id]);

    $this->actingAs($this->candidate, 'candidate')
        ->get(route('portal.apply.create', [$this->job->company->slug, $this->job->slug]))
        ->assertRedirect(route('portal.jobs.show', [$this->job->company->slug, $this->job->slug]))
        ->assertSessionHas('error');
});

test('database rejects duplicated applications even without the controller check', function () {
    Application::factory()->for($this->candidate)->create(['job_id' => $this->job->id]);

    Application::factory()->for($this->candidate)->create(['job_id' => $this->job->id]);
})->throws(UniqueConstraintViolationException::class);

test('the candidate can apply to jobs of other companies', function () {
    $otherJob = Job::factory()->published()->create();

    $this->actingAs($this->candidate, 'candidate')->post($this->applyUrl, validApplication())->assertSessionHasNoErrors();
    $this->actingAs($this->candidate, 'candidate')
        ->post(route('portal.apply.store', [$otherJob->company->slug, $otherJob->slug]), validApplication(['answers' => []]))
        ->assertSessionHasNoErrors();

    expect($this->candidate->applications()->count())->toBe(2);
});

test('resume, required answers and consent are validated', function () {
    $this->actingAs($this->candidate, 'candidate')
        ->post($this->applyUrl, validApplication([
            'resume' => UploadedFile::fake()->create('foto.png', 50, 'image/png'),
            'answers' => ['q_inicio' => 'talvez'],
            'consent' => false,
        ]))
        ->assertSessionHasErrors(['resume', 'answers.q_salario', 'answers.q_inicio', 'consent']);

    expect(Application::count())->toBe(0);
});

test('guests are sent to the candidate login before applying', function () {
    $this->get(route('portal.apply.create', [$this->job->company->slug, $this->job->slug]))
        ->assertRedirect(route('candidate.login'));
});

test('paused jobs do not accept applications', function () {
    $this->job->pause();

    $this->actingAs($this->candidate, 'candidate')
        ->post($this->applyUrl, validApplication())
        ->assertNotFound();
});
