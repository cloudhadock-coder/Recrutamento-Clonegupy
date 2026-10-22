<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Services\IbgeLocalidades;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\HttpClientException;

#[Signature('ibge:import-cities')]
#[Description('Importa (ou atualiza) os municípios da API de Localidades do IBGE para a tabela cities')]
class ImportIbgeCities extends Command
{
    public function handle(IbgeLocalidades $ibge): int
    {
        $this->components->info('Baixando municípios do IBGE...');

        try {
            $municipalities = $ibge->municipalities();
        } catch (HttpClientException $exception) {
            $this->components->error("Falha ao consultar o IBGE: {$exception->getMessage()}");

            return self::FAILURE;
        }

        foreach (array_chunk($municipalities, 1000) as $chunk) {
            City::query()->upsert($chunk, ['id'], ['name', 'state']);
        }

        $this->components->info(count($municipalities).' municípios importados.');

        return self::SUCCESS;
    }
}
