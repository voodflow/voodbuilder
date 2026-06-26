<?php

declare(strict_types=1);

namespace Voodflow\Vpress;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use RalphJSmit\Laravel\SEO\Facades\SEOManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Voodflow\Vpress\Console\BuildTailblocksCommand;
use Voodflow\Vpress\Console\CompileThemeAssetsCommand;
use Voodflow\Vpress\Console\InstallCommand;
use Voodflow\Vpress\Console\MakeSubThemeCommand;
use Voodflow\Vpress\Console\SeedSoundmitGrapesLandingCommand;
use Voodflow\Vpress\Console\SubThemeCommand;
use Voodflow\Vpress\Console\SyncThemeStylesheetImportsCommand;
use Voodflow\Vpress\Console\ThemePresetCommand;
use Voodflow\Vpress\Filament\Livewire\ThemeMapBridge;
use Voodflow\Vpress\Filament\Livewire\ThemesWorkspace;
use Voodflow\Vpress\Filament\RichContent\CustomBlocks\FeaturesGridBlock;
use Voodflow\Vpress\Filament\RichContent\CustomBlocks\HeroBlock;
use Voodflow\Vpress\Filament\RichContent\CustomBlocks\PackagePromosBlock;
use Voodflow\Vpress\Filament\RichContent\CustomBlocks\PartnerBannerBlock;
use Voodflow\Vpress\Filament\RichContent\CustomBlocks\ProductPromoBlock;
use Voodflow\Vpress\Http\Controllers\GrapesJsAssetController;
use Voodflow\Vpress\Http\Controllers\GrapesJsBlocksController;
use Voodflow\Vpress\Http\Controllers\GrapesJsPageController;
use Voodflow\Vpress\Http\Middleware\ApplyVpressSiteConfig;
use Voodflow\Vpress\Livewire\AccountSettings;
use Voodflow\Vpress\Livewire\SiteNotificationBell;
use Voodflow\Vpress\Support\ContentChannelRegistry;
use Voodflow\Vpress\Support\GrapesJs\DefaultGrapesJsBlocks;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Vpress\Support\GrapesJs\TailblocksGrapesJsBlocks;
use Voodflow\Vpress\Support\GrapesJs\VpressLandingGrapesJsBlocks;
use Voodflow\Vpress\Support\RegisterFilamentCookieConsentTranslations;
use Voodflow\Vpress\Support\RichContentBlockRegistry;
use Voodflow\Vpress\Support\SitePagesContentChannel;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Support\ThemeMapAssets;
use Voodflow\Vpress\Support\VpressLandingBlocks;
use Voodflow\Vpress\Support\VpressSeo;

class VpressServiceProvider extends PackageServiceProvider
{
    public static string $name = 'vpress';

    public static string $viewNamespace = 'vpress';

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
            ->hasCommand(BuildTailblocksCommand::class)
            ->hasCommand(SeedSoundmitGrapesLandingCommand::class)
            ->hasCommand(ThemePresetCommand::class)
            ->hasCommand(SubThemeCommand::class)
            ->hasCommand(SyncThemeStylesheetImportsCommand::class)
            ->hasCommand(CompileThemeAssetsCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RichContentBlockRegistry::class);
        $this->app->singleton(GrapesJsBlockRegistry::class);
        $this->app->singleton(GrapesJsDynamicBlockRegistry::class);
        $this->app->singleton(SubThemeRegistry::class);
        $this->app->singleton(ContentChannelRegistry::class);
    }

    public function packageBooted(): void
    {
        RegisterFilamentCookieConsentTranslations::apply();

        $this->app->make(SubThemeRegistry::class)->bootFromConfig();
        $this->app->make(ContentChannelRegistry::class)->bootFromConfig();

        if (config('vpress.pages.enabled', true)) {
            Vpress::contentChannel('pages', new SitePagesContentChannel);
        }

        View::replaceNamespace('cookie-consent', [
            __DIR__.'/../resources/views/cookie-consent',
        ]);

        Blade::componentNamespace('Voodflow\\Vpress\\Components', 'vpress');

        Livewire::component('vpress.site-notification-bell', SiteNotificationBell::class);
        Livewire::component('vpress.account-settings', AccountSettings::class);
        Livewire::component('vpress.themes-workspace', ThemesWorkspace::class);
        Livewire::component('vpress.theme-map-bridge', ThemeMapBridge::class);

        ThemeMapAssets::register();

        if (config('vpress.grapesjs.enabled', true)) {
            $this->registerGrapesJsRoutes();
            $this->registerGrapesJsBlocks();
        }

        SEOManager::SEODataTransformer(static function ($seoData) {
            return VpressSeo::applyDefaults($seoData);
        });

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->pushMiddlewareToGroup('web', ApplyVpressSiteConfig::class);

        $registry = $this->app->make(RichContentBlockRegistry::class);

        $registry
            ->register('Layout', HeroBlock::class)
            ->register('Layout', FeaturesGridBlock::class)
            ->register('Layout', PartnerBannerBlock::class)
            ->register('Layout', ProductPromoBlock::class)
            ->register('Layout', PackagePromosBlock::class);

        foreach (VpressLandingBlocks::blockClasses() as $blockClass) {
            $registry->register('Landing', $blockClass);
        }
    }

    protected function registerGrapesJsRoutes(): void
    {
        Route::middleware(['web', 'auth', 'throttle:60,1'])
            ->prefix('vpress/grapesjs')
            ->name('vpress.grapesjs.')
            ->group(function (): void {
                Route::get('blocks', GrapesJsBlocksController::class)->name('blocks');
                Route::post('upload', [GrapesJsAssetController::class, 'store'])->name('upload');
                Route::put('pages/{sitePage}', [GrapesJsPageController::class, 'update'])->name('pages.update');
            });
    }

    protected function registerGrapesJsBlocks(): void
    {
        $this->app->booted(function (): void {
            $registry = $this->app->make(GrapesJsBlockRegistry::class);

            if (config('vpress.grapesjs.tailblocks.enabled', true)) {
                TailblocksGrapesJsBlocks::register($registry);
            }

            if (config('vpress.grapesjs.include_vpress_blocks', true)) {
                DefaultGrapesJsBlocks::register($registry);
            }

            if (config('vpress.grapesjs.include_landing_blocks', true)) {
                VpressLandingGrapesJsBlocks::register();
            }

            $this->app->make(GrapesJsDynamicBlockRegistry::class)->registerEditorBlocks($registry);
        });
    }
}
