<?php

declare(strict_types=1);

namespace App\Application\DTOs;

final readonly class ScanItaliaremoteSummary
{
    public function __construct(
        public int $total,
        public int $matched,
        public int $created,
        public int $skipped,
        public int $failed,
    ) {}
}
