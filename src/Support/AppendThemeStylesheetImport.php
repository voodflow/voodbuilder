<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

/**
 * App themes are runtime skins (ThemePalette + RuntimeSubThemeStylesheet).
 * This helper used to append a Vite input; it now only confirms the CSS file exists.
 */
final class AppendThemeStylesheetImport
{
    public static function append(string $absoluteCssPath): bool
    {
        return is_file($absoluteCssPath);
    }
}
