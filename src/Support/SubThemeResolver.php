<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Models\VpressSettings;

final class SubThemeResolver
{
    public const DEFAULT = 'docs';

    public const SITE = 'site';

    /** @var array<string, string> */
    private const LEGACY_IDS = [
        'default' => self::DEFAULT,
        'events' => self::SITE,
        'blog' => self::DEFAULT,
        'news' => self::DEFAULT,
    ];

    public static function siteDefault(): string
    {
        $theme = self::resolveId((string) VpressSettings::get('sub_theme', self::SITE));

        return $theme ?? self::SITE;
    }

    public static function forPage(SitePage $page): string
    {
        $raw = is_string($page->sub_theme) ? trim($page->sub_theme) : null;

        if ($raw === '') {
            $raw = null;
        }

        $theme = self::resolveId($raw);

        if ($theme !== null) {
            return $theme;
        }

        return self::siteDefault();
    }

    public static function normalize(?string $theme): string
    {
        return self::resolveId($theme) ?? self::DEFAULT;
    }

    public static function forCurrentRoute(): string
    {
        $channel = app(ContentChannelRegistry::class)->matchesCurrentRequest();

        if ($channel !== null) {
            $theme = ContentChannelThemes::resolveForChannel($channel);

            if ($theme !== null) {
                return self::normalize($theme);
            }
        }

        return self::siteDefault();
    }

    public static function resolveId(?string $theme): ?string
    {
        if (! filled($theme)) {
            return null;
        }

        $theme = (string) $theme;
        $theme = self::LEGACY_IDS[$theme] ?? $theme;

        return app(SubThemeRegistry::class)->exists($theme) ? $theme : null;
    }
}
