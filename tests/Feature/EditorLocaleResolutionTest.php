<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Filament\Facades\Filament;
use Filament\FilamentServiceProvider;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Http\Controllers\HomeController;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Tests\TestCase;

/**
 * The visual editor must resolve the home page of the requested language.
 */
class EditorLocaleResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        EditorGate::authorizeUsing(null);
        PageBuilderAccess::authorizeUsing(null);

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            FilamentServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Filament::registerPanel(
            Panel::make()
                ->id('admin')
                ->path('admin'),
        );

        PageBuilderAccess::authorizeUsing(static fn (): bool => auth()->check());

        EditorGate::authorizeUsing(
            fn (SitePage $page): bool => $page->usesEditorBuilder() && auth()->check(),
        );
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('app.locale', 'it');
        $app['config']->set('app.locales', [
            'it' => 'Italiano',
            'en' => 'English',
        ]);
        $app['config']->set('app.default_locale', 'it');
        $app['config']->set('voodbuilder.home.route_enabled', true);
    }

    protected function defineWebRoutes($router): void
    {
        $router->get('/', HomeController::class)->name('home');
    }

    public function test_editing_the_translated_home_uses_that_page_not_the_default_home(): void
    {
        $italian = $this->homePage('it', 'Home IT');
        $english = $this->homePage('en', 'Home EN');

        $this->actingAs($this->admin());

        $response = $this->get('/?locale=en&edit=1');

        $response->assertOk();
        $this->assertStringContainsString('"pageId":' . $english->getKey(), $response->getContent());
        $this->assertStringNotContainsString('"pageId":' . $italian->getKey() . ',', $response->getContent());
    }

    public function test_the_default_home_still_opens_the_default_language(): void
    {
        $italian = $this->homePage('it', 'Home IT');
        $this->homePage('en', 'Home EN');

        $this->actingAs($this->admin());

        $response = $this->get('/?edit=1');

        $response->assertOk();
        $this->assertStringContainsString('"pageId":' . $italian->getKey(), $response->getContent());
    }

    public function test_the_editor_lists_every_language_and_links_the_translation(): void
    {
        $italian = $this->homePage('it', 'Home IT');
        $english = $this->homePage('en', 'Home EN');

        $this->actingAs($this->admin());

        $config = EditorGate::config($english);
        $switcher = $config['localeSwitcher'];

        $this->assertSame('en', $config['pageLocale']);
        $this->assertSame('EN', $switcher['current']);
        $this->assertCount(2, $switcher['options']);

        $byCode = collect($switcher['options'])->keyBy('code');

        $this->assertTrue($byCode['EN']['current']);
        $this->assertNull($byCode['EN']['url']);
        $this->assertSame($italian->editorUrl(), $byCode['IT']['url']);
        $this->assertStringContainsString('edit=1', (string) $byCode['IT']['url']);
    }

    public function test_the_switcher_is_hidden_when_there_is_nothing_to_switch_to(): void
    {
        $page = SitePage::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'locale' => 'it',
            'builder' => PageBuilder::Visual,
            'builder_payload' => ['html' => '<section class="voodbuilder-editor-section">About</section>', 'css' => ''],
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($this->admin());

        $this->assertNull(EditorGate::config($page)['localeSwitcher']);
    }

    private function homePage(string $locale, string $title): SitePage
    {
        return SitePage::query()->create([
            'title' => $title,
            'slug' => 'home',
            'locale' => $locale,
            'builder' => PageBuilder::Visual,
            'builder_payload' => [
                'html' => '<section class="voodbuilder-editor-section">Home ' . strtoupper($locale) . '</section>',
                'css' => '',
            ],
            'layout' => 'home',
            'is_home' => true,
            'published' => true,
            'published_at' => now(),
        ]);
    }

    private function admin(): mixed
    {
        $user = new class extends User implements FilamentUser
        {
            protected $table = 'users';

            public function canAccessPanel(Panel $panel): bool
            {
                return true;
            }
        };

        $user->forceFill([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ])->save();

        return $user;
    }
}
