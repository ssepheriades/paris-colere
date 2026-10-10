<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum SourceProvider: string implements TranslatableInterface
{
    case Link = 'link';
    case YouTube = 'youtube';
    case Dailymotion = 'dailymotion';
    case Vimeo = 'vimeo';
    case Tweet = 'tweet';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::Link => 'Lien',
            self::YouTube => 'YouTube',
            self::Dailymotion => 'Dailymotion',
            self::Vimeo => 'Vimeo',
            self::Tweet => 'Message',
        };
    }
}
