<?php

namespace App\Security;

/**
 * Rôle des clients API authentifiés par le jeton statique.
 * Distinct de ROLE_ADMIN : le jeton n'ouvre ni EasyAdmin ni /api/docs.
 */
final class ApiAccess
{
    public const ROLE = 'ROLE_API';

    public const WRITE = "is_granted('ROLE_API')";
}
