<?php

namespace App\Models\Scopes;

use App\Support\CurrentCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    /**
     * Restringe a consulta à empresa do request atual, quando houver uma.
     *
     * Fora do painel (portal público, área do candidato, filas) nenhuma empresa está definida
     * e as consultas filtram explicitamente por empresa ou candidato.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $companyId = app(CurrentCompany::class)->id();

        if ($companyId !== null) {
            $builder->where($model->qualifyColumn('company_id'), $companyId);
        }
    }
}
