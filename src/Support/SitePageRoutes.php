<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Http\Controllers\SitePageController;
use Voodflow\Voodbuilder\Http\Controllers\SitePageUnlockController;
use Voodflow\Vtuts\Support\Locales;

/**
 * Registers public site-page catch-alls after companion reserved prefixes are known.
 */
final class SitePageRoutes
{
    public static function register(): void
    {
        if (! config('voodbuilder.pages.enabled', true)) {
            return;
        }

        if (Route::has('voodbuilder.pages.show')) {
            return;
        }

        $localeMiddleware = [];

        if (class_exists(Locales::class) && config('vtuts.features.localization', false)) {
            $localeMiddleware[] = 'vtuts.locale';
        }

        Route::middleware(array_merge(['web'], $localeMiddleware))->group(function (): void {
            $prefix = trim((string) config('voodbuilder.pages.route_prefix', 'pages'), '/');
            $menuPaths = (bool) config('voodbuilder.pages.menu_paths', false);
            $base = $prefix !== '' ? '/'.$prefix : '';

            $coreReserved = [
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
                trim((string) config('voodbuilder.search.route', 'search'), '/'),
                trim((string) config('voodbuilder.account.route', 'account'), '/'),
            ];

            $pluginReserved = app()->bound(ReservedPathRegistry::class)
                ? app(ReservedPathRegistry::class)->all()
                : [];

            $reserved = implode('|', array_values(array_unique(array_filter([
                ...$coreReserved,
                ...$pluginReserved,
            ]))));

            if ($menuPaths) {
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
        });
    }
}
