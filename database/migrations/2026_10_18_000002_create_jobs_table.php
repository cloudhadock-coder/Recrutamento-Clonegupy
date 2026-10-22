<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Vagas. A fila usa Redis, então a tabela `jobs` é livre para o domínio.
     */
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description');
            $table->text('requirements')->nullable();
            $table->text('benefits')->nullable();
            $table->string('work_model', 10);
            $table->string('employment_type', 15);
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->string('status', 10)->default('draft');
            $table->jsonb('questions')->default('[]');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'status', 'published_at']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE jobs ADD COLUMN search tsvector GENERATED ALWAYS AS (
                setweight(to_tsvector('portuguese', coalesce(title, '')), 'A') ||
                setweight(to_tsvector('portuguese', coalesce(requirements, '')), 'B') ||
                setweight(to_tsvector('portuguese', coalesce(description, '')), 'C')
            ) STORED
        SQL);

        DB::statement('CREATE INDEX jobs_search_index ON jobs USING GIN (search)');
        DB::statement('CREATE INDEX jobs_title_trgm_index ON jobs USING GIN (title gin_trgm_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
