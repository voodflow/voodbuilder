<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Navigation;
use Voodflow\Voodbuilder\Support\NavigationMenuResolver;
use Voodflow\Voodbuilder\Support\NavigationMenuTranslation;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;
use Voodflow\Voodbuilder\Tests\TestCase;

/**
 * Content translations are a VoodBuilder feature: `voodflow/vtuts` only owns locale
 * routing, so a site without it must still get the translate page/menu actions.
 */
class ContentLocalizationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.locale', 'it');
        $app['config']->set('app.locales', [
            'it' => 'Italiano',
            'en' => 'English',
        ]);
        $app['config']->set('app.default_locale', 'it');

        // A site that manages languages without voodflow/vtuts.
        $app['config']->set('vtuts.features.localization', null);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Navigation::clearCache();
    }

    #[Test]
    public function content_localization_only_needs_locales_and_columns(): void
    {
        $this->assertTrue(SitePageResolver::localizationEnabled());
        $this->assertTrue(NavigationMenuResolver::localizationEnabled());
    }

    #[Test]
    public function locale_routing_stays_a_vtuts_feature(): void
    {
        $this->assertFalse(SitePageResolver::localeRoutingEnabled());
        $this->assertSame(url('/'), VoodbuilderUrls::home());
    }

    #[Test]
    public function the_other_locale_is_offered_for_menu_translations(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Main',
            'slug' => 'main',
            'locale' => 'en',
        ]);

        $targets = NavigationMenuTranslation::availableTargetLocales($menu);

        $this->assertSame(['it' => 'Italiano'], $targets);
    }

    #[Test]
    public function a_menu_stored_under_another_locale_keeps_rendering(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Main',
            'slug' => 'main',
            'locale' => 'en',
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Home',
            'type' => MenuItemType::Url,
            'link' => '/',
            'sort_order' => 0,
        ]);

        $this->assertSame($menu->id, NavigationMenuResolver::forPlacement('main')?->getKey());
        $this->assertSame('Home', Navigation::items('main')->first()?->label);
    }

    #[Test]
    public function the_preferred_locale_wins_once_it_exists(): void
    {
        $english = NavigationMenu::query()->create([
            'name' => 'Main EN',
            'slug' => 'main',
            'locale' => 'en',
        ]);

        $italian = NavigationMenu::query()->create([
            'name' => 'Main IT',
            'slug' => 'main',
            'locale' => 'it',
        ]);

        $this->assertSame($italian->id, NavigationMenuResolver::forPlacement('main')?->getKey());
        $this->assertSame($english->id, NavigationMenuResolver::forPlacement('main', 'en')?->getKey());
    }

    #[Test]
    public function editing_a_fallback_menu_invalidates_the_visitor_locale_cache(): void
    {
        $menu = NavigationMenu::query()->create([
            'name' => 'Main',
            'slug' => 'main',
            'locale' => 'en',
        ]);

        $item = NavigationMenuItem::query()->create([
            'menu_id' => $menu->id,
            'label' => 'Home',
            'type' => MenuItemType::Url,
            'link' => '/',
            'sort_order' => 0,
        ]);

        $this->assertSame('Home', Navigation::items('main')->first()?->label);

        $item->update(['label' => 'Casa']);

        // What the admin tree actions do after a save: invalidate the menu's own locale.
        Navigation::clearCache($menu->slug, $menu->locale);

        $this->assertSame('Casa', Navigation::items('main')->first()?->label);
    }

    #[Test]
    public function page_options_fall_back_when_the_menu_locale_has_no_pages(): void
    {
        SitePage::query()->create([
            'title' => 'Laboratori',
            'slug' => 'laboratori',
            'builder' => PageBuilder::Visual,
            'locale' => 'it',
            'published' => true,
        ]);

        $method = new ReflectionMethod(NavigationMenuResource::class, 'sitePageOptions');

        $this->assertArrayHasKey('laboratori', $method->invoke(null, null));
        $this->assertArrayHasKey('laboratori', $method->invoke(null, 'it'));
        $this->assertArrayHasKey('laboratori', $method->invoke(null, 'en'));
    }
}
