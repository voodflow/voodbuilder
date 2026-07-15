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

        if (in_array('pages', $channels, true)) {
            return self::forPagesChannel();
        }

        $configured = config('voodbuilder.chrome_layouts.editor_sub_theme');

        if (is_string($configured) && trim($configured) !== '') {
            return SubThemeResolver::normalize($configured);
        }

        $registry = app(ContentChannelRegistry::class);

        foreach ($channels as $channelId) {
            $theme = self::themeForChannelId($registry->get($channelId));

            if ($theme !== null) {
                return $theme;
            }
        }

        return self::forPagesChannel();
    }

    public static function forPagesChannel(): string
    {
        $theme = self::themeForChannelId(app(ContentChannelRegistry::class)->get('pages'));

        return $theme ?? SubThemeResolver::siteDefault();
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
