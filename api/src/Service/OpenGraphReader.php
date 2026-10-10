<?php

namespace App\Service;

final class OpenGraphReader
{
    public function read(string $html, string $pageUrl): ResolvedEmbed
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8">'.$html, \LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $meta = [];
        foreach ($document->getElementsByTagName('meta') as $element) {
            $key = strtolower($element->getAttribute('property') ?: $element->getAttribute('name'));
            $content = trim($element->getAttribute('content'));
            if ('' === $key || '' === $content || isset($meta[$key])) {
                continue;
            }

            $meta[$key] = $content;
        }

        $title = $this->text($meta['og:title'] ?? $meta['twitter:title'] ?? null);
        if (null === $title) {
            $titleNodes = $document->getElementsByTagName('title');
            $title = $titleNodes->length > 0 ? $this->text($titleNodes->item(0)?->textContent) : null;
        }

        $image = $this->absoluteUrl($meta['og:image'] ?? $meta['og:image:secure_url'] ?? $meta['twitter:image'] ?? $meta['twitter:image:src'] ?? null, $pageUrl);

        return ResolvedEmbed::link(
            $this->clip($title, 255),
            $image,
            $this->clip($this->text($meta['og:site_name'] ?? null), 255),
            $this->clip($this->text($meta['og:description'] ?? $meta['twitter:description'] ?? null), 2000),
        );
    }

    private function text(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = html_entity_decode($value, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        return '' === $value ? null : $value;
    }

    private function clip(?string $value, int $length): ?string
    {
        if (null === $value) {
            return null;
        }

        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return mb_substr($value, 0, $length);
    }

    private function absoluteUrl(?string $value, string $base): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim($value);
        if ('' === $value) {
            return null;
        }

        $lower = strtolower($value);
        if (str_starts_with($lower, 'data:') || str_starts_with($lower, 'javascript:')) {
            return null;
        }

        if (str_starts_with($value, '//')) {
            $scheme = parse_url($base, \PHP_URL_SCHEME);
            $value = (\is_string($scheme) && '' !== $scheme ? $scheme : 'https').':'.$value;
        } elseif (!str_starts_with($lower, 'http://') && !str_starts_with($lower, 'https://')) {
            $origin = parse_url($base);
            if (!\is_array($origin) || !isset($origin['host']) || !\is_string($origin['host'])) {
                return null;
            }

            $prefix = ($origin['scheme'] ?? 'https').'://'.$origin['host'];
            if (isset($origin['port'])) {
                $prefix .= ':'.$origin['port'];
            }

            if (str_starts_with($value, '/')) {
                $value = $prefix.$value;
            } else {
                $path = \is_string($origin['path'] ?? null) ? $origin['path'] : '/';
                $slash = strrpos($path, '/');
                $directory = false === $slash ? '/' : substr($path, 0, $slash + 1);
                $value = $prefix.$directory.$value;
            }
        }

        if (mb_strlen($value) > 2048) {
            return null;
        }

        $scheme = parse_url($value, \PHP_URL_SCHEME);

        return \in_array($scheme, ['http', 'https'], true) ? $value : null;
    }
}
