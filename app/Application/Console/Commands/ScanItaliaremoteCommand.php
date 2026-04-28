<?php

declare(strict_types=1);

namespace App\Application\Console\Commands;

use App\Application\Actions\Company\ScanItaliaremoteAction;
use Illuminate\Console\Command;

class ScanItaliaremoteCommand extends Command
{
    protected $signature = 'italiaremote:scan';

    protected $description = 'Scan awesome-italia-remote and auto-add companies for supported job board providers';

    public function __construct(
        private readonly ScanItaliaremoteAction $scanAction,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting Italia Remote scan...');

        try {
            $summary = $this->scanAction->execute();

            $this->newLine();
            $this->info('Scan completed.');
            $this->newLine();

            $this->table(
                ['Metric', 'Count'],
                [
                    ['Entries fetched', $summary->total],
                    ['Matching providers', $summary->matched],
                    ['Companies created', $summary->created],
                    ['Already existed (skipped)', $summary->skipped],
                    ['Validation failed', $summary->failed],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Scan failed: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }
    }
}
