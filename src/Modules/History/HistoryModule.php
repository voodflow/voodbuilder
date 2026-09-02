<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Modules\History;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Contracts\RegistersRoutes;
use Voodflow\Voodbuilder\Http\Controllers\EditorPageRevisionsController;
use Voodflow\Voodbuilder\Http\Middleware\EnsurePageBuilderAccess;
use Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleContext;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;

/**
 * History Module.
 */
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
        Route::middleware(['web', 'auth', EnsurePageBuilderAccess::class, 'throttle:60,1'])
            ->prefix('voodbuilder/editor')
            ->name('voodbuilder.editor.')
            ->group(function (): void {
                Route::get('pages/{sitePage}/revisions', [EditorPageRevisionsController::class, 'index'])
                    ->name('pages.revisions.index');
                Route::post('pages/{sitePage}/revisions/{revision}/restore', [EditorPageRevisionsController::class, 'restore'])
                    ->name('pages.revisions.restore');
                // Autosave writes a revision and never touches builder_payload, so its own
                // throttle is looser than a save's: the editor calls it on a timer.
                Route::post('pages/{sitePage}/autosave', [EditorPageRevisionsController::class, 'autosave'])
                    ->name('pages.autosave');
                Route::delete('pages/{sitePage}/autosave', [EditorPageRevisionsController::class, 'discardAutosaves'])
                    ->name('pages.autosave.discard');
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
