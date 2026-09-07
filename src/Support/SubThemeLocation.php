<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * Sub Theme Location.
 */
final readonly class SubThemeLocation
{
    public function __construct(
        public string $id,
        public string $origin,
        public string $cssPath,
        public string $themeRoot,
        public string $viewsRoot,
    ) {}

    public static function app(string $id): ?self
    {
        $cssPath = ThemeConvention::appCssPath($id);

        if (! is_file($cssPath)) {
            return null;
        }

        return new self(
            id: $id,
            origin: 'app',
            cssPath: $cssPath,
            themeRoot: dirname($cssPath),
            viewsRoot: ThemeConvention::appViewsPath($id),
        );
    }

    public static function package(string $id, string $packageThemeId): ?self
    {
        $cssPath = ThemeConvention::packageCssPath($packageThemeId);

        if (! is_file($cssPath)) {
            return null;
        }

        $packageRoot = VoodbuilderPaths::packagePath();

        return new self(
            id: $id,
            origin: 'package',
            cssPath: $cssPath,
            themeRoot: dirname($cssPath),
            viewsRoot: "{$packageRoot}/resources/views/themes/{$packageThemeId}",
        );
    }

    public function isApp(): bool
    {
        return $this->origin === 'app';
    }
}
