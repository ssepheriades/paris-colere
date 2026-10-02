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
}
