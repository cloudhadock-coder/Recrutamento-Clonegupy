<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class CitySeeder extends Seeder
{
    /**
     * Capitais e grandes cidades usadas quando a API do IBGE não estiver acessível (seed offline).
     */
    private const FALLBACK = [
        [3550308, 'São Paulo', 'SP'], [3509502, 'Campinas', 'SP'], [3304557, 'Rio de Janeiro', 'RJ'],
        [3106200, 'Belo Horizonte', 'MG'], [4106902, 'Curitiba', 'PR'], [4314902, 'Porto Alegre', 'RS'],
        [4205407, 'Florianópolis', 'SC'], [2927408, 'Salvador', 'BA'], [2611606, 'Recife', 'PE'],
        [2304400, 'Fortaleza', 'CE'], [5300108, 'Brasília', 'DF'], [5208707, 'Goiânia', 'GO'],
        [1302603, 'Manaus', 'AM'], [1501402, 'Belém', 'PA'], [3205309, 'Vitória', 'ES'],
    ];

    /**
     * Importa os municípios do IBGE uma única vez; depois disso a tabela local é a fonte.
     */
    public function run(): void
    {
        if (City::query()->count() > 1000) {
            return;
        }

        if (Artisan::call('ibge:import-cities') === 0) {
            $this->command?->info(trim(Artisan::output()));

            return;
        }

        $this->command?->warn('IBGE indisponível: usando lista reduzida de cidades. Rode `php artisan ibge:import-cities` depois.');

        City::query()->upsert(
            array_map(fn (array $city): array => ['id' => $city[0], 'name' => $city[1], 'state' => $city[2]], self::FALLBACK),
            ['id'],
            ['name', 'state'],
        );
    }
}
