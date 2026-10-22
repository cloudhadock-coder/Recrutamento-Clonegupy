<?php

use App\Enums\JobStatus;
use App\Enums\UserRole;
use App\Models\Application;
use App\Models\City;
use App\Models\Company;
use App\Models\Job;

beforeEach(function () {
    $this->company = Company::factory()->create(['slug' => 'acme']);
    $this->recruiter = recruiter($this->company);
});

test('new jobs start as drafts with the default pipeline stages', function () {
    $city = City::factory()->create();

    $this->actingAs($this->recruiter)->post(route('panel.jobs.store'), [
        'title' => 'Desenvolvedor PHP',
        'description' => 'Laravel e PostgreSQL.',
        'work_model' => 'hibrido',
        'employment_type' => 'clt',
        'city_id' => $city->id,
        'questions' => [['label' => 'Pretensão salarial?', 'type' => 'text', 'required' => true]],
    ])->assertSessionHasNoErrors();

    $job = Job::sole();

    expect($job)
        ->status->toBe(JobStatus::Draft)
        ->slug->toBe('desenvolvedor-php')
        ->and($job->stages()->pluck('name')->all())->toBe(Job::DEFAULT_STAGES)
        ->and($job->questions[0]['id'])->toStartWith('q_');
});

test('on-site jobs require a city', function () {
    $this->actingAs($this->recruiter)->post(route('panel.jobs.store'), [
        'title' => 'Caixa',
        'description' => 'Atendimento.',
        'work_model' => 'presencial',
        'employment_type' => 'clt',
    ])->assertSessionHasErrors('city_id');
});

test('recruiter edits, publishes and pauses jobs', function () {
    $job = Job::factory()->for($this->company)->create();

    $this->actingAs($this->recruiter)->put(route('panel.jobs.update', $job), [
        'title' => 'Novo título',
        'description' => 'Nova descrição.',
        'work_model' => 'remoto',
        'employment_type' => 'pj',
    ])->assertSessionHasNoErrors();

    expect($job->fresh())->title->toBe('Novo título')->employment_type->value->toBe('pj');

    $this->actingAs($this->recruiter)->post(route('panel.jobs.publish', $job));
    expect($job->fresh())->status->toBe(JobStatus::Published)->published_at->not->toBeNull();

    $this->actingAs($this->recruiter)->post(route('panel.jobs.pause', $job));
    expect($job->fresh()->status)->toBe(JobStatus::Paused);
});

test('managers cannot create or edit jobs', function () {
    $manager = recruiter($this->company, UserRole::Manager);
    $job = Job::factory()->for($this->company)->create();

    $this->actingAs($manager)->get(route('panel.jobs.create'))->assertForbidden();
    $this->actingAs($manager)->post(route('panel.jobs.publish', $job))->assertForbidden();
});

test('pipeline stages can be added, renamed, reordered and removed', function () {
    $job = Job::factory()->for($this->company)->create();
    $stages = $job->stages()->get();

    $this->actingAs($this->recruiter)->post(route('panel.jobs.stages.store', $job), ['name' => 'Teste prático']);
    $this->actingAs($this->recruiter)->patch(route('panel.jobs.stages.update', [$job, $stages[1]]), ['name' => 'Triagem de currículo']);

    $ids = $job->stages()->pluck('id')->reverse()->values()->all();
    $this->actingAs($this->recruiter)->put(route('panel.jobs.stages.reorder', $job), ['stage_ids' => $ids])->assertSessionHasNoErrors();

    expect($job->stages()->pluck('name')->first())->toBe('Teste prático')
        ->and($job->stages()->count())->toBe(count(Job::DEFAULT_STAGES) + 1);

    $this->actingAs($this->recruiter)->delete(route('panel.jobs.stages.destroy', [$job, $stages[1]]))->assertSessionHasNoErrors();
    expect($job->stages()->where('name', 'Triagem de currículo')->exists())->toBeFalse();
});

test('stages with applications cannot be removed', function () {
    $job = Job::factory()->published()->for($this->company)->create();
    $application = Application::factory()->create(['job_id' => $job->id]);

    $this->actingAs($this->recruiter)
        ->delete(route('panel.jobs.stages.destroy', [$job, $application->job_stage_id]))
        ->assertSessionHasErrors('stage');
});

test('public portal lists only open jobs of the company', function () {
    Job::factory()->published()->for($this->company)->create(['title' => 'Vaga aberta']);
    Job::factory()->paused()->for($this->company)->create(['title' => 'Vaga pausada']);
    Job::factory()->for($this->company)->create(['title' => 'Rascunho']);
    Job::factory()->published()->create(['title' => 'Vaga de outra empresa']);

    $this->get(route('portal.jobs.index', 'acme'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Portal/Index')
            ->has('jobs.data', 1)
            ->where('jobs.data.0.title', 'Vaga aberta'));
});

test('public portal searches by text, city and work model', function () {
    $curitiba = City::factory()->create(['name' => 'Curitiba', 'state' => 'PR']);

    Job::factory()->published()->for($this->company)->create([
        'title' => 'Desenvolvedora Back-end', 'requirements' => 'Experiência com PostgreSQL e filas', 'work_model' => 'remoto',
    ]);
    Job::factory()->published()->for($this->company)->create([
        'title' => 'Operador de Caixa', 'requirements' => 'Atendimento ao público', 'work_model' => 'presencial', 'city_id' => $curitiba->id,
    ]);

    $titles = fn (array $query) => collect($this->get(route('portal.jobs.index', ['acme', ...$query]))->viewData('page')['props']['jobs']['data'])
        ->pluck('title')->all();

    expect($titles(['q' => 'postgresql']))->toBe(['Desenvolvedora Back-end'])
        ->and($titles(['q' => 'desenvolvedores']))->toBe(['Desenvolvedora Back-end'])
        ->and($titles(['q' => 'operdor de caixa']))->toBe(['Operador de Caixa'])
        ->and($titles(['cidade' => $curitiba->id]))->toBe(['Operador de Caixa'])
        ->and($titles(['modelo' => 'remoto']))->toBe(['Desenvolvedora Back-end']);
});

test('public job page returns 404 for unpublished jobs', function () {
    $draft = Job::factory()->for($this->company)->create();

    $this->get(route('portal.jobs.show', ['acme', $draft->slug]))->assertNotFound();
});
