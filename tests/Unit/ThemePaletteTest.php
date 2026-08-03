<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Tests\TestCase;

class ThemePaletteTest extends TestCase
{
    #[Test]
    public function it_keeps_header_icon_hover_readable_against_branded_header_bg(): void
    {
        $css = ThemePalette::headerChromeCss();

        $this->assertStringContainsString(
            ".voodbuilder-header-icon-btn:is(:hover,:focus-visible):not(:where([data-mobile-nav],[data-mobile-nav] *)){color:var(--vx-header-text,var(--color-vp-text-1))!important",
            $css,
        );
        $this->assertStringNotContainsString(
            '.voodbuilder-header-icon-btn:is(:hover,:focus-visible):not(:where([data-mobile-nav],[data-mobile-nav] *)){color:var(--color-vp-brand-1)!important',
            $css,
        );
        $this->assertStringContainsString(
            '.hover\:text-vp-brand-1:hover:not(:where([role=\'menu\'],[role=\'menu\'] *,[data-voodbuilder-search-dialog],[data-voodbuilder-search-dialog] *,[data-mobile-nav],[data-mobile-nav] *)){color:color-mix(in srgb,var(--vx-header-text) 88%,#fff)!important}',
            $css,
        );
    }

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
        $this->assertStringContainsString("html[data-voodbuilder-sub-theme='site']:not(.dark){--color-vp-brand-1:#47cc49!important", $css);
        $this->assertStringContainsString('--vp-c-brand-1:var(--color-vp-brand-1)', $css);
        $this->assertStringContainsString('--color-indigo-500:var(--color-vp-brand-3)', $css);
        $this->assertStringContainsString('html header[role=\'banner\'] .bg-vp-bg', $css);
        $this->assertStringContainsString('--vx-header-bg,var(--color-vp-bg))!important', $css);
        $this->assertStringContainsString('html header[role=\'banner\'] .voodbuilder-header-icon-btn', $css);
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
        $this->assertStringContainsString('--vx-header-bg:#0f172a!important', $css);
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

    public function test_header_bg_opacity_emits_color_mix(): void
    {
        config()->set('voodbuilder.sub_themes', [
            'site' => ['label' => 'Site'],
        ]);

        VoodbuilderSettings::saveData([
            'sub_theme_colors' => ThemePalette::normalize([
                'site' => [
                    'custom' => true,
                    'light' => [
                        'header_bg' => '#0f172a',
                        'header_bg_opacity' => 65,
                    ],
                ],
            ]),
        ]);
        VoodbuilderSettings::clearCache();

        $css = ThemePalette::css();

        $this->assertStringContainsString(
            '--vx-header-bg:color-mix(in srgb, #0f172a 65%, transparent)',
            $css,
        );
        $this->assertSame(
            'color-mix(in srgb, #0f172a 65%, transparent)',
            ThemePalette::headerBackgroundCssValue('#0f172a', 65),
        );
        $this->assertSame('#0f172a', ThemePalette::headerBackgroundCssValue('#0f172a', 100));
        $this->assertSame('transparent', ThemePalette::headerBackgroundCssValue('#0f172a', 0));
    }

    public function test_critical_chrome_shell_css_prefers_admin_header_bg_over_bundled_semantic(): void
    {
        config()->set('voodbuilder.sub_themes', [
            'site' => [
                'label' => 'Site',
                'css' => 'themes/site/theme.css',
            ],
        ]);
        app(SubThemeRegistry::class)->register('site', [
            'label' => 'Site',
            'css' => 'themes/site/theme.css',
        ]);

        VoodbuilderSettings::saveData([
            'sub_theme_colors' => ThemePalette::normalize([
                'site' => [
                    'custom' => true,
                    'light' => [
                        'header_bg' => '#4f46e5',
                        'header_text' => '#ffffff',
                    ],
                    'dark' => [],
                ],
            ]),
        ]);
        VoodbuilderSettings::clearCache();

        $css = ThemePalette::criticalChromeShellCss('site');

        $this->assertStringContainsString('--vx-header-bg:#4f46e5', $css);
        // Bundled default must not be re-emitted once admin overrides header_bg,
        // otherwise page-embedded Editor CSS can win later in the cascade.
        $this->assertStringNotContainsString('--vx-header-bg:#0f172a', $css);
        $this->assertStringContainsString(
            "html[data-voodbuilder-sub-theme='site'] [data-voodbuilder-chrome-shell][data-voodbuilder-sub-theme='site']:not(.dark)",
            $css,
        );
        $this->assertStringContainsString("header[role='banner'].bg-vp-bg", $css);
    }

    #[Test]
    public function it_omits_bundled_header_bg_from_canvas_css_when_admin_overrides(): void
    {
        config()->set('voodbuilder.sub_themes', [
            'site' => [
                'label' => 'Site',
                'css' => 'themes/site/theme.css',
            ],
        ]);
        app(SubThemeRegistry::class)->register('site', [
            'label' => 'Site',
            'css' => 'themes/site/theme.css',
        ]);

        VoodbuilderSettings::saveData([
            'sub_theme_colors' => ThemePalette::normalize([
                'site' => [
                    'custom' => true,
                    'light' => [
                        'header_bg' => '#9e2ca0',
                        'header_text' => '#ffffff',
                    ],
                    'dark' => [],
                ],
            ]),
        ]);
        VoodbuilderSettings::clearCache();

        $css = ThemePalette::cssForCanvas('site');

        $this->assertStringContainsString('--vx-header-bg:#9e2ca0', $css);
        $this->assertStringNotContainsString('--vx-header-bg:#0f172a', $css);
        $this->assertStringContainsString("html[data-voodbuilder-sub-theme='site']:not(.dark)", $css);
    }

    #[Test]
    public function it_strips_embedded_palette_overrides_from_saved_css(): void
    {
        $css = <<<'CSS'
html:not(.dark){--vx-header-bg:#0f172a !important;--color-vp-brand-1:#c8102e !important}
.hero{color:red}
CSS;

        $stripped = ThemePalette::stripEmbeddedPaletteOverrides($css);

        $this->assertStringNotContainsString('--vx-header-bg', $stripped);
        $this->assertStringNotContainsString('--color-vp-brand-1', $stripped);
        $this->assertStringContainsString('.hero{color:red}', $stripped);
    }

    #[Test]
    public function it_strips_theme_managed_header_chrome_rules_from_saved_css(): void
    {
        $css = <<<'CSS'
html[data-voodbuilder-sub-theme] header[role='banner'] .voodbuilder-header-icon-btn{color:red}
.hero{color:blue}
CSS;

        $stripped = ThemePalette::stripEmbeddedPaletteOverrides($css);

        $this->assertStringNotContainsString('voodbuilder-header-icon-btn', $stripped);
        $this->assertStringContainsString('.hero{color:blue}', $stripped);
    }
}
