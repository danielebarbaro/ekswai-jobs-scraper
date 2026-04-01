<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Scraping\Exceptions;

use App\Domain\Company\JobBoardProvider;

class DomStructureChangedException extends ScrapingFailedException
{
    public function __construct(
        JobBoardProvider $provider,
        string $slug,
        int $attemptsMade,
        public readonly string $expectedSelector,
        public readonly string $actualHtmlSnippet,
        ?\Throwable $previous = null,
    ) {
        $message = sprintf(
            'DOM structure changed for %s provider (slug: %s): selector "%s" not found after %d attempts',
            $provider->value,
            $slug,
            $expectedSelector,
            $attemptsMade,
        );

        parent::__construct($provider, $slug, $attemptsMade, $message, $previous);
    }
}
