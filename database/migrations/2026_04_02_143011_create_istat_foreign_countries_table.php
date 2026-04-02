<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('continents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('istat_code', 10)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('continent_id')->constrained('continents')->onDelete('cascade');
            $table->string('name');
            $table->string('istat_code', 10)->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('continent_id')->constrained('continents')->onDelete('cascade');
            $table->foreignUuid('area_id')->constrained('areas')->onDelete('cascade');
            $table->foreignUuid('parent_country_id')->nullable()->constrained('countries')->onDelete('set null');
            $table->string('type', 1); // S = State, T = Territory
            $table->string('name');
            $table->string('istat_code', 10)->unique();
            $table->string('iso_alpha2', 4)->nullable();
            $table->string('iso_alpha3', 4)->nullable();
            $table->string('at_code', 10)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type']);
            $table->index(['iso_alpha2']);
            $table->index(['iso_alpha3']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('continents');
    }
};
