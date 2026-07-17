<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Illuminate\Support\Facades\Vite;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

final class GrapesJsCanvas
{
    /**
     * @return list<string>
     */
    public static function styleUrls(): array
    {
        $entries = config('voodbuilder.grapesjs.canvas_styles', VoodbuilderPaths::grapesJsCanvasStyleEntries());

        return collect($entries)
            ->map(static fn (string $entry): ?string => self::resolveViteAsset($entry))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Stylesheets required on published GrapesJS pages (section Tailwind utilities).
     *
     * @return list<string>
     */
    public static function publishedStyleUrls(): array
    {
        if (! VoodbuilderSectionGrapesJsBlocks::isAvailable()) {
            return [];
        }

        $utilities = self::resolveViteAsset(VoodbuilderSectionGrapesJsBlocks::utilitiesCssEntry());

        return $utilities ? [$utilities] : [];
    }

    protected static function resolveViteAsset(string $entry): ?string
    {
        if (str_starts_with($entry, 'http://') || str_starts_with($entry, 'https://')) {
            return $entry;
        }

        if (! class_exists(Vite::class) || ! Vite::isRunningHot() && ! self::hasBuiltAsset($entry)) {
            return null;
        }

        try {
            $url = Vite::asset($entry);
        } catch (\Throwable) {
            return null;
        }

        // GrapesJS canvas loads styles inside an iframe on the current host.
        // Absolute APP_URL assets break when the browser hits a different port
        // than APP_URL (e.g. admin on :8010 while APP_URL still points at :8006).
        if (Vite::isRunningHot()) {
            return $url;
        }

        return self::toRootRelativeAssetUrl($url) ?? $url;
    }

    /**
     * Prefer same-origin root-relative URLs so canvas CSS follows the request host/port.
     */
    public static function toRootRelativeAssetUrl(string $url): ?string
    {
        if (str_starts_with($url, '/')) {
            return $url;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path) || $path === '' || ! str_starts_with($path, '/')) {
            return null;
        }

        $query = parse_url($url, PHP_URL_QUERY);

        return is_string($query) && $query !== ''
            ? $path.'?'.$query
            : $path;
    }

    public static function pageBackgroundColor(string $subTheme): string
    {
        return match ($subTheme) {
            'site', 'events' => '#f4f5f7',
            default => '#ffffff',
        };
    }

