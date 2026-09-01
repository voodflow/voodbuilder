<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\Editor\EditorRichContentBlockAdapter;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterColumnsSimpleBlock;
use Voodflow\Voodbuilder\Support\Editor\SiteNavConfig;
use Voodflow\Voodbuilder\Support\Editor\SiteNavSimpleBlock;

/**
 * Chrome layout (header + footer) for the local Voodflow marketing site.
 */
final class MarketingSiteLayout
{
    public const SLUG = 'voodflow-marketing';

    public static function seed(): ChromeLayout
    {
        $navConfig = SiteNavConfig::normalize([
            'show_search' => false,
            'show_notifications' => false,
            'show_profile_menu' => false,
            'sticky_nav' => 'sticky',
        ]);

        $footerConfig = SiteFooterColumnsSimpleBlock::normalizeConfig([
            'show_newsletter' => false,
            'show_social' => false,
            'show_footer_menu' => false,
            'tagline' => 'Visual automation & content plugins for Laravel and Filament.',
        ]);

        $navHtml = EditorRichContentBlockAdapter::wrap(
            SiteNavSimpleBlock::getId(),
            $navConfig,
            EditorRichContentBlockAdapter::fallbackEditorHtml(SiteNavSimpleBlock::getLabel()),
        );

        $footerHtml = EditorRichContentBlockAdapter::wrap(
            SiteFooterColumnsSimpleBlock::getId(),
            $footerConfig,
            EditorRichContentBlockAdapter::fallbackEditorHtml(SiteFooterColumnsSimpleBlock::getLabel()),
        );

        $html = $navHtml
            .ChromeLayoutDefaults::contentSlotHtml()
            .$footerHtml;

        $layout = ChromeLayout::query()->updateOrCreate(
            ['slug' => self::SLUG],
            [
                'name' => 'Voodflow Marketing',
                'html' => $html,
                'css' => self::css(),
                'js' => '',
                'enabled' => true,
                'is_default' => false,
                'channel_ids' => ['pages'],
                'content_width' => 'full',
            ],
        );

        ChromeLayoutResolver::forgetCache();

        return $layout;
    }

    protected static function css(): string
    {
        return <<<'CSS'
[data-voodbuilder-chrome-shell] header,
[data-voodbuilder-chrome-shell] .voodbuilder-site-header-spacer {
    --vx-header-bg: #0b1220;
    --vx-header-text: #f8fafc;
    --vx-header-muted: #94a3b8;
}
[data-voodbuilder-chrome-shell] footer {
    background: #0b1220;
    color: #cbd5e1;
}
CSS;
    }
}
