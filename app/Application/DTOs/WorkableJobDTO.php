<?php

declare(strict_types=1);

namespace App\Application\DTOs;

readonly class WorkableJobDTO
{
    public function __construct(
        public string $externalId,
        public string $title,
        public ?string $location,
        public string $url,
        public ?string $department,
        public array $rawPayload,
    ) {}

    public static function fromApiResponse(array $data): self
    {
        return new self(
            externalId: (string) $data['id'],
            title: $data['title'] ?? 'Untitled Position',
            location: $data['location']['city'] ?? null,
            url: $data['url'] ?? '',
            department: $data['department'] ?? null,
            rawPayload: $data,
        );
    }

    public function toArray(): array
    {
        return [
            'external_id' => $this->externalId,
            'title' => $this->title,
            'location' => $this->location,
            'url' => $this->url,
            'department' => $this->department,
            'raw_payload' => $this->rawPayload,
        ];
    }
}
