<?php

declare(strict_types=1);

namespace App\Application\DTOs;

readonly class JobPostingDTO
{
    public function __construct(
        public string $externalId,
        public string $title,
        public ?string $location,
        public string $url,
        public ?string $department,
        public array $rawPayload,
    ) {}

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
