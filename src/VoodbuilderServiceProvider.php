<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use RalphJSmit\Laravel\SEO\Facades\SEOManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Voodflow\Voodbuilder\Console\BuildSectionsCommand;
use Voodflow\Voodbuilder\Console\CompileThemeAssetsCommand;
use Voodflow\Voodbuilder\Console\InstallCommand;
use Voodflow\Voodbuilder\Console\MakeSubThemeCommand;
use Voodflow\Voodbuilder\Console\SeedSoundmitGrapesLandingCommand;
use Voodflow\Voodbuilder\Console\SubThemeCommand;
use Voodflow\Voodbuilder\Console\SyncNpmDepsCommand;
use Voodflow\Voodbuilder\Console\SyncThemeStylesheetImportsCommand;
use Voodflow\Voodbuilder\Console\ThemePresetCommand;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\FeaturesGridBlock;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\HeroBlock;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\PackagePromosBlock;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\PartnerBannerBlock;
use Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks\ProductPromoBlock;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsAssetController;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsBlockRenderController;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsBlocksController;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsCodeHighlightController;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsLinkTargetsController;
use Voodflow\Voodbuilder\Http\Controllers\GrapesJsMediaPreviewController;
use Voodflow\Voodbuilder\Http\Middleware\ApplyVoodbuilderSiteConfig;
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
use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
use Voodflow\Voodbuilder\Support\FilamentAdminAssets;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingImageResolverRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BuiltinBindingSources;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationBindingRegistrar;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\ModelIntegrationRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsServerBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterBlocks;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteNavBlocks;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderLanding01Sections;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderLanding02Sections;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderLanding03Sections;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderLandingGrapesJsBlocks;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderMediaSections;
use Voodflow\Voodbuilder\Support\GrapesJs\VoodbuilderSectionGrapesJsBlocks;
use Voodflow\Voodbuilder\Support\IntegrationRegistrar;
use Voodflow\Voodbuilder\Support\MenuItemTypeRegistry;
use Voodflow\Voodbuilder\Support\ModelRegistry;
use Voodflow\Voodbuilder\Support\RegisterFilamentCookieConsentTranslations;
use Voodflow\Voodbuilder\Support\ReverseRelationRegistry;
use Voodflow\Voodbuilder\Support\RichContentBlockRegistry;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Support\VoodbuilderLandingBlocks;
use Voodflow\Voodbuilder\Support\VoodbuilderSeo;

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
            ->hasCommand(SeedSoundmitGrapesLandingCommand::class)
            ->hasCommand(ThemePresetCommand::class)
            ->hasCommand(SubThemeCommand::class)
            ->hasCommand(SyncThemeStylesheetImportsCommand::class)
            ->hasCommand(SyncNpmDepsCommand::class)
            ->hasCommand(CompileThemeAssetsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RichContentBlockRegistry::class);
        $this->app->singleton(GrapesJsBlockRegistry::class);
        $this->app->singleton(GrapesJsDynamicBlockRegistry::class);
        $this->app->singleton(GrapesJsServerBlockRegistry::class);
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
        $this->app->singleton(MenuItemTypeRegistry::class);
        $this->app->singleton(ModuleRegistry::class);
        $this->app->singleton(EntitlementManager::class, function (): EntitlementManager {
            return new EntitlementManager(EntitlementProviderFactory::make());
        });
    }

    public function packageBooted(): void
    {
        RegisterFilamentCookieConsentTranslations::apply();

        Relation::morphMap([
            'site_page' => SitePage::class,
        ]);

        Gate::policy(ModelIntegration::class, ModelIntegrationPolicy::class);

        $this->app->make(SubThemeRegistry::class)->bootFromConfig();
        $this->app->make(ContentChannelRegistry::class)->bootFromConfig();
        IntegrationRegistrar::boot();

        View::replaceNamespace('cookie-consent', [
            __DIR__.'/../resources/views/cookie-consent',
        ]);

        // Upstream drag handle span is empty; our node-row adds a visible grip icon.
        View::prependNamespace('filament-nestable-tree', [
            __DIR__.'/../resources/views/vendor/filament-nestable-tree',
        ]);

        Blade::componentNamespace('Voodflow\\Voodbuilder\\Components', 'voodbuilder');

        Livewire::component('voodbuilder.site-notification-bell', SiteNotificationBell::class);
        Livewire::component('voodbuilder.account-settings', AccountSettings::class);

        BrandMarkAssets::ensurePublished();
        FilamentAdminAssets::register();

        if (config('voodbuilder.grapesjs.enabled', true)) {
            $this->registerGrapesJsRoutes();
            $this->registerGrapesJsBlocks();
            $this->registerGrapesJsBindings();
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

    protected function registerGrapesJsRoutes(): void
    {
        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('voodbuilder/grapesjs')
            ->name('voodbuilder.grapesjs.')
            ->group(function (): void {
                Route::get('blocks', GrapesJsBlocksController::class)->name('blocks');
                Route::get('link-targets', GrapesJsLinkTargetsController::class)->name('link-targets');
                Route::get('media/{media}', GrapesJsMediaPreviewController::class)->name('media.preview');
                Route::get('blocks/render', GrapesJsBlockRenderController::class)->name('blocks.render');
                Route::post('code/highlight', GrapesJsCodeHighlightController::class)->name('code.highlight');
                Route::post('upload', [GrapesJsAssetController::class, 'store'])->name('upload');
            });
    }

    protected function registerAdminRoutes(): void
    {
        // Menu preview routes are owned by MenusModule.
    }

    protected function registerGrapesJsBlocks(): void
    {
        $this->app->booted(function (): void {
            $registry = $this->app->make(GrapesJsBlockRegistry::class);

            if (config('voodbuilder.grapesjs.site_blocks.header_footer', true)) {
                $serverRegistry = $this->app->make(GrapesJsServerBlockRegistry::class);
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

            if (config('voodbuilder.grapesjs.include_landing_blocks', false)) {
                VoodbuilderLandingGrapesJsBlocks::register();
            }

            VoodbuilderLanding01Sections::registerBlocks();
            VoodbuilderLanding02Sections::registerBlocks();
            VoodbuilderLanding03Sections::registerBlocks();
            VoodbuilderMediaSections::registerBlocks();

            if (config('voodbuilder.grapesjs.sections.enabled', true) && ! $this->app->runningInConsole()) {
                VoodbuilderSectionGrapesJsBlocks::register($registry);
            }
        });
    }

    protected function registerGrapesJsBindings(): void
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

        // Popups live in voodflow/voodbuilder-popups (Filament plugin) and
        // register via Voodbuilder::registerModule() during Application::booting.
    }
}
