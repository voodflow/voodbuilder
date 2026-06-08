<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Voodflow\Vpress\Http\Controllers\AccountController;
use Voodflow\Vpress\Http\Controllers\AuthController;
use Voodflow\Vpress\Http\Controllers\HomeController;
use Voodflow\Vpress\Http\Controllers\SearchController;
use Voodflow\Vpress\Http\Controllers\SitePageController;
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
    if (config('vpress.home.route_enabled', true)) {
        Route::get('/', HomeController::class)->name('home');

        if ($nonDefaultLocales !== []) {
            Route::prefix('{locale}')
                ->where(['locale' => implode('|', $nonDefaultLocales)])
                ->group(function (): void {
                    Route::get('/', HomeController::class)->name('home.localized');
                });
        }
    }

    if (config('vpress.search.enabled', true)) {
        $searchRoute = trim((string) config('vpress.search.route', 'search'), '/');

        Route::get('/'.$searchRoute, SearchController::class)
            ->name('vpress.search');
    }

    if (config('vpress.pages.enabled', true)) {
        $prefix = trim((string) config('vpress.pages.route_prefix', 'pages'), '/');

        Route::get('/'.$prefix.'/{slug}', [SitePageController::class, 'show'])
            ->name('vpress.pages.show');
    }

    if (config('vpress.auth.enabled', true)) {
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

    if (config('vpress.account.enabled', true)) {
        Route::middleware('auth')
            ->get('/'.trim((string) config('vpress.account.route', 'account'), '/'), AccountController::class)
            ->name('vpress.account');
    }
});
