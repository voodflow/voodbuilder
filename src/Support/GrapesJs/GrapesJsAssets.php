<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Illuminate\Support\Facades\Vite;
use Voodflow\Voodbuilder\Support\ConfigureNpmForVoodbuilder;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderSectionGrapesJsBlocks;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

final class GrapesJsAssets
{
    public static function editorScriptEntry(): string
    {
        return (string) config('voodbuilder.grapesjs.vite', VoodbuilderPaths::grapesJsViteEntry());
    }

    public static function editorStyleEntry(): string
    {
        return VoodbuilderPaths::grapesJsEditorCssEntry();
    }

    public static function blockPreviewStyleEntry(): string
    {
        return VoodbuilderPaths::grapesJsBlockPreviewCssEntry();
    }

    /**
     * @return list<string>
     */
    public static function viteEntries(): array
    {
        $entries = [
            self::editorScriptEntry(),
            self::editorStyleEntry(),
        ];

        if (VoodbuilderSectionGrapesJsBlocks::isAvailable()) {
            $entries[] = self::blockPreviewStyleEntry();
        }

        return $entries;
    }

    public static function isBuilt(): bool
    {
        if (class_exists(Vite::class) && Vite::isRunningHot()) {
            return true;
        }

        foreach (self::viteEntries() as $entry) {
            if (! self::hasBuiltAsset($entry)) {
                return false;
            }
        }

        return true;
    }

    public static function buildInstructions(): string
    {
        $missing = ConfigureNpmForVoodbuilder::missingFromPackageJson();

        if ($missing !== []) {
            return 'Missing npm packages: '.implode(', ', $missing).'. '
                .'Run `php artisan voodbuilder:install --skip-migrate --skip-seed --with-npm-build` '
                .'(or `php artisan voodbuilder:sync-npm-deps --install`, then `npm run build`).';
        }

        $script = self::editorScriptEntry();
        $style = self::editorStyleEntry();

        return "Add `{$script}` and `{$style}` to vite.config.js input, then run `npm install --legacy-peer-deps && npm run build`. "
            .'Or run `php artisan voodbuilder:install --skip-migrate --skip-seed --with-npm-build`.';
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
