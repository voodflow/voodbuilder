<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Models\VpressSettings;

final class SubThemeResolver
{
    public const DEFAULT = 'default';

    public static function siteDefault(): string
    {
        $theme = (string) VpressSettings::get('sub_theme', self::DEFAULT);

        return app(SubThemeRegistry::class)->exists($theme) ? $theme : self::DEFAULT;
    }

    public static function forPage(SitePage $page): string
    {
        if (filled($page->sub_theme)) {
            $theme = (string) $page->sub_theme;

            if (app(SubThemeRegistry::class)->exists($theme)) {
                return $theme;
            }
        }

        return static::siteDefault();
    }

    public static function normalize(?string $theme): string
    {
        if (filled($theme) && app(SubThemeRegistry::class)->exists($theme)) {
            return $theme;
        }

        return self::DEFAULT;
    }

    public static function forCurrentRoute(): string
    {
        $channel = app(ContentChannelRegistry::class)->matchesCurrentRequest();

        if ($channel !== null && filled($channel->subTheme())) {
            return self::normalize($channel->subTheme());
        }

        return self::siteDefault();
    }
}
