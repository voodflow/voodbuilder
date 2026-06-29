<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\SubThemeCapability;
use Voodflow\Voodbuilder\Enums\SubThemeType;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Support\ThemeBindings;
use Voodflow\Voodbuilder\Tests\TestCase;

class SubThemeRegistryTest extends TestCase
{
    public function test_it_loads_builtin_sub_themes_from_config(): void
    {
        $registry = app(SubThemeRegistry::class);

        $this->assertTrue($registry->exists('docs'));
        $this->assertTrue($registry->exists('site'));
        $this->assertTrue($registry->exists('blog'));
        $this->assertTrue($registry->exists('news'));
        $this->assertSame('Landing page', $registry->label('site'));
    }

    public function test_it_returns_layout_overrides_for_builtin_themes(): void
    {
        $registry = app(SubThemeRegistry::class);

        $this->assertSame(
            'voodbuilder::themes.site.layouts.page',
            $registry->layout('site', 'page'),
        );

        $this->assertNull($registry->layout('docs', 'page'));
    }

    public function test_it_exposes_theme_capabilities(): void
    {
        $registry = app(SubThemeRegistry::class);

        $this->assertTrue($registry->supportsCapability('docs', SubThemeCapability::Doc));
        $this->assertTrue($registry->supportsCapability('site', SubThemeCapability::Landing));
        $this->assertTrue($registry->supportsCapability('blog', SubThemeCapability::Article));
        $this->assertTrue($registry->supportsCapability('news', SubThemeCapability::Article));
        $this->assertFalse($registry->supportsCapability('docs', SubThemeCapability::Landing));
    }

    public function test_it_groups_sub_themes_by_type(): void
    {
        $registry = app(SubThemeRegistry::class);

        $contentIds = $registry->idsByType(SubThemeType::Content);
        $marketingIds = $registry->idsByType(SubThemeType::Marketing);

        $this->assertContains('docs', $contentIds);
        $this->assertContains('blog', $contentIds);
        $this->assertContains('news', $contentIds);
        $this->assertContains('site', $marketingIds);
        $this->assertArrayHasKey('site', $registry->marketingOptions());
        $this->assertArrayHasKey('docs', $registry->contentOptions());
        $this->assertArrayHasKey('blog', $registry->contentOptions());
    }

    public function test_custom_registration_extends_registry(): void
    {
        $registry = app(SubThemeRegistry::class);

        $registry->register('magazine', [
            'label' => 'Magazine',
            'capabilities' => ['landing'],
            'layouts' => [
                'page' => 'voodbuilder.themes.magazine.layouts.page',
            ],
        ]);

        $this->assertTrue($registry->exists('magazine'));
        $this->assertSame('Magazine', $registry->optionsForCapability(SubThemeCapability::Landing)['magazine']);
    }

    public function test_app_sub_themes_merge_with_bundled_docs(): void
    {
        config()->set('voodbuilder.sub_themes', [
            'polito' => [
                'label' => 'Politecnico',
                'capabilities' => ['landing'],
            ],
        ]);

        $registry = new SubThemeRegistry;
        $registry->bootFromConfig();

        $this->assertTrue($registry->exists('docs'));
        $this->assertTrue($registry->exists('site'));
        $this->assertTrue($registry->exists('polito'));
        $this->assertArrayHasKey('docs', $registry->optionsForCapability(SubThemeCapability::Doc));
    }

    public function test_blog_channel_offers_article_themes(): void
    {
        $options = ThemeBindings::selectOptionsForChannel('blog');

        $this->assertArrayHasKey('blog', $options);
        $this->assertArrayHasKey('news', $options);
        $this->assertNotSame([], $options);
    }
}
