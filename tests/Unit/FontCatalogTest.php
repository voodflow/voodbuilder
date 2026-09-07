<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Fonts\FontDefinition;
use Voodflow\Voodbuilder\Support\Fonts\FontStylesheets;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class FontCatalogTest extends TestCase
{
    public function test_core_catalog_boots_about_fifty_fontsource_fonts(): void
    {
        $catalog = Voodbuilder::fonts();
        $all = $catalog->all();

        $this->assertGreaterThanOrEqual(45, count($all));
        $this->assertLessThanOrEqual(60, count($all));
        $this->assertNotNull($catalog->get('inter'));
        $this->assertNotNull($catalog->get('jetbrains-mono'));
        $this->assertSame('fontsource', $catalog->get('inter')?->provider);
    }

    public function test_register_fonts_extends_catalog_for_plugins(): void
    {
        Voodbuilder::registerFonts([
            'id' => 'bunny-demo',
            'family' => 'Bunny Demo',
            'category' => 'sans-serif',
            'provider' => 'bunny',
            'stack' => '"Bunny Demo", sans-serif',
            'weights' => [400],
        ]);

        $font = Voodbuilder::fonts()->get('bunny-demo');

        $this->assertInstanceOf(FontDefinition::class, $font);
        $this->assertSame('bunny', $font->provider);
        $this->assertSame('Bunny Demo', $font->family);
    }

    public function test_detect_used_ids_from_css(): void
    {
        $ids = Voodbuilder::fonts()->detectUsedIds(
            ".hero { font-family: 'Inter Variable', ui-sans-serif, system-ui, sans-serif; }"
            ."\n"
            .".code { font-family: 'JetBrains Mono', ui-monospace, monospace; }",
        );

        $this->assertContains('inter', $ids);
        $this->assertContains('jetbrains-mono', $ids);
    }

    public function test_sanitize_inline_font_families_repairs_broken_style_attributes(): void
    {
        $broken = '<h2 class="text-4xl" style="font-family:" fira="" code="" ui-monospace="" monospace="" id="ikqba7">Hello</h2>';

        $fixed = FontStylesheets::sanitizeInlineFontFamilies($broken);

        $this->assertStringContainsString("style=\"font-family: 'Fira Code', ui-monospace, monospace\"", $fixed);
        $this->assertStringNotContainsString('fira=""', $fixed);
        $this->assertStringContainsString('id="ikqba7"', $fixed);
    }

    public function test_sanitize_strips_orphaned_font_stack_attributes_when_style_already_set(): void
    {
        $broken = '<h2 exo="" ui-sans-serif="" system-ui="" sans-serif="" id="ikqba7-3" class="text-4xl" style="font-family: \'Literata\', ui-serif, Georgia, serif !important">Hello</h2>';

        $fixed = FontStylesheets::sanitizeInlineFontFamilies($broken);

        $this->assertStringNotContainsString('exo=""', $fixed);
        $this->assertStringNotContainsString('ui-sans-serif=""', $fixed);
        $this->assertStringContainsString("font-family: 'Literata'", $fixed);
        $this->assertStringContainsString('id="ikqba7-3"', $fixed);
    }

    public function test_sanitize_composer_css_collapses_duplicate_id_font_rules(): void
    {
        $css = <<<'CSS'
#ikqba7-3 {font-family:'Literata', ui-serif, Georgia, serif !important;}
#ikqba7 {font-family:"Bebas Neue", ui-sans-serif, system-ui, sans-serif;font-weight:500;}
#ikqba7 {font-family:"Fira Code", ui-monospace, monospace;}
.flex {display:flex}
CSS;

        $fixed = FontStylesheets::sanitizeComposerCss($css);

        $this->assertStringContainsString("font-family: 'Fira Code', ui-monospace, monospace", $fixed);
        $this->assertStringContainsString('font-weight: 500', $fixed);
        $this->assertEquals(1, substr_count($fixed, '#ikqba7 {'));
        $this->assertStringContainsString("font-family: 'Literata'", $fixed);
        $this->assertStringContainsString('.flex {display:flex}', $fixed);
        $this->assertStringNotContainsString('font-family:"', $fixed);
    }

    public function test_sync_id_font_rules_follow_inline_style(): void
    {
        $html = '<h2 id="ikqba7-3" class="text-4xl" style="font-family: \'Literata\', ui-serif, Georgia, serif !important">Hi</h2>';
        $css = "#ikqba7-3 {font-family: 'Exo 2', ui-sans-serif, system-ui, sans-serif;}";

        $fixed = FontStylesheets::syncIdFontRulesFromInlineHtml($css, $html);

        $this->assertStringContainsString("font-family: 'Literata', ui-serif, Georgia, serif !important", $fixed);
        $this->assertStringNotContainsString('Exo 2', $fixed);
    }

    public function test_css_safe_stack_uses_single_quotes(): void
    {
        $this->assertSame(
            "'Fira Code', ui-monospace, monospace",
            FontDefinition::cssSafeStack('"Fira Code", ui-monospace, monospace'),
        );
        $this->assertSame(
            "'Fira Code', ui-monospace, monospace",
            Voodbuilder::fonts()->get('fira-code')?->stack,
        );
    }

    public function test_with_detected_fonts_persists_ids_on_payload(): void
    {
        $payload = FontStylesheets::withDetectedFonts([
            'html' => '<p style="font-family: Poppins, sans-serif">Hi</p>',
            'css' => '',
            'js' => '',
            'project' => null,
        ]);

        $this->assertContains('poppins', $payload['fonts']);
    }

    public function test_woff2_urls_are_extracted_from_font_stylesheet(): void
    {
        $dir = public_path('build/assets');

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $cssName = 'voodbuilder-font-test-'.uniqid('', true).'.css';
        $cssPath = $dir.'/'.$cssName;
        file_put_contents(
            $cssPath,
            '@font-face{font-family:Test;font-display:block;src:url(/build/assets/test-font.woff2)format("woff2"),url(/build/assets/test-font.woff)format("woff")}',
        );

        try {
            $urls = FontStylesheets::woff2UrlsFromStylesheet('/build/assets/'.$cssName);

            $this->assertSame(['/build/assets/test-font.woff2'], $urls);
        } finally {
            @unlink($cssPath);
        }
    }

    public function test_required_fontsource_packages_include_core_packages(): void
    {
        $packages = Voodbuilder::fonts()->requiredFontsourcePackages();

        $this->assertArrayHasKey('@fontsource-variable/inter', $packages);
        $this->assertArrayHasKey('@fontsource/roboto', $packages);
        $this->assertArrayHasKey('@fontsource/jetbrains-mono', $packages);
    }

    public function test_style_manager_options_include_system_and_catalog(): void
    {
        $options = Voodbuilder::fonts()->styleManagerOptions();
        $labels = array_column($options, 'label');

        $this->assertContains('Arial', $labels);
        $this->assertContains('Inter Variable', $labels);
    }
}
