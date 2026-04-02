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
        Schema::create('job_postings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained()->cascadeOnDelete();
            $table->string('external_id');
            $table->string('title');
            $table->string('location')->nullable();
            $table->string('url');
            $table->string('department')->nullable();
            $table->dateTime('first_seen_at');
            $table->dateTime('last_seen_at')->nullable();
            $table->json('raw_payload');
            $table->timestamps();

            $table->unique(['company_id', 'external_id']);
            $table->index('first_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
