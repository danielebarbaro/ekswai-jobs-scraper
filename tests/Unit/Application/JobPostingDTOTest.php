<?php

declare(strict_types=1);

use App\Application\DTOs\JobPostingDTO;

it('converts to array with correct keys', function (): void {
    $dto = new JobPostingDTO(
        externalId: 'abc-123',
        title: 'Software Engineer',
        location: 'Remote',
        url: 'https://example.com/jobs/abc-123',
        department: 'Engineering',
        rawPayload: ['id' => 'abc-123', 'title' => 'Software Engineer'],
    );

    expect($dto->toArray())->toBe([
        'external_id' => 'abc-123',
        'title' => 'Software Engineer',
        'location' => 'Remote',
        'url' => 'https://example.com/jobs/abc-123',
        'department' => 'Engineering',
        'raw_payload' => ['id' => 'abc-123', 'title' => 'Software Engineer'],
    ]);
});

it('handles nullable fields', function (): void {
    $dto = new JobPostingDTO(
        externalId: 'abc-123',
        title: 'Designer',
        location: null,
        url: 'https://example.com/jobs/abc-123',
        department: null,
        rawPayload: [],
    );

    expect($dto->toArray())
        ->location->toBeNull()
        ->department->toBeNull();
});
