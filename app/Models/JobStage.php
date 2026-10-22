<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\JobStageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Etapa do pipeline de uma vaga (coluna do kanban).
 */
#[Fillable(['company_id', 'job_id', 'name', 'position'])]
class JobStage extends Model
{
    /** @use HasFactory<JobStageFactory> */
    use BelongsToCompany, HasFactory;

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return HasMany<Application, $this>
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
