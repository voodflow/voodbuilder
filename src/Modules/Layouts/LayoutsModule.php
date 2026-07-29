<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules\Layouts;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Contracts\RegistersBlocks;
use Voodflow\Voodbuilder\Contracts\RegistersRoutes;
use Voodflow\Voodbuilder\Http\Controllers\ChromeLayoutEditorController;
use Voodflow\Voodbuilder\Http\Controllers\EditorChromeLayoutController;
use Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;
use Voodflow\Voodbuilder\Support\Editor\ChromeLayoutContentSlotBlock;
use Voodflow\Voodbuilder\Support\Editor\EditorBlockRegistry;

/**
 * Chrome layout admin/editor. Public shell resolution stays Core (`ChromeLayoutResolver`).
 */
final class LayoutsModule extends AbstractVoodBuilderModule implements RegistersRoutes, RegistersBlocks
{
    public const ID = 'layouts';

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return 'Layouts';
    }

    public function capabilities(): array
    {
        return [
            'layouts.chrome',
            'layouts.editor',
        ];
    }

    public function registerRoutes(Router $router, ModuleContext $context): void
    {
        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('voodbuilder/editor')
            ->name('voodbuilder.editor.')
            ->group(function (): void {
                Route::match(['put', 'post'], 'chrome-layouts/{chromeLayout}/content', [EditorChromeLayoutController::class, 'update'])
                    ->name('chrome-layouts.content.update');
            });

        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('voodbuilder')
            ->name('voodbuilder.')
            ->group(function (): void {
                Route::get('chrome-layouts/{chromeLayout}/editor', [ChromeLayoutEditorController::class, 'show'])
                    ->name('chrome-layouts.editor');
            });
    }

    public function registerBlocks(EditorBlockRegistry $blocks, ModuleContext $context): void
    {
        if (! config('voodbuilder.chrome_layouts.enabled', true)) {
            return;
        }

        $blocks->register(ChromeLayoutContentSlotBlock::definition());
    }

    public function register(ModuleContext $context): void
    {
        $this->registerRoutes($context->app->make(Router::class), $context);
        $this->registerBlocks($context->app->make(EditorBlockRegistry::class), $context);
    }

    public static function isEnabled(): bool
    {
        $registry = app(ModuleRegistry::class);

        return $registry->has(self::ID) && $registry->isEnabled(self::ID);
    }
}
