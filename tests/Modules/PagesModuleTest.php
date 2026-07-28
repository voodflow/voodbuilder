<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Modules;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Http\Controllers\SitePageController;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Modules\Pages\PagesModule;
use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class PagesModuleTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.modules.pages.enabled', false);
    }

    protected function defineWebRoutes($router): void
    {
        $router->get('pages/{slug}', [SitePageController::class, 'show'])->name('voodbuilder.pages.show');
    }

    public function test_pages_module_can_be_disabled(): void
    {
        $this->assertTrue(Voodbuilder::modules()->has(PagesModule::ID));
        $this->assertFalse(PagesModule::isEnabled());
        $this->assertFalse(Route::has('voodbuilder.grapesjs.pages.update'));
        $this->assertFalse(Route::has('voodbuilder.grapesjs.forms.submit'));
    }

    public function test_page_save_route_is_absent_when_module_disabled(): void
    {
        $user = new User;
        $user->forceFill([
            'name' => 'Admin',
            'email' => 'pages-off@example.com',
        ])->save();

        $page = SitePage::query()->create([
            'title' => 'Off',
            'slug' => 'off',
            'locale' => 'en',
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => ['html' => '<p>x</p>', 'css' => '', 'js' => ''],
            'published' => true,
            'published_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)
            ->putJson('/voodbuilder/grapesjs/pages/'.$page->getKey(), [
                'html' => '<p>y</p>',
                'css' => '',
                'js' => '',
            ])
            ->assertNotFound();
    }

    public function test_pages_content_channel_absent_when_module_disabled(): void
    {
        $registry = app(ContentChannelRegistry::class);

        $this->assertNull($registry->get('pages'));
    }

    public function test_public_page_show_still_works_when_admin_module_disabled(): void
    {
        SitePage::query()->create([
            'title' => 'Public',
            'slug' => 'public-page',
            'locale' => 'en',
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => ['html' => '<p>Hello public</p>', 'css' => '', 'js' => ''],
            'published' => true,
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/pages/public-page')
            ->assertOk()
            ->assertSee('Hello public', false);
    }
}
