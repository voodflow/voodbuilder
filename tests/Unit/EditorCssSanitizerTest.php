<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorCssSanitizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorCssSanitizerTest extends TestCase
{
    public function test_removes_broad_section_layout_background_image_rules(): void
    {
        $css = '.text-vp-text-2.body-font.voodbuilder-editor-section.bg-vp-bg{background-image:url("/storage/voodbuilder/test.jpg");}';

        $this->assertSame('', EditorCssSanitizer::sanitize($css));
    }

    public function test_keeps_id_scoped_background_rules(): void
    {
        $css = '#iyeh{background-image:url("/storage/voodbuilder/test.jpg");}';

        $this->assertSame($css, EditorCssSanitizer::sanitize($css));
    }

    public function test_collapses_duplicate_reset_rules(): void
    {
        $reset = '* { box-sizing: border-box; } body {margin: 0;}';

        $this->assertSame(
            $reset,
            EditorCssSanitizer::sanitize($reset.$reset.$reset),
        );
    }

    public function test_kebab_cases_camel_css_properties(): void
    {
        $css = '#icon {maxWidth:40px; flexShrink:0; alignItems:center}';

        $this->assertSame(
            '#icon {max-width:40px; flex-shrink:0; align-items:center}',
            EditorCssSanitizer::kebabCaseCamelCssProperties($css),
        );
    }

    public function test_strips_tailwind_property_layer_blocks(): void
    {
        $css = <<<'CSS'
.keep { color: red; }
@layer properties {
  @supports ((-webkit-hyphens: none)) {
    * { --tw-border-style: solid; }
  }
}
@property --tw-shadow {
  syntax: "*";
  inherits: false;
}
.also { color: blue; }
CSS;

        $out = EditorCssSanitizer::stripRedundantTailwindPropertyLayers($css);

        $this->assertStringContainsString('.keep { color: red; }', $out);
        $this->assertStringContainsString('.also { color: blue; }', $out);
        $this->assertStringNotContainsString('@layer properties', $out);
        $this->assertStringNotContainsString('@property --tw-shadow', $out);
    }
}
