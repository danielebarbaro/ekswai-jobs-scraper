<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Domain\Company\JobBoardProvider;

class JobBoardUrlParser
{
    /** @var array<string, JobBoardProvider> */
    private const array URL_PATTERNS = [
        'apply.workable.com' => JobBoardProvider::Workable,
        'jobs.lever.co' => JobBoardProvider::Lever,
        'jobs.eu.lever.co' => JobBoardProvider::Lever,
        'jobs.ashbyhq.com' => JobBoardProvider::Ashby,
        'boards.greenhouse.io' => JobBoardProvider::Greenhouse,
        'job-boards.greenhouse.io' => JobBoardProvider::Greenhouse,
        'job-boards.eu.greenhouse.io' => JobBoardProvider::Greenhouse,
        'teamtailor.com' => JobBoardProvider::Teamtailor,
        'factorialhr.com' => JobBoardProvider::Factorial,
    ];

    /**
     * @return array{provider: JobBoardProvider, slug: string}|null
     */
    public function parse(string $input): ?array
    {
        $input = trim($input);

        if ($input === '') {
            return null;
        }

        // Try URL parsing first
        if (str_contains($input, '.') || str_contains($input, '://')) {
            if (! str_starts_with($input, 'http')) {
                $input = 'https://'.$input;
            }

            $parsed = parse_url($input);
            $host = $parsed['host'] ?? '';
            $path = trim($parsed['path'] ?? '', '/');

            // Teamtailor: {slug}.teamtailor.com
            if (str_ends_with($host, '.teamtailor.com')) {
                $slug = str_replace('.teamtailor.com', '', $host);

                return ['provider' => JobBoardProvider::Teamtailor, 'slug' => $slug];
            }

            // Factorial: {slug}.factorialhr.com
            if (str_ends_with($host, '.factorialhr.com')) {
                $slug = str_replace('.factorialhr.com', '', $host);

                return ['provider' => JobBoardProvider::Factorial, 'slug' => $slug];
            }

            // Personio: {slug}.jobs.personio.de
            if (str_ends_with($host, '.jobs.personio.de')) {
                $slug = str_replace('.jobs.personio.de', '', $host);

                return ['provider' => JobBoardProvider::Personio, 'slug' => $slug];
            }

            // Other providers: host match + slug from path
            foreach (self::URL_PATTERNS as $domain => $provider) {
                if ($host === $domain) {
                    $slug = explode('/', $path)[0];
                    if ($slug !== '') {
                        return ['provider' => $provider, 'slug' => $slug];
                    }
                }
            }
        }

        return null;
    }
}
