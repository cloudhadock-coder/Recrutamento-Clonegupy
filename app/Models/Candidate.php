<?php

namespace App\Models;

use Database\Factories\CandidateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Conta global de candidato: pode se candidatar a vagas de qualquer empresa.
 */
#[Fillable(['name', 'email', 'password', 'phone', 'linkedin_url', 'cep', 'city_id', 'state'])]
#[Hidden(['password', 'remember_token'])]
class Candidate extends Authenticatable
{
    /** @use HasFactory<CandidateFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'anonymized_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function isAnonymized(): bool
    {
        return $this->anonymized_at !== null;
    }
}
