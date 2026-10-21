<?php

namespace App\Tests;

use App\Entity\CampaignPromise;
use App\Entity\Party;
use App\Entity\Person;
use App\Entity\Theme;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

class ApiTokenTest extends WebTestCase
{
    private const TOKEN = 'test-api-token';

    public function testWriteWithoutTokenIsRejected(): void
    {
        $client = static::createClient();
        $name = $this->unique('sans-token');

        $client->request('POST', '/api/themes', server: $this->jsonHeaders(), content: $this->themePayload($name));

        self::assertResponseStatusCodeSame(401);
        self::assertSame(0, $this->themeCount($name));
    }

    public function testWrongTokenIsRejected(): void
    {
        $client = static::createClient();
        $name = $this->unique('mauvais');

        $client->request('GET', '/api/themes', server: [
            'HTTP_ACCEPT' => 'application/ld+json',
            'HTTP_AUTHORIZATION' => 'Bearer wrong-token',
        ]);
        self::assertResponseStatusCodeSame(401);
        self::assertStringContainsString('invalid_token', (string) $client->getResponse()->headers->get('WWW-Authenticate'));

        $client->request('POST', '/api/themes', server: $this->jsonHeaders('wrong-token'), content: $this->themePayload($name));

        self::assertResponseStatusCodeSame(401);
        self::assertSame(0, $this->themeCount($name));
    }

    public function testEmptyBearerDoesNotAuthenticate(): void
    {
        $client = static::createClient();
        $name = $this->unique('bearer-vide');

        $client->request('POST', '/api/themes', server: $this->jsonHeaders(''), content: $this->themePayload($name));

        self::assertResponseStatusCodeSame(401);
        self::assertSame(0, $this->themeCount($name));
    }

    public function testEmptyConfiguredTokenAuthenticatesNothing(): void
    {
        $this->bootWithToken('');
        $client = static::getClient();
        self::assertNotNull($client);
        $name = $this->unique('serveur-vide');

        try {
            $client->request('GET', '/api/themes', server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);
            self::assertResponseIsSuccessful();

            foreach (['', self::TOKEN, 'whatever'] as $presented) {
                $client->request('POST', '/api/themes', server: $this->jsonHeaders($presented), content: $this->themePayload($name));
                self::assertResponseStatusCodeSame(401);
            }

            self::assertSame(0, $this->themeCount($name));
        } finally {
            $this->restoreTestToken();
        }
    }

