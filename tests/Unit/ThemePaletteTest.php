<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Tests\TestCase;

class ThemePaletteTest extends TestCase
{
    #[Test]
    public function it_sanitizes_hex_colors(): void
    {
        $this->assertSame('#3451b2', ThemePalette::sanitizeColor('#3451b2'));
        $this->assertSame('#3451b2', ThemePalette::sanitizeColor('#3451B2'));
        $this->assertSame('#3451b2', ThemePalette::sanitizeColor('#35b'));
        $this->assertSame('#002b49', ThemePalette::sanitizeColor('002B49'));
        $this->assertSame('#ef7b00', ThemePalette::sanitizeColor('EF7B00'));
        $this->assertNull(ThemePalette::sanitizeColor('red'));
        $this->assertNull(ThemePalette::sanitizeColor(''));
    }

    #[Test]
    public function it_builds_css_for_custom_sub_theme_colors(): void
    {
        config()->set('voodbuilder.sub_themes', [
            'docs' => ['label' => 'Default'],
        ]);

        $css = ThemePalette::css();

        $this->assertStringContainsString('header[role=\'banner\']', $css);
        $this->assertStringContainsString('--vx-header-text', $css);
        $this->assertStringContainsString('--vp-c-brand-1:var(--color-vp-brand-1)', $css);

        $normalized = ThemePalette::normalize([
            'docs' => [
                'custom' => true,
                'light' => [
                    'primary' => '#111111',
                    'secondary' => '#222222',
                ],
                'dark' => [
                    'primary' => '#aaaaaa',
                    'secondary' => '#bbbbbb',
                ],
            ],
        ]);

        $this->assertTrue($normalized['docs']['custom']);
        $this->assertSame('#111111', $normalized['docs']['light']['primary']);
    }

    #[Test]
    public function it_builds_canvas_css_without_sub_theme_attribute_selector(): void
    {
        config()->set('voodbuilder.sub_themes', [
            'site' => ['label' => 'Site'],
        ]);

        VoodbuilderSettings::query()->create([
            'data' => array_merge(VoodbuilderSettings::docss(), [
                'sub_theme_colors' => [
                    'site' => [
                        'light' => [
                            'primary' => '#47cc49',
                            'secondary' => '#d68527',
                        ],
                    ],
                ],
            ]),
        ]);
        VoodbuilderSettings::clearCache();

        $css = ThemePalette::cssForCanvas('site');

        $this->assertStringContainsString('html:not(.dark){--color-vp-brand-1:#47cc49!important', $css);
        $this->assertStringContainsString('--vp-c-brand-1:var(--color-vp-brand-1)', $css);
        $this->assertStringNotContainsString("data-voodbuilder-sub-theme='site'", $css);
    }

    #[Test]
    public function it_includes_builtin_sub_theme_tokens_in_canvas_css_without_admin_overrides(): void
    {
        config()->set('voodbuilder.sub_themes', [
            'site' => [
                'label' => 'Site',
                'css' => 'themes/site/theme.css',
            ],
        ]);

        $css = ThemePalette::cssForCanvas('site');

        $this->assertStringContainsString('html:not(.dark){--color-vp-brand-1:#c8102e!important', $css);
        $this->assertStringContainsString('--vp-c-brand-1:var(--color-vp-brand-1)', $css);
    }

    #[Test]
    public function it_builds_critical_document_css_for_active_sub_theme(): void
    {
        config()->set('voodbuilder.sub_themes', [
            'site' => [
                'label' => 'Site',
                'css' => 'themes/site/theme.css',
            ],
        ]);

        VoodbuilderSettings::query()->create([
            'data' => array_merge(VoodbuilderSettings::docss(), [
                'sub_theme_colors' => [
                    'site' => [
                        'light' => [
                            'primary' => '#47cc49',
                        ],
                    ],
                ],
            ]),
        ]);
        VoodbuilderSettings::clearCache();

        $css = ThemePalette::criticalDocumentCss('site');

        $this->assertStringContainsString("html[data-voodbuilder-sub-theme='site']:not(.dark){--color-vp-brand-1:#c8102e!important", $css);
        $this->assertStringContainsString("html[data-voodbuilder-sub-theme='site']:not(.dark){--color-vp-brand-1:#47cc49!important", $css);
    }

    #[Test]
    public function it_builds_css_for_semantic_theme_colors(): void
    {
        config()->set('voodbuilder.sub_themes', [
            'site' => ['label' => 'Site'],
        ]);

        VoodbuilderSettings::query()->create([
            'data' => array_merge(VoodbuilderSettings::docss(), [
                'sub_theme_colors' => [
                    'site' => [
                        'light' => [
                            'primary' => '#002b49',
                            'secondary' => '#ef7b00',
                            'header_bg' => '#002b49',
                            'header_text' => '#ffffff',
                            'body_bg' => '#ffffff',
                            'text' => '#1a1a1a',
                        ],
                    ],
                ],
            ]),
        ]);
        VoodbuilderSettings::clearCache();

        $css = ThemePalette::css();

        $this->assertStringContainsString('--vx-header-bg:#002b49', $css);
        $this->assertStringContainsString('--color-vp-bg:#ffffff', $css);
        $this->assertStringContainsString('--color-vp-text-2:color-mix', $css);
        $this->assertStringContainsString("html[data-voodbuilder-sub-theme='site']:not(.dark)", $css);
    }
}
