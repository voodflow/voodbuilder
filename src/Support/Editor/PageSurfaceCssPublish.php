<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Remap Grapes page-surface #id wallpaper rules to html/body for public pages.
 *
 * Editor Save keeps wrapper #id rules so Style Size/Position/Repeat can hydrate
 * after reload. The wrapper id is not present in published HTML, so orphan
 * wallpaper #id rules must become body/html rules at publish time.
 */
final class PageSurfaceCssPublish
{
    public const PAGE_SURFACE_CLASS = 'voodbuilder-page-surface';

    /**
     * Remap orphan #id wallpaper rules (and wrapper attribute selectors) to the
     * public page surface targets, then fill missing cover/center/no-repeat/fixed.
     */
    public static function remapForPublic(string $css, string $html = ''): string
    {
        $source = trim($css);

        if ($source === '' || ! str_contains(strtolower($source), 'background-image')) {
            return $source;
        }

        $idsInHtml = self::idsPresentInHtml($html);
        $bodyTarget = self::bodyTarget();

        $remapped = preg_replace_callback(
            '/#([A-Za-z][\w-]*)(?=[\s,{.:#[])/',
            static function (array $match) use ($idsInHtml, $bodyTarget, $source): string {
                $id = $match[1];

                if (isset($idsInHtml[$id])) {
                    return $match[0];
                }

                // Only remap ids that paint a wallpaper (avoid rewriting unrelated orphans).
                if (! self::idRuleHasWallpaper($source, $id)) {
                    return $match[0];
                }

                return $bodyTarget;
            },
            $source,
        ) ?? $source;

        $remapped = preg_replace(
            '/\[data-gjs-type=["\']wrapper["\']\]/',
            $bodyTarget,
            $remapped,
        ) ?? $remapped;

        return self::ensureWallpaperLayout($remapped).self::transparentShellOverlay($remapped);
    }

    /**
     * When a page wallpaper is present, keep chrome content shells transparent so
     * the body image shows through (parity with the editor canvas).
     */
    private static function transparentShellOverlay(string $css): string
    {
        if (! self::cssHasWallpaper($css)) {
            return '';
        }

        return "\n"
            ."body.voodbuilder-page-surface .voodbuilder-site-shell,\n"
            ."body.voodbuilder-page-surface .voodbuilder-site-content,\n"
            ."body.voodbuilder-page-surface .voodbuilder-home-shell,\n"
            ."body.voodbuilder-page-surface .voodbuilder-landing-shell,\n"
            ."body.voodbuilder-page-surface .voodbuilder-events-shell {\n"
            ."  background-color: transparent !important;\n"
            ."}\n";
    }

    private static function cssHasWallpaper(string $css): bool
    {
        return (bool) preg_match('/background-image\s*:[^;]*url\s*\(/i', $css);
    }

    /**
     * When the full sheet is linked as an artifact, return only the remapped
     * orphan wallpaper rules so an inline companion style can paint the page.
     */
    public static function publicWallpaperOverlay(string $css, string $html = ''): string
    {
        $source = trim($css);

        if ($source === '' || ! str_contains(strtolower($source), 'background-image')) {
            return '';
        }

        $idsInHtml = self::idsPresentInHtml($html);
        $bodyTarget = self::bodyTarget();
        $blocks = [];

        if (preg_match_all('/#([A-Za-z][\w-]*)\s*\{([^{}]*)\}/', $source, $matches, PREG_SET_ORDER) !== false) {
            foreach ($matches as $match) {
                $id = $match[1];
                $body = $match[2];

                if (isset($idsInHtml[$id]) || ! self::declarationHasWallpaper($body)) {
                    continue;
                }

                $blocks[] = $bodyTarget.' {'.self::withWallpaperDefaults($body).'}';
            }
        }

        if (preg_match_all('/\[data-gjs-type=["\']wrapper["\']\]\s*\{([^{}]*)\}/', $source, $attrMatches, PREG_SET_ORDER) !== false) {
            foreach ($attrMatches as $match) {
                $body = $match[1];

                if (! self::declarationHasWallpaper($body)) {
                    continue;
                }

                $blocks[] = $bodyTarget.' {'.self::withWallpaperDefaults($body).'}';
            }
        }

        $overlay = trim(implode("\n", array_unique($blocks)));

        if ($overlay === '') {
            // Legacy sheets already remapped to body — still need transparent shells.
            if (self::cssHasWallpaper($source)) {
                return trim(self::transparentShellOverlay($source));
            }

            return '';
        }

        return trim($overlay.self::transparentShellOverlay($overlay));
    }

    public static function bodyTarget(): string
    {
        $class = self::PAGE_SURFACE_CLASS;

        return "html, body, body.{$class}, .{$class}";
    }

    /**
     * @return array<string, true>
     */
    private static function idsPresentInHtml(string $html): array
    {
        $ids = [];

        if ($html === '' || ! preg_match_all('/\bid\s*=\s*["\']([^"\']+)["\']/i', $html, $matches)) {
            return $ids;
        }

        foreach ($matches[1] as $id) {
            $ids[$id] = true;
        }

        return $ids;
    }

    private static function idRuleHasWallpaper(string $css, string $id): bool
    {
        $escaped = preg_quote($id, '/');

        if (preg_match('/#'.$escaped.'\s*\{([^{}]*)\}/', $css, $match) !== 1) {
            return false;
        }

        return self::declarationHasWallpaper($match[1]);
    }

    private static function declarationHasWallpaper(string $declarations): bool
    {
        return (bool) preg_match('/background-image\s*:[^;]*url\s*\(/i', $declarations);
    }

    private static function ensureWallpaperLayout(string $css): string
    {
        $class = preg_quote(self::PAGE_SURFACE_CLASS, '/');

        return preg_replace_callback(
            '/(html\s*,\s*body[^,{]*(?:,[^,{]*)*|body[^,{]*(?:,[^,{]*'.$class.'[^,{]*)*)\{([^{}]*)\}/i',
            static function (array $match): string {
                if (! self::declarationHasWallpaper($match[2])) {
                    return $match[0];
                }

                return $match[1].' {'.self::withWallpaperDefaults($match[2]).'}';
            },
            $css,
        ) ?? $css;
    }

    private static function withWallpaperDefaults(string $declarations): string
    {
        $next = $declarations;

        if (! preg_match('/background-size\s*:/i', $next)) {
            $next .= '; background-size: cover';
        }

        if (! preg_match('/background-position\s*:/i', $next)) {
            $next .= '; background-position: center';
        }

        if (! preg_match('/background-repeat\s*:/i', $next)) {
            $next .= '; background-repeat: no-repeat';
        }

        if (! preg_match('/background-attachment\s*:/i', $next)) {
            $next .= '; background-attachment: fixed';
        }

        return trim(preg_replace('/;;+/', ';', preg_replace('/^;\s*/', '', $next) ?? $next) ?? $next);
    }
}
