<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ContactStatus: string implements TranslatableInterface
{
    case New = 'new';
    case Pending = 'pending';
    case Done = 'done';
    case Ignored = 'ignored';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::New => 'Nouveau',
            self::Pending => 'En cours',
            self::Done => 'Traité',
            self::Ignored => 'Ignoré',
        };
    }
}
