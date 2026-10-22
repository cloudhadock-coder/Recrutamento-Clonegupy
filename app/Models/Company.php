<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name', 'trade_name', 'slug', 'cnpj', 'email', 'phone', 'cep', 'street', 'number',
    'complement', 'neighborhood', 'city_id', 'state', 'about',
])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    /**
     * Slugs que colidem com rotas da aplicação e não podem ser usados por empresas.
     */
    public const RESERVED_SLUGS = [
        'admin', 'api', 'build', 'cadastro', 'candidato', 'confirm-password', 'dashboard', 'email',
        'forgot-password', 'horizon', 'login', 'logout', 'painel', 'password', 'profile', 'register',
        'reset-password', 'storage', 'up', 'utils', 'verify-email', 'vagas',
    ];

    /**
     * Nome de exibição: nome fantasia quando houver, senão a razão social.
     */
    public function displayName(): string
    {
        return $this->trade_name ?: $this->name;
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Job, $this>
     */
    public function jobs(): HasMany
    {
        return $this->hasMany(Job::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
