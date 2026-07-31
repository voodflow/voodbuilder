<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\VoodbuilderThemeTokenMigrator;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderThemeTokenMigratorTest extends TestCase
{
    public function test_migrates_surface_and_text_tokens(): void
    {
        $html = '<section class="bg-white text-gray-600"><h1 class="text-gray-900">Title</h1></section>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('voodbuilder-editor-section', $migrated);
        $this->assertStringContainsString('bg-vp-bg-elv', $migrated);
        $this->assertStringContainsString('text-vp-text-2', $migrated);
        $this->assertStringContainsString('text-vp-text-1', $migrated);
        $this->assertStringNotContainsString('bg-white', $migrated);
        $this->assertStringNotContainsString('text-gray-900', $migrated);
    }

    public function test_adds_theme_background_to_sections_without_surface_class(): void
    {
        $html = '<section class="body-font"><h1 class="text-gray-900">Title</h1></section>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('voodbuilder-editor-section', $migrated);
        $this->assertStringNotContainsString('bg-vp-bg', $migrated);
    }

    public function test_migrates_inline_light_background_to_theme_variable(): void
    {
        $html = '<section style="background-color: #ffffff;"><h1>Title</h1></section>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('background-color: var(--color-vp-bg-elv)', $migrated);
    }

    public function test_migrates_saved_editor_css_backgrounds(): void
    {
        $css = '#hero { background-color: #ffffff; color: #111827; }';

        $migrated = VoodbuilderThemeTokenMigrator::migrateCss($css);

        $this->assertStringContainsString('background-color: var(--color-vp-bg-elv)', $migrated);
        $this->assertStringContainsString('color: var(--color-vp-text-1)', $migrated);
    }

    public function test_preserves_brand_palette_utilities(): void
    {
        $this->assertSame('hover:bg-indigo-600', VoodbuilderThemeTokenMigrator::migrateToken('hover:bg-indigo-600'));
        $this->assertSame('text-indigo-500', VoodbuilderThemeTokenMigrator::migrateToken('text-indigo-500'));
        $this->assertSame('bg-indigo-50', VoodbuilderThemeTokenMigrator::migrateToken('bg-indigo-50'));
        $this->assertSame('bg-blue-200', VoodbuilderThemeTokenMigrator::migrateToken('bg-blue-200'));
    }

    public function test_migrates_legacy_button_class_to_theme_utilities(): void
    {
        $classes = VoodbuilderThemeTokenMigrator::migrateClassList('voodbuilder-editor-btn-primary inline-flex text-white');

        $this->assertStringNotContainsString('voodbuilder-editor-btn-primary', $classes);
        $this->assertStringContainsString('bg-vp-brand-1', $classes);
        $this->assertStringContainsString('hover:bg-vp-brand-2', $classes);
    }

    public function test_strips_legacy_button_css_rules(): void
    {
        $css = '.voodbuilder-editor-btn-primary { background-color: #6366f1; } .voodbuilder-editor-btn-primary:hover { background-color: #4f46e5; } .safe { color: red; }';

        $migrated = VoodbuilderThemeTokenMigrator::migrateCss($css);

        $this->assertStringNotContainsString('voodbuilder-editor-btn-primary', $migrated);
        $this->assertStringContainsString('.safe { color: red; }', $migrated);
    }

    public function test_preserves_non_theme_utilities(): void
    {
        $this->assertSame('text-white', VoodbuilderThemeTokenMigrator::migrateToken('text-white'));
        $this->assertSame('rounded-lg', VoodbuilderThemeTokenMigrator::migrateToken('rounded-lg'));
    }

    public function test_migrates_brand_hex_in_css_without_touching_custom_colors(): void
    {
        $css = '#btn { background-color: #6366f1; } .custom { background-color: #002b49; }';

        $migrated = VoodbuilderThemeTokenMigrator::migrateCss($css);

        $this->assertStringContainsString('background-color: var(--color-vp-brand-1)', $migrated);
        $this->assertStringContainsString('background-color: #002b49', $migrated);
    }

    public function test_migrate_css_preserves_tailwind_var_references_in_declarations(): void
    {
        $css = <<<'CSS'
        :root {
          --color-blue-200: oklch(0.882 0.059 254.128);
        }
        .bg-blue-200 {
          background-color: var(--color-blue-200);
        }
        CSS;

        $migrated = VoodbuilderThemeTokenMigrator::migrateCss($css);

        $this->assertStringContainsString('background-color: var(--color-blue-200)', $migrated);
        $this->assertDoesNotMatchRegularExpression('/background-color:\s*var\(\s*\}/', $migrated);
    }

    public function test_migrate_component_css_preserves_palette_utilities_with_fallbacks(): void
    {
        $css = <<<'CSS'
        .voodbuilder-pasted-component .bg-blue-400 {
          background-color: var(--color-blue-400);
        }
        .voodbuilder-pasted-component .bg-blue-200 {
          background-color: var(--color-blue-200);
        }
        CSS;

        $migrated = VoodbuilderThemeTokenMigrator::migrateComponentCss($css);

        $this->assertStringContainsString('var(--color-blue-400, oklch(70.7% 0.165 254.624))', $migrated);
        $this->assertStringContainsString('var(--color-blue-200, oklch(88.2% 0.059 254.128))', $migrated);
    }

    public function test_migrates_editor_project_component_classes(): void
    {
        $project = [
            'pages' => [[
                'id' => 'main',
                'frames' => [[
                    'component' => [
                        'type' => 'wrapper',
                        'components' => [[
                            'tagName' => 'a',
                            'attributes' => [
                                'class' => 'inline-flex bg-indigo-500 text-white',
                            ],
                        ]],
                    ],
                ]],
            ]],
        ];

        $migrated = VoodbuilderThemeTokenMigrator::migrateProject($project);
        $class = $migrated['pages'][0]['frames'][0]['component']['components'][0]['attributes']['class'];

        $this->assertStringContainsString('bg-indigo-500', $class);
        $this->assertStringNotContainsString('bg-vp-brand-1', $class);
        $this->assertSame('text-white', VoodbuilderThemeTokenMigrator::migrateToken('text-white'));
    }

    public function test_migrates_editor_project_string_classes_and_removes_legacy_button_styles(): void
    {
        $project = [
            'styles' => [
                [
                    'selectors' => ['voodbuilder-editor-btn-primary'],
                    'style' => [
                        'background-color' => 'rgb(99, 102, 241)',
                        'color' => 'rgb(255, 255, 255)',
                    ],
                ],
            ],
            'pages' => [[
                'id' => 'main',
                'frames' => [[
                    'component' => [
                        'type' => 'wrapper',
                        'components' => [[
                            'tagName' => 'a',
                            'classes' => [
                                'voodbuilder-editor-btn-primary',
                                'inline-flex',
                                'text-white',
                                'hover:bg-vp-brand-3',
                            ],
                        ]],
                    ],
                ]],
            ]],
        ];

        $migrated = VoodbuilderThemeTokenMigrator::migrateProject($project);
        $classes = $migrated['pages'][0]['frames'][0]['component']['components'][0]['classes'];

        $this->assertSame([], $migrated['styles']);
        $this->assertNotContains('voodbuilder-editor-btn-primary', $classes);
        $this->assertContains('bg-vp-brand-1', $classes);
        $this->assertContains('hover:bg-vp-brand-2', $classes);
        $this->assertNotContains('hover:bg-vp-brand-3', $classes);
        $this->assertNotContains('hover:bg-vp-brand-1', $classes);
    }

    public function test_preserves_conflicting_hover_palette_classes(): void
    {
        $classes = VoodbuilderThemeTokenMigrator::migrateClassList(
            'inline-flex bg-indigo-500 hover:bg-indigo-600 hover:bg-indigo-500 text-white',
        );

        $this->assertStringContainsString('bg-indigo-500', $classes);
        $this->assertStringContainsString('hover:bg-indigo-600', $classes);
        $this->assertStringContainsString('hover:bg-indigo-500', $classes);
    }

    public function test_dedupes_conflicting_hover_brand_classes_after_legacy_button_migration(): void
    {
        $classes = VoodbuilderThemeTokenMigrator::migrateClassList(
            'voodbuilder-editor-btn-primary inline-flex text-white hover:bg-vp-brand-3 hover:bg-vp-brand-1',
        );

        $hoverClasses = array_values(array_filter(
            preg_split('/\s+/', $classes) ?: [],
            static fn (string $token): bool => str_starts_with($token, 'hover:bg-vp-brand'),
        ));

        $this->assertCount(1, $hoverClasses);
        $this->assertSame('hover:bg-vp-brand-2', $hoverClasses[0]);
    }

    public function test_strips_inline_dark_text_color_when_text_white_class_is_present(): void
    {
        $html = '<h1 class="title-font text-4xl text-white" style="color: rgb(60, 60, 67);">Voodbuilder</h1>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('text-white', $migrated);
        $this->assertStringNotContainsString('color:', $migrated);
    }

    public function test_migrates_project_inline_color_without_overriding_text_white_class(): void
    {
        $project = [
            'pages' => [[
                'id' => 'main',
                'frames' => [[
                    'component' => [
                        'type' => 'wrapper',
                        'components' => [[
                            'tagName' => 'h1',
                            'classes' => ['title-font', 'text-4xl', 'text-white'],
                            'style' => [
                                'color' => 'rgb(60, 60, 67)',
                            ],
                        ]],
                    ],
                ]],
            ]],
        ];

        $migrated = VoodbuilderThemeTokenMigrator::migrateProject($project);
        $heading = $migrated['pages'][0]['frames'][0]['component']['components'][0];

        $this->assertContains('text-white', $heading['classes']);
        $this->assertArrayNotHasKey('color', $heading['style']);
    }

    public function test_migrate_html_preserves_background_classes_inside_component_instances(): void
    {
        $html = '<div class="voodbuilder-editor-component-instance bg-blue-400" data-voodbuilder-component="abc">'
            .'<div class="voodbuilder-pasted-component bg-blue-400"><p class="text-blue-500">Hi</p></div>'
            .'</div>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('bg-blue-400', $migrated);
        $this->assertStringNotContainsString('bg-vp-brand-1', $migrated);
        $this->assertStringContainsString('text-blue-500', $migrated);
    }

    public function test_adds_base_width_to_lg_only_flex_columns(): void
    {
        $html = '<div class="flex flex-wrap -m-4"><div class="p-4 lg:w-1/3">Card</div></div>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('w-full md:w-1/3', $migrated);
        $this->assertStringContainsString('lg:w-1/3', $migrated);
    }

    public function test_migrates_tailwind_container_to_voodbuilder_wrapper(): void
    {
        $html = '<section class="voodbuilder-editor-section"><div class="container px-5 py-24 mx-auto">Content</div></section>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('voodbuilder-editor-container', $migrated);
        $this->assertStringContainsString('max-w-[var(--width-vp-layout)]', $migrated);
        $this->assertStringNotContainsString('class="container', $migrated);
    }

    public function test_collapses_double_prefixed_container_class(): void
    {
        $html = '<section class="voodbuilder-editor-section"><div class="voodbuilder-editor-voodbuilder-editor-container px-5 py-24">Content</div></section>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('voodbuilder-editor-container', $migrated);
        $this->assertStringNotContainsString('voodbuilder-editor-voodbuilder-editor-container', $migrated);
    }

    public function test_restores_container_on_section_catalog_blocks(): void
    {
        $html = '<section data-voodbuilder-section-block="vb-blog-1" class="text-vp-text-2 voodbuilder-editor-section"><div class="px-5 py-24">Content</div></section>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('voodbuilder-editor-container', $migrated);
        $this->assertStringContainsString('max-w-[var(--width-vp-layout)]', $migrated);
    }

    public function test_does_not_treat_hero_media_as_section_container(): void
    {
        $html = '<section data-voodbuilder-section-block="vb-hero-cinematic" class="voodbuilder-editor-section relative">'
            .'<div class="voodbuilder-hero-media" data-voodbuilder-role="media" aria-hidden="true">'
            .'<img class="voodbuilder-hero-media__img" src="/x.jpg" alt="" />'
            .'</div>'
            .'<div class="voodbuilder-editor-container relative z-10">Copy</div>'
            .'</section>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertMatchesRegularExpression(
            '/class="[^"]*\bvoodbuilder-hero-media\b(?![^"]*\bvoodbuilder-editor-container\b)[^"]*"/',
            $migrated,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/voodbuilder-hero-media[^"]*max-w-\[var\(--width-vp-layout\)\]/',
            $migrated,
        );
        $this->assertStringContainsString('voodbuilder-editor-container relative z-10', $migrated);
    }

    public function test_strips_container_utilities_already_on_hero_media(): void
    {
        $html = '<div class="voodbuilder-editor-container voodbuilder-hero-media mx-auto w-full max-w-[var(--width-vp-layout)]"></div>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('voodbuilder-hero-media', $migrated);
        $this->assertStringNotContainsString('voodbuilder-editor-container', $migrated);
        $this->assertStringNotContainsString('max-w-[var(--width-vp-layout)]', $migrated);
        $this->assertStringNotContainsString('mx-auto', $migrated);
    }

    public function test_expands_voodbuilder_container_with_tailwind_utilities(): void
    {
        $classes = VoodbuilderThemeTokenMigrator::migrateClassList('voodbuilder-editor-container px-5 py-24');

        $this->assertStringContainsString('mx-auto', $classes);
        $this->assertStringContainsString('w-full', $classes);
        $this->assertStringContainsString('max-w-[var(--width-vp-layout)]', $classes);
    }

    public function test_does_not_force_layout_max_width_when_content_width_attr_present(): void
    {
        $html = '<div class="voodbuilder-editor-container relative px-5 w-full max-w-[80rem] mx-auto"'
            .' data-voodbuilder-content-width="normal">Content</div>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('data-voodbuilder-content-width="normal"', $migrated);
        $this->assertStringContainsString('max-w-[80rem]', $migrated);
        $this->assertStringNotContainsString('max-w-[var(--width-vp-layout)]', $migrated);
    }

    public function test_does_not_readd_layout_max_width_for_full_content_width(): void
    {
        $html = '<div class="voodbuilder-editor-container relative px-5 w-full"'
            .' data-voodbuilder-content-width="full">Content</div>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('data-voodbuilder-content-width="full"', $migrated);
        $this->assertStringNotContainsString('max-w-[var(--width-vp-layout)]', $migrated);
    }

    public function test_migrates_border_opacity_to_tailwind_v4_slash_modifier(): void
    {
        $classes = VoodbuilderThemeTokenMigrator::migrateClassList('border-2 border-gray-200 border-opacity-60 rounded-lg');

        $this->assertStringContainsString('border-vp-divider/60', $classes);
        $this->assertStringNotContainsString('border-opacity-60', $classes);
    }

    public function test_migrates_theme_border_opacity_after_token_migration(): void
    {
        $html = '<div class="border-2 border-gray-200 border-opacity-60"></div>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('border-vp-divider/60', $migrated);
        $this->assertStringNotContainsString('border-opacity-60', $migrated);
    }

    public function test_migrates_legacy_gjs_editor_namespace_classes(): void
    {
        $classes = VoodbuilderThemeTokenMigrator::migrateClassList(
            'voodbuilder-gjs-dynamic voodbuilder-gjs-footer w-full',
        );

        $this->assertSame('voodbuilder-editor-dynamic voodbuilder-editor-footer w-full', $classes);

        $html = '<footer class="voodbuilder-gjs-footer"><div class="voodbuilder-gjs-container px-5"></div></footer>';
        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('voodbuilder-editor-footer', $migrated);
        $this->assertStringContainsString('voodbuilder-editor-container', $migrated);
        $this->assertStringNotContainsString('voodbuilder-gjs-', $migrated);
    }

    public function test_preserves_columns_that_already_have_responsive_widths(): void
    {
        $classes = VoodbuilderThemeTokenMigrator::migrateClassList('p-2 lg:w-1/3 md:w-1/2 w-full');

        $this->assertSame('p-2 lg:w-1/3 md:w-1/2 w-full', $classes);
    }
}
