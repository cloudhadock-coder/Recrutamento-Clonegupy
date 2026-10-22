<?php

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->acme = Company::factory()->create();
    $this->rival = Company::factory()->create();

    $this->recruiter = recruiter($this->acme, UserRole::Admin);

    $this->ownJob = Job::factory()->published()->for($this->acme)->create(['title' => 'Vaga da Acme']);
    $this->rivalJob = Job::factory()->published()->for($this->rival)->create(['title' => 'Vaga da concorrente']);

    $this->ownApplication = Application::factory()->create(['job_id' => $this->ownJob->id]);
    $this->rivalApplication = Application::factory()->create(['job_id' => $this->rivalJob->id]);
});

test('recruiter only lists jobs from their own company', function () {
    $this->actingAs($this->recruiter)
        ->get(route('panel.jobs.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Panel/Jobs/Index')
            ->has('jobs.data', 1)
            ->where('jobs.data.0.id', $this->ownJob->id));
});

test('recruiter cannot access jobs from another company', function (string $method, string $routeName) {
    $this->actingAs($this->recruiter)
        ->{$method}(route($routeName, $this->rivalJob))
        ->assertNotFound();
})->with([
    'kanban' => ['get', 'panel.jobs.show'],
    'edit form' => ['get', 'panel.jobs.edit'],
    'update' => ['put', 'panel.jobs.update'],
    'publish' => ['post', 'panel.jobs.publish'],
    'pause' => ['post', 'panel.jobs.pause'],
    'delete' => ['delete', 'panel.jobs.destroy'],
    'stages' => ['get', 'panel.jobs.stages.index'],
]);

test('recruiter cannot load kanban columns from another company', function () {
    $this->actingAs($this->recruiter)
        ->getJson(route('panel.jobs.column', [$this->rivalJob, $this->rivalJob->stages()->first()]))
        ->assertNotFound();
});

test('recruiter only lists applications from their own company', function () {
    $this->actingAs($this->recruiter)
        ->get(route('panel.applications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('applications.data', 1)
            ->where('applications.data.0.id', $this->ownApplication->id)
            ->has('jobs', 1));
});

test('recruiter cannot access applications from another company', function (string $method, string $routeName, array $payload) {
    $this->actingAs($this->recruiter)
        ->{$method}(route($routeName, $this->rivalApplication), $payload)
        ->assertNotFound();

    expect($this->rivalApplication->fresh())
        ->job_stage_id->toBe($this->rivalApplication->job_stage_id)
        ->status->value->toBe('active');
})->with([
    'detail' => ['get', 'panel.applications.show', []],
    'resume download' => ['get', 'panel.applications.resume', []],
    'move stage' => ['patchJson', 'panel.applications.move', ['job_stage_id' => 1]],
    'reject' => ['post', 'panel.applications.reject', []],
]);

test('recruiter cannot move own application into a stage from another company', function () {
    $rivalStage = $this->rivalJob->stages()->orderByDesc('position')->first();

    $this->actingAs($this->recruiter)
        ->patchJson(route('panel.applications.move', $this->ownApplication), ['job_stage_id' => $rivalStage->id])
        ->assertNotFound();
});

test('policies deny access to records of another company', function () {
    $rivalAdmin = User::factory()->for($this->rival)->admin()->create();

    expect(Gate::forUser($this->recruiter)->denies('view', $this->rivalJob))->toBeTrue()
        ->and(Gate::forUser($this->recruiter)->denies('update', $this->rivalJob))->toBeTrue()
        ->and(Gate::forUser($this->recruiter)->denies('view', $this->rivalApplication))->toBeTrue()
        ->and(Gate::forUser($this->recruiter)->denies('update', $this->rivalApplication))->toBeTrue()
        ->and(Gate::forUser($rivalAdmin)->allows('update', $this->rivalJob))->toBeTrue();
});

test('jobs created in the panel always belong to the recruiter company', function () {
    $this->actingAs($this->recruiter)->post(route('panel.jobs.store'), [
        'company_id' => $this->rival->id,
        'title' => 'Analista de Suporte',
        'description' => 'Atendimento a clientes.',
        'work_model' => 'remoto',
        'employment_type' => 'clt',
    ])->assertRedirect();

    expect(Job::withoutGlobalScopes()->where('title', 'Analista de Suporte')->sole())
        ->company_id->toBe($this->acme->id);
});
