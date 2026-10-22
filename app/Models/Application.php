<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\ApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Candidatura de um candidato a uma vaga.
 *
 * @property array<string, string>|null $answers
 */
#[Fillable([
    'company_id', 'job_id', 'candidate_id', 'job_stage_id', 'status', 'answers', 'resume_path',
    'resume_original_name', 'consent_at', 'consent_version', 'consent_ip', 'stage_entered_at',
])]
#[Hidden(['resume_search', 'resume_text', 'resume_path', 'consent_ip'])]
class Application extends Model
{
    /** @use HasFactory<ApplicationFactory> */
    use BelongsToCompany, HasFactory;

    /**
     * Versão do termo de consentimento LGPD aceito pelo candidato.
     */
    public const CONSENT_VERSION = '2026-10';

    protected $attributes = [
        'status' => 'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'answers' => 'array',
            'consent_at' => 'datetime',
            'stage_entered_at' => 'datetime',
            'rejected_at' => 'datetime',
            'resume_text_extracted_at' => 'datetime',
        ];
    }

    public function isRejected(): bool
    {
        return $this->status === ApplicationStatus::Rejected;
    }

    /**
     * @return BelongsTo<Job, $this>
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /**
     * @return BelongsTo<Candidate, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * @return BelongsTo<JobStage, $this>
     */
    public function stage(): BelongsTo
    {
        return $this->belongsTo(JobStage::class, 'job_stage_id');
    }

    /**
     * @return HasMany<ApplicationEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ApplicationEvent::class);
    }
}
