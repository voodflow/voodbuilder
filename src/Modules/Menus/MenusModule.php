<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules\Menus;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Contracts\RegistersAssets;
use Voodflow\Voodbuilder\Contracts\RegistersRoutes;
use Voodflow\Voodbuilder\Http\Controllers\NavigationMenuPreviewController;
use Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;
use Voodflow\Voodbuilder\Support\FilamentMenuTreeAssets;

/**
 * Admin menus UI. Public navigation resolution remains Core.
 */
final class MenusModule extends AbstractVoodBuilderModule implements RegistersRoutes, RegistersAssets
{
    public const ID = 'menus';

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return 'Menus';
    }

    public function capabilities(): array
    {
        return [
            'menus.admin',
            'menus.preview',
        ];
    }

    public function registerRoutes(Router $router, ModuleContext $context): void
    {
        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('voodbuilder/admin')
            ->name('voodbuilder.admin.')
            ->group(function (): void {
                Route::get('navigation-menus/{navigationMenu}/preview', NavigationMenuPreviewController::class)
                    ->name('navigation-menus.preview');
            });
    }

    public function registerAssets(ModuleContext $context): void
    {
        FilamentMenuTreeAssets::register();
    }

    public function register(ModuleContext $context): void
    {
        $this->registerRoutes($context->app->make(Router::class), $context);
        $this->registerAssets($context);
    }

    public static function isEnabled(): bool
    {
        $registry = app(ModuleRegistry::class);

        return $registry->has(self::ID) && $registry->isEnabled(self::ID);
    }
}
