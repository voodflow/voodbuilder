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
