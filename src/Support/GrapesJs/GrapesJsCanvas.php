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
            return Vite::asset($entry);
        } catch (\Throwable) {
            return null;
        }
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

        return <<<CSS
        body {
            margin: 0;
            background-color: var(--color-vp-bg, #ffffff);
            color: var(--color-vp-text-1, #3c3c43);
        }

        [data-gjs-type="wrapper"] {
            background-color: var(--color-vp-bg, #ffffff);
            box-sizing: border-box;
            min-height: 100vh;
            padding-top: 0.5rem;
            padding-bottom: 2.5rem;
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

        body.voodbuilder-gjs-block-dragging .voodbuilder-gjs-top-drop-spacer,
        .voodbuilder-gjs-top-drop-spacer.is-active {
            min-height: 4.5rem;
            height: 4.5rem;
            opacity: 1;
            pointer-events: auto;
            background: color-mix(in srgb, var(--color-vp-brand-1, #6366f1) 8%, transparent);
            border-radius: 0.375rem;
        }

        body.voodbuilder-gjs-block-dragging .voodbuilder-gjs-top-drop-spacer::after,
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

        * ::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.1) }
        * ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.2) }
        * ::-webkit-scrollbar { width: 10px }
        {$paletteCss}
        {$tabsCss}
        {$formsCss}
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
