<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Illuminate\Support\Facades\Vite;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

final class EditorCanvas
{
    /**
     * @return list<string>
     */
    public static function styleUrls(): array
    {
        $entries = config('voodbuilder.editor.canvas_styles', VoodbuilderPaths::editorCanvasStyleEntries());

        return collect($entries)
            ->map(static fn (string $entry): ?string => self::resolveViteAsset($entry))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Stylesheets required on published Editor pages.
     *
     * Intentionally empty: critical section chrome (hero media cover, media frames) lives in
     * `theme.css`, which the public site already loads. `section-utilities.css` stays canvas-only
     * (Tailwind JIT for block catalogs).
     *
     * @return list<string>
     */
    public static function publishedStyleUrls(): array
    {
        return [];
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

        // Editor canvas loads styles inside an iframe on the current host.
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

    public static function frameStyle(string $subTheme, bool $popupMode = false): string
    {
        $paletteCss = ThemePalette::cssForCanvas($subTheme);
        $tabsCss = self::readPackageCanvasCss('tabs.css');
        $formsCss = self::readPackageCanvasCss('forms.css');
        $chromeLayoutCss = self::readPackageCanvasCss('chrome-layout-canvas.css');
        $chromeBlockUtilitiesCss = self::readPackageCanvasCss('chrome-block-utilities.css');
        $wrapperMinHeight = $popupMode ? 'auto' : '100vh';
        $popupShellCss = $popupMode ? self::readPopupShellCanvasCss() : '';

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

        body.voodbuilder-popup-editor-canvas {
            --width-vp-layout: 100%;
            --width-vp-content: 100%;
        }

        [data-gjs-type="wrapper"] {
            background-color: var(--color-vp-bg, #ffffff);
            box-sizing: border-box;
            min-height: {$wrapperMinHeight};
            /* No extra chrome padding — public pages do not pad the document wrapper. */
            padding-top: 0;
            padding-bottom: 0;
        }

        .voodbuilder-editor-top-drop-spacer {
            box-sizing: border-box;
            height: 0 !important;
            min-height: 0 !important;
            max-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            outline: none !important;
            box-shadow: none !important;
            overflow: hidden !important;
            opacity: 0 !important;
            visibility: hidden;
            pointer-events: none !important;
            transition: min-height 0.12s ease, opacity 0.12s ease, background-color 0.12s ease, visibility 0s linear 0.12s;
        }

        .voodbuilder-editor-top-drop-spacer.gjs-hovered,
        .voodbuilder-editor-top-drop-spacer.gjs-selected {
            outline: none !important;
            box-shadow: none !important;
        }

        .voodbuilder-editor-top-drop-spacer.is-active {
            min-height: 4.5rem !important;
            height: 4.5rem !important;
            max-height: none !important;
            opacity: 1 !important;
            visibility: visible;
            pointer-events: auto !important;
            background: color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 8%, transparent);
            border-radius: 0.375rem;
            transition: min-height 0.12s ease, opacity 0.12s ease, background-color 0.12s ease;
        }

        .voodbuilder-editor-top-drop-spacer.is-active::after {
            content: '';
            display: block;
            width: 100%;
            height: 100%;
            border: 2px dashed color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 45%, transparent);
            border-radius: 0.375rem;
            box-sizing: border-box;
        }

        .voodbuilder-editor-inner-drop-slot {
            box-sizing: border-box;
            display: none !important;
            width: 100%;
            min-height: 0 !important;
            height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            background: transparent !important;
            outline: none !important;
            box-shadow: none !important;
            overflow: hidden !important;
            pointer-events: none !important;
        }

        body.voodbuilder-editor-block-dragging .voodbuilder-editor-inner-drop-slot,
        body.voodbuilder-editor-inner-drop-dragging .voodbuilder-editor-inner-drop-slot,
        body.voodbuilder-inner-drop-slots-visible .voodbuilder-editor-inner-drop-slot {
            display: block !important;
            min-height: 2px !important;
            height: 2px !important;
            margin: 0.35rem 0 !important;
            border: 0 !important;
            border-radius: 0;
            background: var(--color-vp-brand-1, #6366f1);
            box-shadow: none;
            pointer-events: auto !important;
            overflow: visible !important;
        }

        body.voodbuilder-editor-block-dragging .voodbuilder-editor-inner-drop-slot.gjs-hovered,
        body.voodbuilder-editor-block-dragging .voodbuilder-editor-inner-drop-slot.gjs-selected,
        body.voodbuilder-editor-inner-drop-dragging .voodbuilder-editor-inner-drop-slot.gjs-hovered,
        body.voodbuilder-editor-inner-drop-dragging .voodbuilder-editor-inner-drop-slot.gjs-selected,
        body.voodbuilder-inner-drop-slots-visible .voodbuilder-editor-inner-drop-slot.gjs-hovered,
        body.voodbuilder-inner-drop-slots-visible .voodbuilder-editor-inner-drop-slot.gjs-selected {
            min-height: 2px !important;
            height: 2px !important;
            background: var(--color-vp-brand-1, #6366f1) !important;
            box-shadow: none !important;
            outline: none !important;
        }

        .voodbuilder-editor-drag-chip {
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

        .voodbuilder-editor-drag-chip > * {
            display: none !important;
        }

        .voodbuilder-editor-drag-chip::after {
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

        /*
         * Author content parity with landing.css:
         * :where() defaults only — Tailwind utilities from the editor always win.
         */
        :where(
            .voodbuilder-editor-section > .container:not(.voodbuilder-hero-media),
            .voodbuilder-editor-section > .voodbuilder-editor-container:not(.voodbuilder-hero-media),
            .voodbuilder-editor-section .container:not(.voodbuilder-hero-media),
            .voodbuilder-editor-section .voodbuilder-editor-container:not(.voodbuilder-hero-media)
        ) {
            width: 100%;
            max-width: var(--width-vp-layout, 80rem);
            margin-inline: auto;
            padding-inline: 1.25rem;
            box-sizing: border-box;
        }

        /* Element content-width toolbar (full-width pages) — mirror landing.css.
         * Unlayered + attribute specificity must beat .voodbuilder-editor-container defaults. */
        .voodbuilder-editor-container[data-voodbuilder-content-width='normal'],
        .container[data-voodbuilder-content-width='normal'],
        [data-voodbuilder-role='content'][data-voodbuilder-content-width='normal'],
        [data-voodbuilder-content-width='normal'] {
            width: 100%;
            max-width: 80rem;
            margin-inline: auto;
            box-sizing: border-box;
        }

        .voodbuilder-editor-container[data-voodbuilder-content-width='custom'],
        .container[data-voodbuilder-content-width='custom'],
        [data-voodbuilder-role='content'][data-voodbuilder-content-width='custom'],
        [data-voodbuilder-content-width='custom'] {
            width: 100%;
            max-width: var(--voodbuilder-element-content-max, var(--voodbuilder-page-content-max, 80rem));
            margin-inline: auto;
            box-sizing: border-box;
        }

        .voodbuilder-editor-container[data-voodbuilder-content-width='full'],
        .container[data-voodbuilder-content-width='full'],
        [data-voodbuilder-role='content'][data-voodbuilder-content-width='full'],
        [data-voodbuilder-content-width='full'] {
            width: 100%;
            max-width: none;
            margin-inline: 0;
            box-sizing: border-box;
        }

        :where(
            [data-voodbuilder-layout='container'].vb-layout-row,
            [data-voodbuilder-layout='container'][data-vb-layout-preset],
            [data-voodbuilder-layout='container'].voodbuilder-editor-container
        ) {
            width: 100%;
            max-width: none;
            margin-inline: 0;
            box-sizing: border-box;
        }

        /* Match landing.css — section images must fill the column, not intrinsic SVG width.
         * Do not apply to all body imgs: canvas body is .VPRichPage--landing and would
         * override nav logo height utilities (h-8 / md:h-10). */
        :where(.voodbuilder-editor-section img:not(.voodbuilder-hero-media__img)) {
            max-width: 100%;
            height: auto;
        }

        .voodbuilder-editor-section .voodbuilder-hero-media {
            width: auto;
            max-width: none;
            margin-inline: 0;
        }

        header[role='banner'] a:not([data-voodbuilder-brand-logo-full='1']) img.vb-brand-logo,
        .voodbuilder-mobile-nav__brand img.vb-brand-logo {
            width: auto;
            max-width: min(100%, 16.25rem);
            object-fit: contain;
        }

        header[role='banner'] a[data-voodbuilder-brand-logo-full='1'] img.vb-brand-logo,
        [data-voodbuilder-footer-brand-link][data-voodbuilder-brand-logo-full='1'] img.vb-brand-logo {
            width: 100%;
            max-width: 100%;
            object-fit: contain;
        }

        :where(
            footer.voodbuilder-editor-footer > .container,
            footer.voodbuilder-editor-footer > .voodbuilder-editor-container,
            footer.voodbuilder-editor-dynamic > .container,
            footer.voodbuilder-editor-dynamic > .voodbuilder-editor-container
        ) {
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

        body[data-voodbuilder-editor-device='mobilePortrait'] .voodbuilder-nav--canvas-preview [data-voodbuilder-desktop-nav],
        body[data-voodbuilder-editor-device='mobilePortrait'] .voodbuilder-nav--canvas-preview [data-voodbuilder-desktop-chrome],
        body[data-voodbuilder-editor-device='tablet'] .voodbuilder-nav--canvas-preview [data-voodbuilder-desktop-nav],
        body[data-voodbuilder-editor-device='tablet'] .voodbuilder-nav--canvas-preview [data-voodbuilder-desktop-chrome] {
            display: none !important;
        }

        body[data-voodbuilder-editor-device='mobilePortrait'] .voodbuilder-nav--canvas-preview [data-mobile-nav-toggle],
        body[data-voodbuilder-editor-device='tablet'] .voodbuilder-nav--canvas-preview [data-mobile-nav-toggle] {
            display: inline-flex !important;
        }

        body[data-voodbuilder-editor-device='desktop'] .voodbuilder-nav--canvas-preview [data-mobile-nav-toggle] {
            display: none !important;
        }

        /* Desktop nav links: canvas iframe is often < md (768px), so Tailwind
         * `hidden md:flex` never activates. Device attr is the source of truth
         * (same pattern as brand logos below). */
        body[data-voodbuilder-editor-device='desktop'] .voodbuilder-nav--canvas-preview [data-voodbuilder-desktop-nav] {
            display: flex !important;
        }

        body[data-voodbuilder-editor-device='desktop'] .voodbuilder-nav--canvas-preview [data-voodbuilder-desktop-chrome]:not([data-voodbuilder-chrome-hidden]) {
            display: block !important;
        }

        /* Brand logos: ignore theme media queries in the canvas — device attr is source of truth.
         * Theme `@media (width < 60rem)` can still win on specificity and show mobile+desktop
         * together when the editor center column is narrower than 60rem.
         * Use html+body+attr+3 classes so canvas always beats theme (0,3,1). */
        .vb-brand-logo {
            display: none !important;
        }

        html body[data-voodbuilder-editor-device='desktop'] .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--light,
        html body[data-voodbuilder-editor-device='tablet'] .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--light,
        html body[data-voodbuilder-editor-device='desktop'] .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--dark,
        html body[data-voodbuilder-editor-device='tablet'] .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--dark,
        html body[data-voodbuilder-editor-device='desktop'] .vb-brand-logo.vb-brand-logo--mobile,
        html body[data-voodbuilder-editor-device='tablet'] .vb-brand-logo.vb-brand-logo--mobile {
            display: none !important;
        }

        html body[data-voodbuilder-editor-device='desktop'] .vb-brand-logo.vb-brand-logo--desktop.vb-brand-logo--light,
        html body[data-voodbuilder-editor-device='tablet'] .vb-brand-logo.vb-brand-logo--desktop.vb-brand-logo--light {
            display: block !important;
        }

        html body[data-voodbuilder-editor-device='mobilePortrait'] .vb-brand-logo.vb-brand-logo--desktop.vb-brand-logo--light,
        html body[data-voodbuilder-editor-device='mobilePortrait'] .vb-brand-logo.vb-brand-logo--desktop.vb-brand-logo--dark,
        html body[data-voodbuilder-editor-device='mobilePortrait'] .vb-brand-logo.vb-brand-logo--desktop {
            display: none !important;
        }

        html body[data-voodbuilder-editor-device='mobilePortrait'] .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--light {
            display: block !important;
        }

        html.dark body[data-voodbuilder-editor-device='desktop'] .vb-brand-logo.vb-brand-logo--desktop.vb-brand-logo--light,
        html.dark body[data-voodbuilder-editor-device='tablet'] .vb-brand-logo.vb-brand-logo--desktop.vb-brand-logo--light,
        html.dark body[data-voodbuilder-editor-device='mobilePortrait'] .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--light {
            display: none !important;
        }

        html.dark body[data-voodbuilder-editor-device='desktop'] .vb-brand-logo.vb-brand-logo--desktop.vb-brand-logo--dark,
        html.dark body[data-voodbuilder-editor-device='tablet'] .vb-brand-logo.vb-brand-logo--desktop.vb-brand-logo--dark {
            display: block !important;
        }

        html.dark body[data-voodbuilder-editor-device='mobilePortrait'] .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--dark {
            display: block !important;
        }

        html[data-vb-logo-mode='mobile'] header[role='banner'] .vb-brand-logo.vb-brand-logo--desktop,
        html[data-vb-logo-mode='mobile'] footer .vb-brand-logo.vb-brand-logo--desktop,
        body[data-vb-logo-mode='mobile'] header[role='banner'] .vb-brand-logo.vb-brand-logo--desktop,
        body[data-vb-logo-mode='mobile'] footer .vb-brand-logo.vb-brand-logo--desktop {
            display: none !important;
        }

        html:not(.dark)[data-vb-logo-mode='mobile'] header[role='banner'] .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--light,
        html:not(.dark)[data-vb-logo-mode='mobile'] footer .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--light,
        body:not(.dark)[data-vb-logo-mode='mobile'] header[role='banner'] .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--light,
        body:not(.dark)[data-vb-logo-mode='mobile'] footer .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--light {
            display: block !important;
        }

        html.dark[data-vb-logo-mode='mobile'] header[role='banner'] .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--dark,
        html.dark[data-vb-logo-mode='mobile'] footer .vb-brand-logo.vb-brand-logo--mobile.vb-brand-logo--dark {
            display: block !important;
        }

        /* Brand part toggles must beat device logo display rules (incl. placeholder). */
        [data-voodbuilder-chrome-part="logo"].hidden .vb-brand-logo,
        [data-voodbuilder-chrome-part="logo"].hidden [data-voodbuilder-brand-placeholder],
        [data-voodbuilder-chrome-part="logo"][data-voodbuilder-chrome-hidden] .vb-brand-logo,
        [data-voodbuilder-chrome-part="logo"][data-voodbuilder-chrome-hidden] [data-voodbuilder-brand-placeholder],
        [data-voodbuilder-chrome-part="logo"][hidden] .vb-brand-logo,
        [data-voodbuilder-chrome-part="logo"][hidden] [data-voodbuilder-brand-placeholder] {
            display: none !important;
        }

        /* Never show Editor forms "Button" label on chrome icon buttons. */
        .voodbuilder-header-icon-btn,
        button[data-mobile-nav-toggle],
        button[data-mobile-nav-close] {
            font-size: 0 !important;
            line-height: 0 !important;
        }

        .voodbuilder-header-icon-btn svg,
        button[data-mobile-nav-toggle] svg,
        button[data-mobile-nav-close] svg {
            display: block !important;
            width: 1.25rem !important;
            height: 1.25rem !important;
        }

        button[data-mobile-nav-toggle] svg.h-6 {
            width: 1.5rem !important;
            height: 1.5rem !important;
        }

        .voodbuilder-header-icon-btn svg.h-6 {
            width: 1.5rem !important;
            height: 1.5rem !important;
        }

        /* Canvas root: always full-bleed layout token.
         * Beat theme palette (`html[data-voodbuilder-sub-theme] { --width-vp-layout: … !important }`)
         * and never let frontend landing.css page-width rules shrink the iframe. */
        html.voodbuilder-canvas-ready,
        html.voodbuilder-canvas-ready[data-voodbuilder-sub-theme],
        html.voodbuilder-canvas-ready[data-voodbuilder-sub-theme]:not(.dark),
        html.dark.voodbuilder-canvas-ready[data-voodbuilder-sub-theme],
        html[data-voodbuilder-canvas-content-width],
        html[data-voodbuilder-canvas-content-width][data-voodbuilder-sub-theme],
        body.voodbuilder-canvas-ready,
        body[data-voodbuilder-canvas-content-width] {
            --width-vp-layout: 100% !important;
        }

        html.voodbuilder-canvas-ready [data-gjs-type='wrapper'],
        body.voodbuilder-canvas-ready [data-gjs-type='wrapper'] {
            width: 100% !important;
            max-width: none !important;
            margin-inline: 0 !important;
        }

        /* Page content width: constrain ONLY the page-content / layout content slot. */
        body[data-voodbuilder-canvas-content-width='standard'] [data-voodbuilder-page-content],
        body[data-voodbuilder-canvas-content-width='custom'] [data-voodbuilder-page-content],
        body[data-voodbuilder-canvas-content-width='contained'] [data-voodbuilder-page-content],
        body[data-voodbuilder-canvas-content-width='standard'] .voodbuilder-chrome-content-slot[data-voodbuilder-page-content],
        body[data-voodbuilder-canvas-content-width='custom'] .voodbuilder-chrome-content-slot[data-voodbuilder-page-content],
        body[data-voodbuilder-canvas-content-width='contained'] .voodbuilder-chrome-content-slot[data-voodbuilder-page-content],
        body[data-voodbuilder-canvas-content-width='standard'] [data-voodbuilder-content-slot]:not([data-voodbuilder-chrome-drop-zone]),
        body[data-voodbuilder-canvas-content-width='custom'] [data-voodbuilder-content-slot]:not([data-voodbuilder-chrome-drop-zone]),
        body[data-voodbuilder-canvas-content-width='contained'] [data-voodbuilder-content-slot]:not([data-voodbuilder-chrome-drop-zone]) {
            --width-vp-layout: var(--voodbuilder-page-content-max, 80rem) !important;
            width: 100% !important;
            max-width: var(--voodbuilder-page-content-max, 80rem) !important;
            margin-inline: auto !important;
            box-sizing: border-box;
        }

        /* Chrome match-content: constrain nav/footer shells (page + layout editors).
         * Workspace/frame stays full-bleed via --width-vp-layout: 100% on the canvas root. */
        body[data-voodbuilder-chrome-width='content']:is(
            [data-voodbuilder-canvas-content-width='standard'],
            [data-voodbuilder-canvas-content-width='custom'],
            [data-voodbuilder-canvas-content-width='contained']
        ) :is(
            [data-voodbuilder-chrome-shell],
            [data-voodbuilder-chrome-shell-part],
            [data-voodbuilder-chrome-drop-zone]
        ) {
            --width-vp-layout: var(--voodbuilder-page-content-max, 80rem);
            width: 100%;
            max-width: var(--voodbuilder-page-content-max, 80rem);
            margin-inline: auto;
            box-sizing: border-box;
        }

        /* chrome_width=full: bars stay edge-to-edge; restore layout measure for inner
         * nav/footer containers. Canvas root stays 100% so the workspace does not shrink.
         *
         * Needed especially in the layout editor: chrome-shell is unwrapped, so drop zones
         * cannot inherit ThemePalette's shell token. Without this, standard/custom page
         * content keeps --width-vp-layout at 100% on footers (sparse full-bleed columns)
         * while the published front uses the document content max (~80rem).
         *
         * Prefer --voodbuilder-page-content-max when set (standard/custom); otherwise
         * fall back to --voodbuilder-chrome-layout-max / 80rem (full page, mirrors landing.css). */
        body[data-voodbuilder-chrome-width='full'] :is(
            [data-voodbuilder-chrome-shell],
            [data-voodbuilder-chrome-shell-part],
            [data-voodbuilder-chrome-drop-zone]
        ) {
            --width-vp-layout: var(--voodbuilder-page-content-max, var(--voodbuilder-chrome-layout-max, 80rem));
        }

        body[data-voodbuilder-chrome-width='content']:is(
            [data-voodbuilder-canvas-content-width='standard'],
            [data-voodbuilder-canvas-content-width='custom'],
            [data-voodbuilder-canvas-content-width='contained']
        ) :is(
            [data-voodbuilder-chrome-shell],
            [data-voodbuilder-chrome-shell-part],
            [data-voodbuilder-chrome-drop-zone]
        ) header[role='banner'].fixed,
        body[data-voodbuilder-chrome-width='content']:is(
            [data-voodbuilder-canvas-content-width='standard'],
            [data-voodbuilder-canvas-content-width='custom'],
            [data-voodbuilder-canvas-content-width='contained']
        ) :is(
            [data-voodbuilder-chrome-shell],
            [data-voodbuilder-chrome-shell-part],
            [data-voodbuilder-chrome-drop-zone]
        ) header[role='banner'].sticky {
            left: max(0px, calc(50% - (var(--voodbuilder-page-content-max, 80rem) / 2))) !important;
            right: max(0px, calc(50% - (var(--voodbuilder-page-content-max, 80rem) / 2))) !important;
            width: auto !important;
            max-width: none !important;
        }

        /* Cancel 100vw hero breakout inside a constrained content column. */
        body[data-voodbuilder-canvas-content-width='standard'] [data-voodbuilder-page-content] :is(.voodbuilder-editor-hero, .voodbuilder-editor-cta, .vp-landing-hero),
        body[data-voodbuilder-canvas-content-width='custom'] [data-voodbuilder-page-content] :is(.voodbuilder-editor-hero, .voodbuilder-editor-cta, .vp-landing-hero),
        body[data-voodbuilder-canvas-content-width='contained'] [data-voodbuilder-page-content] :is(.voodbuilder-editor-hero, .voodbuilder-editor-cta, .vp-landing-hero),
        body[data-voodbuilder-canvas-content-width='standard'] [data-voodbuilder-content-slot] :is(.voodbuilder-editor-hero, .voodbuilder-editor-cta, .vp-landing-hero),
        body[data-voodbuilder-canvas-content-width='custom'] [data-voodbuilder-content-slot] :is(.voodbuilder-editor-hero, .voodbuilder-editor-cta, .vp-landing-hero),
        body[data-voodbuilder-canvas-content-width='contained'] [data-voodbuilder-content-slot] :is(.voodbuilder-editor-hero, .voodbuilder-editor-cta, .vp-landing-hero) {
            width: 100% !important;
            max-width: 100% !important;
            margin-inline: 0 !important;
        }

        body[data-voodbuilder-editor-device='desktop'] .voodbuilder-mobile-nav.is-open,
        body[data-voodbuilder-editor-device='tablet'] .voodbuilder-mobile-nav.is-open {
            pointer-events: none !important;
        }

        body[data-voodbuilder-editor-device='desktop'] .voodbuilder-mobile-nav.is-open .voodbuilder-mobile-nav__panel,
        body[data-voodbuilder-editor-device='tablet'] .voodbuilder-mobile-nav.is-open .voodbuilder-mobile-nav__panel {
            transform: translateX(100%) !important;
        }

        body[data-voodbuilder-editor-device='desktop'] .voodbuilder-mobile-nav.is-open .voodbuilder-mobile-nav__overlay,
        body[data-voodbuilder-editor-device='tablet'] .voodbuilder-mobile-nav.is-open .voodbuilder-mobile-nav__overlay {
            opacity: 0 !important;
        }

        .voodbuilder-mobile-nav {
            pointer-events: none;
        }

        .voodbuilder-mobile-nav.is-open,
        html.voodbuilder-mobile-nav-open .voodbuilder-mobile-nav,
        body.voodbuilder-mobile-nav-open .voodbuilder-mobile-nav {
            pointer-events: auto !important;
        }

        html.voodbuilder-mobile-nav-open .voodbuilder-mobile-nav__panel,
        body.voodbuilder-mobile-nav-open .voodbuilder-mobile-nav__panel,
        .voodbuilder-mobile-nav.is-open .voodbuilder-mobile-nav__panel {
            transform: translateX(0) !important;
        }

        html.voodbuilder-mobile-nav-open .voodbuilder-mobile-nav__overlay,
        body.voodbuilder-mobile-nav-open .voodbuilder-mobile-nav__overlay,
        .voodbuilder-mobile-nav.is-open .voodbuilder-mobile-nav__overlay {
            opacity: 1 !important;
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

        [data-voodbuilder-editor-site-header] .voodbuilder-nav-profile-menu__dropdown[hidden] {
            display: none !important;
        }

        /* Mirror theme.css: hide redundant Blade strip; page editor drops header border. */
        [data-voodbuilder-header-divider] {
            display: none !important;
        }

        body[data-voodbuilder-editor-scope='page'] header[role='banner'],
        html[data-voodbuilder-editor-scope='page'] header[role='banner'] {
            border-bottom: 0 !important;
            box-shadow: none !important;
        }

        [data-voodbuilder-chrome-shell-locked] .gjs-badge,
        [data-voodbuilder-editor-site-header] .gjs-badge,
        [data-voodbuilder-block^='site_nav_'] .gjs-badge,
        [data-voodbuilder-block^='site_footer_'] .gjs-badge,
        button[data-mobile-nav-toggle] .gjs-badge {
            display: none !important;
        }

        [data-voodbuilder-editor-site-header] header[role='banner'] .voodbuilder-header-icon-btn,
        [data-voodbuilder-block^='site_nav_'] header[role='banner'] .voodbuilder-header-icon-btn {
            color: var(--vx-header-text, var(--color-vp-text-2, #64748b)) !important;
            background: color-mix(in srgb, var(--vx-header-text, var(--color-vp-text-2, #64748b)) 10%, transparent) !important;
        }

        [data-voodbuilder-editor-site-header] header[role='banner'] .voodbuilder-header-icon-btn svg,
        [data-voodbuilder-block^='site_nav_'] header[role='banner'] .voodbuilder-header-icon-btn svg {
            stroke: currentColor;
        }

        [data-voodbuilder-editor-site-header] header[role='banner'] .voodbuilder-header-icon-btn:is(:hover, :focus-visible),
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

        .gjs-selected [data-voodbuilder-editor-site-header] .voodbuilder-header-icon-btn,
        [data-voodbuilder-editor-site-header] .gjs-selected .voodbuilder-header-icon-btn,
        [data-voodbuilder-block^='site_nav_'] .gjs-selected .voodbuilder-header-icon-btn,
        .gjs-hovered [data-voodbuilder-editor-site-header] .voodbuilder-header-icon-btn,
        [data-voodbuilder-editor-site-header] .gjs-hovered .voodbuilder-header-icon-btn {
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
        body.voodbuilder-editor-block-dragging [data-voodbuilder-section-block] > * {
            pointer-events: none !important;
        }

        body.voodbuilder-editor-block-dragging [data-voodbuilder-section-block] {
            outline: 1px dashed color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 35%, transparent);
            outline-offset: -1px;
        }

        body.voodbuilder-editor-block-dragging .gjs-placeholder,
        body.voodbuilder-editor-inner-drop-dragging .gjs-placeholder,
        body.voodbuilder-editor-block-dragging .gjs-com-placeholder,
        body.voodbuilder-editor-inner-drop-dragging .gjs-com-placeholder {
            background: var(--color-vp-brand-1, #6366f1) !important;
            border: 0 !important;
            border-radius: 0 !important;
            outline: none !important;
            box-shadow: none !important;
        }

        body.voodbuilder-editor-block-dragging .gjs-placeholder.horizontal,
        body.voodbuilder-editor-inner-drop-dragging .gjs-placeholder.horizontal,
        body.voodbuilder-editor-block-dragging .gjs-com-placeholder.horizontal,
        body.voodbuilder-editor-inner-drop-dragging .gjs-com-placeholder.horizontal {
            height: 2px !important;
            min-height: 2px !important;
            max-height: 2px !important;
            margin-top: -1px !important;
        }

        body.voodbuilder-editor-block-dragging .gjs-placeholder.vertical,
        body.voodbuilder-editor-inner-drop-dragging .gjs-placeholder.vertical,
        body.voodbuilder-editor-block-dragging .gjs-com-placeholder.vertical,
        body.voodbuilder-editor-inner-drop-dragging .gjs-com-placeholder.vertical {
            width: 2px !important;
            min-width: 2px !important;
            max-width: 2px !important;
            margin-left: -1px !important;
        }

        body.voodbuilder-editor-block-dragging .gjs-placeholder-int,
        body.voodbuilder-editor-inner-drop-dragging .gjs-placeholder-int,
        body.voodbuilder-editor-block-dragging .gjs-com-placeholder-int,
        body.voodbuilder-editor-inner-drop-dragging .gjs-com-placeholder-int {
            display: none !important;
        }

        body.voodbuilder-editor-block-dragging .gjs-highlighter,
        body.voodbuilder-editor-inner-drop-dragging .gjs-highlighter {
            outline-color: transparent !important;
            opacity: 0 !important;
        }

        /* Decorative plasma overlays use pointer-events-none; keep animated children
           selectable/highlightable in the editor canvas (layers + tools). */
        [aria-hidden="true"].pointer-events-none > [class*="animate-"],
        [aria-hidden="true"].pointer-events-none > [class*="blur-"],
        .pointer-events-none[aria-hidden="true"] > * {
            pointer-events: auto !important;
        }

        /* Section-level page-content drops: show the real Grapes placeholder as a dashed
         * rectangle (same look as the old decorative gaps). Never paint fake ::before zones —
         * those looked droppable but ignored release. */
        body.voodbuilder-editor-block-dragging.voodbuilder-editor-section-gap-drop .gjs-placeholder.horizontal,
        body.voodbuilder-editor-block-dragging.voodbuilder-editor-section-gap-drop .gjs-com-placeholder.horizontal,
        body.voodbuilder-editor-inner-drop-dragging.voodbuilder-editor-section-gap-drop .gjs-placeholder.horizontal,
        body.voodbuilder-editor-inner-drop-dragging.voodbuilder-editor-section-gap-drop .gjs-com-placeholder.horizontal {
            height: 2.5rem !important;
            min-height: 2.5rem !important;
            max-height: none !important;
            margin: 0.25rem 0 !important;
            border-radius: 0.375rem !important;
            background: color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 12%, transparent) !important;
            border: 2px dashed color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 50%, transparent) !important;
            box-shadow: none !important;
            outline: none !important;
        }

        /*
         * Before the first page-content block (sibling spacer under chrome nav).
         * Only during block drag — not when the "show dropzones" toggle is on
         * (that uses inner-drop-slot sentinels, and wrongly shared this cue before).
         */
        body.voodbuilder-editor-block-dragging [data-voodbuilder-page-content] > .voodbuilder-editor-top-drop-spacer {
            min-height: 2.5rem !important;
            height: 2.5rem !important;
            max-height: none !important;
            margin: 0.25rem 0 !important;
            opacity: 1 !important;
            visibility: visible;
            pointer-events: auto !important;
            background: color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 12%, transparent);
            border-radius: 0.375rem;
        }

        body.voodbuilder-editor-block-dragging [data-voodbuilder-page-content] > .voodbuilder-editor-top-drop-spacer::after {
            content: '';
            display: block;
            width: 100%;
            height: 100%;
            border: 2px dashed color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 50%, transparent);
            border-radius: 0.375rem;
            box-sizing: border-box;
        }

        {$paletteCss}
        {$tabsCss}
        {$formsCss}
        {$chromeLayoutCss}
        {$chromeBlockUtilitiesCss}
        {$popupShellCss}
        CSS;
    }

    protected static function readPackageCanvasCss(string $filename): string
    {
        $path = dirname(__DIR__, 3).'/resources/css/editor/'.$filename;

        if (! is_readable($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }

    /**
     * Popup canvas chrome CSS ships with voodbuilder-popups (sibling path package).
     */
    protected static function readPopupShellCanvasCss(): string
    {
        $candidates = [
            dirname(__DIR__, 4).'/voodbuilder-popups/resources/css/editor/popup-shell.css',
            dirname(__DIR__, 3).'/resources/css/editor/popup-shell.css',
        ];

        foreach ($candidates as $path) {
            if (is_readable($path)) {
                return (string) file_get_contents($path);
            }
        }

        return '';
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
