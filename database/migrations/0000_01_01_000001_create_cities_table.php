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
     * O id é o código IBGE do município, importado por `php artisan ibge:import-cities`.
     */
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->string('name');
            $table->char('state', 2);

            $table->index(['state', 'name']);
        });

        DB::statement('CREATE INDEX cities_name_trgm_index ON cities USING GIN (name gin_trgm_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
