<?php

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\WorkModel;
use App\Models\Company;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Etapas padrão são criadas pelo próprio model ao criar a vaga.
 *
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->jobTitle(),
            'description' => fake()->paragraphs(3, true),
            'requirements' => fake()->paragraph(),
            'benefits' => 'Vale-refeição, plano de saúde e auxílio home office.',
            'work_model' => WorkModel::Remote,
            'employment_type' => fake()->randomElement(EmploymentType::cases()),
            'questions' => [],
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Published,
            'published_at' => now()->subDays(fake()->numberBetween(0, 20)),
        ]);
    }

    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => JobStatus::Paused,
            'published_at' => now()->subMonth(),
        ]);
    }

    /**
     * @param  array<int, array{id: string, label: string, type: string, required: bool}>  $questions
     */
    public function withQuestions(array $questions = []): static
    {
        return $this->state(fn (array $attributes) => [
            'questions' => $questions ?: [
                ['id' => 'q_salario', 'label' => 'Qual sua pretensão salarial?', 'type' => 'text', 'required' => true],
                ['id' => 'q_inicio', 'label' => 'Tem disponibilidade para início imediato?', 'type' => 'yes_no', 'required' => false],
            ],
        ]);
    }
}
