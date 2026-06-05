<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Voodflow\Vpress\Http\Controllers\AccountController;
use Voodflow\Vpress\Http\Controllers\HomeController;
use Voodflow\Vpress\Http\Controllers\SitePageController;

Route::middleware('web')->group(function (): void {
    if (config('vpress.home.route_enabled', true)) {
        Route::get('/', HomeController::class)->name('home');
    }

    if (config('vpress.pages.enabled', true)) {
        $prefix = trim((string) config('vpress.pages.route_prefix', 'pages'), '/');

        Route::get('/'.$prefix.'/{slug}', [SitePageController::class, 'show'])
            ->name('vpress.pages.show');
    }

    if (config('vpress.account.enabled', true)) {
        Route::middleware('auth')
            ->get('/'.trim((string) config('vpress.account.route', 'account'), '/'), AccountController::class)
            ->name('vpress.account');
    }
});
