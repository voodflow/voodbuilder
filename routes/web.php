<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Http\Controllers\AccountController;
use Voodflow\Voodbuilder\Http\Controllers\AuthController;
use Voodflow\Voodbuilder\Http\Controllers\HomeController;
use Voodflow\Voodbuilder\Http\Controllers\SearchController;
use Voodflow\Voodbuilder\Http\Controllers\SitePageController;
use Voodflow\Voodbuilder\Http\Controllers\SitePageUnlockController;
use Voodflow\Vtuts\Support\Locales;

$localeMiddleware = [];

if (class_exists(Locales::class) && config('vtuts.features.localization', false)) {
    $localeMiddleware[] = 'vtuts.locale';
}

$usesLocaleUrlPrefix = class_exists(Locales::class) && Locales::usesUrlPrefix();
$nonDefaultLocales = $usesLocaleUrlPrefix && class_exists(Locales::class)
    ? Locales::nonDefaultCodes()
    : [];

Route::middleware(array_merge(['web'], $localeMiddleware))->group(function () use ($nonDefaultLocales): void {
    if (config('voodbuilder.home.route_enabled', true)) {
        Route::get('/', HomeController::class)->name('home');

        if ($nonDefaultLocales !== []) {
            Route::prefix('{locale}')
                ->where(['locale' => implode('|', $nonDefaultLocales)])
                ->group(function (): void {
                    Route::get('/', HomeController::class)->name('home.localized');
                });
        }
    }

    if (config('voodbuilder.search.enabled', true)) {
        $searchRoute = trim((string) config('voodbuilder.search.route', 'search'), '/');

        Route::get('/'.$searchRoute, SearchController::class)
            ->name('voodbuilder.search');
    }

    if (config('voodbuilder.auth.enabled', true)) {
        Route::middleware('guest')->group(function (): void {
            Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
            Route::post('/login', [AuthController::class, 'login']);
            Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
            Route::post('/register', [AuthController::class, 'register']);
        });

        Route::post('/logout', [AuthController::class, 'logout'])
            ->middleware('auth')
            ->name('logout');
    }

    if (config('voodbuilder.account.enabled', true)) {
        Route::middleware('auth')
            ->get('/'.trim((string) config('voodbuilder.account.route', 'account'), '/'), AccountController::class)
            ->name('voodbuilder.account');
    }

    // Site pages last: when route_prefix is empty, {slug} must not shadow login/search/etc.
    if (config('voodbuilder.pages.enabled', true)) {
        $prefix = trim((string) config('voodbuilder.pages.route_prefix', 'pages'), '/');
        $menuPaths = (bool) config('voodbuilder.pages.menu_paths', false);
        $base = $prefix !== '' ? '/'.$prefix : '';
        // Package / framework prefixes must never be claimed as menu-path sections.
        // Otherwise e.g. GET /vmedia/media is shadowed by pages.show.nested → empty media browser.
        $reserved = implode('|', array_filter([
            'login',
            'register',
            'logout',
            'search',
            'account',
            'admin',
            'livewire',
            'filament',
            'storage',
            'vendor',
            'build',
            'up',
            'voodbuilder',
            'vmedia',
            'vcookiebar',
            'vpopups',
            'vforms',
            'galleries',
            trim((string) config('voodbuilder.search.route', 'search'), '/'),
            trim((string) config('voodbuilder.account.route', 'account'), '/'),
            trim((string) config('vmedia.routes.prefix', 'vmedia'), '/'),
            trim((string) config('vmedia.public.prefix', 'galleries'), '/'),
        ]));

        if ($menuPaths) {
            // Lookahead must use (?:/|$) — bare `$` means end of the full URI, so
            // `/vmedia/media` would still match section=vmedia (remaining path ≠ "vmedia").
            $sectionPattern = $prefix === '' && $reserved !== ''
                ? '(?!(?:'.$reserved.')(?:/|$))[A-Za-z0-9\-]+'
                : '[A-Za-z0-9\-]+';

            Route::get($base.'/{section}/{slug}', [SitePageController::class, 'show'])
                ->where(['section' => $sectionPattern, 'slug' => '[A-Za-z0-9\-]+'])
                ->name('voodbuilder.pages.show.nested');
        }

        $slugPattern = $prefix === '' && $reserved !== ''
            ? '(?!(?:'.$reserved.')$)[A-Za-z0-9\-]+'
            : '[A-Za-z0-9\-]+';

        Route::get($base.'/{slug}', [SitePageController::class, 'show'])
            ->where(['slug' => $slugPattern])
            ->name('voodbuilder.pages.show');

        Route::post($base.'/{slug}/unlock', SitePageUnlockController::class)
            ->middleware('throttle:10,1')
            ->name('voodbuilder.pages.unlock');
    }
});
