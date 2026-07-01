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
            ->map(static function (string $entry): ?string {
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
            })
            ->filter()
            ->values()
            ->all();
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
            min-height: 100vh;
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
