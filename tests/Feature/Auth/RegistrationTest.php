<?php

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new companies can register with an admin user', function () {
    $response = $this->post('/register', [
        'cnpj' => '11.222.333/0001-81',
        'name' => 'Empresa Teste Ltda',
        'trade_name' => 'Empresa Teste',
        'slug' => 'empresa-teste',
        'admin_name' => 'Test User',
        'admin_email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $company = Company::sole();
    expect($company->cnpj)->toBe('11222333000181')
        ->and(User::sole())->company_id->toBe($company->id)->role->toBe(UserRole::Admin);
});

test('registration rejects invalid CNPJ and reserved portal slugs', function () {
    $response = $this->post('/register', [
        'cnpj' => '11.222.333/0001-00',
        'name' => 'Empresa Teste Ltda',
        'slug' => 'painel',
        'admin_name' => 'Test User',
        'admin_email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors(['cnpj', 'slug']);
    $this->assertGuest();
});
