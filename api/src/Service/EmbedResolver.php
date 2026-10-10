<?php

namespace App\Service;

use App\Enum\SourceProvider;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\NoPrivateNetworkHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Classifies a pasted URL. Players and tweets use their oEmbed endpoint.
 * Other http(s) pages are fetched once for Open Graph tags.
 */
final class EmbedResolver
{
    private const TIMEOUT_SECONDS = 2.5;

    private const PAGE_TIMEOUT_SECONDS = 5;

    private const MAX_PAGE_BYTES = 1_500_000;

    private const BROWSER = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36';

    private const YOUTUBE_OEMBED = 'https://www.youtube.com/oembed';
    private const DAILYMOTION_OEMBED = 'https://www.dailymotion.com/services/oembed';
    private const VIMEO_OEMBED = 'https://vimeo.com/api/oembed.json';
    private const TWITTER_OEMBED = 'https://publish.twitter.com/oembed';

    private readonly OpenGraphReader $openGraph;

    public function __construct(
        private HttpClientInterface $httpClient,
        ?OpenGraphReader $openGraph = null,
    ) {
        $this->openGraph = $openGraph ?? new OpenGraphReader();
    }

    public function resolve(string $url): ResolvedEmbed
    {
        $url = trim($url);
        if ('' === $url) {
            return ResolvedEmbed::link();
        }

        $parts = $this->parts($url);
        if (null === $parts) {
            return ResolvedEmbed::link();
        }

        $youtubeId = $this->youtubeId($parts);
        if (null !== $youtubeId) {
            $metadata = $this->oembed(self::YOUTUBE_OEMBED, $url, ['format' => 'json']);

            return new ResolvedEmbed(
                SourceProvider::YouTube,
                $youtubeId,
                $this->plain($metadata['title'] ?? null, 255),
                sprintf('https://i.ytimg.com/vi/%s/hqdefault.jpg', $youtubeId),
            );
        }

        $dailymotionId = $this->dailymotionId($parts);
        if (null !== $dailymotionId) {
            $metadata = $this->oembed(self::DAILYMOTION_OEMBED, $url);

            return new ResolvedEmbed(
                SourceProvider::Dailymotion,
                $dailymotionId,
                $this->plain($metadata['title'] ?? null, 255),
                $this->httpsUrl($metadata['thumbnail_url'] ?? null),
            );
        }

        $vimeoId = $this->vimeoId($parts);
        if (null !== $vimeoId) {
            $metadata = $this->oembed(self::VIMEO_OEMBED, $url);

            return new ResolvedEmbed(
                SourceProvider::Vimeo,
                $vimeoId,
                $this->plain($metadata['title'] ?? null, 255),
                $this->httpsUrl($metadata['thumbnail_url'] ?? null),
            );
        }

        $tweetId = $this->tweetId($parts);
        if (null !== $tweetId) {
            $metadata = $this->oembed(self::TWITTER_OEMBED, $url, ['omit_script' => '1']);

            return new ResolvedEmbed(
                SourceProvider::Tweet,
                $tweetId,
                authorName: $this->plain($metadata['author_name'] ?? null, 255),
                embedText: $this->blockquoteText($metadata['html'] ?? null),
            );
        }

        return $this->openGraphFrom($url);
    }

    private function openGraphFrom(string $url): ResolvedEmbed
    {
        $html = $this->fetchPage($url);
        if (null === $html) {
            return ResolvedEmbed::link();
        }

        return $this->openGraph->read($html, $url);
    }

