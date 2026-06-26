<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\SubThemeLocator;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Support\ThemePalette;
use Voodflow\Vpress\Tests\TestCase;

class VpressSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        VpressSettings::query()->delete();
        VpressSettings::clearCache();

        app(SubThemeRegistry::class)->register('blog-custom', [
            'label' => 'Blog custom',
            'capabilities' => ['article'],
        ]);
    }

    public function test_save_data_updates_the_same_record_that_data_reads(): void
    {
        VpressSettings::query()->create([
            'data' => ['site_title' => 'Canonical'],
        ]);

        VpressSettings::query()->create([
            'data' => [
                'sub_theme_colors' => [
                    'blog-custom' => [
                        'custom' => true,
                        'light' => ['primary' => '#111111'],
                        'dark' => [],
                    ],
                ],
            ],
        ]);

        VpressSettings::saveData([
            'sub_theme_colors' => [
                'blog-custom' => [
                    'custom' => true,
                    'light' => ['primary' => '#22c55e'],
                    'dark' => [],
                ],
            ],
        ]);

        $this->assertSame(1, VpressSettings::query()->count());
        $this->assertSame('#22c55e', VpressSettings::get('sub_theme_colors')['blog-custom']['light']['primary'] ?? null);
        $this->assertSame('Canonical', VpressSettings::get('site_title'));
    }

    public function test_save_data_merges_orphan_records_before_deleting_them(): void
    {
        VpressSettings::query()->create([
            'data' => ['site_title' => 'Canonical'],
        ]);

        VpressSettings::query()->create([
            'data' => [
                'sub_theme_colors' => [
                    'blog-custom' => [
                        'custom' => true,
                        'light' => ['primary' => '#16a34a'],
                        'dark' => [],
                    ],
                ],
            ],
        ]);

        VpressSettings::saveData([
            'brand_name' => 'Cosmolab',
        ]);

        $this->assertSame(1, VpressSettings::query()->count());
        $this->assertSame('Canonical', VpressSettings::get('site_title'));
        $this->assertSame('Cosmolab', VpressSettings::get('brand_name'));
        $this->assertSame('#16a34a', VpressSettings::get('sub_theme_colors')['blog-custom']['light']['primary'] ?? null);
    }

    public function test_palette_from_bundled_css_extracts_blog_tokens(): void
    {
        $palette = ThemePalette::paletteFromBundledCss('blog');

        $this->assertIsArray($palette);
        $this->assertTrue($palette['custom']);
        $this->assertSame('#15171a', $palette['light']['primary']);
        $this->assertSame('#fafafa', $palette['light']['body_bg']);
    }

    public function test_appearance_colors_for_bundled_theme_falls_back_to_css(): void
    {
        $colors = SubThemeLocator::appearanceColorsFor('blog');

        $this->assertIsArray($colors);
        $this->assertSame('#15171a', $colors['light']['primary'] ?? null);
    }
}
