<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Composes chrome layout + page content for the GrapesJS page editor preview.
 */
final class ChromeLayoutEditorPreview
{
    public static function shellSubTheme(?SitePage $page = null, ?ChromeLayout $layout = null): string
    {
        if ($page !== null) {
            return ChromeLayoutSubThemeResolver::forSitePage($page);
        }

        if ($layout !== null) {
            return ChromeLayoutSubThemeResolver::forChromeLayout($layout);
        }

        return ChromeLayoutSubThemeResolver::forPagesChannel();
    }

    public static function unwrapShellPreview(string $html): string
    {
        $html = trim($html);

        if ($html === '' || ! str_contains($html, 'data-voodbuilder-chrome-shell')) {
            return $html;
        }

        if (preg_match('/^<div[^>]*data-voodbuilder-chrome-shell[^>]*>(.*)<\/div>\s*$/is', $html, $matches) === 1) {
            return trim($matches[1]);
        }

        return $html;
    }

    public static function wrapShellPreview(string $html, ?string $subTheme = null): string
    {
        if ($html === '') {
            return '';
        }

        $subTheme = SubThemeResolver::normalize($subTheme ?? self::shellSubTheme());

        return '<div data-voodbuilder-chrome-shell data-voodbuilder-sub-theme="'
            .e($subTheme)
            .'">'
            .$html
            .'</div>';
    }

    /**
     * @return array{html: string, css: string}|null
     */
    public static function composeForPage(SitePage $page, string $pageHtml, string $pageCss): ?array
    {
        $layout = ChromeLayoutManagedContent::chromeLayoutForSitePage($page);

        if ($layout === null) {
            return null;
        }

        return self::compose($layout, $pageHtml, $pageCss, self::shellSubTheme($page));
    }

    /**
     * @return array{html: string, css: string}
     */
    public static function compose(ChromeLayout $layout, string $pageHtml, string $pageCss, ?string $subTheme = null): array
    {
        $resolvedSubTheme = SubThemeResolver::normalize($subTheme ?? self::shellSubTheme(layout: $layout));
        $rendered = app(ChromeLayoutRenderer::class)->render($layout, canvasPreview: true);
        $subThemeAttr = ' data-voodbuilder-sub-theme="'.e($resolvedSubTheme).'"';

        $slot = '<div'
            .' data-voodbuilder-content-slot="main"'
            .' data-voodbuilder-page-content="1"'
            .' class="voodbuilder-page-content-slot voodbuilder-chrome-content-slot"'
            .'>'
            .$pageHtml
            .'</div>';

        $before = $rendered['before'] !== ''
            ? '<div data-voodbuilder-chrome-shell-part="before" data-voodbuilder-chrome-shell-locked="1" data-voodbuilder-chrome-shell="1"'.$subThemeAttr.'>'.$rendered['before'].'</div>'
            : '';
        $after = $rendered['after'] !== ''
            ? '<div data-voodbuilder-chrome-shell-part="after" data-voodbuilder-chrome-shell-locked="1" data-voodbuilder-chrome-shell="1"'.$subThemeAttr.'>'.$rendered['after'].'</div>'
            : '';

        $chromeHtml = $before.$slot.$after;
        $chromeCss = ThemePalette::stripEmbeddedPaletteOverrides(trim($rendered['css']));
        $componentCss = ThemePalette::stripEmbeddedPaletteOverrides(
            app(\Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentCssRenderer::class)
                ->cssForHtml($rendered['before'].$rendered['after']),
        );
        // Page CSS can bake stale --vx-header-bg etc.; strip so admin/canvas palette wins.
        $safePageCss = ThemePalette::stripEmbeddedPaletteOverrides(trim($pageCss));

        return [
            'html' => $chromeHtml,
            'css' => trim(implode("\n\n", array_filter([
                $chromeCss,
                $componentCss,
                ThemePalette::criticalChromeShellCss($resolvedSubTheme),
                $safePageCss,
            ]))),
        ];
    }
}
