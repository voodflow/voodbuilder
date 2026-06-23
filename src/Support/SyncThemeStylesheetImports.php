<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Facades\File;

final class SyncThemeStylesheetImports
{
    /**
     * Drop @import lines that point to missing files and add imports for app themes that exist.
     */
    public static function sync(): bool
    {
        $bundlePath = VpressPaths::themeCssAbsolutePath();

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

            $absolute = self::resolveImportAbsolute($bundlePath, $importPath);

            if ($absolute === null || ! is_file($absolute)) {
                $changed = true;

                continue;
            }

            $presentImports[$importPath] = true;
            $kept[] = $line;
        }

        foreach (self::appThemeCssPaths() as $absoluteCssPath) {
            $importPath = ThemeConvention::cssImportPathFromBundle($absoluteCssPath);

            if ($importPath === null || isset($presentImports[$importPath])) {
                continue;
            }

            $kept[] = '';
            $kept[] = "@import '{$importPath}';";
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
     * @return list<string>
     */
    private static function appThemeCssPaths(): array
    {
        $paths = [];

        foreach (config('vpress.sub_themes', []) as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $css = $definition['css'] ?? null;

            if (! is_string($css) || ! str_contains($css, 'resources/vpress/themes/')) {
                continue;
            }

            $absolute = base_path($css);

            if (is_file($absolute)) {
                $paths[] = $absolute;
            }
        }

        return $paths;
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
