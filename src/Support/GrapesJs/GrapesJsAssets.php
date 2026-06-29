<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

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

    /**
     * @return list<string>
     */
    public static function viteEntries(): array
    {
        return [
            self::editorScriptEntry(),
            self::editorStyleEntry(),
        ];
    }

    public static function isBuilt(): bool
    {
        if (class_exists(\Illuminate\Support\Facades\Vite::class) && \Illuminate\Support\Facades\Vite::isRunningHot()) {
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
        $script = self::editorScriptEntry();
        $style = self::editorStyleEntry();

        return "Add `{$script}` and `{$style}` to vite.config.js input, run `npm install grapesjs grapesjs-blocks-basic`, then `npm run build`.";
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
