<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules\Templates;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Contracts\RegistersRoutes;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsPageTemplatesController;
use Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;
use Voodflow\Voodbuilder\Voodbuilder;

final class TemplatesModule extends AbstractVoodBuilderModule implements RegistersRoutes
{
    public const ID = 'templates';

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return 'Templates';
    }

    public function capabilities(): array
    {
        return [
            'templates.local',
            'templates.import',
            'templates.export',
            'templates.remote-install',
        ];
    }

    public function registerRoutes(Router $router, ModuleContext $context): void
    {
        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('voodbuilder/grapesjs')
            ->name('voodbuilder.grapesjs.')
            ->group(function (): void {
                // Community: local template library only.
                Route::get('page-templates', [GrapesJsPageTemplatesController::class, 'index'])
                    ->name('page-templates.index');
                Route::post('page-templates', [GrapesJsPageTemplatesController::class, 'store'])
                    ->name('page-templates.store');
                Route::delete('page-templates/{pageTemplate}', [GrapesJsPageTemplatesController::class, 'destroy'])
                    ->name('page-templates.destroy');

                if (Voodbuilder::can('templates.import')) {
                    Route::post('page-templates/import', [GrapesJsPageTemplatesController::class, 'import'])
                        ->name('page-templates.import');
                    Route::post('page-templates/import-url', [GrapesJsPageTemplatesController::class, 'importFromUrl'])
                        ->name('page-templates.import-url');
                }

                if (Voodbuilder::can('templates.remote-install')) {
                    Route::get('page-templates/catalog', [GrapesJsPageTemplatesController::class, 'catalog'])
                        ->name('page-templates.catalog');
                    Route::post('page-templates/install', [GrapesJsPageTemplatesController::class, 'installCatalogEntry'])
                        ->name('page-templates.install');
                }

                if (Voodbuilder::can('templates.export')) {
                    Route::post('page-templates/export', [GrapesJsPageTemplatesController::class, 'export'])
                        ->name('page-templates.export');
                }
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
