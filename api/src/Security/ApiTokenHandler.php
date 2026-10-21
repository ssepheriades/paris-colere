<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Jeton statique lu dans API_TOKEN.
 *
 * Une valeur vide ne correspond à aucun jeton, y compris un Bearer vide :
 * le hash configuré reste absent et la comparaison ne peut pas réussir.
 * La comparaison passe par SHA-256 puis hash_equals, donc elle ne dépend
 * pas de la longueur du secret.
 *
 * La lecture est directe (et non %env(API_TOKEN)%) : le processeur d'env
 * Symfony traite une chaîne vide comme une variable absente et peut alors
 * reprendre une valeur non vide chargée plus tôt.
 */
final class ApiTokenHandler implements AccessTokenHandlerInterface
{
    private readonly ?string $configuredHash;

    public function __construct()
    {
        $token = self::readConfiguredToken();
        $this->configuredHash = '' === $token ? null : hash('sha256', $token, true);
    }

    public function getUserBadgeFrom(#[\SensitiveParameter] string $accessToken): UserBadge
    {
        $known = $this->configuredHash ?? hash('sha256', 'api-token-disabled', true);
        $matches = hash_equals($known, hash('sha256', $accessToken, true));

        if (!$matches || null === $this->configuredHash) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        return new UserBadge(ApiTokenUser::IDENTIFIER, static fn (): ApiTokenUser => new ApiTokenUser());
    }

    private static function readConfiguredToken(): string
    {
        if (\array_key_exists('API_TOKEN', $_ENV)) {
            return (string) $_ENV['API_TOKEN'];
        }

        if (\array_key_exists('API_TOKEN', $_SERVER)) {
            return (string) $_SERVER['API_TOKEN'];
        }

        $value = getenv('API_TOKEN');

        return \is_string($value) ? $value : '';
    }
}
