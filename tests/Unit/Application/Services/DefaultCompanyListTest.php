<?php

declare(strict_types=1);

use App\Application\Services\DefaultCompanyList;
use App\Domain\Company\JobBoardProvider;

it('loads the default companies from the json file', function (): void {
    $companies = DefaultCompanyList::all();

    expect($companies)->not->toBeEmpty()
        ->and($companies[0])->toHaveKeys(['provider', 'slug', 'name']);
});

it('only references providers we actually support', function (): void {
    $supported = array_column(JobBoardProvider::cases(), 'value');

    foreach (DefaultCompanyList::all() as $company) {
        expect($supported)->toContain($company['provider']);
    }
});

it('has no duplicate provider and slug pairs', function (): void {
    $keys = array_map(
        fn (array $c): string => $c['provider'].':'.$c['slug'],
        DefaultCompanyList::all()
    );

    expect(array_unique($keys))->toHaveCount(count($keys));
});

it('caches the parsed file between calls', function (): void {
    expect(DefaultCompanyList::all())->toBe(DefaultCompanyList::all());
});
