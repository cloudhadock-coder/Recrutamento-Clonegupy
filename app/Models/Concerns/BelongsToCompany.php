<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\CompanyScope;
use App\Support\CurrentCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * @mixin Model
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new CompanyScope);

        static::creating(function (Model $model): void {
            $currentCompanyId = app(CurrentCompany::class)->id();

            if ($currentCompanyId === null) {
                return;
            }

            $model->company_id ??= $currentCompanyId;

            if ((int) $model->company_id !== $currentCompanyId) {
                throw new LogicException('Tentativa de gravar '.class_basename($model).' em outra empresa.');
            }
        });
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
