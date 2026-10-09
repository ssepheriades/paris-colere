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

    public static function link(): self
    {
        return new self(SourceProvider::Link);
    }
}
