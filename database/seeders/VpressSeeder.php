<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;
use JeffersonGoncalves\CookieConsent\Settings\CookieConsentSettings;
use Voodflow\Vpress\Enums\MenuItemType;
use Voodflow\Vpress\Models\NavigationMenu;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\DefaultHomeContent;
use Voodflow\Vpress\Support\Navigation;

class VpressSeeder extends Seeder
{
    public function run(): void
    {
        VpressSettings::query()->firstOrCreate(
            ['id' => 1],
            ['data' => VpressSettings::defaults()],
        );

        SitePage::query()->updateOrCreate(
            ['slug' => 'privacy-policy'],
            [
                'title' => 'Privacy Policy',
                'layout' => 'page',
                'published' => true,
                'published_at' => now(),
                'is_home' => false,
            ],
        );

        SitePage::query()->updateOrCreate(
            ['slug' => 'cookie-policy'],
            [
                'title' => 'Cookie Policy',
                'layout' => 'page',
                'published' => true,
                'published_at' => now(),
                'is_home' => false,
            ],
        );

        $this->seedHomePage();

        $this->seedMenus();
        $this->seedCookieConsentSettings();
    }

    protected function seedHomePage(): void
    {
        SitePage::query()
            ->where('is_home', true)
            ->where('slug', '!=', 'home')
            ->update(['is_home' => false]);

        SitePage::query()->updateOrCreate(
            ['slug' => 'home'],
            [
                'title' => __('vpress::home.page_title'),
                'layout' => 'home',
                'content' => DefaultHomeContent::content(),
                'published' => true,
                'published_at' => now(),
                'is_home' => true,
            ],
        );
    }

    protected function seedMenus(): void
    {
        $main = NavigationMenu::query()->updateOrCreate(
            ['slug' => 'main'],
            ['name' => 'Main navigation'],
        );

        $main->items()->delete();
        $main->items()->createMany([
            [
                'label' => 'Home',
                'type' => MenuItemType::Route,
                'link' => 'home',
                'route_match' => 'home',
                'sort_order' => 0,
            ],
            ...$this->tutorialMenuItems(),
        ]);

        $footer = NavigationMenu::query()->updateOrCreate(
            ['slug' => 'footer'],
            ['name' => 'Footer'],
        );

        $footer->items()->delete();
        $footer->items()->createMany([
            [
                'label' => 'Privacy Policy',
                'type' => MenuItemType::Page,
                'link' => 'privacy-policy',
                'sort_order' => 0,
            ],
            [
                'label' => 'Cookie Policy',
                'type' => MenuItemType::Page,
                'link' => 'cookie-policy',
                'sort_order' => 1,
            ],
        ]);

        Navigation::clearCache();
    }

    /** @return list<array<string, mixed>> */
    protected function tutorialMenuItems(): array
    {
        if (! Route::has('vtuts.index')) {
            return [];
        }

        return [
            [
                'label' => 'Tutorials',
                'type' => MenuItemType::Route,
                'link' => 'vtuts.index',
                'route_match' => 'vtuts.*',
                'sort_order' => 1,
            ],
        ];
    }

    protected function seedCookieConsentSettings(): void
    {
        if (! class_exists(CookieConsentSettings::class)) {
            return;
        }

        $settings = app(CookieConsentSettings::class);

        $settings->content_message = __('We use cookies to improve your experience. By continuing, you accept our cookie policy.');
        $settings->content_allow = __('Accept');
        $settings->content_deny = __('Decline');
        $settings->content_link = __('Learn more');
        $cookiePolicy = SitePage::query()->where('slug', 'cookie-policy')->first();
        $settings->content_href = $cookiePolicy?->getUrl();
        $settings->position = 'bottom';
        $settings->theme = 'block';

        $settings->save();
    }
}
