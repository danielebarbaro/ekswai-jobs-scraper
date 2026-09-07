<?php

declare(strict_types=1);

namespace App\Application\Services;

use JsonException;
use RuntimeException;

/**
 * Single source of truth for the companies every install starts with.
 *
 * Backed by resources/data/default-companies.json so the list can grow
 * without touching PHP. Read by both the seeder and the in-app
 * "load defaults" flow, which previously kept two constants that drifted.
 */
class DefaultCompanyList
{
    private const string PATH = 'data/default-companies.json';

    /** @var list<array{provider: string, slug: string, name: string}>|null */
    private static ?array $cache = null;

    /**
     * @return list<array{provider: string, slug: string, name: string}>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $path = resource_path(self::PATH);

        if (! is_file($path)) {
            throw new RuntimeException("Default companies file not found at {$path}.");
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException("Unable to read default companies file at {$path}.");
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Invalid JSON in default companies file at {$path}: {$e->getMessage()}", 0, $e);
        }

        if (! is_array($decoded)) {
            throw new RuntimeException("Default companies file at {$path} must contain a JSON array.");
        }

        /** @var list<array{provider: string, slug: string, name: string}> $entries */
        $entries = [];

        foreach ($decoded as $index => $entry) {
            if (! is_array($entry)) {
                throw new RuntimeException("Default company at index {$index} must be an object.");
            }

            foreach (['provider', 'slug', 'name'] as $key) {
                if (! isset($entry[$key]) || ! is_string($entry[$key]) || trim($entry[$key]) === '') {
                    throw new RuntimeException("Default company at index {$index} is missing a non-empty \"{$key}\".");
                }
            }

            $entries[] = [
                'provider' => $entry['provider'],
                'slug' => $entry['slug'],
                'name' => $entry['name'],
            ];
        }

        return self::$cache = $entries;
    }

    /**
     * Reset the in-memory cache. Only useful in tests that swap the file.
     */
    public static function flush(): void
    {
        self::$cache = null;
    }
}
