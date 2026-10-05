<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    public function testApiEntrypointIsPublic(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api', server: [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        self::assertResponseIsSuccessful();
    }

    public function testAdminRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        self::assertResponseRedirects('/login');
    }

    public function testApiDocsRedirectToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/docs');

        self::assertResponseRedirects('/login');
    }

    public function testApiDocsExportRedirectsToLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/docs.jsonopenapi');

        self::assertResponseRedirects('/login');
    }

    public function testContactCanBeCreatedViaApi(): void
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/contacts',
            server: [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ],
            content: json_encode([
                'name' => 'Smoke Contact',
                'email' => 'smoke@example.com',
                'message' => 'Message de test',
            ], \JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(201);
    }

    public function testContactItemIsNotReadableViaApi(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/contacts/00000000-0000-0000-0000-000000000001', server: [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        self::assertResponseStatusCodeSame(404);
    }
}
