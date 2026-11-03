<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Http\Controllers\HomeController;
use Voodflow\Voodbuilder\Http\Controllers\SitePageController;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\NavProfileMenu;
use Voodflow\Voodbuilder\Support\SiteLanguageSwitcher;
use Voodflow\Voodbuilder\Tests\TestCase;

/**
 * A site that only installs VoodBuilder still manages two languages, so the header
 * needs a switcher even when `voodflow/vtuts` is missing.
 */
class SiteLanguageSwitcherTest extends TestCase
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

        // Language switcher without the vtuts package options.
        $app['config']->set('vtuts.features.localization', null);
        $app['config']->set('voodbuilder.home.route_enabled', true);
    }

    protected function defineWebRoutes($router): void
    {
        $router->get('/', HomeController::class)->name('home');
        $router->get('pages/{slug}', [SitePageController::class, 'show'])->name('voodbuilder.pages.show');
        $router->get('locale-probe', static fn (): string => (string) app()->getLocale())->name('locale.probe');
    }

    #[Test]
    public function it_is_visible_when_the_site_has_translated_pages(): void
    {
        $this->assertTrue(SiteLanguageSwitcher::enabled());
        $this->assertTrue(SiteLanguageSwitcher::visible());
        $this->assertSame(['it' => 'Italiano', 'en' => 'English'], SiteLanguageSwitcher::options());
        $this->assertSame('it', SiteLanguageSwitcher::current());
    }

    #[Test]
    public function the_admin_toggle_hides_it(): void
    {
        VoodbuilderSettings::saveData(['show_language_switcher' => false]);

        $this->assertFalse(SiteLanguageSwitcher::visible());
    }

    #[Test]
    public function it_points_to_the_linked_translation_of_the_current_page(): void
    {
        $group = '66666666-6666-6666-6666-666666666666';

        SitePage::query()->create([
            'title' => 'Contatti',
            'slug' => 'contatti',
            'locale' => 'it',
            'translation_group_id' => $group,
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        SitePage::query()->create([
            'title' => 'Contact',
            'slug' => 'contact',
            'locale' => 'en',
            'translation_group_id' => $group,
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('voodbuilder.pages.show', ['slug' => 'contatti']));

        $this->assertStringContainsString('contact', SiteLanguageSwitcher::urlFor('en'));
        $this->assertStringContainsString('contatti', SiteLanguageSwitcher::urlFor('it'));
    }

    #[Test]
    public function an_untranslated_page_keeps_its_url_and_switches_the_locale_query(): void
    {
        SitePage::query()->create([
            'title' => 'Laboratori',
            'slug' => 'laboratori',
            'locale' => 'it',
            'content' => [],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        $this->get(route('voodbuilder.pages.show', ['slug' => 'laboratori']));

        $url = SiteLanguageSwitcher::urlFor('en');

        $this->assertStringContainsString('laboratori', $url);
        $this->assertStringContainsString('locale=en', $url);
    }

    #[Test]
    public function the_locale_query_switches_the_application_locale(): void
    {
        $this->get(route('locale.probe', ['locale' => 'en']))
            ->assertOk()
            ->assertSee('en');

        // The choice survives the following request through the session.
        $this->get(route('locale.probe'))
            ->assertOk()
            ->assertSee('en');
    }

    #[Test]
    public function it_renders_the_native_switcher_without_vtuts(): void
    {
        $this->assertTrue(NavProfileMenu::languageSwitcherVisible());

        $html = Blade::render('<x-voodbuilder::optional-language-switcher variant="dropdown" :labeled="true" />');

        $this->assertStringContainsString('data-voodbuilder-language-switcher', $html);
        $this->assertStringContainsString('hreflang="en"', $html);
        $this->assertStringContainsString('aria-current="true"', $html);
    }
}
