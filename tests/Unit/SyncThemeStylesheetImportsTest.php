<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\File;
use Voodflow\Voodbuilder\Support\SyncThemeStylesheetImports;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;
use Voodflow\Voodbuilder\Tests\TestCase;

class SyncThemeStylesheetImportsTest extends TestCase
{
    public function test_it_removes_imports_for_missing_app_themes(): void
    {
        $bundlePath = VoodbuilderPaths::themeCssAbsolutePath();
        $original = File::get($bundlePath);

        File::put($bundlePath, $original."\n@import '../../../../../resources/voodbuilder/themes/missing/theme.css';\n");

        try {
            $this->assertTrue(SyncThemeStylesheetImports::sync());
            $updated = File::get($bundlePath);
            $this->assertStringNotContainsString('missing/theme.css', $updated);
        } finally {
            File::put($bundlePath, $original);
        }
    }

    public function test_it_preserves_tailwindcss_package_import(): void
    {
        $bundlePath = VoodbuilderPaths::themeCssAbsolutePath();
        $original = File::get($bundlePath);

        $withoutTailwind = preg_replace("/@import 'tailwindcss';\n?/", '', $original) ?? $original;
        File::put($bundlePath, $withoutTailwind);

        try {
            $this->assertFalse(SyncThemeStylesheetImports::sync());
            $this->assertStringNotContainsString("@import 'tailwindcss';", File::get($bundlePath));
        } finally {
            File::put($bundlePath, $original);
        }

        $withTailwind = str_contains($original, "@import 'tailwindcss';")
            ? $original
            : str_replace(
                "@import './fonts.css';",
                "@import './fonts.css';\n@import 'tailwindcss';",
                $original,
            );

        File::put($bundlePath, $withTailwind);

        try {
            $this->assertFalse(SyncThemeStylesheetImports::sync());
            $this->assertStringContainsString("@import 'tailwindcss';", File::get($bundlePath));
        } finally {
            File::put($bundlePath, $original);
        }
    }
}
