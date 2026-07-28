<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules\Pages;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Contracts\RegistersRoutes;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsFormController;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsPageController;
use Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;
use Voodflow\Voodbuilder\Support\SitePagesContentChannel;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Site pages admin/editor + pages content channel.
 * Public show routes remain in package routes/web.php behind pages.enabled.
 */
final class PagesModule extends AbstractVoodBuilderModule implements RegistersRoutes
{
    public const ID = 'pages';

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return 'Pages';
    }

    public function capabilities(): array
    {
        return [
            'pages.admin',
            'pages.editor',
            'pages.forms',
        ];
    }

    public function registerRoutes(Router $router, ModuleContext $context): void
    {
        Route::middleware(['web', 'throttle:20,1'])
            ->prefix('voodbuilder/grapesjs')
            ->name('voodbuilder.grapesjs.')
            ->group(function (): void {
                Route::post('forms/{sitePage}', GrapesJsFormController::class)->name('forms.submit');
            });

        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('voodbuilder/grapesjs')
            ->name('voodbuilder.grapesjs.')
            ->group(function (): void {
                Route::match(['put', 'post'], 'pages/{sitePage}', [GrapesJsPageController::class, 'update'])
                    ->name('pages.update');
            });
    }

    public function register(ModuleContext $context): void
    {
        Voodbuilder::contentChannel('pages', new SitePagesContentChannel);
        $this->registerRoutes($context->app->make(Router::class), $context);
    }

    public static function isEnabled(): bool
    {
        $registry = app(ModuleRegistry::class);

        return $registry->has(self::ID) && $registry->isEnabled(self::ID);
    }
}
