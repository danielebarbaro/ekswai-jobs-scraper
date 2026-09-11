<?php

declare(strict_types=1);

it('has landing config with required keys', function (): void {
    $config = config('landing');

    expect($config)->toBeArray()
        ->and($config)->toHaveKeys(['repo_url', 'umami', 'job_boards'])
        ->and($config['umami'])->toHaveKeys(['enabled', 'script_url', 'website_id']);
});

it('has umami disabled by default', function (): void {
    expect(config('landing.umami.enabled'))->toBeFalse();
});

it('has default repo url', function (): void {
    expect(config('landing.repo_url'))->toBe('https://github.com/danielebarbaro/ekswai-jobs-scraper');
});

it('has job_boards config with required keys', function (): void {
    $jobBoards = config('landing.job_boards');

    expect($jobBoards)->toBeArray()
        ->and($jobBoards)->toHaveKeys(['core', 'skeleton', 'connectors'])
        ->and($jobBoards['connectors'])->not->toBeEmpty();
});

it('has valid github urls for the core and skeleton packages', function (): void {
    expect(config('landing.job_boards.core'))->toStartWith('https://github.com/plin-code/')
        ->and(config('landing.job_boards.skeleton'))->toStartWith('https://github.com/plin-code/');
});
