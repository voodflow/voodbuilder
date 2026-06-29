<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\File;

final class AppendThemeStylesheetImport
{
    public static function append(string $absoluteCssPath): bool
    {
        $importPath = ThemeConvention::cssImportPathFromBundle($absoluteCssPath);

        if ($importPath === null) {
            return false;
        }

        $bundlePath = VoodbuilderPaths::themeCssAbsolutePath();

        if (! is_file($bundlePath)) {
            return false;
        }

        $importLine = "@import '{$importPath}';";
        $contents = File::get($bundlePath);

        if (str_contains($contents, $importLine)) {
            return false;
        }

        File::put($bundlePath, rtrim($contents)."\n{$importLine}\n");

        return true;
    }
}
