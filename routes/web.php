<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Http\Controllers\AccountController;
use Voodflow\Voodbuilder\Http\Controllers\AuthController;
use Voodflow\Voodbuilder\Http\Controllers\HomeController;
use Voodflow\Voodbuilder\Http\Controllers\SearchController;
use Voodflow\Voodbuilder\Http\Controllers\SearchSuggestController;
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

        Route::get('/'.$searchRoute.'/suggest', SearchSuggestController::class)
            ->middleware('throttle:60,1')
            ->name('voodbuilder.search.suggest');
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
});

// Site-page catch-alls are registered in VoodbuilderServiceProvider after boot so
// companions can Voodbuilder::reservePathPrefix() first.
