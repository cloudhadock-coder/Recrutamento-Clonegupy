<?php

namespace App\Models;

use App\Enums\ApplicationEventType;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Histórico imutável de uma candidatura (entrada, mudanças de etapa, reprovação).
 *
 * @property array<string, mixed>|null $meta
 */
#[Fillable(['company_id', 'application_id', 'user_id', 'type', 'from_stage_id', 'to_stage_id', 'meta'])]
class ApplicationEvent extends Model
{
    use BelongsToCompany;

    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ApplicationEventType::class,
            'meta' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Application, $this>
     */
    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<JobStage, $this>
     */
    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(JobStage::class, 'from_stage_id');
    }

    /**
     * @return BelongsTo<JobStage, $this>
     */
    public function toStage(): BelongsTo
    {
        return $this->belongsTo(JobStage::class, 'to_stage_id');
    }
}
