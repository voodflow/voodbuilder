<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\SiteVisit;

/**
 * Normalize public URL paths for site-visit matching.
 */
final class SiteVisitPath
{
    public static function normalize(string $path): string
    {
        $trimmed = trim($path);

        if ($trimmed === '') {
            return '/';
        }

        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            $parsed = parse_url($trimmed, PHP_URL_PATH);
            $trimmed = is_string($parsed) ? $parsed : '/';
        }

        $normalized = '/'.trim($trimmed, '/');

        if ($normalized === '/') {
            return '/';
        }

        return rtrim($normalized, '/');
    }

    public static function fromUrl(string $url): string
    {
        if (str_starts_with($url, '/')) {
            return self::normalize((string) (parse_url($url, PHP_URL_PATH) ?: $url));
        }

        $path = parse_url($url, PHP_URL_PATH);

        return self::normalize(is_string($path) ? $path : '');
    }
}
