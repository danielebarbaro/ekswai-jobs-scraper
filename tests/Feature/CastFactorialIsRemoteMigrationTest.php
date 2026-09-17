<?php

declare(strict_types=1);

use App\Domain\Company\Company;
use App\Domain\JobPosting\JobPosting;

beforeEach(function (): void {
    $this->migration = require database_path('migrations/2026_09_17_150000_cast_factorial_is_remote_to_boolean.php');
    $this->company = Company::factory()->create();
});

it('converts factorial is_remote strings to booleans', function (): void {
    $remote = JobPosting::factory()->create(['company_id' => $this->company->id, 'raw_payload' => ['source' => 'factorial', 'is_remote' => 'true', 'team_id' => '1']]);
    $office = JobPosting::factory()->create(['company_id' => $this->company->id, 'raw_payload' => ['source' => 'factorial', 'is_remote' => 'false']]);
    $missing = JobPosting::factory()->create(['company_id' => $this->company->id, 'raw_payload' => ['source' => 'factorial', 'is_remote' => null]]);

    $this->migration->up();

    // MySQL reorders JSON object keys, so assert per key instead of the whole array.
    expect($remote->fresh()->raw_payload)
        ->toHaveCount(3)
        ->toHaveKey('source', 'factorial')
        ->toHaveKey('team_id', '1')
        ->and($remote->fresh()->raw_payload['is_remote'])->toBeTrue()
        ->and($office->fresh()->raw_payload['is_remote'])->toBeFalse()
        ->and($missing->fresh()->raw_payload['is_remote'])->toBeNull();
});

it('leaves postings from other providers untouched', function (): void {
    $other = JobPosting::factory()->create(['company_id' => $this->company->id, 'raw_payload' => ['is_remote' => 'true']]);

    $this->migration->up();

    expect($other->fresh()->raw_payload['is_remote'])->toBe('true');
});
