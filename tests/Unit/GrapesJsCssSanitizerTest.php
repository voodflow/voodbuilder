<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsCssSanitizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsCssSanitizerTest extends TestCase
{
    public function test_removes_broad_tailblocks_background_image_rules(): void
    {
        $css = '.text-vp-text-2.body-font.voodbuilder-gjs-section.bg-vp-bg{background-image:url("/storage/voodbuilder/grapesjs/test.jpg");}';

        $this->assertSame('', GrapesJsCssSanitizer::sanitize($css));
    }

    public function test_keeps_id_scoped_background_rules(): void
    {
        $css = '#iyeh{background-image:url("/storage/voodbuilder/grapesjs/test.jpg");}';

        $this->assertSame($css, GrapesJsCssSanitizer::sanitize($css));
    }

    public function test_collapses_duplicate_reset_rules(): void
    {
        $reset = '* { box-sizing: border-box; } body {margin: 0;}';

        $this->assertSame(
            $reset,
            GrapesJsCssSanitizer::sanitize($reset.$reset.$reset),
        );
    }
}
