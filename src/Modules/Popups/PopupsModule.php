<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules\Popups;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Contracts\RegistersRoutes;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsPopupController;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsPopupsController;
use Voodflow\Voodbuilder\Http\Controllers\PopupEditorController;
use Voodflow\Voodbuilder\Http\Controllers\PopupsAnalyticsController;
use Voodflow\Voodbuilder\Http\Controllers\PopupsPublicController;
use Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;

/**
 * Popups admin, editor APIs, and public runtime endpoints.
 * First candidate for later package extraction (`voodbuilder-popups`).
 */
final class PopupsModule extends AbstractVoodBuilderModule implements RegistersRoutes
{
    public const ID = 'popups';

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return 'Popups';
    }

    public function capabilities(): array
    {
        return [
            'popups.admin',
            'popups.editor',
            'popups.runtime',
            'popups.analytics',
        ];
    }

    public function registerRoutes(Router $router, ModuleContext $context): void
    {
        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('voodbuilder/grapesjs')
            ->name('voodbuilder.grapesjs.')
            ->group(function (): void {
                Route::get('popups', [GrapesJsPopupsController::class, 'index'])->name('popups.index');
                Route::get('popups/page-paths', [GrapesJsPopupsController::class, 'pagePaths'])->name('popups.page-paths');
                Route::post('popups', [GrapesJsPopupsController::class, 'store'])->name('popups.store');
                Route::put('popups/{popup}', [GrapesJsPopupsController::class, 'update'])->name('popups.update');
                Route::delete('popups/{popup}', [GrapesJsPopupsController::class, 'destroy'])->name('popups.destroy');
                Route::match(['put', 'post'], 'popups/{popup}/content', [GrapesJsPopupController::class, 'update'])
                    ->name('popups.content.update');
            });

        Route::middleware(['web', 'throttle:120,1'])
            ->prefix('voodbuilder')
            ->name('voodbuilder.')
            ->group(function (): void {
                Route::get('popups/data', [PopupsPublicController::class, 'index'])->name('popups.public');
                Route::post('popups/events', [PopupsAnalyticsController::class, 'store'])->name('popups.events');
            });

        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('voodbuilder')
            ->name('voodbuilder.')
            ->group(function (): void {
                Route::get('popups/{popup}/editor', [PopupEditorController::class, 'show'])->name('popups.editor');
            });
    }

    public function register(ModuleContext $context): void
    {
        $this->registerRoutes($context->app->make(Router::class), $context);
    }

    public static function isEnabled(): bool
    {
        $registry = app(ModuleRegistry::class);

        return $registry->has(self::ID) && $registry->isEnabled(self::ID);
    }
}
