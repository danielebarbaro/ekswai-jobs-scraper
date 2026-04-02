<?php

declare(strict_types=1);

namespace App\Infrastructure\Console\Commands;

use App\Infrastructure\Services\Scraping\ScraperHealthChecker;
use Illuminate\Console\Command;

class CheckScrapersHealthCommand extends Command
{
    protected $signature = 'jobs:check-scrapers-health';

    protected $description = 'Run health checks on all active scraper providers and alert admins on failures';

    public function __construct(
        private readonly ScraperHealthChecker $healthChecker
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Running scraper health checks...');

        $results = $this->healthChecker->checkAll();

        if ($results->isEmpty()) {
            $this->info('No active scraper providers with companies to check.');

            return self::SUCCESS;
        }

        $passed = $results->where('passed', true)->count();
        $failed = $results->where('passed', false)->count();

        $this->table(
            ['Provider', 'Status'],
            $results->map(fn ($r): array => [
                ucfirst((string) $r['provider']),
                $r['passed'] ? 'PASS' : 'FAIL: '.($r['error'] ?? 'Unknown'),
            ])->toArray()
        );

        $this->newLine();
        $this->info(sprintf('Results: %d passed, %d failed', $passed, $failed));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
