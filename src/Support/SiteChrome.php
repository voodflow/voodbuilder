<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\SitePage;

final class SiteChrome
{
    public static function shouldHideNav(?SitePage $page = null, bool $grapesJsEditor = false): bool
    {
        if ($page?->shouldHideSiteNav()) {
            return true;
        }

        return self::themeChromeFlag($page, 'hide_site_nav');
    }

    public static function shouldHideFooter(?SitePage $page = null, bool $grapesJsEditor = false): bool
    {
        if ($grapesJsEditor) {
            return true;
        }

        if ($page?->shouldHideSiteFooter()) {
            return true;
        }

        return self::themeChromeFlag($page, 'hide_site_footer');
    }

    protected static function themeChromeFlag(?SitePage $page, string $flag): bool
    {
        if ($page === null || ! $page->isLandingLayout()) {
            return false;
        }

        $chrome = app(SubThemeRegistry::class)->chrome($page->resolvedSubTheme());

        return (bool) ($chrome[$flag] ?? false);
    }
}
