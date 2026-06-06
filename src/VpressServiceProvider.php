<?php

declare(strict_types=1);

namespace Voodflow\Vpress;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use RalphJSmit\Laravel\SEO\Facades\SEOManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Voodflow\Vpress\Console\InstallCommand;
use Voodflow\Vpress\Filament\RichContent\CustomBlocks\FeaturesGridBlock;
use Voodflow\Vpress\Filament\RichContent\CustomBlocks\HeroBlock;
use Voodflow\Vpress\Filament\RichContent\CustomBlocks\PartnerBannerBlock;
use Voodflow\Vpress\Http\Middleware\ApplyVpressSiteConfig;
use Voodflow\Vpress\Livewire\AccountSettings;
use Voodflow\Vpress\Livewire\SiteNotificationBell;
use Voodflow\Vpress\Support\RichContentBlockRegistry;
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
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(RichContentBlockRegistry::class);
    }

    public function packageBooted(): void
    {
        View::replaceNamespace('cookie-consent', [
            __DIR__.'/../resources/views/cookie-consent',
        ]);

        Blade::componentNamespace('Voodflow\\Vpress\\Components', 'vpress');

        View::composer('vpress::layouts.app', function ($view): void {
            $view->with(
                'vpressHasDocSidebar',
                str_contains(
                    (string) $view->getFactory()->getSection('body_class', ''),
                    'vpress-has-doc-sidebar'
                )
            );
        });

        Livewire::component('vpress.site-notification-bell', SiteNotificationBell::class);
        Livewire::component('vpress.account-settings', AccountSettings::class);

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
            ->register('Layout', PartnerBannerBlock::class);
    }
}
