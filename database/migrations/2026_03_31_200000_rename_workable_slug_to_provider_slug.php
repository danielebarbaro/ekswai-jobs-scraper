<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('provider')->default('workable')->after('name');
            $table->renameColumn('workable_account_slug', 'provider_slug');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropUnique(['workable_account_slug']);
            $table->unique(['provider', 'provider_slug']);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropUnique(['provider', 'provider_slug']);
            $table->renameColumn('provider_slug', 'workable_account_slug');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->unique(['workable_account_slug']);
            $table->dropColumn('provider');
        });
    }
};
