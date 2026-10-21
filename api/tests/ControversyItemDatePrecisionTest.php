<?php

namespace App\Tests;

use App\Entity\Controversy;
use App\Entity\ControversyItem;
use App\Enum\ControversyItemType;
use App\Enum\DatePrecision;
use App\Form\ControversyItemType as ItemFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Uid\Uuid;

class ControversyItemDatePrecisionTest extends WebTestCase
{
    private const TOKEN = 'test-api-token';

    public function testStoredDateIsNormalizedFromEitherSetterOrder(): void
    {
        $monthThenDate = (new ControversyItem())
            ->setDatePrecision(DatePrecision::Month)
            ->setDate(new \DateTime('2025-05-17'));
        $monthThenDate->normalizeStoredDate();
        self::assertSame(DatePrecision::Month, $monthThenDate->getDatePrecision());
        self::assertSame('2025-05-01', $monthThenDate->getDate()?->format('Y-m-d'));

        $dateThenYear = (new ControversyItem())
            ->setDate(new \DateTime('2025-05-17'))
            ->setDatePrecision(DatePrecision::Year);
        $dateThenYear->normalizeStoredDate();
        self::assertSame(DatePrecision::Year, $dateThenYear->getDatePrecision());
        self::assertSame('2025-01-01', $dateThenYear->getDate()?->format('Y-m-d'));

        $day = (new ControversyItem())->setDate(new \DateTime('2019-01-12 15:30:00'));
        $day->normalizeStoredDate();
        self::assertSame(DatePrecision::Day, $day->getDatePrecision());
        self::assertSame('2019-01-12', $day->getDate()?->format('Y-m-d'));

        $retargeted = (new ControversyItem())
            ->setDate(new \DateTime('2024-08-09'))
            ->setDatePrecision(DatePrecision::Year);
        $retargeted->normalizeStoredDate();
        $retargeted
            ->setDate(new \DateTime('2019-01-12'))
            ->setDatePrecision(DatePrecision::Day);
        $retargeted->normalizeStoredDate();
        self::assertSame('2019-01-12', $retargeted->getDate()?->format('Y-m-d'));

        $alreadyFirst = (new ControversyItem())
            ->setDate(new \DateTime('2025-05-01'))
            ->setDatePrecision(DatePrecision::Month);
        $alreadyFirst->normalizeStoredDate();
        self::assertSame('2025-05-01', $alreadyFirst->getDate()?->format('Y-m-d'));
    }

