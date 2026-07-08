<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderThemeTokenMigrator;
use Voodflow\Voodbuilder\Tests\TestCase;

class VoodbuilderThemeTokenMigratorTest extends TestCase
{
    public function test_migrates_surface_and_text_tokens(): void
    {
        $html = '<section class="bg-white text-gray-600"><h1 class="text-gray-900">Title</h1></section>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('voodbuilder-gjs-section', $migrated);
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

        $this->assertStringContainsString('voodbuilder-gjs-section', $migrated);
        $this->assertStringNotContainsString('bg-vp-bg', $migrated);
    }

    public function test_migrates_inline_light_background_to_theme_variable(): void
    {
        $html = '<section style="background-color: #ffffff;"><h1>Title</h1></section>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('background-color: var(--color-vp-bg-elv)', $migrated);
    }

    public function test_migrates_saved_grapesjs_css_backgrounds(): void
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
        $classes = VoodbuilderThemeTokenMigrator::migrateClassList('voodbuilder-gjs-btn-primary inline-flex text-white');

        $this->assertStringNotContainsString('voodbuilder-gjs-btn-primary', $classes);
        $this->assertStringContainsString('bg-vp-brand-1', $classes);
        $this->assertStringContainsString('hover:bg-vp-brand-2', $classes);
    }

    public function test_strips_legacy_button_css_rules(): void
    {
        $css = '.voodbuilder-gjs-btn-primary { background-color: #6366f1; } .voodbuilder-gjs-btn-primary:hover { background-color: #4f46e5; } .safe { color: red; }';

        $migrated = VoodbuilderThemeTokenMigrator::migrateCss($css);

        $this->assertStringNotContainsString('voodbuilder-gjs-btn-primary', $migrated);
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

    public function test_migrates_grapesjs_project_component_classes(): void
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

    public function test_migrates_grapesjs_project_string_classes_and_removes_legacy_button_styles(): void
    {
        $project = [
            'styles' => [
                [
                    'selectors' => ['voodbuilder-gjs-btn-primary'],
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
                                'voodbuilder-gjs-btn-primary',
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
        $this->assertNotContains('voodbuilder-gjs-btn-primary', $classes);
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
            'voodbuilder-gjs-btn-primary inline-flex text-white hover:bg-vp-brand-3 hover:bg-vp-brand-1',
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
        $html = '<div class="voodbuilder-gjs-component-instance bg-blue-400" data-voodbuilder-component="abc">'
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
        $html = '<section class="voodbuilder-gjs-section"><div class="container px-5 py-24 mx-auto">Content</div></section>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('voodbuilder-gjs-container', $migrated);
        $this->assertStringContainsString('max-w-[var(--width-vp-layout)]', $migrated);
        $this->assertStringNotContainsString('class="container', $migrated);
    }

    public function test_restores_container_on_section_catalog_blocks(): void
    {
        $html = '<section data-voodbuilder-section-block="vb-blog-1" class="text-vp-text-2 voodbuilder-gjs-section"><div class="px-5 py-24">Content</div></section>';

        $migrated = VoodbuilderThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('voodbuilder-gjs-container', $migrated);
        $this->assertStringContainsString('max-w-[var(--width-vp-layout)]', $migrated);
    }

    public function test_expands_voodbuilder_container_with_tailwind_utilities(): void
    {
        $classes = VoodbuilderThemeTokenMigrator::migrateClassList('voodbuilder-gjs-container px-5 py-24');

        $this->assertStringContainsString('mx-auto', $classes);
        $this->assertStringContainsString('w-full', $classes);
        $this->assertStringContainsString('max-w-[var(--width-vp-layout)]', $classes);
    }

    public function test_preserves_columns_that_already_have_responsive_widths(): void
    {
        $classes = VoodbuilderThemeTokenMigrator::migrateClassList('p-2 lg:w-1/3 md:w-1/2 w-full');

        $this->assertSame('p-2 lg:w-1/3 md:w-1/2 w-full', $classes);
    }
}
