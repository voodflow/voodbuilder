<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Vtuts\Support\LocaleSwitcher;
use Voodflow\Vtuts\VtutsServiceProvider;

/**
 * Visibility and chrome for the desktop profile / preferences dropdown.
 */
final class NavProfileMenu
{
    public static function accountChromeEnabled(): bool
    {
        return (bool) VoodbuilderSettings::get('show_account_link', true);
    }

    public static function accountPageEnabled(): bool
    {
        return self::accountChromeEnabled()
            && (bool) config('voodbuilder.account.enabled', true)
            && Route::has('voodbuilder.account');
    }

    public static function themeToggleEnabled(): bool
    {
        return (bool) VoodbuilderSettings::get('show_theme_toggle', true);
    }

    public static function languageSwitcherVisible(): bool
    {
        return class_exists(LocaleSwitcher::class)
            && LocaleSwitcher::visible()
            && app()->providerIsLoaded(VtutsServiceProvider::class)
            && view()->exists('vtuts::components.language-switcher');
    }

    public static function hasContent(?bool $authenticated = null): bool
    {
        $authenticated ??= auth()->check();

        return self::accountChromeEnabled()
            || self::themeToggleEnabled()
            || self::languageSwitcherVisible()
            || $authenticated;
    }

    /**
     * Account menu on → user icon; off → preferences (theme / language) icon.
     */
    public static function triggerIcon(): string
    {
        return self::accountChromeEnabled() ? 'user' : 'adjustments-horizontal';
    }

    public static function triggerAriaLabel(): string
    {
        return self::accountChromeEnabled()
            ? __('voodbuilder::nav.menu_aria')
            : __('voodbuilder::nav.preferences_menu_aria');
    }
}
