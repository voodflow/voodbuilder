<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Support\Editor\EditorLinkTargets;
use Voodflow\Voodbuilder\Support\MenuItemTypeRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

final class EditorLinkTargetsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(MenuItemTypeRegistry::class)->flush();
    }

    #[Test]
    public function it_includes_app_routes_in_the_catalog(): void
    {
        $catalog = EditorLinkTargets::catalog();

        $this->assertArrayHasKey('pages', $catalog);
        $this->assertArrayHasKey('menuItems', $catalog);
        $this->assertArrayHasKey('routes', $catalog);
        $this->assertIsArray($catalog['routes']);

        foreach ($catalog['routes'] as $route) {
            $this->assertArrayHasKey('id', $route);
            $this->assertArrayHasKey('label', $route);
            $this->assertArrayHasKey('url', $route);
            $this->assertArrayHasKey('requiredParams', $route);
            $this->assertIsArray($route['requiredParams']);
        }
    }

    #[Test]
    public function it_resolves_named_routes_or_returns_hash_when_missing(): void
    {
        $missing = EditorLinkTargets::resolveRoute('voodbuilder.route.that.does.not.exist');
        $this->assertSame('#', $missing);

        $catalog = EditorLinkTargets::catalog();
        $paramFree = collect($catalog['routes'])
            ->first(fn (array $route): bool => ($route['requiredParams'] ?? []) === [] && ($route['url'] ?? '#') !== '#');

        if ($paramFree === null) {
            $this->assertTrue(true);

            return;
        }

        $url = EditorLinkTargets::resolveRoute((string) $paramFree['id']);
        $this->assertSame($paramFree['url'], $url);
    }

    #[Test]
    public function it_includes_nested_persisted_menu_items_with_parent_in_label(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Main navigation',
            'slug' => 'main-nested-' . uniqid(),
        ]);

        $products = NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Products',
            'type' => MenuItemType::Group,
            'sort_order' => 0,
        ]);

        $child = NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'parent_id' => $products->id,
            'label' => 'VoodBuilder',
            'type' => MenuItemType::Url,
            'link' => 'https://example.test/voodbuilder',
            'sort_order' => 0,
        ]);

        $labels = collect(EditorLinkTargets::menuItems())->pluck('label', 'id');

        $this->assertArrayNotHasKey((string) $products->id, $labels->all());
        $this->assertSame(
            'Main navigation · Products · VoodBuilder',
            $labels->get((string) $child->id),
        );
    }

    #[Test]
    public function it_includes_dynamic_children_from_registered_menu_types(): void
    {
        Voodbuilder::menuItemType('docs', [
            'label' => 'Documentation',
            'resolve_url' => fn (): string => 'https://example.test/docs',
            'resolve_children' => fn () => collect([
                new NavigationMenuItem([
                    'label' => 'VoodBuilder',
                    'type' => MenuItemType::Url,
                    'link' => 'https://example.test/docs/voodbuilder',
                ]),
            ]),
            'has_resolvable_link' => fn (): bool => false,
        ]);

        $menu = NavigationMenu::query()->create([
            'name' => 'Main navigation',
            'slug' => 'main-docs-' . uniqid(),
        ]);

        $docs = NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Docs',
            'type' => 'docs',
            'sort_order' => 0,
        ]);

        $items = EditorLinkTargets::menuItems();
        $dyn = collect($items)->first(
            fn (array $row): bool => str_starts_with((string) $row['id'], 'dyn:' . $docs->id . ':'),
        );

        $this->assertNotNull($dyn);
        $this->assertSame('Main navigation · Docs · VoodBuilder', $dyn['label']);
        $this->assertSame('https://example.test/docs/voodbuilder', $dyn['url']);
        $this->assertSame(
            EditorLinkTargets::dynamicMenuChildId($docs, 'https://example.test/docs/voodbuilder'),
            $dyn['id'],
        );
    }
}
