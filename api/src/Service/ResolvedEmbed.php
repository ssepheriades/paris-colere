<?php

namespace App\Service;

use App\Enum\SourceProvider;

final readonly class ResolvedEmbed
{
    public function __construct(
        public SourceProvider $provider,
        public ?string $externalId = null,
        public ?string $title = null,
        public ?string $thumbnailUrl = null,
        public ?string $authorName = null,
        public ?string $embedText = null,
    ) {
    }

    public static function link(?string $title = null, ?string $thumbnailUrl = null, ?string $authorName = null, ?string $embedText = null): self
    {
        return new self(SourceProvider::Link, null, $title, $thumbnailUrl, $authorName, $embedText);
    }
}
