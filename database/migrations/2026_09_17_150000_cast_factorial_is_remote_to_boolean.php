<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Factorial postings stored is_remote as the string 'true' or 'false'.
     * The sync never rewrites raw_payload for existing postings, so convert
     * them in place. Done in PHP to stay portable across MySQL, PostgreSQL
     * and SQLite JSON functions.
     */
    public function up(): void
    {
        $converted = 0;

        DB::table('job_postings')
            ->where('raw_payload->source', 'factorial')
            ->orderBy('id')
            ->select(['id', 'raw_payload'])
            ->chunk(500, function ($rows) use (&$converted): void {
                foreach ($rows as $row) {
                    $payload = json_decode((string) $row->raw_payload, true);

                    if (! is_array($payload) || ! is_string($payload['is_remote'] ?? null)) {
                        continue;
                    }

                    $payload['is_remote'] = filter_var($payload['is_remote'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                    DB::table('job_postings')
                        ->where('id', $row->id)
                        ->update(['raw_payload' => json_encode($payload)]);

                    $converted++;
                }
            });

        Log::info('Converted Factorial is_remote strings to booleans', ['count' => $converted]);
    }

    public function down(): void
    {
        // Irreversible data fix: the string form was a bug, not a state to restore.
    }
};