    public function testApiReadsAndWritesDatePrecision(): void
    {
        $client = static::createClient();
        $controversy = $this->persistControversy();
        $itemIri = null;

        try {
            $client->request('POST', '/api/controversy_items', server: $this->jsonHeaders(self::TOKEN), content: json_encode([
                'type' => 'fact',
                'title' => 'Fait de mai',
                'date' => '2025-05-17',
                'datePrecision' => 'month',
                'isVisible' => true,
                'shortDescription' => 'Un événement daté au mois.',
                'controversy' => '/api/controversies/'.$controversy->getId(),
            ], \JSON_THROW_ON_ERROR));

            self::assertResponseStatusCodeSame(201, (string) $client->getResponse()->getContent());
            self::assertStringContainsString('application/ld+json', (string) $client->getResponse()->headers->get('content-type'));
            $created = $this->json($client->getResponse()->getContent());
            self::assertArrayHasKey('@id', $created);
            self::assertSame('month', $created['datePrecision']);
            self::assertStringStartsWith('2025-05-01', $this->requireString($created['date']));
            $itemIri = $this->requireString($created['@id']);

            $client->request('GET', $itemIri, server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);
            self::assertResponseIsSuccessful();
            $read = $this->json($client->getResponse()->getContent());
            self::assertSame('month', $read['datePrecision']);
            self::assertStringStartsWith('2025-05-01', $this->requireString($read['date']));

            $stored = $this->reloadItem($itemIri);
            self::assertSame(DatePrecision::Month, $stored->getDatePrecision());
            self::assertSame('2025-05-01', $stored->getDate()?->format('Y-m-d'));

            $client->request('GET', '/api/controversies/'.$controversy->getId(), server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);
            self::assertResponseIsSuccessful();
            $controversyPayload = $this->json($client->getResponse()->getContent());
            $nested = $this->nestedItem($controversyPayload, 'Fait de mai');
            self::assertSame('month', $nested['datePrecision']);
            self::assertStringStartsWith('2025-05-01', $this->requireString($nested['date']));

            $client->request('PATCH', $itemIri, server: $this->mergePatchHeaders(), content: json_encode([
                'datePrecision' => 'year',
            ], \JSON_THROW_ON_ERROR));
            self::assertResponseIsSuccessful((string) $client->getResponse()->getContent());
            $patched = $this->json($client->getResponse()->getContent());
            self::assertSame('year', $patched['datePrecision']);
            self::assertStringStartsWith('2025-01-01', $this->requireString($patched['date']));
            self::assertSame('2025-01-01', $this->reloadItem($itemIri)->getDate()?->format('Y-m-d'));

            $client->request('PATCH', $itemIri, server: $this->mergePatchHeaders(), content: json_encode([
                'date' => '2019-01-12',
                'datePrecision' => 'day',
            ], \JSON_THROW_ON_ERROR));
            self::assertResponseIsSuccessful((string) $client->getResponse()->getContent());
            $exact = $this->json($client->getResponse()->getContent());
            self::assertSame('day', $exact['datePrecision']);
            self::assertStringStartsWith('2019-01-12', $this->requireString($exact['date']));

            $client->request('PATCH', $itemIri, server: $this->mergePatchHeaders(), content: json_encode([
                'datePrecision' => 'month',
                'date' => '2025-05-17',
            ], \JSON_THROW_ON_ERROR));
            self::assertResponseIsSuccessful((string) $client->getResponse()->getContent());
            $monthFirst = $this->json($client->getResponse()->getContent());
            self::assertSame('month', $monthFirst['datePrecision']);
            self::assertStringStartsWith('2025-05-01', $this->requireString($monthFirst['date']));
            self::assertSame('2025-05-01', $this->reloadItem($itemIri)->getDate()?->format('Y-m-d'));

            $client->request('POST', '/api/controversy_items', server: $this->jsonHeaders(self::TOKEN), content: json_encode([
                'type' => 'statement',
                'title' => 'Fait au jour',
                'date' => '2024-06-20',
                'isVisible' => true,
                'controversy' => '/api/controversies/'.$controversy->getId(),
            ], \JSON_THROW_ON_ERROR));
            self::assertResponseStatusCodeSame(201, (string) $client->getResponse()->getContent());
            $defaulted = $this->json($client->getResponse()->getContent());
            self::assertSame('day', $defaulted['datePrecision']);
            self::assertStringStartsWith('2024-06-20', $this->requireString($defaulted['date']));
        } finally {
            $this->removeControversy($controversy->getId());
        }
    }

    public function testInvalidDatePrecisionIsRejected(): void
    {
        $client = static::createClient();
        $controversy = $this->persistControversy();
        $item = (new ControversyItem())
            ->setType(ControversyItemType::Fact)
            ->setTitle('Fait déjà daté')
            ->setDate(new \DateTime('2024-03-18'))
            ->setIsVisible(true);
        $controversy->addControversyItem($item);
        $this->manager()->flush();
        $itemIri = '/api/controversy_items/'.$item->getId();

        try {
            foreach (['week', 'Day', ''] as $precision) {
                $client->request('POST', '/api/controversy_items', server: $this->jsonHeaders(self::TOKEN), content: json_encode([
                    'type' => 'fact',
                    'title' => 'Fait refusé',
                    'date' => '2025-05-17',
                    'datePrecision' => $precision,
                    'isVisible' => true,
                    'controversy' => '/api/controversies/'.$controversy->getId(),
                ], \JSON_THROW_ON_ERROR));

                self::assertResponseStatusCodeSame(422, (string) $client->getResponse()->getContent());
                self::assertStringContainsString('datePrecision', (string) $client->getResponse()->getContent());
            }

            $client->request('POST', '/api/controversy_items', server: $this->jsonHeaders(self::TOKEN), content: json_encode([
                'type' => 'fact',
                'title' => 'Fait sans précision',
                'date' => '2025-05-17',
                'datePrecision' => null,
                'isVisible' => true,
                'controversy' => '/api/controversies/'.$controversy->getId(),
            ], \JSON_THROW_ON_ERROR));
            self::assertResponseStatusCodeSame(422, (string) $client->getResponse()->getContent());

            $client->request('PATCH', $itemIri, server: $this->mergePatchHeaders(), content: json_encode([
                'datePrecision' => 'season',
            ], \JSON_THROW_ON_ERROR));
            self::assertResponseStatusCodeSame(422, (string) $client->getResponse()->getContent());
            self::assertStringContainsString('datePrecision', (string) $client->getResponse()->getContent());

            $unchanged = $this->reloadItem($itemIri);
            self::assertSame(DatePrecision::Day, $unchanged->getDatePrecision());
            self::assertSame('2024-03-18', $unchanged->getDate()?->format('Y-m-d'));
        } finally {
            $this->removeControversy($controversy->getId());
        }
    }

