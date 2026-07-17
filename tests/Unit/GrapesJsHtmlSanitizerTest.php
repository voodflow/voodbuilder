<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsHtmlSanitizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsHtmlSanitizerTest extends TestCase
{
    public function test_sanitizes_malformed_percent_in_google_maps_src(): void
    {
        $html = '<iframe src="https://maps.google.com/maps?width=100%&amp;height=600&amp;q=test"></iframe>';

        $sanitized = GrapesJsHtmlSanitizer::sanitize($html);

        $this->assertStringContainsString('width=100%25&', $sanitized);
        $this->assertStringNotContainsString('width=100%&', $sanitized);
    }

    public function test_preserves_valid_percent_encoding(): void
    {
        $value = 'https://example.test/path?q=%C4%B0zmir';

        $this->assertSame(
            'https://example.test/path?q=%C4%B0zmir',
            GrapesJsHtmlSanitizer::encodeMalformedPercentSequences($value),
        );
    }

    public function test_strips_uncompiled_blade_attributes(): void
    {
        $html = '<div data-voodbuilder-nav-mobile-panel @hidden(!$isActive)><span>Menu</span></div>';

        $sanitized = GrapesJsHtmlSanitizer::sanitize($html);

        $this->assertStringNotContainsString('@hidden', $sanitized);
        $this->assertStringContainsString('data-voodbuilder-nav-mobile-panel', $sanitized);
        $this->assertStringContainsString('<span>Menu</span>', $sanitized);
    }

    public function test_strips_data_gjs_attributes_including_json_resizable(): void
    {
        $html = '<div class="lg:max-w-lg">'
            .'<img alt="hero" class="w-full h-auto object-cover" '
            .'data-gjs-type="image" data-gjs-resizable="{"ratioDefault":1}" data-gjs-locked="false"/>'
            .'</div>';

        $cleaned = GrapesJsHtmlSanitizer::stripEditorOnlyAttributes($html);

        $this->assertStringNotContainsString('data-gjs-', $cleaned);
        $this->assertStringNotContainsString('ratioDefault', $cleaned);
        $this->assertStringContainsString('class="w-full h-auto object-cover"', $cleaned);
        $this->assertStringContainsString('alt="hero"', $cleaned);
    }
}
