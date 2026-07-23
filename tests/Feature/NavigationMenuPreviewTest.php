<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Foundation\Auth\User;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterColumnsSimpleBlock;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteNavSimpleBlock;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;
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

    public function test_site_nav_block_renders_theme_navigation_slots(): void
    {
        $html = SiteNavSimpleBlock::toHtml(SiteNavSimpleBlock::defaultConfig(), []);

        $this->assertStringContainsString('data-voodbuilder-gjs-site-header', $html);
        $this->assertStringContainsString('data-mobile-nav-toggle', $html);
    }

    public function test_site_nav_block_renders_a_single_brand_title(): void
    {
        $html = SiteNavSimpleBlock::toHtml(SiteNavSimpleBlock::defaultConfig(), []);

        $this->assertSame(1, preg_match_all(
            '/inline-flex h-16 w-full items-center gap-2\.5/',
            $html,
        ));
    }

    public function test_doc_sidebar_nav_hides_inline_brand_on_desktop(): void
    {
        $html = view('voodbuilder::components.nav', [
            'hasDocSidebar' => true,
            'showReadingProgress' => true,
        ])->render();

        $this->assertStringContainsString('hidden max-vp:flex max-vp:items-center', $html);
        $this->assertStringContainsString('w-[var(--vp-sidebar-outer-width)] bg-vp-bg-alt vp:flex', $html);
    }

    public function test_canvas_block_code_toolbar_command_is_registered_in_editor_bundle(): void
    {
        $packagePath = VoodbuilderPaths::packagePath();
        $initJs = file_get_contents($packagePath.'/resources/js/grapesjs/editor/init.js');
        $toolbarJs = file_get_contents($packagePath.'/resources/js/grapesjs/canvas-component-toolbar.js');

        $this->assertIsString($initJs);
        $this->assertStringContainsString('registerCanvasBlockCodeEditor', $initJs);
        $this->assertStringContainsString('registerJoditImageEditor', $initJs);
        $this->assertStringContainsString('CMD_EDIT_BLOCK_CODE', $toolbarJs);
        $this->assertStringContainsString('CMD_EDIT_IMAGE', $toolbarJs);
        $this->assertFileExists($packagePath.'/resources/js/grapesjs/jodit-image-editor.js');
    }

    public function test_site_nav_block_uses_full_width_row_by_default(): void
    {
        $html = SiteNavSimpleBlock::toHtml(SiteNavSimpleBlock::defaultConfig(), []);

        $this->assertStringContainsString('voodbuilder-nav__row', $html);
        $this->assertStringContainsString('w-full', $html);
        $this->assertStringNotContainsString('mx-auto max-w-[calc(var(--width-vp-layout)-4rem)]', $html);
    }

    public function test_site_nav_block_pins_nav_when_sticky_is_enabled(): void
    {
        $html = SiteNavSimpleBlock::toHtml(array_merge(SiteNavSimpleBlock::defaultConfig(), [
            'sticky_nav' => 'sticky',
        ]), []);

        $this->assertStringContainsString('voodbuilder-site-header-spacer', $html);
        $this->assertMatchesRegularExpression('/<header[^>]*\bfixed\b/', $html);
    }

    public function test_site_footer_block_renders_theme_footer_columns(): void
    {
        $html = SiteFooterColumnsSimpleBlock::toHtml(SiteFooterColumnsSimpleBlock::defaultConfig(), []);

        $this->assertStringContainsString('data-voodbuilder-footer-col', $html);
        $this->assertStringContainsString('role="contentinfo"', $html);
    }

    public function test_authenticated_user_can_preview_footer_column_menu(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Editor',
            'email' => 'editor@example.com',
        ])->save();

        $menu = NavigationMenu::query()->create([
            'name' => 'Footer column 1',
            'slug' => 'footer_col_1',
        ]);

        $this->actingAs($user)
            ->get(route('voodbuilder.admin.navigation-menus.preview', $menu))
            ->assertOk();
    }
}
