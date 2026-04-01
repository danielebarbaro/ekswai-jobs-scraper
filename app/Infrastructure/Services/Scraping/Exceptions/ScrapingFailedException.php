<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Scraping\Exceptions;

use App\Domain\Company\JobBoardProvider;

class ScrapingFailedException extends \RuntimeException
{
    public function __construct(
        public readonly JobBoardProvider $provider,
        public readonly string $slug,
        public readonly int $attemptsMade,
        string $message = '',
        ?\Throwable $previous = null,
    ) {
        $defaultMessage = sprintf(
            'Scraping failed for %s provider (slug: %s) after %d attempts',
            $provider->value,
            $slug,
            $attemptsMade,
        );

        parent::__construct($message ?: $defaultMessage, 0, $previous);
    }
}
