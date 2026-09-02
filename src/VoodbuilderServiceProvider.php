<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use RalphJSmit\Laravel\SEO\Facades\SEOManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Voodflow\Vmedia\Support\Integration\PluginVaultRootBootstrap;
use Voodflow\Vmedia\Vmedia;
use Voodflow\Voodbuilder\Console\BuildSectionsCommand;
use Voodflow\Voodbuilder\Console\CompileThemeAssetsCommand;
use Voodflow\Voodbuilder\Console\InstallCommand;
use Voodflow\Voodbuilder\Console\MakeSubThemeCommand;
use Voodflow\Voodbuilder\Console\SeedDemoLandingCommand;
use Voodflow\Voodbuilder\Console\SeedMarketingSiteCommand;
use Voodflow\Voodbuilder\Console\SubThemeCommand;
use Voodflow\Voodbuilder\Console\SyncNpmDepsCommand;
use Voodflow\Voodbuilder\Console\SyncThemeStylesheetImportsCommand;
use Voodflow\Voodbuilder\Console\ThemePresetCommand;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\FeaturesGridBlock;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\HeroBlock;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\PackagePromosBlock;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\PartnerBannerBlock;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\ProductPromoBlock;
use Voodflow\Voodbuilder\Http\Controllers\EditorBindingsController;
use Voodflow\Voodbuilder\Http\Controllers\EditorBindingsPreviewController;
use Voodflow\Voodbuilder\Http\Controllers\EditorBlockRenderController;
use Voodflow\Voodbuilder\Http\Controllers\EditorBlocksController;
use Voodflow\Voodbuilder\Http\Controllers\EditorCodeHighlightController;
use Voodflow\Voodbuilder\Http\Controllers\EditorCompileCssController;
use Voodflow\Voodbuilder\Http\Controllers\EditorLinkTargetsController;
use Voodflow\Voodbuilder\Http\Controllers\EditorMediaPreviewController;
use Voodflow\Voodbuilder\Http\Middleware\ApplyVoodbuilderSiteConfig;
use Voodflow\Voodbuilder\Http\Middleware\EnsurePageBuilderAccess;
use Voodflow\Voodbuilder\Licensing\EntitlementManager;
use Voodflow\Voodbuilder\Licensing\EntitlementProviderFactory;
use Voodflow\Voodbuilder\Livewire\AccountSettings;
use Voodflow\Voodbuilder\Livewire\SiteNotificationBell;
use Voodflow\Voodbuilder\Models\ModelIntegration;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Modules\Conditions\ConditionsModule;
use Voodflow\Voodbuilder\Modules\History\HistoryModule;
use Voodflow\Voodbuilder\Modules\Layouts\LayoutsModule;
use Voodflow\Voodbuilder\Modules\Menus\MenusModule;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;
use Voodflow\Voodbuilder\Modules\Pages\PagesModule;
use Voodflow\Voodbuilder\Modules\Templates\TemplatesModule;
use Voodflow\Voodbuilder\Modules\Themes\ThemesModule;
use Voodflow\Voodbuilder\Policies\ModelIntegrationPolicy;
use Voodflow\Voodbuilder\Support\BrandMarkAssets;
use Voodflow\Voodbuilder\Support\ChannelStylesheetRegistry;
use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageRegistry;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageRelatedBindingSourceRegistry;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingImageResolverRegistry;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BuiltinBindingSources;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ModelIntegrationBindingRegistrar;
use Voodflow\Voodbuilder\Support\Editor\Bindings\ModelIntegrationRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorBlockConfigRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorServerBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterBlocks;
use Voodflow\Voodbuilder\Support\Editor\SiteNavBlocks;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderEditorBlockConfigs;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderLanding01Sections;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderLanding02Sections;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderLandingEditorBlocks;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderMediaSections;
use Voodflow\Voodbuilder\Support\Editor\VoodbuilderSectionEditorBlocks;
use Voodflow\Voodbuilder\Support\FilamentAdminAssets;
use Voodflow\Voodbuilder\Support\Fonts\FontCatalog;
use Voodflow\Voodbuilder\Support\IntegrationRegistrar;
use Voodflow\Voodbuilder\Support\MenuItemTypeRegistry;
use Voodflow\Voodbuilder\Support\ModelRegistry;
use Voodflow\Voodbuilder\Support\ReverseRelationRegistry;
use Voodflow\Voodbuilder\Support\RichContentBlockRegistry;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Support\VoodbuilderLandingBlocks;
use Voodflow\Voodbuilder\Support\VoodbuilderSeo;

