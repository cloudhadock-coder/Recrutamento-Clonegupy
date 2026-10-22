<?php

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Município do IBGE. O id é o código IBGE.
 */
#[Fillable(['id', 'name', 'state'])]
#[Table(incrementing: false, timestamps: false)]
class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory;

    /**
     * Busca por prefixo ou similaridade (pg_trgm), tolerante a acentos e erros de digitação.
     *
     * @param  Builder<City>  $query
     * @return Builder<City>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query
            ->where(fn (Builder $query) => $query
                ->where('name', 'ilike', $term.'%')
                ->orWhereRaw('similarity(name, ?) > 0.35', [$term]))
            ->orderByRaw('name ilike ? desc, similarity(name, ?) desc', [$term.'%', $term])
            ->orderBy('name');
    }

    public function label(): string
    {
        return "{$this->name} - {$this->state}";
    }
}
