<?php

namespace App\Tests;

use App\Entity\Controversy;
use App\Entity\ControversyItem;
use App\Enum\SourceProvider;
use App\Service\OpenGraphReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class SourcePreviewTest extends TestCase
{
    public function testOpenGraphTagsBecomeALinkPreview(): void
    {
        $embed = (new OpenGraphReader())->read(<<<'HTML'
            <!DOCTYPE html>
            <html><head>
            <meta property="og:site_name" content="BFM">
            <meta property="og:title" content="Explosion rue de Trévise: signature de l&#39;accord-cadre">
            <meta property="og:description" content="La Ville s&#39;était dite prête à signer.">
            <meta property="og:image" content="/images/photo.jpg">
            </head></html>
            HTML, 'https://www.bfmtv.com/paris/article.html');

        self::assertSame(SourceProvider::Link, $embed->provider);
        self::assertNull($embed->externalId);
        self::assertSame("Explosion rue de Trévise: signature de l'accord-cadre", $embed->title);
        self::assertSame('BFM', $embed->authorName);
        self::assertSame("La Ville s'était dite prête à signer.", $embed->embedText);
        self::assertSame('https://www.bfmtv.com/images/photo.jpg', $embed->thumbnailUrl);
    }

    public function testOpenGraphFallsBackToTwitterAndTitle(): void
    {
        $embed = (new OpenGraphReader())->read(<<<'HTML'
            <!DOCTYPE html>
            <html><head>
            <title>Titre de repli</title>
            <meta name="twitter:description" content="Résumé twitter">
            <meta name="twitter:image" content="//cdn.example/photo.jpg">
            </head></html>
            HTML, 'https://example.com/article');

        self::assertSame('Titre de repli', $embed->title);
        self::assertSame('Résumé twitter', $embed->embedText);
        self::assertSame('https://cdn.example/photo.jpg', $embed->thumbnailUrl);
        self::assertNull($embed->authorName);
    }

    public function testJavascriptThumbnailIsIgnored(): void
    {
        $embed = (new OpenGraphReader())->read(
            '<meta property="og:image" content="javascript:alert(1)"><title>Sans image</title>',
            'https://example.com/article',
        );

        self::assertSame('Sans image', $embed->title);
        self::assertNull($embed->thumbnailUrl);
    }

    public function testVisibleFactsAreNewestFirst(): void
    {
        $controversy = new Controversy();
        $older = $this->fact('Ancien', '2019-01-12', '00000000-0000-0000-0000-000000000002');
        $sameDayLaterId = $this->fact('Même jour', '2022-01-12', '00000000-0000-0000-0000-000000000009');
        $sameDayEarlierId = $this->fact('Même jour, id plus petit', '2022-01-12', '00000000-0000-0000-0000-000000000001');
        $hidden = $this->fact('Masqué', '2024-06-01', '00000000-0000-0000-0000-000000000003', false);

        $controversy->addControversyItem($older);
        $controversy->addControversyItem($sameDayEarlierId);
        $controversy->addControversyItem($hidden);
        $controversy->addControversyItem($sameDayLaterId);

        $titles = array_map(
            static fn (ControversyItem $item): ?string => $item->getTitle(),
            $controversy->getVisibleControversyItems(),
        );

        self::assertSame(['Même jour', 'Même jour, id plus petit', 'Ancien'], $titles);
    }

    private function fact(string $title, string $date, string $id, bool $visible = true): ControversyItem
    {
        $item = (new ControversyItem())
            ->setTitle($title)
            ->setDate(new \DateTime($date))
            ->setIsVisible($visible);

        $property = new \ReflectionProperty(ControversyItem::class, 'id');
        $property->setValue($item, Uuid::fromString($id));

        return $item;
    }
}
