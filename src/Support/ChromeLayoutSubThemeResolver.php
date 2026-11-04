<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Contracts\PublicContentChannel;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Resolves the visual sub-theme for chrome layout shells (nav/footer).
 */
final class ChromeLayoutSubThemeResolver
{
    public static function forSitePage(SitePage $page): string
    {
        $raw = is_string($page->sub_theme) ? trim($page->sub_theme) : null;

        if ($raw === '') {
            $raw = null;
        }

        $theme = SubThemeResolver::resolveId($raw);

        if ($theme !== null) {
            return $theme;
        }

        return self::forPagesChannel();
    }

    public static function forChromeLayout(ChromeLayout $layout): string
    {
        $channels = $layout->assignedChannelIds();

        // Unassigned shells preview as the site-pages theme (same as live default),
        // not a hardcoded "site" id from config.
        if ($channels === [] || in_array('pages', $channels, true)) {
            return self::forPagesChannel();
        }

        $registry = app(ContentChannelRegistry::class);

        foreach ($channels as $channelId) {
            $theme = self::themeForChannelId($registry->get($channelId));

            if ($theme !== null) {
                return $theme;
            }
        }

        $configured = config('voodbuilder.chrome_layouts.editor_sub_theme');

        if (is_string($configured) && trim($configured) !== '') {
            return SubThemeResolver::normalize($configured);
        }

        return self::forPagesChannel();
    }

    public static function forPagesChannel(): string
    {
        // Site pages are bound by the site-wide `sub_theme` (Theme Studio → Pagine
        // del sito). A legacy `content_channel_sub_themes.pages` row must not shadow
        // it: the `pages` channel is routing/search/chrome only, so a stale override
        // made pages render with another skin's palette (e.g. white footer) while
        // Theme Studio still showed `sub_theme`.
        return SubThemeResolver::siteDefault();
    }

    private static function themeForChannelId(?PublicContentChannel $channel): ?string
    {
        if ($channel === null) {
            return null;
        }

        $theme = ContentChannelThemes::resolveForChannel($channel);

        return $theme !== null ? SubThemeResolver::normalize($theme) : null;
    }
}
