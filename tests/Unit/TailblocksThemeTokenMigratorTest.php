<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Support\GrapesJs\TailblocksThemeTokenMigrator;
use Voodflow\Vpress\Tests\TestCase;

class TailblocksThemeTokenMigratorTest extends TestCase
{
    public function test_migrates_surface_and_text_tokens(): void
    {
        $html = '<section class="bg-white text-gray-600"><h1 class="text-gray-900">Title</h1></section>';

        $migrated = TailblocksThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('vpress-gjs-section', $migrated);
        $this->assertStringContainsString('bg-vp-bg-elv', $migrated);
        $this->assertStringContainsString('text-vp-text-2', $migrated);
        $this->assertStringContainsString('text-vp-text-1', $migrated);
        $this->assertStringNotContainsString('bg-white', $migrated);
        $this->assertStringNotContainsString('text-gray-900', $migrated);
    }

    public function test_adds_theme_background_to_sections_without_surface_class(): void
    {
        $html = '<section class="body-font"><h1 class="text-gray-900">Title</h1></section>';

        $migrated = TailblocksThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('vpress-gjs-section bg-vp-bg', $migrated);
    }

    public function test_migrates_inline_light_background_to_theme_variable(): void
    {
        $html = '<section style="background-color: #ffffff;"><h1>Title</h1></section>';

        $migrated = TailblocksThemeTokenMigrator::migrateHtml($html);

        $this->assertStringContainsString('background-color: var(--color-vp-bg-elv)', $migrated);
    }

    public function test_migrates_saved_grapesjs_css_backgrounds(): void
    {
        $css = '#hero { background-color: #ffffff; color: #111827; }';

        $migrated = TailblocksThemeTokenMigrator::migrateCss($css);

        $this->assertStringContainsString('background-color: var(--color-vp-bg-elv)', $migrated);
        $this->assertStringContainsString('color: var(--color-vp-text-1)', $migrated);
    }

    public function test_migrates_brand_and_variant_tokens(): void
    {
        $token = TailblocksThemeTokenMigrator::migrateToken('hover:bg-indigo-600');

        $this->assertSame('hover:bg-vp-brand-3', $token);
        $this->assertSame('text-vp-brand-1', TailblocksThemeTokenMigrator::migrateToken('text-indigo-500'));
        $this->assertSame('bg-vp-gray-soft', TailblocksThemeTokenMigrator::migrateToken('bg-indigo-50'));
    }

    public function test_migrates_legacy_button_class_to_theme_utilities(): void
    {
        $classes = TailblocksThemeTokenMigrator::migrateClassList('vpress-gjs-btn-primary inline-flex text-white');

        $this->assertStringNotContainsString('vpress-gjs-btn-primary', $classes);
        $this->assertStringContainsString('bg-vp-brand-1', $classes);
        $this->assertStringContainsString('hover:bg-vp-brand-2', $classes);
    }

    public function test_strips_legacy_button_css_rules(): void
    {
        $css = '.vpress-gjs-btn-primary { background-color: #6366f1; } .vpress-gjs-btn-primary:hover { background-color: #4f46e5; } .safe { color: red; }';

        $migrated = TailblocksThemeTokenMigrator::migrateCss($css);

        $this->assertStringNotContainsString('vpress-gjs-btn-primary', $migrated);
        $this->assertStringContainsString('.safe { color: red; }', $migrated);
    }

    public function test_preserves_non_theme_utilities(): void
    {
        $this->assertSame('text-white', TailblocksThemeTokenMigrator::migrateToken('text-white'));
        $this->assertSame('rounded-lg', TailblocksThemeTokenMigrator::migrateToken('rounded-lg'));
    }

    public function test_migrates_brand_hex_in_css_without_touching_custom_colors(): void
    {
        $css = '#btn { background-color: #6366f1; } .custom { background-color: #002b49; }';

        $migrated = TailblocksThemeTokenMigrator::migrateCss($css);

        $this->assertStringContainsString('background-color: var(--color-vp-brand-1)', $migrated);
        $this->assertStringContainsString('background-color: #002b49', $migrated);
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

        $migrated = TailblocksThemeTokenMigrator::migrateProject($project);
        $class = $migrated['pages'][0]['frames'][0]['component']['components'][0]['attributes']['class'];

        $this->assertStringContainsString('bg-vp-brand-1', $class);
        $this->assertStringNotContainsString('bg-indigo-500', $class);
        $this->assertSame('text-white', TailblocksThemeTokenMigrator::migrateToken('text-white'));
    }

    public function test_migrates_grapesjs_project_string_classes_and_removes_legacy_button_styles(): void
    {
        $project = [
            'styles' => [
                [
                    'selectors' => ['vpress-gjs-btn-primary'],
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
                                'vpress-gjs-btn-primary',
                                'inline-flex',
                                'text-white',
                                'hover:bg-vp-brand-3',
                            ],
                        ]],
                    ],
                ]],
            ]],
        ];

        $migrated = TailblocksThemeTokenMigrator::migrateProject($project);
        $classes = $migrated['pages'][0]['frames'][0]['component']['components'][0]['classes'];

        $this->assertSame([], $migrated['styles']);
        $this->assertNotContains('vpress-gjs-btn-primary', $classes);
        $this->assertContains('bg-vp-brand-1', $classes);
        $this->assertContains('hover:bg-vp-brand-2', $classes);
        $this->assertNotContains('hover:bg-vp-brand-3', $classes);
        $this->assertNotContains('hover:bg-vp-brand-1', $classes);
    }

    public function test_dedupes_conflicting_hover_brand_classes_from_indigo_migration(): void
    {
        $classes = TailblocksThemeTokenMigrator::migrateClassList(
            'inline-flex bg-indigo-500 hover:bg-indigo-600 hover:bg-indigo-500 text-white',
        );

        $hoverClasses = array_values(array_filter(
            preg_split('/\s+/', $classes) ?: [],
            static fn (string $token): bool => str_starts_with($token, 'hover:bg-vp-brand'),
        ));

        $this->assertCount(1, $hoverClasses);
        $this->assertSame('hover:bg-vp-brand-1', $hoverClasses[0]);
    }

    public function test_dedupes_conflicting_hover_brand_classes_after_legacy_button_migration(): void
    {
        $classes = TailblocksThemeTokenMigrator::migrateClassList(
            'vpress-gjs-btn-primary inline-flex text-white hover:bg-vp-brand-3 hover:bg-vp-brand-1',
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
        $html = '<h1 class="title-font text-4xl text-white" style="color: rgb(60, 60, 67);">Vpress</h1>';

        $migrated = TailblocksThemeTokenMigrator::migrateHtml($html);

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

        $migrated = TailblocksThemeTokenMigrator::migrateProject($project);
        $heading = $migrated['pages'][0]['frames'][0]['component']['components'][0];

        $this->assertContains('text-white', $heading['classes']);
        $this->assertArrayNotHasKey('color', $heading['style']);
    }
}
