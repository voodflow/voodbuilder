<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Remap Grapes page-surface #id wallpaper rules to a fixed paint layer for public pages.
 *
 * Editor Save keeps wrapper #id rules so Style Size/Position/Repeat can hydrate
 * after reload. The wrapper id is not present in published HTML, so orphan
 * wallpaper #id rules must become public surface rules at publish time.
 *
 * Dark theme wallpapers use `html.dark #id` in the editor and become
 * `html.dark body.voodbuilder-page-surface::before` on publish.
 *
 * Prefer `body::before { position: fixed }` over `background-attachment: fixed` —
 * Chrome repaints/janks fixed attachments when on-page animations use transform.
 */
final class PageSurfaceCssPublish
{
    public const PAGE_SURFACE_CLASS = 'voodbuilder-page-surface';

    /**
     * Remap orphan #id wallpaper rules (and wrapper attribute selectors) to the
     * public page surface fixed layer, then keep chrome shells transparent.
     */
    public static function remapForPublic(string $css, string $html = ''): string
    {
        $source = trim($css);

        if ($source === '' || ! str_contains(strtolower($source), 'background-image')) {
            return $source;
        }

        // Grapes selectorsAdd "html.dark" serializes as `#id, html.dark` (comma list),
        // which paints the dark photo onto #id. Drop those before remap.
        $source = preg_replace(
            '/#[A-Za-z][\w-]*\s*,\s*html\.dark\s*\{[^{}]*\}/i',
            '',
            $source,
        ) ?? $source;

        $idsInHtml = self::idsPresentInHtml($html);
        $bodyTarget = self::bodyTarget();
        $darkBodyTarget = self::darkBodyTarget();

        // Dark wallpaper first so `#id` remap does not swallow `html.dark #id`.
        // Match the full rule so we evaluate THIS declaration block (not the first
        // html.dark #id elsewhere — layout-only companions used to block remap).
        $remapped = preg_replace_callback(
            '/html\.dark\s+#([A-Za-z][\w-]*)\s*\{([^{}]*)\}/',
            static function (array $match) use ($idsInHtml, $darkBodyTarget): string {
                $id = $match[1];
                $declarations = $match[2];

                if (isset($idsInHtml[$id])) {
                    return $match[0];
                }

                if (! self::declarationHasPageWallpaper($declarations)) {
                    return $match[0];
                }

                return $darkBodyTarget . ' {' . $declarations . '}';
            },
            $source,
        ) ?? $source;

        // Light orphan #id rules only. Never rewrite `#id` inside `html.dark #id` —
        // that produced `html.dark html, body… { dark-url }` and leaked the dark
        // photo onto the light ::before layer on the public front.
        $remapped = preg_replace_callback(
            '/(?<!html\.dark )#([A-Za-z][\w-]*)\s*\{([^{}]*)\}/',
            static function (array $match) use ($idsInHtml, $bodyTarget): string {
                $id = $match[1];
                $declarations = $match[2];

                if (isset($idsInHtml[$id])) {
                    return $match[0];
                }

                if (! self::declarationHasPageWallpaper($declarations)) {
                    return $match[0];
                }

                return $bodyTarget . ' {' . $declarations . '}';
            },
            $remapped,
        ) ?? $remapped;

        $remapped = preg_replace(
            '/\[data-gjs-type=["\']wrapper["\']\]/',
            $bodyTarget,
            $remapped,
        ) ?? $remapped;

        $promoted = self::promoteWallpaperToFixedLayer($remapped);

        return $promoted . self::transparentShellOverlay($promoted !== '' ? $promoted : $remapped);
    }

    /**
     * When a page wallpaper is present, keep chrome content shells transparent so
     * the body image shows through (parity with the editor canvas).
     */
    private static function transparentShellOverlay(string $css): string
    {
        if (! self::cssHasPageSurfaceWallpaper($css)) {
            return '';
        }

        return "\n"
            . "body.voodbuilder-page-surface .voodbuilder-site-shell,\n"
            . "body.voodbuilder-page-surface .voodbuilder-site-content,\n"
            . "body.voodbuilder-page-surface .voodbuilder-home-shell,\n"
            . "body.voodbuilder-page-surface .voodbuilder-landing-shell,\n"
            . "body.voodbuilder-page-surface .voodbuilder-events-shell {\n"
            . "  background-color: transparent !important;\n"
            . "}\n";
    }

