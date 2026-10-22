<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\Job;
use App\Models\JobStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Por padrão a candidatura entra na primeira etapa da vaga.
 *
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => Job::factory()->published(),
            'company_id' => fn (array $attributes) => Job::withoutGlobalScopes()->findOrFail($attributes['job_id'])->company_id,
            'candidate_id' => Candidate::factory(),
            'job_stage_id' => fn (array $attributes) => JobStage::withoutGlobalScopes()
                ->where('job_id', $attributes['job_id'])
                ->orderBy('position')
                ->value('id'),
            'answers' => [],
            'consent_at' => now(),
            'consent_version' => Application::CONSENT_VERSION,
            'consent_ip' => '127.0.0.1',
            'stage_entered_at' => now(),
        ];
    }

    public function inStage(JobStage $stage): static
    {
        return $this->state(fn (array $attributes) => [
            'job_id' => $stage->job_id,
            'job_stage_id' => $stage->id,
        ]);
    }
}
