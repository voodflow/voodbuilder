<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Illuminate\Support\Facades\File;
use Voodflow\Vpress\Support\SyncThemeStylesheetImports;
use Voodflow\Vpress\Support\VpressPaths;
use Voodflow\Vpress\Tests\TestCase;

class SyncThemeStylesheetImportsTest extends TestCase
{
    public function test_it_removes_imports_for_missing_app_themes(): void
    {
        $bundlePath = VpressPaths::themeCssAbsolutePath();
        $original = File::get($bundlePath);

        File::put($bundlePath, $original."\n@import '../../../../../resources/vpress/themes/missing/theme.css';\n");

        try {
            $this->assertTrue(SyncThemeStylesheetImports::sync());
            $updated = File::get($bundlePath);
            $this->assertStringNotContainsString('missing/theme.css', $updated);
        } finally {
            File::put($bundlePath, $original);
        }
    }
}