/**
 * Package service provider: registers modules, editor routes, Filament plugin hooks, and view/config merges.
 *
 * Companion packages should register via {@see Voodbuilder} / {@see VoodBuilderModule}, not by editing this class.
 */
class VoodbuilderServiceProvider extends PackageServiceProvider
{
    public static string $name = 'voodbuilder';

    public static string $viewNamespace = 'voodbuilder';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasViews(static::$viewNamespace)
            ->hasTranslations()
            ->discoversMigrations()
            ->runsMigrations()
            ->hasRoutes('web')
            ->hasCommand(InstallCommand::class)
            ->hasCommand(MakeSubThemeCommand::class)
            ->hasCommand(BuildSectionsCommand::class)
            ->hasCommand(SeedDemoLandingCommand::class)
            ->hasCommand(SeedMarketingSiteCommand::class)
            ->hasCommand(ThemePresetCommand::class)
            ->hasCommand(SubThemeCommand::class)
            ->hasCommand(SyncThemeStylesheetImportsCommand::class)
            ->hasCommand(SyncNpmDepsCommand::class)
            ->hasCommand(CompileThemeAssetsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RichContentBlockRegistry::class);
        $this->app->singleton(EditorBlockRegistry::class);
        $this->app->singleton(EditorDynamicBlockRegistry::class);
        $this->app->singleton(EditorServerBlockRegistry::class);
        $this->app->singleton(BindingRegistry::class);
        $this->app->singleton(BindingImageResolverRegistry::class);
        $this->app->singleton(ModelRegistry::class);
        $this->app->singleton(ReverseRelationRegistry::class);
        $this->app->singleton(ModelIntegrationRegistry::class);
        // RepeatListRegistry / ModelIntegrationListResolver are bound by
        // voodbuilder-dynamic-data when that package is installed.
        $this->app->singleton(ModelIntegrationBindingRegistrar::class);
        $this->app->singleton(SubThemeRegistry::class);
        $this->app->singleton(ContentChannelRegistry::class);
        $this->app->singleton(DynamicPageRegistry::class);
        $this->app->singleton(DynamicPageRelatedBindingSourceRegistry::class);
        $this->app->singleton(ChannelStylesheetRegistry::class);
        $this->app->singleton(EditorBlockConfigRegistry::class);
        $this->app->singleton(MenuItemTypeRegistry::class);
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(FontCatalog::class);
        $this->app->singleton(EntitlementManager::class, function (): EntitlementManager {
            return new EntitlementManager(EntitlementProviderFactory::make());
        });
    }

    public function packageBooted(): void
    {
        Relation::morphMap([
            'site_page' => SitePage::class,
        ]);

        Gate::policy(ModelIntegration::class, ModelIntegrationPolicy::class);

        $this->ensureMediaRuntime();

        $this->app->booted(static fn (): mixed => PluginVaultRootBootstrap::ensureFor('voodbuilder'));

        $this->app->make(SubThemeRegistry::class)->bootFromConfig();
        $this->app->make(ContentChannelRegistry::class)->bootFromConfig();
        IntegrationRegistrar::boot();

        // Upstream drag handle span is empty; our node-row adds a visible grip icon.
        View::prependNamespace('filament-nestable-tree', [
            __DIR__.'/../resources/views/vendor/filament-nestable-tree',
        ]);

        Blade::componentNamespace('Voodflow\\Voodbuilder\\Components', 'voodbuilder');

        Livewire::component('voodbuilder.site-notification-bell', SiteNotificationBell::class);
        Livewire::component('voodbuilder.account-settings', AccountSettings::class);

        BrandMarkAssets::ensurePublished();
        FilamentAdminAssets::register();

        if (config('voodbuilder.editor.enabled', true)) {
            VoodbuilderEditorBlockConfigs::register();
            $this->registerEditorRoutes();
            $this->registerEditorBlocks();
            $this->registerEditorBindings();
        }

        $this->registerAdminRoutes();
        $this->registerInternalModules();
        $this->app->make(ModuleRegistry::class)->boot();

        SEOManager::SEODataTransformer(static function ($seoData) {
            return VoodbuilderSeo::applyDefaults($seoData);
        });

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('web', ApplyVoodbuilderSiteConfig::class);

        $registry = $this->app->make(RichContentBlockRegistry::class);

        $registry
            ->register('Layout', HeroBlock::class)
            ->register('Layout', FeaturesGridBlock::class)
            ->register('Layout', PartnerBannerBlock::class)
            ->register('Layout', ProductPromoBlock::class)
            ->register('Layout', PackagePromosBlock::class);

        foreach (VoodbuilderLandingBlocks::blockClasses() as $blockClass) {
            $registry->register('Landing', $blockClass);
        }

        foreach (config('voodbuilder.rich_content_blocks', []) as $definition) {
            $group = $definition['group'] ?? null;
            $blockClass = $definition['class'] ?? null;

            if (! is_string($group) || ! is_string($blockClass) || ! class_exists($blockClass)) {
                continue;
            }

            $registry->register($group, $blockClass);
        }
    }

    protected function registerEditorRoutes(): void
    {
        $this->configureEditorRateLimiters();

        Route::middleware(['web', 'auth', EnsurePageBuilderAccess::class, 'throttle:voodbuilder-editor'])
            ->prefix('voodbuilder/editor')
            ->name('voodbuilder.editor.')
            ->group(function (): void {
                Route::get('blocks', EditorBlocksController::class)->name('blocks');
                Route::get('bindings', EditorBindingsController::class)->name('bindings');
                Route::get('bindings/preview/{sitePage}', EditorBindingsPreviewController::class)->name('bindings.preview');
                Route::get('link-targets', EditorLinkTargetsController::class)->name('link-targets');
                // Media list/upload: Core owns them unless the Media companion plugin is active.
                // Deferred so Filament plugin activation can claim the routes first.
                Route::get('media/{media}', EditorMediaPreviewController::class)
                    ->whereNumber('media')
                    ->name('media.preview');
                Route::get('blocks/render', EditorBlockRenderController::class)->name('blocks.render');
                Route::post('code/highlight', EditorCodeHighlightController::class)->name('code.highlight');
            });

        // Separate quota from catalog/bindings — page JIT can burst without starving the library.
        Route::middleware(['web', 'auth', EnsurePageBuilderAccess::class, 'throttle:voodbuilder-compile-css'])
            ->prefix('voodbuilder/editor')
            ->name('voodbuilder.editor.')
            ->group(function (): void {
                Route::post('compile-css', EditorCompileCssController::class)->name('compile-css');
            });

        // Media list/upload/replace/galleries belong to voodflow/vmedia, which is a hard
        // requirement — see ensureMediaRuntime().
    }

    /**
     * Make sure the media runtime is up, whether or not the host registered its panel plugin.
     *
     * The editor cannot open without an upload endpoint: EditorGate resolves the URL at boot
     * and previously 500'd `?edit=1` when the route was missing, so the page never mounted
     * and saves could not persist. Registering VmediaPlugin on a Filament panel is what
     * normally activates those routes, but that is a panel concern and the editor is not —
     * a host can reasonably keep the media resources out of the sidebar and still expect
     * the canvas to accept an image.
     *
     * Activating is idempotent, so doing it here simply removes the ordering question.
     */
    protected function ensureMediaRuntime(): void
    {
        // Deferred: Vmedia registers its HTTP routes outside Filament's route group, and
        // calling it mid-boot is how route names ended up nested under `filament.`.
        $this->app->booted(static function (): void {
            // The route, not Vmedia::isActive(). The active flag is static and outlives an
            // application refresh, so between tests it reads true while the router has been
            // emptied — and the early return then left the editor with no upload endpoint.
            if (Route::has('vmedia.media.upload')) {
                return;
            }

            // Clears that same stale flag, which would otherwise make activate() a no-op.
            Vmedia::reset();
            Vmedia::activate();
        });
    }

    protected function configureEditorRateLimiters(): void
    {
        RateLimiter::for(
            'voodbuilder-editor',
            static function (Request $request): Limit {
                return Limit::perMinute(120)
                    ->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
            },
        );

        RateLimiter::for(
            'voodbuilder-compile-css',
            static function (Request $request): Limit {
                return Limit::perMinute(180)
                    ->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
            },
        );
    }

    protected function registerAdminRoutes(): void
    {
        // Menu preview routes are owned by MenusModule.
    }

    protected function registerEditorBlocks(): void
    {
        $this->app->booted(function (): void {
            $registry = $this->app->make(EditorBlockRegistry::class);

            if (config('voodbuilder.editor.site_blocks.header_footer', true)) {
                $serverRegistry = $this->app->make(EditorServerBlockRegistry::class);
                foreach (SiteNavBlocks::blockClasses() as $navBlockClass) {
                    $serverRegistry->register('Site', $navBlockClass);
                }

                foreach (SiteFooterBlocks::blockClasses() as $footerBlockClass) {
                    $serverRegistry->register('Site', $footerBlockClass);
                }

                // Check console first: Schema::hasTable() connects to DB and fails on host builds (DB_HOST=mysql).
                if (! $this->app->runningInConsole() && $this->schemaHasTable('voodbuilder_settings')) {
                    $serverRegistry->registerEditorBlocks($registry);
                }
            }

            if (config('voodbuilder.editor.include_landing_blocks', false)) {
                VoodbuilderLandingEditorBlocks::register();
            }

            VoodbuilderLanding01Sections::registerBlocks();
            VoodbuilderLanding02Sections::registerBlocks();
            VoodbuilderMediaSections::registerBlocks();

            if (config('voodbuilder.editor.sections.enabled', true) && ! $this->app->runningInConsole()) {
                VoodbuilderSectionEditorBlocks::register($registry);
            }
        });
    }

    protected function registerEditorBindings(): void
    {
        $this->app->booted(function (): void {
            BuiltinBindingSources::register($this->app->make(BindingRegistry::class));
            // Model-integration sources are owned by DynamicDataModule.
        });
    }

    /**
     * Safe Schema::hasTable that returns false when the database is unreachable
     * (e.g. host npm build with DB_HOST=mysql resolving only inside Docker).
     */
    protected function schemaHasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function registerInternalModules(): void
    {
        $registry = $this->app->make(ModuleRegistry::class);

        $registry->register(
            new HistoryModule,
            enabled: (bool) config('voodbuilder.modules.history.enabled', true),
        );

        $registry->register(
            new ConditionsModule,
            enabled: (bool) config('voodbuilder.modules.conditions.enabled', true),
        );

        $registry->register(
            new TemplatesModule,
            enabled: (bool) config('voodbuilder.modules.templates.enabled', true),
        );

        $registry->register(
            new ThemesModule,
            enabled: (bool) config('voodbuilder.modules.themes.enabled', true),
        );

        $registry->register(
            new MenusModule,
            enabled: (bool) config('voodbuilder.modules.menus.enabled', true),
        );

        $registry->register(
            new LayoutsModule,
            enabled: (bool) config('voodbuilder.modules.layouts.enabled', true),
        );

        $registry->register(
            new PagesModule,
            enabled: (bool) config('voodbuilder.modules.pages.enabled', true)
                && (bool) config('voodbuilder.pages.enabled', true),
        );

        // Dynamic Data lives in voodflow/voodbuilder-dynamic-data (Filament plugin).

        // Components live in voodflow/voodbuilder-components (Filament plugin) and
        // register via Voodbuilder::registerModule() during Application::booting.

        // Popups live in voodflow/vpopups (Filament plugin) and
        // register via Voodbuilder::registerModule() during Application::booting.
    }
}
