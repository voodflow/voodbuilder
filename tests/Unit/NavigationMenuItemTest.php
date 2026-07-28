<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Support\NavigationMenuItemTree;
use Voodflow\Voodbuilder\Tests\TestCase;

class NavigationMenuItemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/events/{slug}/report', fn () => 'report')->name('vevents.report');
    }

    public function test_resolves_parameterized_route_urls(): void
    {
        $item = new NavigationMenuItem([
            'label' => 'Report',
            'type' => MenuItemType::Route,
            'link' => 'vevents.report',
            'route_parameters' => ['slug' => 'soundmit-2026'],
        ]);

        $this->assertSame(
            url('/events/soundmit-2026/report'),
            $item->resolveUrl(),
        );
    }

    public function test_returns_hash_when_route_parameters_are_missing(): void
    {
        $item = new NavigationMenuItem([
            'label' => 'Report',
            'type' => MenuItemType::Route,
            'link' => 'vevents.report',
        ]);

        $this->assertSame('#', $item->resolveUrl());
    }

    public function test_group_items_resolve_to_hash_without_a_link(): void
    {
        $item = new NavigationMenuItem([
            'label' => 'Soundmit 2026',
            'type' => MenuItemType::Group,
            'link' => null,
        ]);

        $this->assertSame('#', $item->resolveUrl());
        $this->assertFalse($item->hasResolvableLink());
    }

    public function test_parent_is_active_when_a_child_matches_the_current_route(): void
    {
        Route::get('/events/{slug}', fn () => 'event')->name('vevents.show');

        $child = new NavigationMenuItem([
            'label' => 'Report',
            'type' => MenuItemType::Route,
            'link' => 'vevents.show',
            'route_parameters' => ['slug' => 'soundmit-2026'],
            'route_match' => 'vevents.*',
        ]);

        $parent = new NavigationMenuItem([
            'label' => 'Soundmit 2026',
            'type' => MenuItemType::Group,
        ]);
        $parent->setRelation('children', collect([$child]));

        $this->get('/events/soundmit-2026');

        $this->assertTrue($child->isActive());
        $this->assertTrue($parent->isActive());
    }

    public function test_rejects_third_level_nesting(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Main',
            'slug' => 'main',
        ]);

        $root = NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Docs',
            'type' => MenuItemType::Group,
            'sort_order' => 0,
        ]);

        $child = NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'label' => 'Soundmit 2026',
            'type' => MenuItemType::Group,
            'sort_order' => 0,
        ]);

        $this->expectException(ValidationException::class);

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => $child->id,
            'label' => 'Report',
            'type' => MenuItemType::Route,
            'link' => 'vevents.report',
            'route_parameters' => ['slug' => 'soundmit-2026'],
            'sort_order' => 0,
        ]);
    }

    public function test_flattens_items_beyond_max_depth(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Main',
            'slug' => 'main',
        ]);

        $root = NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Docs',
            'type' => MenuItemType::Group,
            'sort_order' => 0,
        ]);

        $child = NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'label' => 'Soundmit 2026',
            'type' => MenuItemType::Group,
            'sort_order' => 0,
        ]);

        $grandchild = NavigationMenuItem::withoutEvents(fn (): NavigationMenuItem => NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => $child->id,
            'label' => 'Report',
            'type' => MenuItemType::Route,
            'link' => 'vevents.report',
            'route_parameters' => ['slug' => 'soundmit-2026'],
            'sort_order' => 0,
        ]));

        NavigationMenuItemTree::flattenItemsBeyondMaxDepth($menu);

        $grandchild->refresh();

        $this->assertSame($root->id, $grandchild->parent_id);
    }

    public function test_tree_build_includes_type_labels_for_admin_ui(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Header',
            'slug' => 'header',
        ]);

        $root = NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'First level',
            'type' => MenuItemType::Group,
            'sort_order' => 0,
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => $root->id,
            'label' => 'Child link',
            'type' => MenuItemType::Url,
            'link' => 'https://example.com',
            'sort_order' => 0,
        ]);

        $tree = NavigationMenuItemTree::build($menu);

        $this->assertSame('Dropdown group', $tree[0]['type_label']);
        $this->assertSame('External URL', $tree[0]['children'][0]['type_label']);
    }

    public function test_nestable_tree_node_row_override_includes_drag_handle_icon(): void
    {
        $path = view('filament-nestable-tree::components.tree.node-row')->getPath();

        $this->assertStringEndsWith(
            'resources/views/vendor/filament-nestable-tree/components/tree/node-row.blade.php',
            str_replace('\\', '/', $path),
        );

        $contents = file_get_contents($path);

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('fi-tree-drag-handle', $contents);
        $this->assertStringContainsString('<svg', $contents);
        $this->assertStringContainsString('fi-tree-node-row--nested', $contents);
        $this->assertStringContainsString('fi-tree-node-row--drop-before', $contents);
        $this->assertStringContainsString('fi-tree-node-row--drop-inside', $contents);
        $this->assertStringContainsString('fi-tree-nest-hint', $contents);
    }

    public function test_menu_tree_view_override_uses_max_depth_config(): void
    {
        $path = view('filament-nestable-tree::livewire.components.tree')->getPath();

        $this->assertStringEndsWith(
            'resources/views/vendor/filament-nestable-tree/livewire/components/tree.blade.php',
            str_replace('\\', '/', $path),
        );

        $contents = file_get_contents($path);

        $this->assertNotFalse($contents);
        $this->assertStringContainsString('menu-tree-view', $contents);
        $this->assertStringContainsString('getMaxDepth()', $contents);
    }

    public function test_menu_tree_resolve_drop_position_widens_nest_band(): void
    {
        $js = file_get_contents(
            dirname(__DIR__, 2).'/resources/js/filament-menu-tree-view.js',
        );

        $this->assertNotFalse($js);
        $this->assertStringContainsString('resolveDropPosition', $js);
        $this->assertStringContainsString('0.2', $js);
        $this->assertStringContainsString('0.8', $js);
        $this->assertStringContainsString('maxDepth', $js);
    }
}
