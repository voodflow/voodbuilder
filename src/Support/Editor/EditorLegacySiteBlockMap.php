<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Maps deprecated Editor site chrome block IDs to their replacements.
 */
final class EditorLegacySiteBlockMap
{
    public static function resolve(string $blockId): ?string
    {
        return match ($blockId) {
            // Retired nav variants collapsed onto simple (fullscreen is a live id).
            'site_header',
            'site_nav_centered_links',
            'site_nav_with_action',
            'site_nav_with_search',
            'site_nav_menu_left' => 'site_nav_simple',
            'site_footer' => 'site_footer_columns_simple',
            'site_footer_a', 'site_footer_columns_mission' => 'site_footer_columns_simple',
            'site_footer_b', 'site_footer_columns_brand_end' => 'site_footer_columns_simple',
            'site_footer_c', 'site_footer_e' => 'site_footer_columns_simple',
            'site_footer_columns_cta', 'site_footer_columns_newsletter_below' => 'site_footer_columns_simple',
            'site_footer_d' => 'site_footer_social',
            default => null,
        };
    }
}
