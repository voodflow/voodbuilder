<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\ChromeLayoutManagedContent;

final class SiteChrome
{
    public static function shouldHideNav(?SitePage $page = null, bool $grapesJsEditor = false): bool
    {
        if ($page !== null && ChromeLayoutManagedContent::sitePageUsesChromeShell($page)) {
            return true;
        }

        if ($grapesJsEditor) {
            return false;
        }

        if ($page?->shouldHideSiteNav()) {
            return true;
        }

        if ($page !== null && self::pageContainsSiteNavBlock($page)) {
            return true;
        }

        return self::themeChromeFlag($page, 'hide_site_nav');
    }

    public static function pageContainsSiteNavBlock(SitePage $page): bool
    {
        if (! $page->usesGrapesJsBuilder()) {
            return false;
        }

        $html = (string) ($page->builder_payload['html'] ?? '');

        if ($html === '') {
            return false;
        }

        return (bool) preg_match(
            '/data-voodbuilder-block="(?:site_header|site_nav_[^"]+|landing_navbar)"/',
            $html,
        );
    }

    public static function shouldHideFooter(?SitePage $page = null, bool $grapesJsEditor = false): bool
    {
        if ($page !== null && ChromeLayoutManagedContent::sitePageUsesChromeShell($page)) {
            return true;
        }

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
