<?php

namespace App\Tests;

use App\Enum\SourceProvider;
use App\Service\EmbedResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class EmbedResolverTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: SourceProvider, 2: string}>
     */
    public static function recognizedUrls(): iterable
    {
        yield 'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', SourceProvider::YouTube, 'dQw4w9WgXcQ'];
        yield 'youtube watch extra params' => ['https://youtube.com/watch?list=PL123&v=dQw4w9WgXcQ&t=12', SourceProvider::YouTube, 'dQw4w9WgXcQ'];
        yield 'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ?t=10', SourceProvider::YouTube, 'dQw4w9WgXcQ'];
        yield 'youtube shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', SourceProvider::YouTube, 'dQw4w9WgXcQ'];
        yield 'youtube embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', SourceProvider::YouTube, 'dQw4w9WgXcQ'];
        yield 'mobile youtube' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', SourceProvider::YouTube, 'dQw4w9WgXcQ'];
        yield 'dailymotion video' => ['https://www.dailymotion.com/video/x2jvvep', SourceProvider::Dailymotion, 'x2jvvep'];
        yield 'dailymotion embed' => ['https://www.dailymotion.com/embed/video/x2jvvep', SourceProvider::Dailymotion, 'x2jvvep'];
        yield 'dai.ly' => ['https://dai.ly/x2jvvep', SourceProvider::Dailymotion, 'x2jvvep'];
        yield 'vimeo' => ['https://vimeo.com/123456789', SourceProvider::Vimeo, '123456789'];
        yield 'vimeo player' => ['https://player.vimeo.com/video/123456789', SourceProvider::Vimeo, '123456789'];
        yield 'vimeo channel' => ['https://vimeo.com/channels/staffpicks/123456789', SourceProvider::Vimeo, '123456789'];
        yield 'tweet on x' => ['https://x.com/paris/status/1234567890123456789', SourceProvider::Tweet, '1234567890123456789'];
        yield 'tweet on twitter' => ['https://twitter.com/paris/status/1234567890123456789?s=20', SourceProvider::Tweet, '1234567890123456789'];
        yield 'mobile tweet' => ['https://mobile.twitter.com/paris/status/99', SourceProvider::Tweet, '99'];
    }

    #[DataProvider('recognizedUrls')]
    public function testParserKeepsProviderAndId(string $url, SourceProvider $provider, string $externalId): void
    {
        $requested = null;
        $resolver = new EmbedResolver($this->client(function (string $endpoint) use (&$requested): void {
            $requested = $endpoint;
        }));

        $resolved = $resolver->resolve($url);

        self::assertSame($provider, $resolved->provider);
        self::assertSame($externalId, $resolved->externalId);
        self::assertIsString($requested);
        self::assertStringStartsWith($this->expectedEndpoint($provider), $requested);
        self::assertStringNotContainsString('evil.example', $requested);
    }

    public function testYoutubeUsesFallbackThumbnailAndOEmbedTitleOnly(): void
    {
        $resolver = new EmbedResolver($this->jsonClient([
            'title' => 'Titre <b>YouTube</b>',
            'thumbnail_url' => 'https://i.ytimg.com/vi/dQw4w9WgXcQ/maxresdefault.jpg',
            'author_name' => 'Chaîne',
            'html' => '<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ"></iframe>',
        ]));

        $resolved = $resolver->resolve('https://www.youtube.com/watch?v=dQw4w9WgXcQ');

        self::assertSame(SourceProvider::YouTube, $resolved->provider);
        self::assertSame('Titre YouTube', $resolved->title);
        self::assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $resolved->thumbnailUrl);
        self::assertNull($resolved->authorName);
        self::assertNull($resolved->embedText);
        self::assertStringNotContainsString('<', (string) $resolved->title);
    }

    public function testDailymotionAndVimeoReadTitleAndThumbnail(): void
    {
        $dailymotion = (new EmbedResolver($this->jsonClient([
            'title' => 'Vidéo Dailymotion',
            'thumbnail_url' => 'https://s1.dmcdn.net/v/example/x240',
        ])))->resolve('https://www.dailymotion.com/video/x2jvvep');

        self::assertSame('Vidéo Dailymotion', $dailymotion->title);
        self::assertSame('https://s1.dmcdn.net/v/example/x240', $dailymotion->thumbnailUrl);

        $vimeo = (new EmbedResolver($this->jsonClient([
            'title' => 'Vidéo Vimeo',
            'thumbnail_url' => 'https://i.vimeocdn.com/video/123_640.jpg',
        ])))->resolve('https://vimeo.com/123456789');

        self::assertSame('Vidéo Vimeo', $vimeo->title);
        self::assertSame('https://i.vimeocdn.com/video/123_640.jpg', $vimeo->thumbnailUrl);
    }

    public function testTweetBlockquoteIsPlainText(): void
    {
        $html = <<<'HTML'
            <blockquote class="twitter-tweet"><p lang="fr" dir="ltr">Bonjour &amp; bienvenue <a href="https://t.co/abc">ici</a><br>suite</p>&mdash; Camille (@cam) <a href="https://twitter.com/cam/status/99">9 oct. 2026</a></blockquote>
            <script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
            HTML;

        $resolved = (new EmbedResolver($this->jsonClient([
            'author_name' => 'Camille',
            'html' => $html,
        ])))->resolve('https://x.com/cam/status/99');

        self::assertSame(SourceProvider::Tweet, $resolved->provider);
        self::assertSame('99', $resolved->externalId);
        self::assertSame('Camille', $resolved->authorName);
        self::assertSame("Bonjour & bienvenue ici\nsuite", $resolved->embedText);
        self::assertIsString($resolved->embedText);
        self::assertStringNotContainsString('<', $resolved->embedText);
        self::assertStringNotContainsString('widgets.js', $resolved->embedText);
        self::assertStringNotContainsString('blockquote', $resolved->embedText);
        self::assertStringNotContainsString('@cam', $resolved->embedText);
    }

    public function testBlockquoteWithoutParagraphDropsAttributionAndTags(): void
    {
        $html = '<blockquote>Un fait &quot;clair&quot; <b>ici</b> &mdash; Auteur <a href="https://twitter.com/a/status/1">date</a><script>alert(1)</script></blockquote>';

        $resolved = (new EmbedResolver($this->jsonClient([
            'author_name' => 'Auteur',
            'html' => $html,
        ])))->resolve('https://twitter.com/a/status/1');

        self::assertSame('Un fait "clair" ici', $resolved->embedText);
        self::assertIsString($resolved->embedText);
        self::assertStringNotContainsString('<', $resolved->embedText);
        self::assertStringNotContainsString('alert', $resolved->embedText);
    }

    public function testPlainLinkDoesNotFetchAnything(): void
    {
        $http = new MockHttpClient(function (): never {
            self::fail('Une URL quelconque ne doit pas être téléchargée.');
        });

        $resolved = (new EmbedResolver($http))->resolve('https://example.com/youtube/watch?v=dQw4w9WgXcQ');

        self::assertSame(SourceProvider::Link, $resolved->provider);
        self::assertNull($resolved->externalId);
        self::assertSame(0, $http->getRequestsCount());
    }

    public function testLookalikeHostStaysALink(): void
    {
        $http = new MockHttpClient(function (): never {
            self::fail('Un hôte imitant YouTube ne doit pas être contacté.');
        });

        $resolved = (new EmbedResolver($http))->resolve('https://www.youtube.com.evil.example/watch?v=dQw4w9WgXcQ');

        self::assertSame(SourceProvider::Link, $resolved->provider);
        self::assertSame(0, $http->getRequestsCount());
    }

    public function testFailedOEmbedKeepsRecognizedProvider(): void
    {
        $http = new MockHttpClient([
            new MockResponse('down', ['http_code' => 503]),
            new MockResponse('{', ['http_code' => 200]),
            static function (): never {
                throw new TransportException('timeout');
            },
        ]);
        $resolver = new EmbedResolver($http);

        $youtube = $resolver->resolve('https://youtu.be/dQw4w9WgXcQ');
        self::assertSame(SourceProvider::YouTube, $youtube->provider);
        self::assertSame('dQw4w9WgXcQ', $youtube->externalId);
        self::assertNull($youtube->title);
        self::assertSame('https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $youtube->thumbnailUrl);

        $vimeo = $resolver->resolve('https://vimeo.com/123456789');
        self::assertSame(SourceProvider::Vimeo, $vimeo->provider);
        self::assertSame('123456789', $vimeo->externalId);
        self::assertNull($vimeo->title);
        self::assertNull($vimeo->thumbnailUrl);

        $tweet = $resolver->resolve('https://x.com/paris/status/42');
        self::assertSame(SourceProvider::Tweet, $tweet->provider);
        self::assertSame('42', $tweet->externalId);
        self::assertNull($tweet->authorName);
        self::assertNull($tweet->embedText);
    }

    private function expectedEndpoint(SourceProvider $provider): string
    {
        return match ($provider) {
            SourceProvider::YouTube => 'https://www.youtube.com/oembed?',
            SourceProvider::Dailymotion => 'https://www.dailymotion.com/services/oembed?',
            SourceProvider::Vimeo => 'https://vimeo.com/api/oembed.json?',
            SourceProvider::Tweet => 'https://publish.twitter.com/oembed?',
            SourceProvider::Link => 'https://invalid.example/',
        };
    }

    /**
     * @param \Closure(string): void $onRequest
     */
    private function client(\Closure $onRequest): MockHttpClient
    {
        return new MockHttpClient(function (string $method, string $url, array $options) use ($onRequest): MockResponse {
            self::assertSame('GET', $method);
            self::assertLessThanOrEqual(3, $options['timeout']);
            self::assertSame(0, $options['max_redirects']);
            $onRequest($url);

            return new MockResponse(json_encode([
                'title' => 'Titre',
                'thumbnail_url' => 'https://cdn.example/thumb.jpg',
                'author_name' => 'Auteur',
                'html' => '<blockquote><p>Texte</p></blockquote>',
            ], \JSON_THROW_ON_ERROR));
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonClient(array $payload): MockHttpClient
    {
        return new MockHttpClient(new MockResponse(json_encode($payload, \JSON_THROW_ON_ERROR)));
    }
}
