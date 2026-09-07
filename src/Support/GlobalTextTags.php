<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

/**
 * Site-wide curly tags for plain / rich text (page content, footer, chrome).
 *
 * Distinct from Editor data-voodbuilder-bind model bindings — these are
 * always available and resolve at PHP render time (no extra JS libraries).
 *
 * Available tags:
 * - {current_year} → calendar year
 * - {brand_name} → settings brand (fallback site title / VoodBuilder)
 * - {site_name} → SEO site name or site title
 * - {site_url} → application URL
 * - {logged_username} → authenticated user display name (empty string for guests)
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
            'logged_username' => self::resolveLoggedUsername($overrides),
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
     * Runs on the full markup string after bindings — covers Editor text
     * blocks and rich text equally (tokens live in the HTML).
     *
     * @param  array<string, string|null>  $overrides
     */
    public static function replaceInHtml(string $html, array $overrides = []): string
    {
        return self::replace($html, $overrides);
    }

    /**
     * Display name of the session user, or empty string when guest / missing.
     *
     * Prefers `name`, then `username`. Value is HTML-escaped for safe injection
     * into page/chrome markup. Pass `logged_username` in $overrides to force a value.
     *
     * @param  array<string, string|null>  $overrides
     */
    private static function resolveLoggedUsername(array $overrides): string
    {
        if (array_key_exists('logged_username', $overrides)) {
            return self::escapeText(trim((string) ($overrides['logged_username'] ?? '')));
        }

        $user = auth()->user();

        if ($user === null) {
            return '';
        }

        $name = trim((string) data_get($user, 'name', ''));

        if ($name === '') {
            $name = trim((string) data_get($user, 'username', ''));
        }

        return self::escapeText($name);
    }

    private static function escapeText(string $value): string
    {
        if ($value === '') {
            return '';
        }

        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}
