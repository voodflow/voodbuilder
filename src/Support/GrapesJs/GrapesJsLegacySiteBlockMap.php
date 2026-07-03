<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

/**
 * Maps deprecated GrapesJS site chrome block IDs to their replacements.
 */
final class GrapesJsLegacySiteBlockMap
{
    public static function resolve(string $blockId): ?string
    {
        return match ($blockId) {
            'site_header' => 'site_nav_simple',
            'site_footer' => 'site_footer_columns_simple',
            'site_footer_a' => 'site_footer_columns_mission',
            'site_footer_b' => 'site_footer_columns_brand_end',
            'site_footer_c', 'site_footer_e' => 'site_footer_columns_simple',
            'site_footer_d' => 'site_footer_social',
            default => null,
        };
    }
}