    private function fetchPage(string $url): ?string
    {
        try {
            $response = (new NoPrivateNetworkHttpClient($this->httpClient))->request('GET', $url, [
                'timeout' => self::PAGE_TIMEOUT_SECONDS,
                'max_duration' => 8,
                'max_redirects' => 3,
                'headers' => [
                    'User-Agent' => self::BROWSER,
                    'Accept' => 'text/html,application/xhtml+xml',
                    'Accept-Language' => 'fr-FR,fr;q=0.9',
                ],
                'on_progress' => static function (int $dlNow): void {
                    if ($dlNow > self::MAX_PAGE_BYTES) {
                        throw new TransportException('Réponse trop volumineuse.');
                    }
                },
            ]);

            if (200 !== $response->getStatusCode()) {
                return null;
            }

            $type = strtolower($response->getHeaders(false)['content-type'][0] ?? '');
            if (str_contains($type, 'image/') || str_contains($type, 'application/json')) {
                return null;
            }

            return $response->getContent(false);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{host: string, path: string, query: array<string, mixed>}|null
     */
    private function parts(string $url): ?array
    {
        $parts = parse_url($url);
        if (!\is_array($parts) || !isset($parts['host']) || !\is_string($parts['host'])) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!\in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host']);
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        $query = [];
        if (isset($parts['query']) && \is_string($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        return [
            'host' => $host,
            'path' => \is_string($parts['path'] ?? null) ? $parts['path'] : '/',
            'query' => $query,
        ];
    }

    /**
     * @param array{host: string, path: string, query: array<string, mixed>} $parts
     */
    private function youtubeId(array $parts): ?string
    {
        if ('youtu.be' === $parts['host']) {
            return $this->capture('#^/([a-zA-Z0-9_-]{11})(?:/|$)#', $parts['path']);
        }

        if (!\in_array($parts['host'], ['youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtube-nocookie.com'], true)) {
            return null;
        }

        if (1 === preg_match('#^/watch/?$#', $parts['path'])) {
            $videoId = $parts['query']['v'] ?? null;

            return \is_string($videoId) && 1 === preg_match('/^[a-zA-Z0-9_-]{11}$/', $videoId) ? $videoId : null;
        }

        return $this->capture('#^/(?:shorts|embed)/([a-zA-Z0-9_-]{11})(?:/|$)#', $parts['path']);
    }

    /**
     * @param array{host: string, path: string, query: array<string, mixed>} $parts
     */
    private function dailymotionId(array $parts): ?string
    {
        if ('dai.ly' === $parts['host']) {
            return $this->capture('#^/([a-zA-Z0-9]{1,32})(?:/|$)#', $parts['path']);
        }

        if ('dailymotion.com' !== $parts['host']) {
            return null;
        }

        return $this->capture('#^/(?:embed/video|video)/([a-zA-Z0-9]{1,32})(?:/|$)#', $parts['path']);
    }

    /**
     * @param array{host: string, path: string, query: array<string, mixed>} $parts
     */
    private function vimeoId(array $parts): ?string
    {
        if (!\in_array($parts['host'], ['vimeo.com', 'player.vimeo.com'], true)) {
            return null;
        }

        return $this->capture('#/video/(\d{1,20})(?:/|$)#', $parts['path'])
            ?? $this->capture('#^/(\d{1,20})(?:/|$)#', $parts['path'])
            ?? $this->capture('#/(\d{1,20})(?:/|$)#', $parts['path']);
    }

    /**
     * @param array{host: string, path: string, query: array<string, mixed>} $parts
     */
    private function tweetId(array $parts): ?string
    {
        if (!\in_array($parts['host'], ['x.com', 'twitter.com', 'mobile.twitter.com'], true)) {
            return null;
        }

        return $this->capture('#/status(?:es)?/(\d{1,22})(?:/|$)#', $parts['path']);
    }

    private function capture(string $pattern, string $path): ?string
    {
        if (1 !== preg_match($pattern, $path, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @param array<string, string> $query
     *
     * @return array<string, mixed>
     */
    private function oembed(string $endpoint, string $pageUrl, array $query = []): array
    {
        try {
            $response = $this->httpClient->request('GET', $endpoint, [
                'timeout' => self::TIMEOUT_SECONDS,
                'max_redirects' => 0,
                'headers' => [
                    'Accept' => 'application/json',
                ],
                'query' => ['url' => $pageUrl, ...$query],
            ]);

            if ($response->getStatusCode() >= 400) {
                return [];
            }

            $data = $response->toArray(false);
        } catch (\Throwable) {
            return [];
        }

        return $data;
    }

    private function plain(mixed $value, int $max): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $text = trim(html_entity_decode(strip_tags($value), \ENT_QUOTES | \ENT_HTML5, 'UTF-8'));
        if ('' === $text) {
            return null;
        }

        if (mb_strlen($text) > $max) {
            return mb_substr($text, 0, $max);
        }

        return $text;
    }

    private function httpsUrl(mixed $value): ?string
    {
        $url = $this->plain($value, 2048);
        if (null === $url || !str_starts_with($url, 'https://')) {
            return null;
        }

        return $url;
    }

    private function blockquoteText(mixed $html): ?string
    {
        if (!\is_string($html) || '' === trim($html)) {
            return null;
        }

        $fragment = $html;
        if (1 === preg_match('#<blockquote\b[^>]*>(.*)</blockquote>#is', $html, $matches)) {
            $fragment = $matches[1];
        }

        $fragment = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $fragment) ?? $fragment;

        if (1 === preg_match_all('#<p\b[^>]*>(.*?)</p>#is', $fragment, $paragraphs) && [] !== $paragraphs[1]) {
            $fragment = implode("\n\n", $paragraphs[1]);
        } else {
            $fragment = preg_replace('~(?:&mdash;|&#8212;|—).*$~s', '', $fragment) ?? $fragment;
        }

        $fragment = preg_replace('#<br\s*/?>#i', "\n", $fragment) ?? $fragment;
        $text = html_entity_decode(strip_tags($fragment), \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/[ \t]{2,}/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;
        $text = trim($text);

        if ('' === $text) {
            return null;
        }

        if (mb_strlen($text) > 5000) {
            return mb_substr($text, 0, 5000);
        }

        return $text;
    }
}
