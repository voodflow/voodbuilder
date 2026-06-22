<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Enums\SubThemeCapability;
use Voodflow\Vpress\Enums\SubThemeType;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Tests\TestCase;

class SubThemeRegistryTest extends TestCase
{
    public function test_it_loads_builtin_sub_themes_from_config(): void
    {
        $registry = app(SubThemeRegistry::class);

        $this->assertTrue($registry->exists('default'));
        $this->assertTrue($registry->exists('site'));
        $this->assertSame('Site', $registry->label('site'));
        $this->assertFalse($registry->exists('blog'));
        $this->assertFalse($registry->exists('news'));
    }

    public function test_it_returns_layout_overrides_for_builtin_themes(): void
    {
        $registry = app(SubThemeRegistry::class);

        $this->assertSame(
            'vpress::themes.site.layouts.page',
            $registry->layout('site', 'page'),
        );

        $this->assertNull($registry->layout('default', 'page'));
    }

    public function test_it_exposes_theme_capabilities(): void
    {
        $registry = app(SubThemeRegistry::class);

        $this->assertTrue($registry->supportsCapability('default', SubThemeCapability::Doc));
        $this->assertTrue($registry->supportsCapability('site', SubThemeCapability::Landing));
        $this->assertFalse($registry->supportsCapability('default', SubThemeCapability::Landing));
    }

    public function test_it_groups_sub_themes_by_type(): void
    {
        $registry = app(SubThemeRegistry::class);

        $this->assertSame(['default'], $registry->idsByType(SubThemeType::Content));
        $this->assertSame(['site'], $registry->idsByType(SubThemeType::Marketing));
        $this->assertSame(['site'], array_keys($registry->marketingOptions()));
        $this->assertArrayHasKey('default', $registry->contentOptions());
    }

    public function test_custom_registration_extends_registry(): void
    {
        $registry = app(SubThemeRegistry::class);

        $registry->register('magazine', [
            'label' => 'Magazine',
            'capabilities' => ['landing'],
            'layouts' => [
                'page' => 'vpress.themes.magazine.layouts.page',
            ],
        ]);

        $this->assertTrue($registry->exists('magazine'));
        $this->assertSame('Magazine', $registry->optionsForCapability(SubThemeCapability::Landing)['magazine']);
    }

    public function test_app_sub_themes_merge_with_bundled_defaults(): void
    {
        config()->set('vpress.sub_themes', [
            'polito' => [
                'label' => 'Politecnico',
                'capabilities' => ['landing'],
            ],
        ]);

        $registry = new SubThemeRegistry;
        $registry->bootFromConfig();

        $this->assertTrue($registry->exists('default'));
        $this->assertTrue($registry->exists('site'));
        $this->assertTrue($registry->exists('polito'));
        $this->assertArrayHasKey('default', $registry->optionsForCapability(SubThemeCapability::Doc));
    }
}
