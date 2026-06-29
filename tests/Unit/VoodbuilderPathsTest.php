<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\VoodbuilderPaths;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderPathsTest extends TestCase
{
    public function test_theme_css_relative_path_points_inside_package(): void
    {
        $relative = VoodbuilderPaths::themeCssRelativePath();

        $this->assertStringEndsWith('voodflow/voodbuilder/resources/css/theme.css', $relative);
        $this->assertFileExists(base_path($relative));
    }

    public function test_default_vite_entries_include_theme_and_app_js(): void
    {
        $entries = VoodbuilderPaths::defaultViteEntries();

        $this->assertSame(VoodbuilderPaths::themeCssRelativePath(), $entries[0]);
        $this->assertSame('resources/js/app.js', $entries[1]);
    }
}
