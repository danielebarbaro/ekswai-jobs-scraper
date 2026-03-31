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
        $location = collect([
            $data['city'] ?? null,
            $data['country'] ?? null,
        ])->filter()->implode(', ') ?: null;

        return new self(
            externalId: $data['shortcode'] ?? (string) ($data['id'] ?? ''),
            title: $data['title'] ?? 'Untitled Position',
            location: $location,
            url: $data['url'] ?? $data['shortlink'] ?? '',
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
