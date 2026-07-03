<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteHeaderGrapesJsBlock;
use Voodflow\Voodbuilder\Tests\TestCase;

class NavigationMenuPreviewTest extends TestCase
{
    public function test_guest_is_redirected_from_menu_preview(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Main navigation',
            'slug' => 'main',
        ]);

        $this->get(route('voodbuilder.admin.navigation-menus.preview', $menu))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_preview_header_menu(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Editor',
            'email' => 'editor@example.com',
        ])->save();

        $menu = NavigationMenu::query()->create([
            'name' => 'Main navigation',
            'slug' => 'main',
        ]);

        $this->actingAs($user)
            ->get(route('voodbuilder.admin.navigation-menus.preview', $menu))
            ->assertOk()
            ->assertSee('data-voodbuilder-gjs-site-header', false);
    }

    public function test_site_header_block_renders_theme_navigation_slots(): void
    {
        $config = array_merge(SiteHeaderGrapesJsBlock::defaultConfig(), [
            'show_profile' => false,
            'show_notifications' => false,
        ]);

        $html = SiteHeaderGrapesJsBlock::toHtml($config, []);

        $this->assertStringContainsString('data-voodbuilder-gjs-site-header', $html);
        $this->assertStringContainsString('data-mobile-nav-toggle', $html);
    }
}
