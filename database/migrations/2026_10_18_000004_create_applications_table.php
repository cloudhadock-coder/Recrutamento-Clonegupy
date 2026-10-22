<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_stage_id')->constrained();
            $table->string('status', 10)->default('active');
            $table->jsonb('answers')->nullable();
            $table->string('resume_path')->nullable();
            $table->string('resume_original_name')->nullable();
            $table->text('resume_text')->nullable();
            $table->timestamp('resume_text_extracted_at')->nullable();
            $table->timestamp('consent_at');
            $table->string('consent_version', 20);
            $table->string('consent_ip', 45)->nullable();
            $table->timestamp('stage_entered_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();

            $table->unique(['job_id', 'candidate_id']);
            $table->index(['job_id', 'job_stage_id', 'id']);
            $table->index(['company_id', 'id']);
            $table->index(['candidate_id', 'id']);
        });

        DB::statement("ALTER TABLE applications ADD COLUMN resume_search tsvector GENERATED ALWAYS AS (to_tsvector('portuguese', coalesce(resume_text, ''))) STORED");
        DB::statement('CREATE INDEX applications_resume_search_index ON applications USING GIN (resume_search)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
