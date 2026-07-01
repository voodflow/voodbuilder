<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Voodflow\Voodbuilder\Http\Controllers\HomeController;
use Voodflow\Voodbuilder\Http\Controllers\SitePageController;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\Support\SitePageTranslation;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Vtuts\Support\Locales;
use Voodflow\Vtuts\Support\LocaleSwitcher;

class SitePageTranslationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $vtutsViews = dirname(__DIR__, 2).'/../vtuts/resources/views';

        if (is_dir($vtutsViews)) {
            View::addNamespace('vtuts', $vtutsViews);
        }
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('vtuts.locales', [
            'en' => 'English',
            'it' => 'Italiano',
        ]);
        $app['config']->set('vtuts.default_locale', 'en');
        $app['config']->set('vtuts.features.localization', true);
        $app['config']->set('voodbuilder.home.route_enabled', true);
    }

    protected function defineWebRoutes($router): void
    {
        $router->get('/', HomeController::class)->name('home');
        $router->get('pages/{slug}', [SitePageController::class, 'show'])->name('voodbuilder.pages.show');
    }

    public function test_it_creates_linked_translation_with_shared_group(): void
    {
        $english = SitePage::query()->create([
            'title' => 'About us',
            'slug' => 'about-us',
            'locale' => 'en',
            'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Hello']]]],
            'layout' => 'page',
            'builder' => 'rich_editor',
            'published' => true,
            'published_at' => now(),
        ]);

        $italian = SitePageTranslation::createFrom($english, 'it');

        $this->assertNotSame($english->getKey(), $italian->getKey());
        $this->assertSame($english->translation_group_id, $italian->translation_group_id);
        $this->assertSame('it', $italian->locale);
        $this->assertFalse($italian->published);
        $this->assertSame('about-us', $italian->slug);
        $this->assertSame('About us', $italian->title);
    }

    public function test_menu_resolution_returns_linked_translation_for_current_locale(): void
    {
        $group = '11111111-1111-1111-1111-111111111111';

        SitePage::query()->create([
            'title' => 'About EN',
            'slug' => 'about',
            'locale' => 'en',
            'translation_group_id' => $group,
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        SitePage::query()->create([
            'title' => 'Chi siamo',
            'slug' => 'chi-siamo',
            'locale' => 'it',
            'translation_group_id' => $group,
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        app()->setLocale('it');

        $resolved = SitePageResolver::publishedForMenu('about');

        $this->assertNotNull($resolved);
        $this->assertSame('it', $resolved->locale);
        $this->assertSame('chi-siamo', $resolved->slug);
    }

    public function test_language_switcher_points_to_linked_translation_url(): void
    {
        $group = '22222222-2222-2222-2222-222222222222';

        $english = SitePage::query()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'locale' => 'en',
            'translation_group_id' => $group,
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        $italian = SitePage::query()->create([
            'title' => 'Contatti',
            'slug' => 'contatti',
            'locale' => 'it',
            'translation_group_id' => $group,
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('voodbuilder.pages.show', ['slug' => 'contact']));

        $url = LocaleSwitcher::urlFor('it');

        $this->assertStringContainsString('/pages/contatti', $url);
        $this->assertNotSame($english->getUrl(), $italian->getUrl());
    }

    public function test_home_urls_carry_locale_query_for_non_default_language(): void
    {
        $englishUrl = VoodbuilderUrls::home('en');
        $italianUrl = VoodbuilderUrls::home('it');

        $this->assertStringContainsString('locale=en', $englishUrl);
        $this->assertStringContainsString('locale=it', $italianUrl);
    }

    public function test_home_language_switcher_points_to_linked_home_with_locale_query(): void
    {
        $group = '44444444-4444-4444-4444-444444444444';

        SitePage::query()->create([
            'title' => 'Home EN',
            'slug' => 'home-en',
            'locale' => 'en',
            'translation_group_id' => $group,
            'content' => $this->richParagraph('Welcome'),
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        SitePage::query()->create([
            'title' => 'Home IT',
            'slug' => 'home-it',
            'locale' => 'it',
            'translation_group_id' => $group,
            'content' => $this->richParagraph('Benvenuto'),
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        $this->get('/');

        $url = LocaleSwitcher::urlFor('it');

        $this->assertStringContainsString('locale=it', $url);
        $this->assertSame(VoodbuilderUrls::home('it'), $url);
    }

    /** @return array<string, mixed> */
    private function richParagraph(string $text): array
    {
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => $text],
                    ],
                ],
            ],
        ];
    }

    public function test_each_locale_can_have_its_own_home_page(): void
    {
        $group = '33333333-3333-3333-3333-333333333333';

        SitePage::query()->create([
            'title' => 'Home EN',
            'slug' => 'home-en',
            'locale' => 'en',
            'translation_group_id' => $group,
            'content' => $this->richParagraph('Welcome'),
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        SitePage::query()->create([
            'title' => 'Home IT',
            'slug' => 'home-it',
            'locale' => 'it',
            'translation_group_id' => $group,
            'content' => $this->richParagraph('Benvenuto'),
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        app()->setLocale('en');

        $this->assertSame('Home EN', SitePage::homePage()?->title);

        app()->setLocale('it');

        $this->assertSame('Home IT', SitePage::homePage()?->title);
    }

    public function test_slug_is_unique_per_locale_not_globally(): void
    {
        $indexNames = collect(Schema::getIndexes('site_pages'))
            ->pluck('name')
            ->all();

        $this->assertContains(
            'site_pages_slug_locale_unique',
            $indexNames,
            'Expected composite slug+locale index, found: '.implode(', ', $indexNames),
        );
        $this->assertNotContains('site_pages_slug_unique', $indexNames);

        SitePage::query()->create([
            'title' => 'Privacy EN',
            'slug' => 'privacy',
            'locale' => 'en',
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        SitePage::query()->create([
            'title' => 'Privacy IT',
            'slug' => 'privacy',
            'locale' => 'it',
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        $this->assertSame(2, SitePage::query()->where('slug', 'privacy')->count());
        $this->assertTrue(Locales::isValid('it'));
    }

    public function test_missing_home_for_locale_redirects_to_default_locale_home(): void
    {
        SitePage::query()->create([
            'title' => 'Home EN',
            'slug' => 'home-en',
            'locale' => 'en',
            'content' => $this->richParagraph('Welcome'),
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        $response = $this->get('/?locale=it');

        $response->assertRedirect(VoodbuilderUrls::home('en'));
    }

    public function test_other_translation_locale_codes_exclude_current_language(): void
    {
        $group = '55555555-5555-5555-5555-555555555555';

        $english = SitePage::query()->create([
            'title' => 'Demo EN',
            'slug' => 'demo-en',
            'locale' => 'en',
            'translation_group_id' => $group,
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        $italian = SitePage::query()->create([
            'title' => 'Demo IT',
            'slug' => 'demo-it',
            'locale' => 'it',
            'translation_group_id' => $group,
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        $this->assertSame(['IT'], $english->otherTranslationLocaleCodes());
        $this->assertSame(['EN'], $italian->otherTranslationLocaleCodes());
        $this->assertSame([], SitePage::query()->create([
            'title' => 'Solo EN',
            'slug' => 'solo-en',
            'locale' => 'en',
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ])->otherTranslationLocaleCodes());
    }

    public function test_language_switcher_marks_unavailable_locale_on_untranslated_page(): void
    {
        SitePage::query()->create([
            'title' => 'About EN',
            'slug' => 'about',
            'locale' => 'en',
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('voodbuilder.pages.show', ['slug' => 'about']));

        $this->assertTrue(LocaleSwitcher::isAvailableFor('en'));
        $this->assertFalse(LocaleSwitcher::isAvailableFor('it'));
    }

    public function test_language_switcher_marks_unavailable_home_locale_without_translation(): void
    {
        SitePage::query()->create([
            'title' => 'Home EN',
            'slug' => 'home-en',
            'locale' => 'en',
            'content' => $this->richParagraph('Welcome'),
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);

        $this->get('/');

        $this->assertTrue(LocaleSwitcher::isAvailableFor('en'));
        $this->assertFalse(LocaleSwitcher::isAvailableFor('it'));
    }
}
