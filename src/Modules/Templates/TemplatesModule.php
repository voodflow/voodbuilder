<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules\Templates;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Contracts\RegistersRoutes;
use Voodflow\Voodbuilder\Http\Controllers\EditorPageTemplatesController;
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
            ->prefix('voodbuilder/editor')
            ->name('voodbuilder.editor.')
            ->group(function (): void {
                // Core consume: list + delete + marketplace install-from-URL.
                Route::get('page-templates', [EditorPageTemplatesController::class, 'index'])
                    ->name('page-templates.index');
                Route::delete('page-templates/{pageTemplate}', [EditorPageTemplatesController::class, 'destroy'])
                    ->name('page-templates.destroy');
                Route::post('page-templates/import-url', [EditorPageTemplatesController::class, 'importFromUrl'])
                    ->name('page-templates.import-url');

                // Authoring endpoints always registered; TemplateAuthoringBridge gates in the controller
                // (and soft-gates the editor UI) so Filament plugin activation timing does not matter.
                Route::post('page-templates', [EditorPageTemplatesController::class, 'store'])
                    ->name('page-templates.store');
                Route::post('page-templates/import', [EditorPageTemplatesController::class, 'import'])
                    ->name('page-templates.import');
                Route::post('page-templates/export', [EditorPageTemplatesController::class, 'export'])
                    ->name('page-templates.export');

                if (Voodbuilder::can('templates.remote-install')) {
                    Route::get('page-templates/catalog', [EditorPageTemplatesController::class, 'catalog'])
                        ->name('page-templates.catalog');
                    Route::post('page-templates/install', [EditorPageTemplatesController::class, 'installCatalogEntry'])
                        ->name('page-templates.install');
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
