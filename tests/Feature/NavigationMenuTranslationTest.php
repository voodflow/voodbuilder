<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Navigation;
use Voodflow\Voodbuilder\Support\NavigationMenuTranslation;
use Voodflow\Voodbuilder\Tests\TestCase;

class NavigationMenuTranslationTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('vtuts.locales', [
            'en' => 'English',
            'it' => 'Italiano',
        ]);
        $app['config']->set('vtuts.default_locale', 'en');
        $app['config']->set('vtuts.features.localization', true);
    }

    #[Test]
    public function test_it_creates_linked_menu_translation_with_items(): void
    {
        $englishMenu = NavigationMenu::query()->create([
            'name' => 'Main EN',
            'slug' => 'main',
            'locale' => 'en',
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $englishMenu->id,
            'label' => 'Home',
            'type' => MenuItemType::Url,
            'link' => '/',
            'sort_order' => 0,
        ]);

        $italianMenu = NavigationMenuTranslation::createFrom($englishMenu, 'it');

        $this->assertSame($englishMenu->translation_group_id, $italianMenu->translation_group_id);
        $this->assertSame('main', $italianMenu->slug);
        $this->assertSame('it', $italianMenu->locale);
        $this->assertCount(1, $italianMenu->rootItems);
        $this->assertSame('Home', $italianMenu->rootItems->first()?->label);
    }

    #[Test]
    public function test_it_maps_page_links_to_target_locale_slug(): void
    {
        $group = (string) Str::uuid();

        SitePage::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'locale' => 'en',
            'translation_group_id' => $group,
            'published' => true,
        ]);

        SitePage::query()->create([
            'title' => 'Chi siamo',
            'slug' => 'chi-siamo',
            'locale' => 'it',
            'translation_group_id' => $group,
            'published' => true,
        ]);

        $englishMenu = NavigationMenu::query()->create([
            'name' => 'Main EN',
            'slug' => 'main',
            'locale' => 'en',
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $englishMenu->id,
            'label' => 'About us',
            'type' => MenuItemType::Page,
            'link' => 'about',
            'sort_order' => 0,
        ]);

        $italianMenu = NavigationMenuTranslation::createFrom($englishMenu, 'it');

        $this->assertSame('chi-siamo', $italianMenu->rootItems->first()?->link);
    }

    #[Test]
    public function test_navigation_loads_menu_for_current_locale(): void
    {
        NavigationMenu::query()->create([
            'name' => 'Main EN',
            'slug' => 'main',
            'locale' => 'en',
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => NavigationMenu::query()->where('locale', 'en')->where('slug', 'main')->value('id'),
            'label' => 'English',
            'type' => MenuItemType::Url,
            'link' => '/en',
            'sort_order' => 0,
        ]);

        $italianMenuId = NavigationMenu::query()->create([
            'name' => 'Main IT',
            'slug' => 'main',
            'locale' => 'it',
        ])->id;

        NavigationMenuItem::query()->create([
            'menu_id' => $italianMenuId,
            'label' => 'Italiano',
            'type' => MenuItemType::Url,
            'link' => '/it',
            'sort_order' => 0,
        ]);

        Navigation::clearCache('main');

        app()->setLocale('it');

        $items = Navigation::items('main');

        $this->assertCount(1, $items);
        $this->assertSame('Italiano', $items->first()?->label);
    }

    #[Test]
    public function test_it_clones_menu_to_new_placement(): void
    {
        $source = NavigationMenu::query()->create([
            'name' => 'Social',
            'slug' => 'social',
            'locale' => 'en',
        ]);

        NavigationMenuItem::query()->create([
            'menu_id' => $source->id,
            'label' => 'GitHub',
            'type' => MenuItemType::Url,
            'link' => 'https://github.com',
            'sort_order' => 0,
        ]);

        $source->setAttribute('root_items_count', 1);

        $clone = NavigationMenuTranslation::cloneAsDuplicate($source, 'footer', 'Footer links copy');

        $this->assertNotSame($source->translation_group_id, $clone->translation_group_id);
        $this->assertSame('footer', $clone->slug);
        $this->assertSame('en', $clone->locale);
        $this->assertCount(1, $clone->rootItems);
        $this->assertSame('GitHub', $clone->rootItems->first()?->label);
    }
}
