<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Support\MenuItemTypeRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class NestedMenuNavItemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(MenuItemTypeRegistry::class)->flush();
    }

    public function test_nested_dynamic_type_renders_flyout_instead_of_flattening(): void
    {
        Voodbuilder::menuItemType('docs', [
            'label' => 'Documentation',
            'allows_root' => true,
            'allows_child' => true,
            'resolve_url' => fn (): string => 'https://example.test/docs',
            'resolve_children' => fn () => collect([
                new NavigationMenuItem([
                    'label' => 'Overview',
                    'type' => MenuItemType::Url,
                    'link' => 'https://example.test/docs',
                ]),
                new NavigationMenuItem([
                    'label' => 'Guide',
                    'type' => MenuItemType::Url,
                    'link' => 'https://example.test/docs/guide',
                ]),
            ]),
            'is_active' => fn (): bool => false,
            'has_resolvable_link' => fn (): bool => false,
        ]);

        $docs = new NavigationMenuItem([
            'label' => 'Docs',
            'type' => 'docs',
        ]);

        $group = new NavigationMenuItem([
            'label' => 'First level',
            'type' => MenuItemType::Group,
        ]);
        $group->setRelation('children', collect([$docs]));

        $html = view('voodbuilder::components.menu-nav-item', [
            'item' => $group,
        ])->render();

        $this->assertStringContainsString('data-voodbuilder-nav-dropdown-nested', $html);
        $this->assertStringContainsString('>Docs</span>', $html);
        $this->assertStringContainsString('Overview', $html);
        $this->assertStringContainsString('Guide', $html);
        $this->assertStringNotContainsString('uppercase tracking-wide text-vp-text-3', $html);
    }

    public function test_mobile_nested_dynamic_type_keeps_accordion(): void
    {
        Voodbuilder::menuItemType('docs', [
            'label' => 'Documentation',
            'resolve_children' => fn () => collect([
                new NavigationMenuItem([
                    'label' => 'Overview',
                    'type' => MenuItemType::Url,
                    'link' => 'https://example.test/docs',
                ]),
            ]),
            'has_resolvable_link' => fn (): bool => false,
        ]);

        $docs = new NavigationMenuItem([
            'label' => 'Docs',
            'type' => 'docs',
        ]);

        $html = view('voodbuilder::components.menu-nav-mobile-item', [
            'item' => $docs,
            'depth' => 1,
        ])->render();

        $this->assertStringContainsString('data-voodbuilder-nav-mobile-toggle', $html);
        $this->assertStringContainsString('Docs', $html);
        $this->assertStringContainsString('Overview', $html);
    }
}
