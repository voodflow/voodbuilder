<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules\Components;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Contracts\RegistersRoutes;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsComponentsController;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsGlobalClassesController;
use Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;

/**
 * Saved components + global classes editor APIs.
 * Public component CSS in rendered pages remains Core.
 */
final class ComponentsModule extends AbstractVoodBuilderModule implements RegistersRoutes
{
    public const ID = 'components';

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return 'Components';
    }

    public function capabilities(): array
    {
        return [
            'components.library',
            'components.import-export',
            'components.global-classes',
        ];
    }

    public function registerRoutes(Router $router, ModuleContext $context): void
    {
        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('voodbuilder/grapesjs')
            ->name('voodbuilder.grapesjs.')
            ->group(function (): void {
                Route::get('global-classes', [GrapesJsGlobalClassesController::class, 'index'])
                    ->name('global-classes.index');
                Route::post('global-classes', [GrapesJsGlobalClassesController::class, 'store'])
                    ->name('global-classes.store');
                Route::put('global-classes/{globalClass}', [GrapesJsGlobalClassesController::class, 'update'])
                    ->name('global-classes.update');
                Route::delete('global-classes/{globalClass}', [GrapesJsGlobalClassesController::class, 'destroy'])
                    ->name('global-classes.destroy');
                Route::get('components', [GrapesJsComponentsController::class, 'index'])
                    ->name('components.index');
                Route::post('components', [GrapesJsComponentsController::class, 'store'])
                    ->name('components.store');
                Route::post('components/compile-css', [GrapesJsComponentsController::class, 'compileCss'])
                    ->name('components.compile-css');
                Route::post('components/import', [GrapesJsComponentsController::class, 'import'])
                    ->name('components.import');
                Route::post('components/export', [GrapesJsComponentsController::class, 'export'])
                    ->name('components.export');
                Route::put('components/{component}', [GrapesJsComponentsController::class, 'update'])
                    ->name('components.update');
                Route::delete('components/{component}', [GrapesJsComponentsController::class, 'destroy'])
                    ->name('components.destroy');
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
