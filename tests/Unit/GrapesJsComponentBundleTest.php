<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Validation\ValidationException;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsComponentBundle;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsImportedTailwindCssBuilder;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsPastedComponentNormalizer;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsComponentBundleTest extends TestCase
{
    public function test_build_export_payload_includes_generator_metadata(): void
    {
        $payload = GrapesJsComponentBundle::buildExportPayload([]);

        $this->assertSame(GrapesJsComponentBundle::FORMAT_VERSION, $payload['format_version']);
        $this->assertSame('voodbuilder-components', $payload['format']);
        $this->assertSame('voodbuilder', $payload['generator']['name']);
        $this->assertSame(VoodbuilderPackageVersion::current(), $payload['generator']['version']);
        $this->assertSame([], $payload['components']);
    }

    public function test_css_for_export_strips_bridge_and_runtime_base_styles(): void
    {
        $html = '<div class="bg-gray-900 py-24 voodbuilder-pasted-component"><p class="text-white">44 million</p></div>';
        $css = GrapesJsImportedTailwindCssBuilder::baseStyles()."\n.voodbuilder-pasted-component .text-white { color: #fff; }\n"
            .GrapesJsPastedComponentNormalizer::componentThemeTokenBridgeCss();

        $exported = GrapesJsPastedComponentNormalizer::cssForExport($css, $html);

        $this->assertNotNull($exported);
        $this->assertStringNotContainsString('dialog:not([open])', $exported);
        $this->assertStringNotContainsString('header.absolute', $exported);
        $this->assertStringNotContainsString('--color-vp-brand-1: inherit', $exported);
        $this->assertStringContainsString('.text-white', $exported);
    }

    public function test_rejects_import_from_newer_generator_version(): void
    {
        $this->expectException(ValidationException::class);

        GrapesJsComponentBundle::assertImportable([
            'format' => 'voodbuilder-components',
            'format_version' => 1,
            'generator' => [
                'name' => 'voodbuilder',
                'version' => '99.0.0',
            ],
        ]);
    }

    public function test_allows_legacy_import_without_metadata(): void
    {
        GrapesJsComponentBundle::assertImportable(null);

        $this->assertTrue(true);
    }
}
