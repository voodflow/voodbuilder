<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\Blade;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\NavProfileMenu;
use Voodflow\Voodbuilder\Tests\TestCase;

class NavProfileMenuTest extends TestCase
{
    public function test_trigger_icon_is_preferences_when_account_menu_is_disabled(): void
    {
        VoodbuilderSettings::saveData([
            'show_account_link' => false,
            'show_theme_toggle' => true,
        ]);

        $this->assertFalse(NavProfileMenu::accountChromeEnabled());
        $this->assertSame('adjustments-horizontal', NavProfileMenu::triggerIcon());
        $this->assertTrue(NavProfileMenu::hasContent());
    }

    public function test_trigger_icon_is_user_when_account_menu_is_enabled(): void
    {
        VoodbuilderSettings::saveData([
            'show_account_link' => true,
            'show_theme_toggle' => true,
        ]);

        $this->assertTrue(NavProfileMenu::accountChromeEnabled());
        $this->assertSame('user', NavProfileMenu::triggerIcon());
    }

    public function test_guest_menu_hides_auth_links_and_uses_preferences_icon_when_account_disabled(): void
    {
        VoodbuilderSettings::saveData([
            'show_account_link' => false,
            'show_theme_toggle' => true,
        ]);

        $html = Blade::render('<x-voodbuilder::nav-profile-menu />');

        $this->assertStringContainsString('data-vb-chrome-icon="adjustments-horizontal"', $html);
        $this->assertStringContainsString('data-vb-profile-menu-icon="adjustments-horizontal"', $html);
        $this->assertStringContainsString(__('voodbuilder::nav.preferences_menu_aria'), $html);
        $this->assertStringNotContainsString(__('voodbuilder::auth.login'), $html);
        $this->assertStringNotContainsString(__('voodbuilder::auth.register'), $html);
        $this->assertStringContainsString('data-theme-toggle', $html);
    }

    public function test_guest_menu_shows_auth_links_and_user_icon_when_account_enabled(): void
    {
        VoodbuilderSettings::saveData([
            'show_account_link' => true,
            'show_theme_toggle' => true,
        ]);

        $html = Blade::render('<x-voodbuilder::nav-profile-menu />');

        $this->assertStringContainsString('data-vb-chrome-icon="user"', $html);
        $this->assertStringContainsString(__('voodbuilder::nav.menu_aria'), $html);
        $this->assertStringContainsString(__('voodbuilder::auth.login'), $html);
    }

    public function test_menu_is_hidden_when_account_theme_and_language_are_all_off(): void
    {
        VoodbuilderSettings::saveData([
            'show_account_link' => false,
            'show_theme_toggle' => false,
            'show_language_switcher' => false,
        ]);

        $this->assertFalse(NavProfileMenu::hasContent());

        $html = Blade::render('<x-voodbuilder::nav-profile-menu />');

        $this->assertSame('', trim($html));
    }
}
