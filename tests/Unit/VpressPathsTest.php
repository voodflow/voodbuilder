<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Support\VpressPaths;
use Voodflow\Vpress\Tests\TestCase;

class VpressPathsTest extends TestCase
{
    public function test_theme_css_relative_path_points_inside_package(): void
    {
        $relative = VpressPaths::themeCssRelativePath();

        $this->assertStringEndsWith('voodflow/vpress/resources/css/theme.css', $relative);
        $this->assertFileExists(base_path($relative));
    }

    public function test_default_vite_entries_include_theme_and_app_js(): void
    {
        $entries = VpressPaths::defaultViteEntries();

        $this->assertSame(VpressPaths::themeCssRelativePath(), $entries[0]);
        $this->assertSame('resources/js/app.js', $entries[1]);
    }
}
