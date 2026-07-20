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

    public function test_normalize_payload_strips_data_gjs_so_cta_labels_survive_reload(): void
    {
        $html = '<a href="#" role="button" data-voodbuilder-cta="true" '
            .'class="inline-flex text-white bg-indigo-500" '
            .'data-gjs-type="voodbuilder-cta-button" data-gjs-ctaLabel="Submit" '
            .'data-gjs-droppable="e=>!x7(e)">Submit</a>';

        $normalized = \Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsEditorGate::normalizePayload([
            'html' => $html,
            'css' => '',
            'js' => '',
            'project' => null,
        ]);

        $this->assertStringNotContainsString('data-gjs-', $normalized['html']);
        $this->assertStringNotContainsString('e=>!x7(e)', $normalized['html']);
        $this->assertStringContainsString('data-voodbuilder-cta="true"', $normalized['html']);
        $this->assertStringContainsString('>Submit</a>', $normalized['html']);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Submit"', $normalized['html']);
    }

    public function test_restore_empty_cta_labels_from_data_attribute(): void
    {
        $html = '<a href="#" role="button" data-voodbuilder-cta="true" '
            .'data-voodbuilder-cta-label="Accept" class="inline-flex"></a>';

        $restored = GrapesJsHtmlSanitizer::restoreEmptyCtaLabels($html);

        $this->assertStringContainsString('>Accept</a>', $restored);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Accept"', $restored);
    }

    public function test_strips_logo_scroll_runtime_clones(): void
    {
        $html = '<div data-voodbuilder-logo-scroll>'
            .'<div data-vb-logo-mover>'
            .'<div data-vb-items-root class="vb-logo-scroll__track"><div data-vb-item>A</div></div>'
            .'<div data-vb-logo-clone="1" class="vb-logo-scroll__track vb-logo-scroll__track--clone" aria-hidden="true"><div data-vb-item>A</div></div>'
            .'</div>'
            .'</div>';

        $sanitized = GrapesJsHtmlSanitizer::sanitize($html);

        $this->assertStringNotContainsString('data-vb-logo-clone', $sanitized);
        $this->assertStringNotContainsString('vb-logo-scroll__track--clone', $sanitized);
        $this->assertStringContainsString('data-vb-items-root', $sanitized);
        $this->assertStringContainsString('>A</div>', $sanitized);
    }
}
