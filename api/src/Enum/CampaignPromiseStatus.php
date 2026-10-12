<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum CampaignPromiseStatus: string implements TranslatableInterface
{
    case Kept = 'kept';
    case Partial = 'partial';
    case Broken = 'broken';
    case InProgress = 'in_progress';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return match ($this) {
            self::Kept => 'Tenue',
            self::Partial => 'Partiellement tenue',
            self::Broken => 'Non tenue',
            self::InProgress => 'En cours',
        };
    }
}
