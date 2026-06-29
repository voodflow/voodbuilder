<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Vtuts\Support\LocaleSwitcher;
use Voodflow\Vtuts\Support\Locales;

final class VoodbuilderUrls
{
    public static function home(?string $locale = null): string
    {
        $locale ??= SitePageResolver::preferredLocale();

        if (
            class_exists(Locales::class)
            && Locales::usesUrlPrefix()
            && $locale !== Locales::default()
            && Route::has('home.localized')
        ) {
            return route('home.localized', ['locale' => $locale]);
        }

        $url = Route::has('home') ? route('home') : url('/');

        if (
            class_exists(Locales::class)
            && SitePageResolver::localizationEnabled()
            && ! Locales::usesUrlPrefix()
        ) {
            return LocaleSwitcher::appendLocaleQuery($url, $locale);
        }

        return $url;
    }

    public static function page(SitePage $page): string
    {
        if ($page->is_home) {
            return self::home($page->locale);
        }

        if (! Route::has('voodbuilder.pages.show')) {
            return url('/pages/'.$page->slug);
        }

        return route('voodbuilder.pages.show', ['slug' => $page->slug]);
    }

    public static function login(): string
    {
        return Route::has('login') ? route('login') : url('/login');
    }

    public static function register(): string
    {
        return Route::has('register') ? route('register') : url('/register');
    }

    public static function logout(): string
    {
        return Route::has('logout') ? route('logout') : url('/logout');
    }

    public static function search(array $query = []): string
    {
        if (! Route::has('voodbuilder.search')) {
            return url('/search');
        }

        return route('voodbuilder.search', $query);
    }
}
