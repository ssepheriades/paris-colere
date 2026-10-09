<?php

namespace App\Tests;

use App\Entity\Controversy;
use App\Entity\ControversyItem;
use App\Entity\KeyFigure;
use App\Entity\Person;
use App\Entity\Source;
use App\Enum\ControversyItemType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

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

    public function testContentWritesAreRejected(): void
    {
        $client = static::createClient();
        $server = [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ];

        $client->request('POST', '/api/people', server: $server, content: '{}');
        self::assertResponseStatusCodeSame(405);

        $client->request('PATCH', '/api/people/00000000-0000-0000-0000-000000000001', server: $server, content: '{}');
        self::assertResponseStatusCodeSame(405);

        $client->request('DELETE', '/api/controversies/00000000-0000-0000-0000-000000000001', server: $server);
        self::assertResponseStatusCodeSame(405);
    }

    public function testKeyFiguresAreNotAResource(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/key_figures', server: [
            'HTTP_ACCEPT' => 'application/ld+json',
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testHiddenControversyIsNotReadable(): void
    {
        $client = static::createClient();
        $controversy = $this->persistControversy('Brouillon secret', false);

        try {
            $client->request('GET', '/api/controversies/'.$controversy->getId(), server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);

            self::assertResponseStatusCodeSame(404);
        } finally {
            $this->removeControversy($controversy->getId());
        }
    }

    public function testHiddenPersonIsNotReadable(): void
    {
        $client = static::createClient();
        $manager = $this->manager();
        $person = (new Person())
            ->setFirstname('Camille')
            ->setLastname('Masquée')
            ->setShortDescription('Ne doit pas être lisible')
            ->setIsVisible(false);
        $manager->persist($person);
        $manager->flush();
        $id = $person->getId();

        try {
            $client->request('GET', '/api/people/'.$id, server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);

            self::assertResponseStatusCodeSame(404);
        } finally {
            $manager = $this->manager();
            $managed = null !== $id ? $manager->find(Person::class, $id) : null;
            if (null !== $managed) {
                $manager->remove($managed);
                $manager->flush();
            }
        }
    }

    public function testHiddenKeyFigureIsAbsentFromControversyJson(): void
    {
        $client = static::createClient();
        $controversy = $this->persistControversy('Affaire publique', true);
        $manager = $this->manager();
        $controversy = $manager->find(Controversy::class, $controversy->getId());
        self::assertInstanceOf(Controversy::class, $controversy);

        $hidden = (new KeyFigure())
            ->setFigure('99')
            ->setLabel('Chiffre masqué')
            ->setPriority(2)
            ->setIsVisible(false);
        $visible = (new KeyFigure())
            ->setFigure('1')
            ->setLabel('Chiffre public')
            ->setPriority(1)
            ->setIsVisible(true);
        $controversy->addKeyFigure($hidden);
        $controversy->addKeyFigure($visible);
        $manager->flush();

        try {
            $client->request('GET', '/api/controversies/'.$controversy->getId(), server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);

            self::assertResponseIsSuccessful();
            $content = $client->getResponse()->getContent();
            self::assertIsString($content);
            self::assertStringContainsString('Chiffre public', $content);
            self::assertStringNotContainsString('Chiffre masqué', $content);
        } finally {
            $this->removeControversy($controversy->getId());
        }
    }

    public function testHiddenSourceIsAbsentFromItemJson(): void
    {
        $client = static::createClient();
        $controversy = $this->persistControversy('Affaire avec sources', true);
        $manager = $this->manager();
        $controversy = $manager->find(Controversy::class, $controversy->getId());
        self::assertInstanceOf(Controversy::class, $controversy);

        $item = (new ControversyItem())
            ->setType(ControversyItemType::Fact)
            ->setTitle('Fait public')
            ->setDate(new \DateTime('2024-01-15'))
            ->setShortDescription('Description publique')
            ->setIsVisible(true);
        $controversy->addControversyItem($item);

        $hidden = (new Source())
            ->setUrl('https://example.com/source-masquee')
            ->setIsVisible(false);
        $visible = (new Source())
            ->setUrl('https://example.com/source-publique')
            ->setIsVisible(true);
        $item->addSource($hidden);
        $item->addSource($visible);
        $manager->flush();

        try {
            $client->request('GET', '/api/controversy_items/'.$item->getId(), server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);

            self::assertResponseIsSuccessful();
            $itemUrls = $this->sourceUrls($client->getResponse()->getContent());
            self::assertContains('https://example.com/source-publique', $itemUrls);
            self::assertNotContains('https://example.com/source-masquee', $itemUrls);

            $client->request('GET', '/api/controversies/'.$controversy->getId(), server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);

            self::assertResponseIsSuccessful();
            $controversyUrls = $this->sourceUrls($client->getResponse()->getContent());
            self::assertContains('https://example.com/source-publique', $controversyUrls);
            self::assertNotContains('https://example.com/source-masquee', $controversyUrls);
        } finally {
            $this->removeControversy($controversy->getId());
        }
    }

    public function testContactPostIsRateLimited(): void
    {
        $client = static::createClient();
        $server = [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
            'REMOTE_ADDR' => '203.0.113.'.random_int(1, 254),
        ];
        $payload = json_encode([
            'name' => 'Smoke Contact',
            'email' => 'smoke-limit@example.com',
            'message' => 'Message de test',
        ], \JSON_THROW_ON_ERROR);

        for ($attempt = 1; $attempt <= 5; ++$attempt) {
            $client->request('POST', '/api/contacts', server: $server, content: $payload);
            self::assertResponseStatusCodeSame(201, \sprintf('La tentative %d doit passer.', $attempt));
        }

        $client->request('POST', '/api/contacts', server: $server, content: $payload);
        self::assertResponseStatusCodeSame(429);
    }

    /**
     * @return list<string>
     */
    private function sourceUrls(string|false $content): array
    {
        self::assertIsString($content);
        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        $urls = [];
        $collect = static function (mixed $value) use (&$collect, &$urls): void {
            if (!\is_array($value)) {
                return;
            }

            if (isset($value['url']) && \is_string($value['url'])) {
                $urls[] = $value['url'];
            }

            foreach ($value as $child) {
                $collect($child);
            }
        };
        $collect($decoded);

        return $urls;
    }

    private function persistControversy(string $name, bool $visible): Controversy
    {
        $manager = $this->manager();
        $controversy = (new Controversy())
            ->setName($name)
            ->setShortDescription('Fiche de test')
            ->setIsVisible($visible);
        $manager->persist($controversy);
        $manager->flush();

        return $controversy;
    }

    private function removeControversy(?Uuid $id): void
    {
        if (null === $id) {
            return;
        }

        $manager = $this->manager();
        $controversy = $manager->find(Controversy::class, $id);
        if (null === $controversy) {
            return;
        }

        $manager->remove($controversy);
        $manager->flush();
    }

    private function manager(): EntityManagerInterface
    {
        $manager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }
}
