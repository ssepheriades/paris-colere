<?php

namespace App\Tests;

use App\Entity\CampaignPromise;
use App\Entity\Source;
use App\Entity\Theme;
use App\Enum\CampaignPromiseResultType;
use App\Enum\CampaignPromiseStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

class CampaignPromiseTest extends WebTestCase
{
    public function testVisiblePromiseExposesStatusThemeAndSource(): void
    {
        $client = static::createClient();
        $manager = $this->manager();

        $theme = (new Theme())
            ->setName('Logement test')
            ->setShortDescription('Thème de test')
            ->setColor('#7A1F2B')
            ->setIcon('mdi-home-city');
        $manager->persist($theme);

        $hidden = (new CampaignPromise())
            ->setTitle('Promesse masquée '.uniqid())
            ->setQuote('Cette citation ne doit pas sortir.')
            ->setStatus(CampaignPromiseStatus::Broken)
            ->setIsVisible(false);
        $manager->persist($hidden);

        $promise = (new CampaignPromise())
            ->setTitle('Construire des logements '.uniqid())
            ->setQuote('Nous construirons des logements abordables.')
            ->setStatus(CampaignPromiseStatus::Kept)
            ->setResultType(CampaignPromiseResultType::Absolute)
            ->setResultValue(12000)
            ->setObjective(60000)
            ->setResultLabel('logements')
            ->setIllustration('logement.jpg')
            ->setIsVisible(true);
        $promise->addTheme($theme);

        $visible = (new Source())
            ->setUrl('https://example.com/promesse-tenue')
            ->setIsVisible(true);
        $masked = (new Source())
            ->setUrl('https://example.com/promesse-masquee')
            ->setIsVisible(false);
        $promise->addSource($visible);
        $promise->addSource($masked);
        $manager->persist($promise);
        $manager->flush();

        try {
            $client->request('GET', '/api/campaign_promises', server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);

            self::assertResponseIsSuccessful();
            $content = $client->getResponse()->getContent();
            self::assertIsString($content);
            $match = $this->promiseMember($content, $promise->getTitle() ?? '');
            self::assertIsArray($match);
            self::assertSame('kept', $match['status']);
            self::assertSame('/uploads/illustrations/logement.jpg', $match['illustration']);
            self::assertSame('absolute', $match['resultType']);
            self::assertEquals(12000, $match['resultValue']);
            self::assertEquals(60000, $match['objective']);
            self::assertSame('logements', $match['resultLabel']);
            $themes = json_encode($match['theme'] ?? [], \JSON_THROW_ON_ERROR);
            self::assertStringContainsString('Logement test', $themes);
            self::assertStringContainsString('mdi-home-city', $themes);
            $urls = $this->sourceUrls($match['sources'] ?? null);
            self::assertContains('https://example.com/promesse-tenue', $urls);
            self::assertNotContains('https://example.com/promesse-masquee', $urls);
            self::assertNull($this->promiseMember($content, $hidden->getTitle() ?? ''));

            $client->request('POST', '/api/campaign_promises', server: [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT' => 'application/ld+json',
            ], content: '{}');
            self::assertResponseStatusCodeSame(401);
        } finally {
            $this->removePromise($promise->getId());
            $this->removePromise($hidden->getId());
            $this->removeTheme($theme->getId());
        }
    }

    public function testPercentageResultOmitsObjective(): void
    {
        $client = static::createClient();
        $manager = $this->manager();
        $promise = (new CampaignPromise())
            ->setTitle('Part des logements '.uniqid())
            ->setQuote('Un quart du parc sera social.')
            ->setStatus(CampaignPromiseStatus::InProgress)
            ->setResultType(CampaignPromiseResultType::Percentage)
            ->setResultValue(25)
            ->setIsVisible(true);
        $manager->persist($promise);
        $manager->flush();

        try {
            $client->request('GET', '/api/campaign_promises/'.$promise->getId(), server: [
                'HTTP_ACCEPT' => 'application/ld+json',
            ]);

            self::assertResponseIsSuccessful();
            $content = $client->getResponse()->getContent();
            self::assertIsString($content);
            $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
            self::assertIsArray($decoded);
            self::assertSame('percentage', $decoded['resultType']);
            self::assertEquals(25, $decoded['resultValue']);
            self::assertArrayHasKey('objective', $decoded);
            self::assertNull($decoded['objective']);
            self::assertNull($decoded['resultLabel']);
        } finally {
            $this->removePromise($promise->getId());
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function promiseMember(string $content, string $title): ?array
    {
        $decoded = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);
        $members = $decoded['hydra:member'] ?? $decoded['member'] ?? [];
        self::assertIsArray($members);

        foreach ($members as $member) {
            if (\is_array($member) && ($member['title'] ?? null) === $title) {
                return $member;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function sourceUrls(mixed $sources): array
    {
        if (!\is_array($sources)) {
            return [];
        }

        $urls = [];
        foreach ($sources as $source) {
            if (\is_array($source) && isset($source['url']) && \is_string($source['url'])) {
                $urls[] = $source['url'];
            }
        }

        return $urls;
    }

    private function removePromise(?Uuid $id): void
    {
        if (null === $id) {
            return;
        }

        $manager = $this->manager();
        $promise = $manager->find(CampaignPromise::class, $id);
        if (null === $promise) {
            return;
        }

        $manager->remove($promise);
        $manager->flush();
    }

    private function removeTheme(?int $id): void
    {
        if (null === $id) {
            return;
        }

        $manager = $this->manager();
        $theme = $manager->find(Theme::class, $id);
        if (null === $theme) {
            return;
        }

        $manager->remove($theme);
        $manager->flush();
    }

    private function manager(): EntityManagerInterface
    {
        $manager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $manager);

        return $manager;
    }
}
