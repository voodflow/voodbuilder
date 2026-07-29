<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules\History;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Contracts\RegistersRoutes;
use Voodflow\Voodbuilder\Http\Controllers\EditorPageRevisionsController;
use Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;

final class HistoryModule extends AbstractVoodBuilderModule implements RegistersRoutes
{
    public const ID = 'history';

    public function id(): string
    {
        return self::ID;
    }

    public function name(): string
    {
        return 'History';
    }

    public function capabilities(): array
    {
        return ['editor.history'];
    }

    public function registerRoutes(Router $router, ModuleContext $context): void
    {
        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('voodbuilder/editor')
            ->name('voodbuilder.editor.')
            ->group(function (): void {
                Route::get('pages/{sitePage}/revisions', [EditorPageRevisionsController::class, 'index'])
                    ->name('pages.revisions.index');
                Route::post('pages/{sitePage}/revisions/{revision}/restore', [EditorPageRevisionsController::class, 'restore'])
                    ->name('pages.revisions.restore');
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
