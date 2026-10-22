<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Company;
use App\Services\BrasilApi;
use App\Services\ViaCep;
use App\Support\Cnpj;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Consultas usadas pelos formulários: CNPJ (BrasilAPI), CEP (ViaCEP) e cidades (tabela local do IBGE).
 */
class LookupController extends Controller
{
    public function cnpj(string $cnpj, BrasilApi $brasilApi): JsonResponse
    {
        if (! Cnpj::isValid($cnpj)) {
            return response()->json(['message' => 'O CNPJ informado é inválido.'], 422);
        }

        if (Company::query()->where('cnpj', Cnpj::digits($cnpj))->exists()) {
            return response()->json(['message' => 'Esta empresa já está cadastrada.'], 409);
        }

        try {
            $company = $brasilApi->company($cnpj);
        } catch (HttpClientException) {
            return response()->json(['message' => 'Não foi possível consultar o CNPJ agora. Preencha os dados manualmente.'], 503);
        }

        if ($company === null) {
            return response()->json(['message' => 'CNPJ não encontrado na Receita Federal.'], 404);
        }

        return response()->json([
            ...$company,
            'city_id' => City::query()->whereKey($company['city_id'])->exists() ? $company['city_id'] : null,
            'slug' => $this->suggestSlug($company['trade_name'] ?: $company['name']),
        ]);
    }

    public function cep(string $cep, ViaCep $viaCep): JsonResponse
    {
        try {
            $address = $viaCep->address($cep);
        } catch (HttpClientException) {
            return response()->json(['message' => 'Não foi possível consultar o CEP agora.'], 503);
        }

        if ($address === null) {
            return response()->json(['message' => 'CEP não encontrado.'], 404);
        }

        return response()->json([
            ...$address,
            'city_id' => City::query()->whereKey($address['city_id'])->exists() ? $address['city_id'] : null,
        ]);
    }

    public function cities(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q'));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $cities = City::query()->search($term)->limit(10)->get();

        return response()->json($cities->map(fn (City $city): array => ['id' => $city->id, 'label' => $city->label()]));
    }

    private function suggestSlug(string $name): string
    {
        $base = Str::of($name)
            ->replaceMatches('/\b(ltda|me|epp|eireli|s\.?a\.?)\b\.?/i', '')
            ->slug()
            ->limit(50, '')
            ->trim('-')
            ->value() ?: 'empresa';

        $slug = $base;

        for ($suffix = 2; in_array($slug, Company::RESERVED_SLUGS, true) || Company::query()->where('slug', $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }
}
