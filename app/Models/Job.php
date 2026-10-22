<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\WorkModel;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Vaga.
 *
 * @property array<int, array{id: string, label: string, type: string, required: bool}> $questions
 */
#[Fillable([
    'company_id', 'city_id', 'title', 'description', 'requirements', 'benefits', 'work_model',
    'employment_type', 'salary_min', 'salary_max', 'questions',
])]
#[Hidden(['search'])]
class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use BelongsToCompany, HasFactory;

    /**
     * Etapas criadas automaticamente em toda vaga nova.
     */
    public const DEFAULT_STAGES = ['Inscritos', 'Triagem', 'Entrevista com RH', 'Entrevista técnica', 'Proposta', 'Contratados'];

    protected $attributes = [
        'status' => 'draft',
        'questions' => '[]',
    ];

    protected static function booted(): void
    {
        static::creating(function (Job $job): void {
            $job->slug ??= static::uniqueSlug($job->company_id, $job->title);
        });

        static::created(function (Job $job): void {
            $job->stages()->createMany(array_map(
                fn (string $name, int $position): array => ['company_id' => $job->company_id, 'name' => $name, 'position' => $position],
                self::DEFAULT_STAGES,
                array_keys(self::DEFAULT_STAGES),
            ));
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'work_model' => WorkModel::class,
            'employment_type' => EmploymentType::class,
            'questions' => 'array',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    private static function uniqueSlug(int $companyId, string $title): string
    {
        $base = Str::slug($title) ?: 'vaga';
        $slug = $base;

        for ($suffix = 2; static::withoutGlobalScopes()->where('company_id', $companyId)->where('slug', $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    public function publish(): void
    {
        $this->status = JobStatus::Published;
        $this->published_at ??= now();
        $this->save();
    }

    public function pause(): void
    {
        $this->status = JobStatus::Paused;
        $this->save();
    }

    public function isOpen(): bool
    {
        return $this->status === JobStatus::Published;
    }

    /**
     * @param  Builder<Job>  $query
     * @return Builder<Job>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', JobStatus::Published);
    }

    /**
     * Busca full-text em português (tsvector) com fallback por similaridade no título (pg_trgm).
     *
     * @param  Builder<Job>  $query
     * @return Builder<Job>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query
            ->where(fn (Builder $query) => $query
                ->whereRaw("search @@ websearch_to_tsquery('portuguese', ?)", [$term])
                ->orWhereRaw('title % ?', [$term]))
            ->orderByRaw("ts_rank(search, websearch_to_tsquery('portuguese', ?)) desc, similarity(title, ?) desc", [$term, $term]);
    }

    /**
     * @return HasMany<JobStage, $this>
     */
    public function stages(): HasMany
    {
        return $this->hasMany(JobStage::class)->orderBy('position');
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
}
