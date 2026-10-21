<?php

namespace App\Tests;

use App\Security\ApiTokenHandler;
use App\Security\ApiTokenUser;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class ApiTokenHandlerTest extends TestCase
{
    public function testMatchingTokenReturnsApiRole(): void
    {
        $handler = $this->handler('secret-token');
        $user = $handler->getUserBadgeFrom('secret-token')->getUser();

        self::assertInstanceOf(ApiTokenUser::class, $user);
        self::assertContains('ROLE_API', $user->getRoles());
    }

    public function testWrongTokenIsRejected(): void
    {
        $handler = $this->handler('secret-token');

        $this->assertRejected($handler, 'other-token');
        $this->assertRejected($handler, 'secret-token-extra');
        $this->assertRejected($handler, 'secret');
        $this->assertRejected($handler, '');
    }

    public function testEmptyConfiguredTokenRejectsEmptyAndArbitraryValues(): void
    {
        $handler = $this->handler('');

        $this->assertRejected($handler, '');
        $this->assertRejected($handler, 'secret-token');
    }

    private function handler(string $token): ApiTokenHandler
    {
        $previousEnv = $_ENV['API_TOKEN'] ?? null;
        $previousServer = $_SERVER['API_TOKEN'] ?? null;
        $_ENV['API_TOKEN'] = $token;
        $_SERVER['API_TOKEN'] = $token;

        try {
            return new ApiTokenHandler();
        } finally {
            $this->restore('API_TOKEN', $previousEnv, $previousServer);
        }
    }

    private function assertRejected(ApiTokenHandler $handler, string $presented): void
    {
        try {
            $handler->getUserBadgeFrom($presented);
            self::fail('Le jeton présenté aurait dû être refusé.');
        } catch (BadCredentialsException) {
            self::assertTrue(true);
        }
    }

    private function restore(string $name, ?string $env, ?string $server): void
    {
        if (null === $env) {
            unset($_ENV[$name]);
        } else {
            $_ENV[$name] = $env;
        }

        if (null === $server) {
            unset($_SERVER[$name]);
        } else {
            $_SERVER[$name] = $server;
        }
    }
}
