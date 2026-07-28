<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules\DynamicData;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Contracts\RegistersRoutes;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsBindingsController;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsBindingsPreviewController;
use Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationBindingRegistrar;

/**
 * Dynamic data admin/editor (bindings catalog + model integrations).
 * Builtin binding sources and public BindingRenderer remain Core.
 */
final class DynamicDataModule extends AbstractVoodBuilderModule implements RegistersRoutes
{
    public const ID = 'dynamic_data';

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return 'Dynamic Data';
    }

    public function capabilities(): array
    {
        // Pro collections live in voodflow/voodbuilder-dynamic-data
        // (DynamicDataCollectionsModule). Keep Community single here.
        return [
            'dynamic-data.single',
        ];
    }

    public function registerRoutes(Router $router, ModuleContext $context): void
    {
        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('voodbuilder/grapesjs')
            ->name('voodbuilder.grapesjs.')
            ->group(function (): void {
                Route::get('bindings', GrapesJsBindingsController::class)->name('bindings');
                Route::get('bindings/preview/{sitePage}', GrapesJsBindingsPreviewController::class)
                    ->name('bindings.preview');
            });
    }

    public function register(ModuleContext $context): void
    {
        $this->registerRoutes($context->app->make(Router::class), $context);

        $context->app->booted(function () use ($context): void {
            if ($context->app->runningInConsole()) {
                return;
            }

            try {
                if (! Schema::hasTable('voodbuilder_model_integrations')) {
                    return;
                }
            } catch (\Throwable) {
                return;
            }

            $context->app->make(ModelIntegrationBindingRegistrar::class)->refreshFromDatabase();
        });
    }

    public static function isEnabled(): bool
    {
        $registry = app(ModuleRegistry::class);

        return $registry->has(self::ID) && $registry->isEnabled(self::ID);
    }
}
