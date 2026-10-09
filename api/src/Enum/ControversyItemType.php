<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum ControversyItemType: string implements TranslatableInterface
{
    case Fact = 'fact';
    case Statement = 'statement';
    case Publication = 'publication';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::Fact => 'Fait',
            self::Statement => 'Déclaration',
            self::Publication => 'Publication',
        };
    }
}
