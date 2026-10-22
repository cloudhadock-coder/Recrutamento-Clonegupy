<?php

namespace App\Services;

use App\Support\Cnpj;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Cliente da BrasilAPI (sem chave). https://brasilapi.com.br/docs#tag/CNPJ
 */
class BrasilApi
{
    /**
     * Dados cadastrais de um CNPJ já normalizados para o cadastro de empresa, ou null se não existir.
     *
     * @return array{name: string, trade_name: string|null, email: string|null, phone: string|null, cep: string|null, street: string|null, number: string|null, complement: string|null, neighborhood: string|null, city_id: int|null, city_name: string|null, state: string|null}|null
     *
     * @throws ConnectionException|RequestException quando a BrasilAPI estiver indisponível
     */
    public function company(string $cnpj): ?array
    {
        $cnpj = Cnpj::digits($cnpj);

        return Cache::remember("brasilapi:cnpj:{$cnpj}", now()->addDay(), function () use ($cnpj): ?array {
            $response = Http::baseUrl('https://brasilapi.com.br/api')
                ->acceptJson()
                ->timeout(10)
                ->retry(2, 300, throw: false)
                ->get("/cnpj/v1/{$cnpj}");

            if ($response->notFound() || $response->badRequest()) {
                return null;
            }

            $data = $response->throw()->json();

            return [
                'name' => $data['razao_social'],
                'trade_name' => $data['nome_fantasia'] ?: null,
                'email' => isset($data['email']) ? mb_strtolower($data['email']) : null,
                'phone' => preg_replace('/\D/', '', (string) ($data['ddd_telefone_1'] ?? '')) ?: null,
                'cep' => preg_replace('/\D/', '', (string) ($data['cep'] ?? '')) ?: null,
                'street' => trim(($data['descricao_tipo_de_logradouro'] ?? '').' '.($data['logradouro'] ?? '')) ?: null,
                'number' => $data['numero'] ?? null,
                'complement' => $data['complemento'] ?: null,
                'neighborhood' => $data['bairro'] ?? null,
                'city_id' => $data['codigo_municipio_ibge'] ?? null,
                'city_name' => $data['municipio'] ?? null,
                'state' => $data['uf'] ?? null,
            ];
        });
    }
}