    /**
     * True only for page-surface wallpaper (body/html remaps or fixed ::before),
     * not for section/block `#id { background-image }` decoration rules.
     */
    private static function cssHasPageSurfaceWallpaper(string $css): bool
    {
        $class = preg_quote(self::PAGE_SURFACE_CLASS, '/');

        if (preg_match(
            '/body\.' . $class . '::before\s*\{[^}]*background-image\s*:[^;]*url\s*\(/i',
            $css,
        ) === 1) {
            return true;
        }

        if (preg_match(
            '/html\.dark\s+body\.' . $class . '::before\s*\{[^}]*background-image\s*:[^;]*url\s*\(/i',
            $css,
        ) === 1) {
            return true;
        }

        return (bool) preg_match(
            '/(?:html\s*,\s*body|body\.' . $class . '|\.' . $class . ')\s*\{[^}]*background-image\s*:[^;]*url\s*\(/i',
            $css,
        );
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
        $darkBodyTarget = self::darkBodyTarget();
        $blocks = [];

        if (preg_match_all('/html\.dark\s+#([A-Za-z][\w-]*)\s*\{([^{}]*)\}/', $source, $darkMatches, PREG_SET_ORDER) !== false) {
            foreach ($darkMatches as $match) {
                $id = $match[1];
                $body = $match[2];

                if (isset($idsInHtml[$id]) || ! self::declarationHasPageWallpaper($body)) {
                    continue;
                }

                $blocks[] = $darkBodyTarget . ' {' . self::withWallpaperDefaults($body) . '}';
            }
        }

        if (preg_match_all('/#([A-Za-z][\w-]*)\s*\{([^{}]*)\}/', $source, $matches, PREG_SET_ORDER) !== false) {
            foreach ($matches as $match) {
                $id = $match[1];
                $body = $match[2];
                $offset = strpos($source, $match[0]);
                $before = $offset === false ? '' : strtolower(substr($source, max(0, $offset - 16), 16));

                if (str_contains($before, 'html.dark')) {
                    continue;
                }

                if (isset($idsInHtml[$id]) || ! self::declarationHasPageWallpaper($body)) {
                    continue;
                }

                $blocks[] = $bodyTarget . ' {' . self::withWallpaperDefaults($body) . '}';
            }
        }

        if (preg_match_all('/\[data-gjs-type=["\']wrapper["\']\]\s*\{([^{}]*)\}/', $source, $attrMatches, PREG_SET_ORDER) !== false) {
            foreach ($attrMatches as $match) {
                $body = $match[1];

                if (! self::declarationHasWallpaper($body)) {
                    continue;
                }

                $blocks[] = $bodyTarget . ' {' . self::withWallpaperDefaults($body) . '}';
            }
        }

        $overlay = trim(implode("\n", array_unique($blocks)));

        if ($overlay === '') {
            // Legacy sheets already remapped to body — promote + transparent shells.
            // Ignore section/block `#id` decoration photos (not page-surface targets).
            if (self::cssHasPageSurfaceWallpaper($source)) {
                $promoted = self::promoteWallpaperToFixedLayer($source);

                return trim($promoted . self::transparentShellOverlay($promoted !== '' ? $promoted : $source));
            }

            return '';
        }

        $promoted = self::promoteWallpaperToFixedLayer($overlay);

        return trim($promoted . self::transparentShellOverlay($promoted));
    }

    public static function bodyTarget(): string
    {
        $class = self::PAGE_SURFACE_CLASS;

        return "html, body, body.{$class}, .{$class}";
    }

    public static function darkBodyTarget(): string
    {
        return 'html.dark body.' . self::PAGE_SURFACE_CLASS;
    }

    public static function fixedLayerSelector(): string
    {
        return 'body.' . self::PAGE_SURFACE_CLASS . '::before';
    }

