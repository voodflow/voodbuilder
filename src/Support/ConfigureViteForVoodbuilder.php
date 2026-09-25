<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;

/**
 * Patch the host app vite.config.js with VoodBuilder entries and editor alias.
 */
final class ConfigureViteForVoodbuilder
{
    private const LEGACY_THEME_PATH = 'packages/voodflow/voodbuilder/resources/css/theme.css';

    private const EDITOR_ALIAS = '@voodbuilder-editor';

    public static function apply(bool $force = false): bool
    {
        $viteConfigPath = base_path('vite.config.js');

        if (! is_file($viteConfigPath)) {
            return false;
        }

        $contents = File::get($viteConfigPath);
        $original = $contents;

        $contents = str_replace(self::LEGACY_THEME_PATH, VoodbuilderPaths::themeCssRelativePath(), $contents);

        foreach (VoodbuilderPaths::viteInputEntries() as $entry) {
            if (! str_contains($contents, $entry)) {
                $contents = self::appendInputEntry($contents, $entry);
            }
        }

        $contents = self::ensureEditorAlias($contents);

        if ($contents === $original) {
            return false;
        }

        File::put($viteConfigPath, $contents);

        return true;
    }

    private static function appendInputEntry(string $contents, string $entry): string
    {
        if (preg_match("/input:\s*\[(.*?)\]/s", $contents, $matches) !== 1) {
            return $contents;
        }

        $replacement = "input: [{$matches[1]}, '{$entry}']";

        return preg_replace("/input:\s*\[(.*?)\]/s", $replacement, $contents, 1) ?? $contents;
    }

    /**
     * Companions (Elements, …) import core editor modules via @voodbuilder-editor
     * so nested Anystack bundles (voodbuilder-developer/…) resolve correctly.
     */
    private static function ensureEditorAlias(string $contents): string
    {
        if (str_contains($contents, self::EDITOR_ALIAS)) {
            return $contents;
        }

        $editorRelative = VoodbuilderPaths::relativeToBasePath(
            VoodbuilderPaths::viteSourcePath() . '/resources/js/editor',
        );

        if (! str_contains($contents, "from 'node:path'") && ! str_contains($contents, 'from "node:path"')
            && ! str_contains($contents, "from 'path'") && ! str_contains($contents, 'from "path"')) {
            $contents = "import path from 'node:path';\n" . $contents;
        }

        if (! str_contains($contents, 'fileURLToPath')) {
            $contents = "import { fileURLToPath } from 'node:url';\n" . $contents;
        }

        if (! str_contains($contents, '__voodbuilderDirname')) {
            $contents = preg_replace(
                '/export default defineConfig\(/',
                "const __voodbuilderDirname = path.dirname(fileURLToPath(import.meta.url));\n\nexport default defineConfig(",
                $contents,
                1,
            ) ?? $contents;
        }

        $aliasBlock = <<<JS
    resolve: {
        alias: {
            '@voodbuilder-editor': path.resolve(__voodbuilderDirname, '{$editorRelative}'),
        },
    },
JS;

        if (preg_match('/export default defineConfig\(\{\s*/', $contents) === 1) {
            return preg_replace(
                '/export default defineConfig\(\{\s*/',
                "export default defineConfig({\n{$aliasBlock}\n",
                $contents,
                1,
            ) ?? $contents;
        }

        return $contents;
    }
}
