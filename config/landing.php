<?php

declare(strict_types=1);

return [
    'repo_url' => env('LANDING_REPO_URL', 'https://github.com/danielebarbaro/ekswai-jobs-scraper'),
    'umami' => [
        'enabled' => env('UMAMI_ENABLED', false),
        'script_url' => env('UMAMI_SCRIPT_URL'),
        'website_id' => env('UMAMI_WEBSITE_ID'),
    ],
];
