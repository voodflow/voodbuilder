<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

/**
 * Tailblocks ship with dummyimage.com placeholders that often resolve to random stock photos.
 * Replace them with a neutral local placeholder so editors start from a clean slate.
 */
final class GrapesJsPlaceholderNormalizer
{
    public static function neutralImageDataUri(): string
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="500" viewBox="0 0 800 500">'
            .'<rect width="800" height="500" fill="#e2e8f0"/>'
            .'<text x="400" y="250" text-anchor="middle" dominant-baseline="middle" fill="#94a3b8" font-family="system-ui,sans-serif" font-size="18">Image placeholder</text>'
            .'</svg>';

        return 'data:image/svg+xml,'.rawurlencode($svg);
    }

    public static function normalizeHtml(string $html): string
    {
        if ($html === '' || ! str_contains($html, '://')) {
            return $html;
        }

        $placeholder = self::neutralImageDataUri();
        // Keep real CDN images (e.g. Unsplash in Preline demos); only neutralize known placeholder hosts.
        $hostPattern = '(?:dummyimage|placehold|placekitten|placeimg|picsum)\.[^"\')\s]+';

        $html = preg_replace_callback(
            '#\bsrc=(["\'])(https?://'.$hostPattern.')\1#i',
            static fn (array $matches): string => 'src='.$matches[1].$placeholder.$matches[1],
            $html,
        ) ?? $html;

        $html = preg_replace(
            '#\bbackground-image\s*:\s*url\((["\']?)(https?://'.$hostPattern.')\1\)\s*;?#i',
            'background-image: url('.$placeholder.');',
            $html,
        ) ?? $html;

        return self::normalizeDataImageSrc($html);
    }

    /**
     * Chrome rejects data:image/svg+xml URIs that contain literal spaces.
     */
    public static function normalizeDataImageSrc(string $html): string
    {
        if ($html === '' || ! str_contains($html, 'data:image/svg+xml')) {
            return $html;
        }

        $normalized = preg_replace_callback(
            '#\bsrc=(["\'])(data:image/svg\+xml,[^"\']+)\1#i',
            static function (array $matches): string {
                $quote = $matches[1];
                $uri = preg_replace('/ /', '%20', $matches[2]) ?? $matches[2];

                return 'src='.$quote.$uri.$quote;
            },
            $html,
        );

        return is_string($normalized) ? $normalized : $html;
    }
}