    public function testValidTokenCanWrite(): void
    {
        $client = static::createClient();
        $title = $this->unique('promesse');
        $partyName = $this->unique('parti');
        $promiseIri = null;
        $personIri = null;
        $partyIri = null;

        try {
            $client->request('POST', '/api/campaign_promises', server: $this->jsonHeaders(self::TOKEN), content: json_encode([
                'title' => $title,
                'quote' => 'Une citation de test.',
                'status' => 'kept',
                'isVisible' => true,
            ], \JSON_THROW_ON_ERROR));
            self::assertResponseStatusCodeSame(201);
            $promiseIri = $this->iri($client->getResponse()->getContent());

            $client->request('GET', $promiseIri, server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);
            self::assertResponseIsSuccessful();
            $publicPromise = $this->json($client->getResponse()->getContent());
            self::assertSame($title, $publicPromise['title']);
            self::assertSame('kept', $publicPromise['status']);

            $client->request('POST', '/api/parties', server: $this->jsonHeaders(self::TOKEN), content: json_encode([
                'name' => $partyName,
                'color' => '#112233',
            ], \JSON_THROW_ON_ERROR));
            self::assertResponseStatusCodeSame(201);
            $partyIri = $this->iri($client->getResponse()->getContent());

            $client->request('POST', '/api/people', server: $this->jsonHeaders(self::TOKEN), content: json_encode([
                'firstname' => 'Camille',
                'lastname' => $this->unique('Nom'),
                'shortDescription' => 'Fiche masquée',
                'isVisible' => false,
                'party' => [$partyIri],
            ], \JSON_THROW_ON_ERROR));
            self::assertResponseStatusCodeSame(201);
            $created = $this->json($client->getResponse()->getContent());
            $personIri = $this->requireString($created['@id'] ?? null);
            self::assertSame(false, $created['isVisible']);
            self::assertContains($partyIri, $created['party']);

            $client->request('GET', $personIri, server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);
            self::assertResponseStatusCodeSame(404);

            $client->request('GET', $personIri, server: $this->jsonHeaders(self::TOKEN));
            self::assertResponseIsSuccessful();

            $client->request('PATCH', $personIri, server: [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT' => 'application/ld+json',
                'HTTP_AUTHORIZATION' => 'Bearer '.self::TOKEN,
            ], content: json_encode(['isVisible' => true], \JSON_THROW_ON_ERROR));
            self::assertResponseIsSuccessful();

            $client->request('GET', $personIri, server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);
            self::assertResponseIsSuccessful();

            $client->request('DELETE', $personIri, server: [
                'HTTP_AUTHORIZATION' => 'Bearer '.self::TOKEN,
            ]);
            self::assertResponseStatusCodeSame(204);
            $personIri = null;

            $client->request('DELETE', $promiseIri, server: [
                'HTTP_AUTHORIZATION' => 'Bearer '.self::TOKEN,
            ]);
            self::assertResponseStatusCodeSame(204);
            $promiseIri = null;

            $client->request('DELETE', $partyIri, server: [
                'HTTP_AUTHORIZATION' => 'Bearer '.self::TOKEN,
            ]);
            self::assertResponseStatusCodeSame(204);
            $partyIri = null;
        } finally {
            $this->removeIri(Person::class, $personIri);
            $this->removeIri(CampaignPromise::class, $promiseIri);
            $this->removeIri(Party::class, $partyIri);
        }
    }

    public function testPublicReadAndLoginFormStayAvailable(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/themes', server: [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);
        self::assertResponseIsSuccessful();

        $client->request('GET', '/login');
        self::assertResponseIsSuccessful();

        $client->request('GET', '/admin');
        self::assertResponseRedirects('/login');
    }

    /**
     * @return array<string, string>
     */
    private function jsonHeaders(?string $token = null): array
    {
        $headers = [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ];
        if (null !== $token) {
            $headers['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        return $headers;
    }

    private function themePayload(string $name): string
    {
        return json_encode([
            'name' => $name,
            'shortDescription' => 'Thème de test',
        ], \JSON_THROW_ON_ERROR);
    }

    private function unique(string $prefix): string
    {
        return substr($prefix.' '.bin2hex(random_bytes(4)), 0, 32);
    }

    private function themeCount(string $name): int
    {
        return $this->manager()->getRepository(Theme::class)->count(['name' => $name]);
    }

    private function bootWithToken(string $token): void
    {
        static::ensureKernelShutdown();
        $_ENV['API_TOKEN'] = $token;
        $_SERVER['API_TOKEN'] = $token;
        putenv('API_TOKEN='.$token);
        static::createClient();
    }

    private function restoreTestToken(): void
    {
        $_ENV['API_TOKEN'] = self::TOKEN;
        $_SERVER['API_TOKEN'] = self::TOKEN;
        putenv('API_TOKEN='.self::TOKEN);
        static::ensureKernelShutdown();
    }

    private function iri(string|false $content): string
    {
        $decoded = $this->json($content);

        return $this->requireString($decoded['@id'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function json(string|false $content): array
    {
        self::assertIsString($content);
        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    private function requireString(mixed $value): string
    {
        self::assertIsString($value);

        return $value;
    }

    /**
     * @param class-string $class
     */
    private function removeIri(string $class, ?string $iri): void
    {
        if (null === $iri) {
            return;
        }

        $id = basename($iri);
        $manager = $this->manager();
        $entity = str_contains($id, '-')
            ? $manager->find($class, Uuid::fromString($id))
            : $manager->find($class, $id);
        if (null === $entity) {
            return;
        }

        $manager->remove($entity);
        $manager->flush();
    }

    private function manager(): EntityManagerInterface
    {
        $manager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }
}
