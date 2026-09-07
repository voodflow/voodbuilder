<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Support\MenuItemTypeRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class MenuItemTypeRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(MenuItemTypeRegistry::class)->flush();
    }

    public function test_registers_plugin_type_and_exposes_options(): void
    {
        Voodbuilder::menuItemType('docs', [
            'label' => 'Documentation',
            'allows_root' => true,
            'allows_child' => true,
        ]);

        $registry = app(MenuItemTypeRegistry::class);

        $this->assertTrue($registry->has('docs'));
        $this->assertSame(['docs' => 'Documentation'], $registry->options(isChild: false));
        $this->assertSame(['docs' => 'Documentation'], $registry->options(isChild: true));
    }

    public function test_child_only_types_are_filtered_from_root_options(): void
    {
        Voodbuilder::menuItemType('leaf_docs', [
            'label' => 'Leaf docs',
            'allows_root' => false,
            'allows_child' => true,
        ]);

        $registry = app(MenuItemTypeRegistry::class);

        $this->assertSame([], $registry->options(isChild: false));
        $this->assertSame(['leaf_docs' => 'Leaf docs'], $registry->options(isChild: true));
    }

    public function test_navigation_menu_item_resolves_registered_type(): void
    {
        Voodbuilder::menuItemType('docs', [
            'label' => 'Documentation',
            'resolve_url' => fn (): string => 'https://example.test/docs',
            'resolve_children' => fn () => collect([
                new NavigationMenuItem([
                    'label' => 'Overview',
                    'type' => MenuItemType::Url,
                    'link' => 'https://example.test/docs',
                ]),
            ]),
            'is_active' => fn (): bool => true,
            'has_resolvable_link' => fn (): bool => false,
        ]);

        $item = new NavigationMenuItem([
            'label' => 'Docs',
            'type' => 'docs',
        ]);

        $this->assertSame('docs', $item->typeKey());
        $this->assertSame('https://example.test/docs', $item->resolveUrl());
        $this->assertTrue($item->hasChildren());
        $this->assertCount(1, $item->navigationChildren());
        $this->assertTrue($item->isActive());
        $this->assertFalse($item->hasResolvableLink());
    }

    public function test_menu_contains_detects_registered_type_in_main_menu(): void
    {
        Voodbuilder::menuItemType('docs', [
            'label' => 'Documentation',
        ]);

        $menu = NavigationMenu::query()->create([
            'name' => 'Main',
            'slug' => 'main',
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Docs',
            'type' => 'docs',
            'sort_order' => 0,
        ]);

        $this->assertTrue(app(MenuItemTypeRegistry::class)->menuContains('docs', 'main'));
        $this->assertFalse(app(MenuItemTypeRegistry::class)->menuContains('missing', 'main'));
    }

    public function test_built_in_enum_types_still_cast_and_resolve(): void
    {
        $item = new NavigationMenuItem([
            'label' => 'Home',
            'type' => MenuItemType::Url,
            'link' => 'https://example.test',
        ]);

        $this->assertSame(MenuItemType::Url, $item->type);
        $this->assertSame('url', $item->typeKey());
        $this->assertSame('https://example.test', $item->resolveUrl());
    }
}
