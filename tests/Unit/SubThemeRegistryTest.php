<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Enums\SubThemeType;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Tests\TestCase;

class SubThemeRegistryTest extends TestCase
{
    public function test_it_loads_builtin_sub_themes_from_config(): void
    {
        $registry = app(SubThemeRegistry::class);

        $this->assertTrue($registry->exists('default'));
        $this->assertTrue($registry->exists('blog'));
        $this->assertTrue($registry->exists('news'));
        $this->assertSame('Blog', $registry->label('blog'));
    }

    public function test_it_returns_layout_overrides_for_builtin_themes(): void
    {
        $registry = app(SubThemeRegistry::class);

        $this->assertSame(
            'vpress::themes.blog.layouts.page',
            $registry->layout('blog', 'page'),
        );

        $this->assertNull($registry->layout('default', 'page'));
    }

    public function test_it_groups_sub_themes_by_type(): void
    {
        $registry = app(SubThemeRegistry::class);

        $this->assertSame(
            ['default', 'blog', 'news'],
            $registry->idsByType(SubThemeType::Content),
        );
        $this->assertSame(['events'], $registry->idsByType(SubThemeType::Marketing));
        $this->assertSame(['events'], array_keys($registry->marketingOptions()));
        $this->assertArrayHasKey('default', $registry->contentOptions());
    }

    public function test_custom_registration_extends_registry(): void
    {
        $registry = app(SubThemeRegistry::class);

        $registry->register('magazine', [
            'label' => 'Magazine',
            'type' => 'marketing',
            'layouts' => [
                'page' => 'vpress.themes.magazine.layouts.page',
            ],
        ]);

        $this->assertTrue($registry->exists('magazine'));
        $this->assertSame('Magazine', $registry->marketingOptions()['magazine']);
    }
}
