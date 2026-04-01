<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraper_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('provider')->unique();
            $table->json('selectors');
            $table->string('health_check_selector');
            $table->string('base_url_pattern');
            $table->unsignedInteger('retry_attempts')->default(3);
            $table->unsignedInteger('retry_delay_seconds')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_health_check_at')->nullable();
            $table->boolean('last_health_check_passed')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraper_configs');
    }
};
