<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\JobStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobStage>
 */
class JobStageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'company_id' => fn (array $attributes) => Job::withoutGlobalScopes()->findOrFail($attributes['job_id'])->company_id,
            'name' => fake()->words(2, true),
            'position' => fn (array $attributes) => (int) JobStage::withoutGlobalScopes()->where('job_id', $attributes['job_id'])->max('position') + 1,
        ];
    }
}
