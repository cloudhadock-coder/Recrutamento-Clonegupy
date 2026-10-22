<?php

use App\Models\City;
use App\Models\Company;
use Illuminate\Support\Facades\Http;

test('CNPJ lookup fills company data from BrasilAPI', function () {
    City::factory()->create(['id' => 3550308, 'name' => 'São Paulo', 'state' => 'SP']);

    Http::fake([
        'brasilapi.com.br/api/cnpj/v1/11222333000181' => Http::response([
            'razao_social' => 'ACME TECNOLOGIA LTDA',
            'nome_fantasia' => 'ACME',
            'email' => 'CONTATO@ACME.COM.BR',
            'ddd_telefone_1' => '1133334444',
            'cep' => '01310100',
            'descricao_tipo_de_logradouro' => 'AVENIDA',
            'logradouro' => 'PAULISTA',
            'numero' => '1000',
            'complemento' => '',
            'bairro' => 'BELA VISTA',
            'municipio' => 'SAO PAULO',
            'codigo_municipio_ibge' => 3550308,
            'uf' => 'SP',
        ]),
    ]);

    $this->getJson(route('lookup.cnpj', '11222333000181'))
        ->assertOk()
        ->assertJson([
            'name' => 'ACME TECNOLOGIA LTDA',
            'trade_name' => 'ACME',
            'email' => 'contato@acme.com.br',
            'street' => 'AVENIDA PAULISTA',
            'city_id' => 3550308,
            'state' => 'SP',
            'slug' => 'acme',
        ]);
});

test('CNPJ lookup validates the CNPJ before calling BrasilAPI', function () {
    Http::fake();

    $this->getJson(route('lookup.cnpj', '11222333000100'))->assertUnprocessable();

    Http::assertNothingSent();
});

test('CNPJ lookup reports companies already registered', function () {
    Company::factory()->create(['cnpj' => '11222333000181']);
    Http::fake();

    $this->getJson(route('lookup.cnpj', '11222333000181'))->assertStatus(409);
});

test('CNPJ lookup handles unknown CNPJs and BrasilAPI outages', function () {
    Http::fake([
        'brasilapi.com.br/api/cnpj/v1/11222333000181' => Http::response(['message' => 'CNPJ não encontrado'], 404),
        'brasilapi.com.br/api/cnpj/v1/45997418000153' => Http::response([], 500),
    ]);

    $this->getJson(route('lookup.cnpj', '11222333000181'))->assertNotFound();
    $this->getJson(route('lookup.cnpj', '45997418000153'))->assertStatus(503);
});
