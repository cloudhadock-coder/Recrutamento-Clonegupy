<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Cliente da API de Localidades do IBGE (sem chave). https://servicodados.ibge.gov.br/api/docs/localidades
 */
class IbgeLocalidades
{
    /**
     * Todos os municípios do Brasil.
     *
     * @return list<array{id: int, name: string, state: string}>
     *
     * @throws ConnectionException|RequestException
     */
    public function municipalities(): array
    {
        $municipalities = Http::acceptJson()
            ->timeout(60)
            ->retry(3, 1000)
            ->get('https://servicodados.ibge.gov.br/api/v1/localidades/municipios')
            ->throw()
            ->json();

        return array_map(fn (array $municipality): array => [
            'id' => (int) $municipality['id'],
            'name' => $municipality['nome'],
            // Alguns municípios novos vêm sem microrregião; a região imediata sempre traz a UF.
            'state' => $municipality['microrregiao']['mesorregiao']['UF']['sigla']
                ?? $municipality['regiao-imediata']['regiao-intermediaria']['UF']['sigla'],
        ], $municipalities);
    }
}
