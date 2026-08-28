<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Support\Editor\EditorHtmlSanitizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class EditorHtmlSanitizerTest extends TestCase
{
    public function test_sanitizes_malformed_percent_in_google_maps_src(): void
    {
        $html = '<iframe src="https://maps.google.com/maps?width=100%&amp;height=600&amp;q=test"></iframe>';

        $sanitized = EditorHtmlSanitizer::sanitize($html);

        $this->assertStringContainsString('width=100%25&', $sanitized);
        $this->assertStringNotContainsString('width=100%&', $sanitized);
    }

    public function test_preserves_escaped_json_data_attributes_for_forms(): void
    {
        $html = '<section data-vforms-managed-form="1">'
            .'<div data-vforms-visibility="{&quot;logic&quot;:&quot;and&quot;,&quot;rules&quot;:[]}">'
            .'<label>Name</label></div></section>';

        $sanitized = EditorHtmlSanitizer::sanitize($html);

        $this->assertStringContainsString('data-vforms-visibility=', $sanitized);
        $this->assertStringNotContainsString('data-vforms-visibility="{"', $sanitized);
        $this->assertStringContainsString('&quot;logic&quot;', $sanitized);
        $this->assertStringNotContainsString('&amp;quot;', $sanitized);
        $this->assertStringContainsString('<label>Name</label>', $sanitized);
    }

    public function test_repairs_broken_raw_json_visibility_attributes(): void
    {
        $html = '<div data-vforms-visibility="{"logic":"and","rules":[]}"><span>Field</span></div>';

        $repaired = EditorHtmlSanitizer::sanitize($html);

        $this->assertStringNotContainsString('data-vforms-visibility="{"', $repaired);
        $this->assertStringContainsString('&quot;logic&quot;', $repaired);
        $this->assertStringNotContainsString('&amp;quot;', $repaired);
        $this->assertStringContainsString('<span>Field</span>', $repaired);
    }

    public function test_preserves_valid_percent_encoding(): void
    {
        $value = 'https://example.test/path?q=%C4%B0zmir';

        $this->assertSame(
            'https://example.test/path?q=%C4%B0zmir',
            EditorHtmlSanitizer::encodeMalformedPercentSequences($value),
        );
    }

    public function test_strips_uncompiled_blade_attributes(): void
    {
        $html = '<div data-voodbuilder-nav-mobile-panel @hidden(!$isActive)><span>Menu</span></div>';

        $sanitized = EditorHtmlSanitizer::sanitize($html);

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

        $cleaned = EditorHtmlSanitizer::stripEditorOnlyAttributes($html);

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

        $normalized = EditorGate::normalizePayload([
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

        $restored = EditorHtmlSanitizer::restoreEmptyCtaLabels($html);

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

        $sanitized = EditorHtmlSanitizer::sanitize($html);

        $this->assertStringNotContainsString('data-vb-logo-clone', $sanitized);
        $this->assertStringNotContainsString('vb-logo-scroll__track--clone', $sanitized);
        $this->assertStringContainsString('data-vb-items-root', $sanitized);
        $this->assertStringContainsString('>A</div>', $sanitized);
    }

    public function test_strips_inner_drop_slot_editor_artifacts(): void
    {
        $html = '<div class="flex gap-4">'
            .'<button type="button">CTA</button>'
            .'<div data-voodbuilder-inner-drop="1" class="voodbuilder-editor-inner-drop-slot" aria-hidden="true"></div>'
            .'</div>';

        $cleaned = EditorHtmlSanitizer::stripEditorOnlyElements($html);

        $this->assertStringNotContainsString('data-voodbuilder-inner-drop', $cleaned);
        $this->assertStringNotContainsString('voodbuilder-editor-inner-drop-slot', $cleaned);
        $this->assertStringContainsString('>CTA</button>', $cleaned);
    }

    public function test_repairs_corrupted_animated_counters_and_stats_layout(): void
    {
        $html = '<section data-voodbuilder-animated-stats="" data-vb-item-count="5" class="vb-animated-stats">'
            .'<div data-vb-items-root="" class="flex flex-wrap -m-4 text-center">'
            .'<div data-vb-item="" class="p-4 w-full sm:w-1/2 md:w-1/3">'
            .'<span object="" class="text-5xl font-bold">2.7K</span>'
            .'<p>Users</p>'
            .'</div>'
            .'</div>'
            .'</section>'
            .'<span object>1,250+</span>';

        $repaired = EditorHtmlSanitizer::sanitize($html);

        $this->assertStringNotContainsString('object=', $repaired);
        $this->assertStringNotContainsString(' object>', $repaired);
        $this->assertStringContainsString('data-voodbuilder-animated-stats="1"', $repaired);
        $this->assertStringContainsString('data-vb-item-columns="5"', $repaired);
        $this->assertStringContainsString('--vb-item-columns: 5', $repaired);
        $this->assertStringContainsString('data-voodbuilder-animated-counter="1"', $repaired);
        $this->assertStringContainsString('data-vb-count-to="2.7"', $repaired);
        $this->assertStringContainsString('data-vb-count-suffix="K"', $repaired);
        $this->assertStringContainsString('data-vb-count-to="1250"', $repaired);
        $this->assertStringContainsString('data-vb-count-suffix="+"', $repaired);
        $this->assertStringContainsString('vb-animated-counter', $repaired);
    }
}
