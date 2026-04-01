<?php

declare(strict_types=1);

it('has landing config with required keys', function () {
    $config = config('landing');

    expect($config)->toBeArray()
        ->and($config)->toHaveKeys(['repo_url', 'umami'])
        ->and($config['umami'])->toHaveKeys(['enabled', 'script_url', 'website_id']);
});

it('has umami disabled by default', function () {
    expect(config('landing.umami.enabled'))->toBeFalse();
});

it('has default repo url', function () {
    expect(config('landing.repo_url'))->toBe('https://github.com/danielebarbaro/ekswai-jobs-scraper');
});
