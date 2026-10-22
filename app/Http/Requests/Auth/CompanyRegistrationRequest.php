<?php

namespace App\Http\Requests\Auth;

use App\Models\Company;
use App\Models\User;
use App\Rules\ValidCnpj;
use App\Support\Cnpj;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CompanyRegistrationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj' => Cnpj::digits($this->input('cnpj')),
            'cep' => preg_replace('/\D/', '', (string) $this->input('cep')) ?: null,
            'phone' => preg_replace('/\D/', '', (string) $this->input('phone')) ?: null,
            'slug' => mb_strtolower((string) $this->input('slug')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cnpj' => ['required', new ValidCnpj, Rule::unique(Company::class)],
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'slug' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::notIn(Company::RESERVED_SLUGS), Rule::unique(Company::class)],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'digits_between:10,11'],
            'cep' => ['nullable', 'digits:8'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'state' => ['nullable', 'string', 'size:2'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'razão social',
            'trade_name' => 'nome fantasia',
            'slug' => 'endereço do portal',
            'admin_name' => 'seu nome',
            'admin_email' => 'seu e-mail',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cnpj.unique' => 'Esta empresa já está cadastrada.',
            'slug.regex' => 'Use apenas letras minúsculas, números e hífens.',
            'slug.not_in' => 'Este endereço é reservado. Escolha outro.',
            'slug.unique' => 'Este endereço já está em uso.',
        ];
    }
}
