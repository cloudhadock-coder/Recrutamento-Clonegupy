<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Cliente do ViaCEP (sem chave). https://viacep.com.br
 */
class ViaCep
{
    /**
     * Endereço de um CEP, ou null se não existir.
     *
     * @return array{cep: string, street: string|null, neighborhood: string|null, city_id: int|null, city_name: string|null, state: string|null}|null
     *
     * @throws ConnectionException|RequestException quando o ViaCEP estiver indisponível
     */
    public function address(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) !== 8) {
            return null;
        }

        return Cache::remember("viacep:{$cep}", now()->addWeek(), function () use ($cep): ?array {
            $data = Http::acceptJson()
                ->timeout(8)
                ->retry(2, 300)
                ->get("https://viacep.com.br/ws/{$cep}/json/")
                ->json();

            if (! is_array($data) || ($data['erro'] ?? false)) {
                return null;
            }

            return [
                'cep' => $cep,
                'street' => $data['logradouro'] ?: null,
                'neighborhood' => $data['bairro'] ?: null,
                'city_id' => isset($data['ibge']) ? (int) $data['ibge'] : null,
                'city_name' => $data['localidade'] ?? null,
                'state' => $data['uf'] ?? null,
            ];
        });
    }
}
