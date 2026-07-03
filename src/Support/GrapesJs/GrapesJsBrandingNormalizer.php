<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

/**
 * Replaces third-party section-catalog branding when building section-blocks.json.
 * Not used at page render or editor runtime — run voodbuilder:build-sections after source changes.
 */
final class GrapesJsBrandingNormalizer
{
    public const BRAND_NAME = 'VoodBuilder';

    public static function normalizeHtml(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $html = str_ireplace(['Tailblocks', 'tailblocks'], self::BRAND_NAME, $html);
        $html = preg_replace('/©\s*\d{4}\s*'.preg_quote(self::BRAND_NAME, '/').'/i', '© '.date('Y').' '.self::BRAND_NAME, $html) ?? $html;
        $html = str_replace('@knyttneve', '@voodbuilder', $html);

        $mark = self::brandMarkHtml();
        $logoPath = 'M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5';

        // Match only legacy bare section-catalog logo SVGs (svg > path). Never reset customized brand marks.
        $html = preg_replace_callback(
            '/<svg\b([^>]*)\bviewBox\s*=\s*["\']0\s+0\s+24\s+24["\']([^>]*)>\s*'
            .'<path\b[^>]*\bd="'.preg_quote($logoPath, '/').'"[^>]*(?:\/>|>\s*<\/path>)\s*'
            .'<\/svg>/i',
            static function (array $matches) use ($mark): string {
                $attributes = $matches[1].$matches[2];

                if (self::isCustomizedBrandLogoSvg($attributes)) {
                    return $matches[0];
                }

                return $mark;
            },
            $html,
        ) ?? $html;

        return $html;
    }

    protected static function isCustomizedBrandLogoSvg(string $attributes): bool
    {
        if (stripos($attributes, 'voodbuilder-brand-mark') !== false) {
            return true;
        }

        return preg_match('/\bstyle\s*=\s*["\'][^"\']*(?:color|fill|stroke|stroke-width|opacity)\s*:/i', $attributes) === 1;
    }

    public static function brandMarkHtml(): string
    {
        return '<span class="voodbuilder-brand-mark inline-flex h-10 w-10 items-center justify-center rounded-full bg-vp-brand-1 p-2 text-white" aria-hidden="true">'
            .'<svg class="voodbuilder-brand-mark__glyph h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24">'
            .'<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>'
            .'</svg></span>';
    }
}
