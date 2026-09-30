<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Tests\TestCase;

class FooterMenuItemTest extends TestCase
{
    public function test_footer_menu_item_renders_tabler_icon_beside_label(): void
    {
        $item = new NavigationMenuItem([
            'label' => 'Docs',
            'icon' => 'home',
            'type' => MenuItemType::Url,
            'link' => 'https://example.test/docs',
        ]);

        $html = view('voodbuilder::components.footer-menu-item', [
            'item' => $item,
        ])->render();

        $this->assertStringContainsString('voodbuilder-footer-menu-item__icon', $html);
        $this->assertStringContainsString('Docs', $html);
        $this->assertStringContainsString('<svg', $html);
        $this->assertStringNotContainsString('voodbuilder-dropdown-panel--mega', $html);
        $this->assertStringNotContainsString('voodbuilder-nav-menu-item__description', $html);
    }

    public function test_footer_menu_item_omits_icon_markup_without_icon(): void
    {
        $item = new NavigationMenuItem([
            'label' => 'About',
            'type' => MenuItemType::Url,
            'link' => 'https://example.test/about',
        ]);

        $html = view('voodbuilder::components.footer-menu-item', [
            'item' => $item,
        ])->render();

        $this->assertStringContainsString('About', $html);
        $this->assertStringNotContainsString('voodbuilder-footer-menu-item__icon', $html);
    }
}
