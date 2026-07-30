<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;

/**
 * Sync Theme Stylesheet Imports.
 */
final class SyncThemeStylesheetImports
{
    /**
     * Drop @import lines that point to missing files.
     * App sub-themes are Vite entries (EditorAssets), not bundled into theme.css.
     */
    public static function sync(): bool
    {
        $bundlePath = VoodbuilderPaths::themeCssAbsolutePath();

        if (! is_file($bundlePath)) {
            return false;
        }

        $contents = File::get($bundlePath);
        $lines = preg_split("/\r\n|\n|\r/", $contents) ?: [];
        $kept = [];
        $presentImports = [];
        $changed = false;

        foreach ($lines as $line) {
            $importPath = self::parseImportPath($line);

            if ($importPath === null) {
                $kept[] = $line;

                continue;
            }

            if (self::isPackageImport($importPath)) {
                $kept[] = $line;

                continue;
            }

            // Legacy app-theme @imports: prune (loaded on demand via Vite).
            if (str_contains($importPath, 'resources/voodbuilder/themes/')
                || str_contains($importPath, '../themes/blog/')
                || str_contains($importPath, '../themes/news/')) {
                $changed = true;

                continue;
            }

            $absolute = self::resolveImportAbsolute($bundlePath, $importPath);

            if ($absolute === null || ! is_file($absolute)) {
                $changed = true;

                continue;
            }

            $presentImports[$importPath] = true;
            $kept[] = $line;
        }

        foreach (self::optionalChannelCssPaths() as $absoluteCssPath) {
            $importPath = ThemeConvention::cssImportPathFromBundle($absoluteCssPath);

            if ($importPath === null || isset($presentImports[$importPath])) {
                continue;
            }

            $insertAt = self::landingImportInsertIndex($kept);

            array_splice($kept, $insertAt, 0, [
                "@import '{$importPath}';",
            ]);
            $presentImports[$importPath] = true;
            $changed = true;
        }

        if (! $changed) {
            return false;
        }

        File::put($bundlePath, rtrim(implode("\n", $kept))."\n");

        return true;
    }


    /**
     * Optional voodbuilder channel stylesheets shipped by other voodflow packages.
     *
     * @return list<string>
     */
    private static function optionalChannelCssPaths(): array
    {
        $paths = [];

        foreach (['vexhibitors', 'vevents'] as $package) {
            foreach ([
                base_path("packages/voodflow/{$package}/resources/css/voodbuilder-channel.css"),
                base_path("vendor/voodflow/{$package}/resources/css/voodbuilder-channel.css"),
            ] as $candidate) {
                if (is_file($candidate)) {
                    $paths[] = $candidate;

                    break;
                }
            }
        }

        return $paths;
    }

    /**
     * @param  list<string>  $lines
     */
    private static function landingImportInsertIndex(array $lines): int
    {
        foreach ($lines as $index => $line) {
            if (str_contains($line, "@import './landing.css'")) {
                return $index + 1;
            }
        }

        return count($lines);
    }

    private static function isPackageImport(string $importPath): bool
    {
        return ! str_starts_with($importPath, '.')
            && ! str_starts_with($importPath, '/');
    }

    private static function parseImportPath(string $line): ?string
    {
        if (preg_match("/@import\s+'([^']+)'\s*;/", trim($line), $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private static function resolveImportAbsolute(string $bundlePath, string $importPath): ?string
    {
        $candidate = dirname($bundlePath).'/'.str_replace('\\', '/', $importPath);
        $resolved = realpath($candidate);

        if ($resolved === false || ! is_file($resolved)) {
            return null;
        }

        return $resolved;
    }
}
