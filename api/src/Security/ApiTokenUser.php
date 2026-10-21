<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Identité technique du jeton statique. Pas de compte en base.
 */
final class ApiTokenUser implements UserInterface
{
    public const IDENTIFIER = 'api-token';

    public function getRoles(): array
    {
        return [ApiAccess::ROLE];
    }

    public function getUserIdentifier(): string
    {
        return self::IDENTIFIER;
    }
}