    public static function frameStyle(string $subTheme): string
    {
        $paletteCss = ThemePalette::cssForCanvas($subTheme);
        $tabsCss = self::readPackageCanvasCss('tabs.css');
        $formsCss = self::readPackageCanvasCss('forms.css');
        $chromeLayoutCss = self::readPackageCanvasCss('chrome-layout-canvas.css');
        $chromeBlockUtilitiesCss = self::readPackageCanvasCss('chrome-block-utilities.css');

        return <<<CSS
        body {
            margin: 0;
            background-color: var(--color-vp-bg, #ffffff);
            color: var(--color-vp-text-1, #3c3c43);
            /* Match public theme tokens so max-w-* / containers resolve like the frontend. */
            --container-sm: 24rem;
            --container-md: 28rem;
            --container-lg: 32rem;
            --container-xl: 36rem;
        }

        [data-gjs-type="wrapper"] {
            background-color: var(--color-vp-bg, #ffffff);
            box-sizing: border-box;
            min-height: 100vh;
            /* No extra chrome padding — public pages do not pad the document wrapper. */
            padding-top: 0;
            padding-bottom: 0;
        }

        .voodbuilder-gjs-top-drop-spacer {
            box-sizing: border-box;
            height: 0;
            min-height: 0;
            margin: 0;
            padding: 0;
            border: 0;
            overflow: hidden;
            opacity: 0;
            pointer-events: none;
            transition: min-height 0.12s ease, opacity 0.12s ease, background-color 0.12s ease;
        }

        .voodbuilder-gjs-top-drop-spacer.is-active {
            min-height: 4.5rem;
            height: 4.5rem;
            opacity: 1;
            pointer-events: auto;
            background: color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 8%, transparent);
            border-radius: 0.375rem;
        }

        .voodbuilder-gjs-top-drop-spacer.is-active::after {
            content: '';
            display: block;
            width: 100%;
            height: 100%;
            border: 2px dashed color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 45%, transparent);
            border-radius: 0.375rem;
            box-sizing: border-box;
        }

        .voodbuilder-gjs-drag-chip {
            position: relative !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-sizing: border-box !important;
            width: auto !important;
            min-width: 7.5rem !important;
            max-width: 14rem !important;
            min-height: 2.5rem !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0.5rem 0.875rem !important;
            overflow: hidden !important;
            border-radius: 0.5rem !important;
            border: 2px solid color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 70%, transparent) !important;
            background: var(--color-vp-bg-elv, #fff) !important;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.16) !important;
            opacity: 0.96 !important;
            pointer-events: none !important;
            font-family: ui-sans-serif, system-ui, sans-serif !important;
            font-size: 0.8125rem !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
            color: var(--color-vp-text-1, #0f172a) !important;
            white-space: nowrap !important;
            text-overflow: ellipsis !important;
        }

        .voodbuilder-gjs-drag-chip > * {
            display: none !important;
        }

        .voodbuilder-gjs-drag-chip::after {
            content: attr(data-voodbuilder-drag-label);
            display: block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        html {
            scroll-padding-top: 0;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .voodbuilder-gjs-section > :is(.container, .voodbuilder-gjs-container),
        .voodbuilder-gjs-section :is(.container, .voodbuilder-gjs-container) {
            width: 100%;
            max-width: var(--width-vp-layout, 80rem);
            margin-inline: auto;
            box-sizing: border-box;
        }

        /* Match landing.css — section images must fill the column, not intrinsic SVG width.
         * Do not apply to all body imgs: canvas body is .VPRichPage--landing and would
         * override nav logo height utilities (h-8 / md:h-10). */
        .voodbuilder-gjs-section img {
            max-width: 100%;
            height: auto;
        }

        header[role='banner'] a img,
        .voodbuilder-mobile-nav__brand img {
            max-height: 2.5rem;
            width: auto;
            max-width: min(100%, 13.75rem);
            object-fit: contain;
        }

        footer.voodbuilder-gjs-footer :is(.container, .voodbuilder-gjs-container),
        footer.voodbuilder-gjs-dynamic :is(.container, .voodbuilder-gjs-container) {
            width: 100%;
            max-width: var(--width-vp-layout, 80rem);
            margin-inline: auto;
            padding-inline: 1.25rem;
            box-sizing: border-box;
        }

        .voodbuilder-mobile-nav {
            --voodbuilder-mobile-bg: var(--color-vp-bg-elv, #ffffff);
            --voodbuilder-mobile-bg-muted: var(--color-vp-bg-alt, #f6f6f7);
            --voodbuilder-mobile-text: var(--color-vp-text-1, #3c3c43);
            --voodbuilder-mobile-text-muted: var(--color-vp-text-2, #67676c);
            --voodbuilder-mobile-border: var(--color-vp-divider, #e2e2e3);
            --voodbuilder-mobile-accent: var(--color-vp-brand-1, #3451b2);
        }

        .voodbuilder-nav--canvas-preview [data-mobile-nav-toggle] {
            color: var(--vx-header-text, var(--color-vp-text-1, #3c3c43)) !important;
        }

        .voodbuilder-nav--canvas-preview [data-mobile-nav-toggle] svg {
            stroke: currentColor;
        }

        .voodbuilder-mobile-nav__tool svg,
        .voodbuilder-mobile-nav__close svg {
            stroke: currentColor;
            fill: none;
        }

        body[data-voodbuilder-gjs-device='mobilePortrait'] .voodbuilder-nav--canvas-preview [data-voodbuilder-desktop-nav],
        body[data-voodbuilder-gjs-device='mobilePortrait'] .voodbuilder-nav--canvas-preview [data-voodbuilder-desktop-chrome],
        body[data-voodbuilder-gjs-device='tablet'] .voodbuilder-nav--canvas-preview [data-voodbuilder-desktop-nav],
        body[data-voodbuilder-gjs-device='tablet'] .voodbuilder-nav--canvas-preview [data-voodbuilder-desktop-chrome] {
            display: none !important;
        }

        body[data-voodbuilder-gjs-device='mobilePortrait'] .voodbuilder-nav--canvas-preview [data-mobile-nav-toggle],
        body[data-voodbuilder-gjs-device='tablet'] .voodbuilder-nav--canvas-preview [data-mobile-nav-toggle] {
            display: inline-flex !important;
        }

        body[data-voodbuilder-gjs-device='desktop'] .voodbuilder-nav--canvas-preview [data-mobile-nav-toggle] {
            display: none !important;
        }

        body[data-voodbuilder-gjs-device='desktop'] .voodbuilder-mobile-nav.is-open,
        body[data-voodbuilder-gjs-device='tablet'] .voodbuilder-mobile-nav.is-open {
            pointer-events: none !important;
        }

        body[data-voodbuilder-gjs-device='desktop'] .voodbuilder-mobile-nav.is-open .voodbuilder-mobile-nav__panel,
        body[data-voodbuilder-gjs-device='tablet'] .voodbuilder-mobile-nav.is-open .voodbuilder-mobile-nav__panel {
            transform: translateX(100%) !important;
        }

        body[data-voodbuilder-gjs-device='desktop'] .voodbuilder-mobile-nav.is-open .voodbuilder-mobile-nav__overlay,
        body[data-voodbuilder-gjs-device='tablet'] .voodbuilder-mobile-nav.is-open .voodbuilder-mobile-nav__overlay {
            opacity: 0 !important;
        }

        .voodbuilder-mobile-nav {
            pointer-events: none;
        }

        .voodbuilder-mobile-nav.is-open {
            pointer-events: auto;
        }

        .voodbuilder-nav--canvas-preview [data-voodbuilder-chrome][data-voodbuilder-chrome-hidden] {
            display: none !important;
        }

        [data-voodbuilder-chrome][data-voodbuilder-chrome-hidden] {
            display: none !important;
        }

        .voodbuilder-nav--canvas-preview [data-voodbuilder-chrome]:not([data-voodbuilder-chrome-hidden]) {
            display: block !important;
        }

        [data-voodbuilder-gjs-site-header] .voodbuilder-nav-profile-menu__dropdown[hidden] {
            display: none !important;
        }

        [data-voodbuilder-chrome-shell-locked] .gjs-badge,
        [data-voodbuilder-gjs-site-header] .gjs-badge,
        [data-voodbuilder-block^='site_nav_'] .gjs-badge,
        [data-voodbuilder-block^='site_footer_'] .gjs-badge,
        button[data-mobile-nav-toggle] .gjs-badge {
            display: none !important;
        }

        [data-voodbuilder-gjs-site-header] header[role='banner'] .voodbuilder-header-icon-btn,
        [data-voodbuilder-block^='site_nav_'] header[role='banner'] .voodbuilder-header-icon-btn {
            color: var(--vx-header-text, var(--color-vp-text-2, #64748b)) !important;
            background: color-mix(in srgb, var(--vx-header-text, var(--color-vp-text-2, #64748b)) 10%, transparent) !important;
        }

        [data-voodbuilder-gjs-site-header] header[role='banner'] .voodbuilder-header-icon-btn svg,
        [data-voodbuilder-block^='site_nav_'] header[role='banner'] .voodbuilder-header-icon-btn svg {
            stroke: currentColor;
        }

        [data-voodbuilder-gjs-site-header] header[role='banner'] .voodbuilder-header-icon-btn:is(:hover, :focus-visible),
        [data-voodbuilder-block^='site_nav_'] header[role='banner'] .voodbuilder-header-icon-btn:is(:hover, :focus-visible) {
            color: var(--color-vp-brand-1) !important;
            background: color-mix(in srgb, var(--vx-header-text, var(--color-vp-text-1)) 16%, transparent) !important;
        }

        [data-voodbuilder-nav-dropdown-toggle] svg {
            color: inherit;
            stroke: currentColor;
        }

        svg[fill="none"] {
            fill: none !important;
        }

        svg[fill="none"] path:not([fill]),
        svg[fill="none"] circle:not([fill]),
        svg[fill="none"] rect:not([fill]),
        svg[fill="none"] line:not([fill]),
        svg[fill="none"] polyline:not([fill]),
        svg[fill="none"] polygon:not([fill]) {
            fill: none !important;
        }

        svg[fill="none"][stroke="currentColor"],
        svg[fill="none"] [stroke="currentColor"] {
            stroke: currentColor;
        }

        .gjs-selected [data-voodbuilder-gjs-site-header] .voodbuilder-header-icon-btn,
        [data-voodbuilder-gjs-site-header] .gjs-selected .voodbuilder-header-icon-btn,
        [data-voodbuilder-block^='site_nav_'] .gjs-selected .voodbuilder-header-icon-btn,
        .gjs-hovered [data-voodbuilder-gjs-site-header] .voodbuilder-header-icon-btn,
        [data-voodbuilder-gjs-site-header] .gjs-hovered .voodbuilder-header-icon-btn {
            color: var(--vx-header-text, var(--color-vp-text-2, #64748b)) !important;
        }

        body:not(.voodbuilder-canvas-ready) {
            visibility: hidden;
        }

        .voodbuilder-pasted-component div:has(> a[href].bg-primary):has(> a[href].bg-layer),
        .voodbuilder-pasted-component div:has(> a[href][class*="bg-primary"]):has(> a[href][class*="bg-layer"]),
        .voodbuilder-pasted-component div.flex.flex-col:has(> a[href].bg-primary),
        .voodbuilder-pasted-component div.flex.flex-col:has(> a[href][class*="bg-primary"]),
        .voodbuilder-pasted-component div.flex.flex-col:has(> a[href].bg-layer),
        .voodbuilder-pasted-component div.flex.flex-col:has(> a[href][class*="bg-layer"]) {
            display: inline-flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.75rem;
            width: auto;
            max-width: 100%;
        }

        * ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }

        * ::-webkit-scrollbar-track {
            background: transparent;
        }

        * ::-webkit-scrollbar-thumb {
            background-color: color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 38%, transparent);
            border: 2px solid transparent;
            border-radius: 9999px;
            background-clip: content-box;
        }

        * ::-webkit-scrollbar-thumb:hover {
            background-color: color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 58%, transparent);
        }

        * {
            scrollbar-width: thin;
            scrollbar-color: color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 45%, transparent) transparent;
        }
        body.voodbuilder-gjs-block-dragging [data-voodbuilder-section-block] > * {
            pointer-events: none !important;
        }

        body.voodbuilder-gjs-block-dragging [data-voodbuilder-section-block] {
            outline: 1px dashed color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 35%, transparent);
            outline-offset: -1px;
        }

        body.voodbuilder-gjs-block-dragging section[data-voodbuilder-section-block] + section[data-voodbuilder-section-block]::before {
            content: '';
            display: block;
            height: 2.75rem;
            margin: -0.375rem 0;
            border-radius: 0.375rem;
            background: color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 10%, transparent);
            border: 2px dashed color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 40%, transparent);
            pointer-events: none;
        }

        {$paletteCss}
        {$tabsCss}
        {$formsCss}
        {$chromeLayoutCss}
        {$chromeBlockUtilitiesCss}
        CSS;
    }

    protected static function readPackageCanvasCss(string $filename): string
    {
        $path = dirname(__DIR__, 3).'/resources/css/grapesjs/'.$filename;

        if (! is_readable($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }

    protected static function readTabsCanvasCss(): string
    {
        return self::readPackageCanvasCss('tabs.css');
    }

    protected static function hasBuiltAsset(string $entry): bool
    {
        $manifest = public_path('build/manifest.json');

        if (! is_file($manifest)) {
            return false;
        }

        $decoded = json_decode((string) file_get_contents($manifest), true);

        return is_array($decoded) && array_key_exists($entry, $decoded);
    }
}
