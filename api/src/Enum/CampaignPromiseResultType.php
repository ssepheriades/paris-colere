<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum CampaignPromiseResultType: string implements TranslatableInterface
{
    case Absolute = 'absolute';
    case Percentage = 'percentage';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::Absolute => 'Nombre',
            self::Percentage => 'Pourcentage',
        };
    }
}
