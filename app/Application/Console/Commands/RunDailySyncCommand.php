<?php

declare(strict_types=1);

namespace App\Application\Console\Commands;

use App\Application\Actions\Sync\RunDailySyncAction;
use Illuminate\Console\Command;

class RunDailySyncCommand extends Command
{
    protected $signature = 'jobs:sync-daily
                          {--company-id= : Sync only a specific company by ID}';

    protected $description = 'Sync job postings from all active providers and notify users of new positions';

    public function __construct(
        private readonly RunDailySyncAction $runDailySyncAction
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('🚀 Starting daily job sync...');

        try {
            $stats = $this->runDailySyncAction->execute();

            $this->newLine();
            $this->info('✅ Sync completed successfully!');
            $this->newLine();

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Companies Synced', $stats['companies_synced']],
                    ['New Jobs Found', $stats['new_jobs_found']],
                    ['Users Notified', $stats['users_notified']],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('❌ Sync failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }
}
