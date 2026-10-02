<?php

namespace App\Enum;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum LegalEntityType: string implements TranslatableInterface
{
    case LocalAuthority = 'Collectivité locale';
    case Company = 'Entreprise';
    case PublicService = 'Service Public';
    case School = 'Etablissement Scolaire';

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $this->value;
    }
}