    public function testAdminItemFormNormalizesOnSave(): void
    {
        static::createClient();
        $controversy = $this->persistControversy();
        $item = (new ControversyItem())
            ->setType(ControversyItemType::Fact)
            ->setTitle('Depuis le formulaire')
            ->setDate(new \DateTime('2024-01-15'))
            ->setIsVisible(true);
        $controversy->addControversyItem($item);
        $this->manager()->flush();

        try {
            $factory = static::getContainer()->get(FormFactoryInterface::class);
            self::assertInstanceOf(FormFactoryInterface::class, $factory);
            $form = $factory->create(ItemFormType::class, $item, [
                'csrf_protection' => false,
            ]);
            $form->submit([
                'date' => '2025-05-17',
                'datePrecision' => 'month',
                'title' => 'Depuis le formulaire',
                'shortDescription' => 'Saisi au mois',
                'people' => [],
                'sources' => [],
            ]);

            self::assertTrue($form->isValid(), (string) $form->getErrors(true));
            $this->manager()->flush();

            $stored = $this->reloadItem('/api/controversy_items/'.$item->getId());
            self::assertSame(DatePrecision::Month, $stored->getDatePrecision());
            self::assertSame('2025-05-01', $stored->getDate()?->format('Y-m-d'));
        } finally {
            $this->removeControversy($controversy->getId());
        }
    }

    public function testExistingRowsDefaultToDayPrecision(): void
    {
        $client = static::createClient();
        $controversy = $this->persistControversy();
        $id = Uuid::v4();

        try {
            $this->manager()->getConnection()->executeStatement(
                'INSERT INTO controversy_item (id, type, title, date, is_visible, controversy_id) VALUES (:id, :type, :title, :date, true, :controversy)',
                [
                    'id' => (string) $id,
                    'type' => 'fact',
                    'title' => 'Fait hérité',
                    'date' => '2020-06-15',
                    'controversy' => (string) $controversy->getId(),
                ],
            );

            $client->request('GET', '/api/controversy_items/'.$id, server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);
            self::assertResponseIsSuccessful();
            $payload = $this->json($client->getResponse()->getContent());
            self::assertSame('day', $payload['datePrecision']);
            self::assertStringStartsWith('2020-06-15', $this->requireString($payload['date']));

            $stored = $this->reloadItem('/api/controversy_items/'.$id);
            self::assertSame(DatePrecision::Day, $stored->getDatePrecision());
            self::assertSame('2020-06-15', $stored->getDate()?->format('Y-m-d'));
        } finally {
            $this->removeControversy($controversy->getId());
        }
    }

    private function persistControversy(): Controversy
    {
        $manager = $this->manager();
        $controversy = (new Controversy())
            ->setName('Précision '.bin2hex(random_bytes(4)))
            ->setShortDescription('Fiche de test')
            ->setIsVisible(true);
        $manager->persist($controversy);
        $manager->flush();

        return $controversy;
    }

    private function reloadItem(string $iri): ControversyItem
    {
        $manager = $this->manager();
        $manager->clear();
        $item = $manager->find(ControversyItem::class, Uuid::fromString(basename($iri)));
        self::assertInstanceOf(ControversyItem::class, $item);

        return $item;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function nestedItem(array $payload, string $title): array
    {
        $items = $payload['controversyItems'] ?? null;
        self::assertIsArray($items);

        foreach ($items as $item) {
            if (\is_array($item) && ($item['title'] ?? null) === $title) {
                return $item;
            }
        }

        self::fail(sprintf('Fait « %s » absent de la controverse.', $title));
    }

    /**
     * @return array<string, string>
     */
    private function jsonHeaders(string $token): array
    {
        return [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mergePatchHeaders(): array
    {
        return [
            'CONTENT_TYPE' => 'application/merge-patch+json',
            'HTTP_ACCEPT' => 'application/ld+json',
            'HTTP_AUTHORIZATION' => 'Bearer '.self::TOKEN,
        ];
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

    private function removeControversy(?Uuid $id): void
    {
        if (null === $id) {
            return;
        }

        $manager = $this->manager();
        $manager->clear();
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
