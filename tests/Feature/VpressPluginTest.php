<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Feature;

use ReflectionClass;
use Voodflow\Vpress\Filament\Pages\VpressSettingsPage;
use Voodflow\Vpress\Filament\Resources\NavigationMenuResource;
use Voodflow\Vpress\Filament\Resources\SitePageResource;
use Voodflow\Vpress\VpressPlugin;
use Voodflow\Vpress\Tests\TestCase;

class VpressPluginTest extends TestCase
{
    public function test_plugin_id_is_vpress(): void
    {
        $this->assertSame('vpress', VpressPlugin::make()->getId());
    }

    public function test_filament_navigation_group_is_vpress(): void
    {
        app()->setLocale('en');

        $this->assertSame('Vpress', NavigationMenuResource::getNavigationGroup());
        $this->assertSame('Menus', NavigationMenuResource::getNavigationLabel());
        $this->assertSame('Vpress', SitePageResource::getNavigationGroup());
        $this->assertSame('Pages', SitePageResource::getNavigationLabel());
        $this->assertSame('Vpress', VpressSettingsPage::getNavigationGroup());
        $this->assertSame('Settings', VpressSettingsPage::getNavigationLabel());
    }

    public function test_filament_navigation_sort_order_is_menus_pages_settings(): void
    {
        $this->assertSame(1, $this->navigationSort(NavigationMenuResource::class));
        $this->assertSame(2, $this->navigationSort(SitePageResource::class));
        $this->assertSame(3, $this->navigationSort(VpressSettingsPage::class));
    }

    protected function navigationSort(string $class): ?int
    {
        $property = (new ReflectionClass($class))->getProperty('navigationSort');
        $property->setAccessible(true);

        return $property->getValue();
    }
}