    public static function darkFixedLayerSelector(): string
    {
        return 'html.dark ' . self::fixedLayerSelector();
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

    private static function idRuleHasPageWallpaper(string $css, string $id): bool
    {
        $escaped = preg_quote($id, '/');

        if (preg_match_all('/#' . $escaped . '\s*\{([^{}]*)\}/', $css, $matches, PREG_SET_ORDER) === false) {
            return false;
        }

        foreach ($matches as $match) {
            $offset = strpos($css, $match[0]);
            $before = $offset === false ? '' : strtolower(substr($css, max(0, $offset - 16), 16));

            if (str_contains($before, 'html.dark')) {
                continue;
            }

            if (self::declarationHasPageWallpaper($match[1])) {
                return true;
            }
        }

        return false;
    }

    private static function darkIdRuleHasWallpaper(string $css, string $id): bool
    {
        $escaped = preg_quote($id, '/');

        if (preg_match('/html\.dark\s+#' . $escaped . '\s*\{([^{}]*)\}/', $css, $match) !== 1) {
            return false;
        }

        return self::declarationHasPageWallpaper($match[1]);
    }

    private static function declarationHasWallpaper(string $declarations): bool
    {
        return (bool) preg_match('/background-image\s*:[^;]*url\s*\(/i', $declarations);
    }

    /**
     * Page-surface wallpaper (wrapper #id) is saved with background-attachment:fixed,
     * or as a legacy image-only #id rule. Section/block decoration photos often share
     * size/position/repeat without fixed — those must not become the public page BG.
     */
    private static function declarationHasPageWallpaper(string $declarations): bool
    {
        if (! self::declarationHasWallpaper($declarations)) {
            return false;
        }

        if (preg_match('/background-attachment\s*:\s*fixed/i', $declarations) === 1) {
            return true;
        }

        // Legacy page wallpaper: image only (layout defaults applied at publish).
        $hasLayout = preg_match('/background-(?:size|position|repeat)\s*:/i', $declarations) === 1;

        return ! $hasLayout;
    }

    /**
     * Move wallpaper paint from html/body onto a position:fixed ::before layer.
     * Avoids Chrome scroll jank from background-attachment:fixed + transform animations.
     */
    private static function promoteWallpaperToFixedLayer(string $css): string
    {
        $class = preg_quote(self::PAGE_SURFACE_CLASS, '/');
        $layerSelector = self::fixedLayerSelector();
        $darkLayerSelector = self::darkFixedLayerSelector();
        $layerBlocks = [];

        $rewritten = preg_replace_callback(
            '/(html\.dark\s+body[^,{]*(?:,[^,{]*)*)\{([^{}]*)\}/i',
            static function (array $match) use (&$layerBlocks, $darkLayerSelector): string {
                if (! self::declarationHasWallpaper($match[2])) {
                    return $match[0];
                }

                $prepared = self::withWallpaperDefaults($match[2]);
                $layerDecls = self::wallpaperLayerDeclarations($prepared);
                $hostDecls = self::wallpaperHostDeclarations($prepared);

                if ($layerDecls !== '') {
                    $layerBlocks[] = $darkLayerSelector . ' {' . $layerDecls . '}';
                }

                return $match[1] . ' {' . $hostDecls . '}';
            },
            $css,
        ) ?? $css;

        $rewritten = preg_replace_callback(
            '/(html\s*,\s*body[^,{]*(?:,[^,{]*)*|body[^,{]*(?:,[^,{]*' . $class . '[^,{]*)*)\{([^{}]*)\}/i',
            static function (array $match) use (&$layerBlocks, $layerSelector, $class): string {
                $selector = trim($match[1]);
                $selectorLower = strtolower($selector);

                if (str_starts_with($selectorLower, 'html.dark')) {
                    return $match[0];
                }

                // PCRE can match the `body.CLASS` suffix inside `html.dark body.CLASS {…}`.
                // Remapped light wallpaper always uses bodyTarget (`html, body, body.CLASS, …`).
                // A bare `body.CLASS` (no `html`) is that false substring — never promote it
                // or the dark photo leaks onto the light ::before and light mode stays dark.
                if (
                    ! str_contains($selectorLower, 'html')
                    && preg_match('/^body\.' . $class . '\b/i', $selector) === 1
                ) {
                    return $match[0];
                }

                if (! self::declarationHasWallpaper($match[2])) {
                    return $match[0];
                }

                $prepared = self::withWallpaperDefaults($match[2]);
                $layerDecls = self::wallpaperLayerDeclarations($prepared);
                $hostDecls = self::wallpaperHostDeclarations($prepared);

                if ($layerDecls !== '') {
                    $layerBlocks[] = $layerSelector . ' {' . $layerDecls . '}';
                }

                return $match[1] . ' {' . $hostDecls . '}';
            },
            $rewritten,
        ) ?? $rewritten;

        if ($layerBlocks === []) {
            return $rewritten;
        }

        return trim($rewritten . "\n" . implode("\n", array_unique($layerBlocks)));
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

        // Drop attachment:fixed — the fixed ::before layer replaces it.
        $next = preg_replace('/background-attachment\s*:[^;]+;?/i', '', $next) ?? $next;

        return trim(preg_replace('/;;+/', ';', preg_replace('/^;\s*/', '', $next) ?? $next) ?? $next);
    }

    private static function wallpaperLayerDeclarations(string $declarations): string
    {
        $parts = [
            'content:""',
            'position:fixed',
            'inset:0',
            'z-index:-1',
            'pointer-events:none',
        ];

        foreach (['background-image', 'background-size', 'background-position', 'background-repeat'] as $property) {
            if (preg_match('/' . preg_quote($property, '/') . '\s*:\s*([^;]+)/i', $declarations, $match) === 1) {
                $parts[] = $property . ':' . trim($match[1]);
            }
        }

        return implode('; ', $parts);
    }

    private static function wallpaperHostDeclarations(string $declarations): string
    {
        $host = $declarations;
        $host = preg_replace('/background-image\s*:[^;]+;?/i', '', $host) ?? $host;
        $host = preg_replace('/background-size\s*:[^;]+;?/i', '', $host) ?? $host;
        $host = preg_replace('/background-position\s*:[^;]+;?/i', '', $host) ?? $host;
        $host = preg_replace('/background-repeat\s*:[^;]+;?/i', '', $host) ?? $host;
        $host = preg_replace('/background-attachment\s*:[^;]+;?/i', '', $host) ?? $host;
        $host = trim(preg_replace('/;;+/', ';', preg_replace('/^;\s*;?/', '', $host) ?? $host) ?? $host);

        // Clear any inherited/image paint on the host so only ::before shows the photo.
        $clear = 'background-image: none';

        return $host === '' ? $clear : $host . '; ' . $clear;
    }
}
