<?php

use App\Enums\UserRole;
use App\Models\Application;
use App\Models\ApplicationEvent;
use App\Models\Job;
use Illuminate\Database\Eloquent\Model;

/*
| Renderiza todas as telas com dados reais e Model::preventLazyLoading() ativo:
| qualquer lazy loading lança LazyLoadingViolationException e o teste falha.
*/

beforeEach(function () {
    expect(Model::preventsLazyLoading())->toBeTrue();

    $this->job = Job::factory()->published()->withQuestions()->create();
    $this->company = $this->job->company;
    $this->recruiter = recruiter($this->company, UserRole::Admin);

    // Lazy loading só é barrado em coleções com mais de um model.
    Job::factory()->paused()->for($this->company)->create();

    $this->applications = Application::factory()->count(3)->create([
        'job_id' => $this->job->id,
        'answers' => ['q_salario' => 'R$ 5.000', 'q_inicio' => 'sim'],
    ]);

    ApplicationEvent::create([
        'company_id' => $this->company->id,
        'application_id' => $this->applications[0]->id,
        'user_id' => $this->recruiter->id,
        'type' => 'stage_changed',
        'from_stage_id' => $this->job->stages()->first()->id,
        'to_stage_id' => $this->applications[0]->job_stage_id,
    ]);
});

test('recruiter panel screens render without lazy loading', function (string $routeName, Closure $parameters) {
    $this->actingAs($this->recruiter)
        ->get(route($routeName, $parameters->call($this)))
        ->assertOk();
})->with([
    'dashboard' => ['dashboard', fn () => []],
    'jobs' => ['panel.jobs.index', fn () => []],
    'new job' => ['panel.jobs.create', fn () => []],
    'edit job' => ['panel.jobs.edit', fn () => [$this->job]],
    'kanban' => ['panel.jobs.show', fn () => [$this->job]],
    'kanban with rejected' => ['panel.jobs.show', fn () => [$this->job, 'reprovados' => 1]],
    'kanban column' => ['panel.jobs.column', fn () => [$this->job, $this->job->stages()->first()]],
    'stages' => ['panel.jobs.stages.index', fn () => [$this->job]],
    'applications' => ['panel.applications.index', fn () => []],
    'application' => ['panel.applications.show', fn () => [$this->applications[0]]],
    'profile' => ['profile.edit', fn () => []],
]);

test('public and candidate screens render without lazy loading', function () {
    $candidate = $this->applications[0]->candidate;
    $otherJob = Job::factory()->published()->for($this->company)->withQuestions()->create();

    $this->get(route('home'))->assertOk();
    $this->get(route('portal.jobs.index', $this->company->slug))->assertOk();
    $this->get(route('portal.jobs.index', [$this->company->slug, 'q' => 'desenvolvedor', 'modelo' => 'remoto']))->assertOk();
    $this->get(route('portal.jobs.show', [$this->company->slug, $this->job->slug]))->assertOk();
    $this->get(route('candidate.login'))->assertOk();
    $this->get(route('candidate.register'))->assertOk();
    $this->get(route('register'))->assertOk();

    $this->actingAs($candidate, 'candidate');
    $this->get(route('portal.jobs.show', [$this->company->slug, $this->job->slug]))->assertOk();
    $this->get(route('portal.apply.create', [$this->company->slug, $otherJob->slug]))->assertOk();
    $this->get(route('candidate.applications.index'))->assertOk();
    $this->get(route('candidate.account.edit'))->assertOk();
});

test('application lists use cursor pagination', function () {
    $this->actingAs($this->recruiter)
        ->get(route('panel.applications.index'))
        ->assertInertia(fn ($page) => $page
            ->has('applications.next_cursor')
            ->has('applications.prev_cursor')
            ->missing('applications.total'));
});
