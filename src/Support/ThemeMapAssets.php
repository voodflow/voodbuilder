<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Facades\File;

final class ThemeMapAssets
{
    public const SCRIPT_ID = 'voodbuilder-theme-map';

    public const STYLE_ID = 'voodbuilder-theme-map';

    public const PACKAGE = 'voodbuilder';

    public static function distPath(): string
    {
        return VoodbuilderPaths::packagePath().'/resources/dist/theme-map.js';
    }

    public static function distCssPath(): string
    {
        return VoodbuilderPaths::packagePath().'/resources/dist/theme-map.css';
    }

    public static function isBuilt(): bool
    {
        return is_file(self::distPath());
    }

    public static function register(): void
    {
        if (! self::isBuilt()) {
            return;
        }

        self::ensurePublished();

        $assets = [
            Js::make(self::SCRIPT_ID, self::distPath())->loadedOnRequest(),
        ];

        if (is_file(self::distCssPath())) {
            $assets[] = Css::make(self::STYLE_ID, self::distCssPath())->loadedOnRequest();
        }

        FilamentAsset::register($assets, self::PACKAGE);
    }

    public static function ensurePublished(): void
    {
        if (! self::isBuilt()) {
            return;
        }

        self::publishIfStale(self::distPath(), public_path('js/'.self::PACKAGE.'/'.self::SCRIPT_ID.'.js'));

        if (is_file(self::distCssPath())) {
            self::publishIfStale(
                self::distCssPath(),
                public_path('css/'.self::PACKAGE.'/'.self::STYLE_ID.'.css'),
            );
        }
    }

    protected static function publishIfStale(string $source, string $destination): void
    {
        if (! is_file($source)) {
            return;
        }

        File::ensureDirectoryExists(dirname($destination));

        if (! is_file($destination) || filemtime($source) > filemtime($destination)) {
            File::copy($source, $destination);
        }
    }

    public static function scriptSrc(): ?string
    {
        if (! self::isBuilt()) {
            return null;
        }

        self::ensurePublished();

        return self::versionedPublicUrl(
            public_path('js/'.self::PACKAGE.'/'.self::SCRIPT_ID.'.js'),
            FilamentAsset::getScriptSrc(self::SCRIPT_ID, self::PACKAGE),
        );
    }

    public static function styleHref(): ?string
    {
        if (! is_file(self::distCssPath())) {
            return null;
        }

        self::ensurePublished();

        return self::versionedPublicUrl(
            public_path('css/'.self::PACKAGE.'/'.self::STYLE_ID.'.css'),
            FilamentAsset::getStyleHref(self::STYLE_ID, self::PACKAGE),
        );
    }

    public static function mountJs(): string
    {
        $scriptSrc = self::scriptSrc();
        $styleHref = self::styleHref();

        if ($scriptSrc === null) {
            return '';
        }

        $encodedScript = json_encode($scriptSrc, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $encodedStyle = json_encode($styleHref, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        return <<<JS
            (function () {
                const scriptSrc = {$encodedScript};
                const styleHref = {$encodedStyle};
                const run = () => window.voodbuilderMountThemeMap?.();

                if (styleHref && ! document.querySelector('link[data-voodbuilder-theme-map-style]')) {
                    const link = document.createElement('link');
                    link.rel = 'stylesheet';
                    link.href = styleHref;
                    link.dataset.voodbuilderThemeMapStyle = '1';
                    document.head.appendChild(link);
                }

                if (typeof window.voodbuilderMountThemeMap === 'function') {
                    run();
                    return;
                }

                let script = document.querySelector('script[data-voodbuilder-theme-map-script]');

                if (! script) {
                    script = document.createElement('script');
                    script.src = scriptSrc;
                    script.dataset.voodbuilderThemeMapScript = '1';
                    script.addEventListener('load', () => {
                        script.dataset.loaded = '1';
                        run();
                    }, { once: true });
                    document.head.appendChild(script);
                    return;
                }

                if (script.dataset.loaded === '1') {
                    run();
                    return;
                }

                script.addEventListener('load', run, { once: true });
            })();
        JS;
    }

    protected static function versionedPublicUrl(string $publicPath, string $fallback): string
    {
        if (! is_file($publicPath)) {
            return $fallback;
        }

        $base = strtok($fallback, '?') ?: $fallback;

        return $base.'?v='.filemtime($publicPath);
    }
}
