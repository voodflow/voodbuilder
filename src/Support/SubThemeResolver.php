<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

/**
 * Sub Theme Resolver.
 */
final class SubThemeResolver
{
    public const DEFAULT = 'docs';

    public const SITE = 'site';

    /** @var array<string, string> */
    private const LEGACY_IDS = [
        'default' => self::DEFAULT,
        'events' => self::SITE,
    ];

    public static function siteDefault(): string
    {
        $theme = self::resolveId((string) VoodbuilderSettings::get('sub_theme', self::SITE));

        return $theme ?? self::SITE;
    }

    public static function forPage(SitePage $page): string
    {
        return ChromeLayoutSubThemeResolver::forSitePage($page);
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
