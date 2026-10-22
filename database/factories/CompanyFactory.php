<?php

namespace Database\Factories;

use App\Models\Company;
use App\Support\Cnpj;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tradeName = fake()->unique()->company();

        return [
            'name' => "{$tradeName} Ltda",
            'trade_name' => $tradeName,
            'slug' => Str::slug($tradeName).'-'.Str::lower(Str::random(4)),
            'cnpj' => Cnpj::generate(),
            'email' => fake()->companyEmail(),
            'phone' => '11'.fake()->numerify('3#######'),
            'state' => fake()->stateAbbr(),
            'about' => fake()->paragraph(),
        ];
    }
}
