<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Illuminate\Support\Collection;
use Voodflow\Vpress\Contracts\PublicContentChannel;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\ContentChannelRegistry;
use Voodflow\Vpress\Support\ThemeBindings;
use Voodflow\Vpress\Tests\TestCase;

class ThemeBindingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(ContentChannelRegistry::class)->register(new class implements PublicContentChannel
        {
            public function id(): string
            {
                return 'docs';
            }

            public function label(): string
            {
                return 'Documentation';
            }

            public function routePatterns(): array
            {
                return ['vdocs.*'];
            }

            public function subTheme(): ?string
            {
                return null;
            }

            public function search(string $term, int $limit = 20): Collection
            {
                return collect();
            }
        });
    }

    public function test_effective_theme_uses_package_default(): void
    {
        $this->assertSame('default', ThemeBindings::effectiveThemeForChannelId('docs'));
    }

    public function test_effective_theme_falls_back_to_site_pages_layout_without_package_default(): void
    {
        VpressSettings::query()->create([
            'data' => array_merge(VpressSettings::defaults(), [
                'sub_theme' => 'site',
            ]),
        ]);
        VpressSettings::clearCache();

        $this->assertSame('site', ThemeBindings::effectiveThemeForChannelId('unknown-channel'));
    }

    public function test_expand_channel_themes_fills_missing_overrides(): void
    {
        $expanded = ThemeBindings::expandChannelThemesForForm([]);

        $this->assertSame('default', $expanded['docs'] ?? null);
    }

    public function test_select_options_for_channel_has_no_automatic_entry(): void
    {
        $options = ThemeBindings::selectOptionsForChannel('docs');

        $this->assertArrayNotHasKey('', $options);
        $this->assertArrayHasKey('default', $options);
    }
}
