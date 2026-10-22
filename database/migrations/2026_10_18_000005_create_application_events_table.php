<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('application_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->foreignId('from_stage_id')->nullable()->constrained('job_stages')->nullOnDelete();
            $table->foreignId('to_stage_id')->nullable()->constrained('job_stages')->nullOnDelete();
            $table->jsonb('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['application_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_events');
    }
};
