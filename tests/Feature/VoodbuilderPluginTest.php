<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use ReflectionClass;
use Voodflow\Voodbuilder\Filament\Pages\VoodbuilderSettingsPage;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;
use Voodflow\Voodbuilder\Filament\Resources\SitePageResource;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\VoodbuilderPlugin;

class VoodbuilderPluginTest extends TestCase
{
    public function test_plugin_id_is_voodbuilder(): void
    {
        $this->assertSame('voodbuilder', VoodbuilderPlugin::make()->getId());
    }

    public function test_filament_navigation_group_is_voodbuilder(): void
    {
        app()->setLocale('en');

        $this->assertSame('VoodBuilder', NavigationMenuResource::getNavigationGroup());
        $this->assertSame('Menus', NavigationMenuResource::getNavigationLabel());
        $this->assertSame('VoodBuilder', SitePageResource::getNavigationGroup());
        $this->assertSame('Pages', SitePageResource::getNavigationLabel());
        $this->assertSame('VoodBuilder', VoodbuilderSettingsPage::getNavigationGroup());
        $this->assertSame('Settings', VoodbuilderSettingsPage::getNavigationLabel());
    }

    public function test_filament_navigation_sort_order_is_menus_pages_settings(): void
    {
        // Menus (1), Pages (2), Theme Studio (3), Chrome (4), Settings (5), Media (6).
        $this->assertSame(1, $this->navigationSort(NavigationMenuResource::class));
        $this->assertSame(2, $this->navigationSort(SitePageResource::class));
        $this->assertSame(5, $this->navigationSort(VoodbuilderSettingsPage::class));
    }

    protected function navigationSort(string $class): ?int
    {
        $property = (new ReflectionClass($class))->getProperty('navigationSort');
        $property->setAccessible(true);

        return $property->getValue();
    }
}
