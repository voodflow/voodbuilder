<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

/**
 * Site-wide curly tags for plain text (footer copyright, taglines, …).
 *
 * Distinct from GrapesJS data-voodbuilder-bind model bindings — these are
 * always available and resolve at render time.
 *
 * Available tags:
 * - {current_year} → calendar year
 * - {brand_name} → settings brand (fallback site title / VoodBuilder)
 * - {site_name} → SEO site name or site title
 * - {site_url} → application URL
 */
final class GlobalTextTags
{
    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::values());
    }

    /**
     * @param  array<string, string|null>  $overrides
     * @return array<string, string>
     */
    public static function values(array $overrides = []): array
    {
        $brand = trim((string) ($overrides['brand_name'] ?? VoodbuilderSettings::brandName()));
        $siteName = trim((string) ($overrides['site_name'] ?? ''));

        if ($siteName === '') {
            $seo = trim((string) VoodbuilderSettings::get('seo_site_name', ''));
            $siteName = $seo !== '' ? $seo : VoodbuilderSettings::siteTitle();
        }

        if ($siteName === '' || strcasecmp($siteName, 'Laravel') === 0) {
            $siteName = $brand !== '' ? $brand : 'VoodBuilder';
        }

        $siteUrl = trim((string) ($overrides['site_url'] ?? ''));

        if ($siteUrl === '') {
            $siteUrl = rtrim((string) config('app.url', ''), '/');
        }

        return [
            'current_year' => (string) ($overrides['current_year'] ?? date('Y')),
            'brand_name' => $brand !== '' ? $brand : 'VoodBuilder',
            'site_name' => $siteName,
            'site_url' => $siteUrl,
        ];
    }

    /**
     * @param  array<string, string|null>  $overrides
     */
    public static function replace(string $text, array $overrides = []): string
    {
        if ($text === '' || ! str_contains($text, '{')) {
            return $text;
        }

        $replacements = [];

        foreach (self::values($overrides) as $key => $value) {
            $replacements['{'.$key.'}'] = $value;
        }

        return strtr($text, $replacements);
    }

    /**
     * Replace global tags anywhere in HTML (chrome / page markup).
     *
     * @param  array<string, string|null>  $overrides
     */
    public static function replaceInHtml(string $html, array $overrides = []): string
    {
        return self::replace($html, $overrides);
    }
}
