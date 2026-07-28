<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Http\Controllers\SitePageController;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Tests\TestCase;

class SitePagePublicationTest extends TestCase
{
    protected function defineWebRoutes($router): void
    {
        $router->get('pages/{slug}', [SitePageController::class, 'show'])->name('voodbuilder.pages.show');
    }

    public function test_future_published_at_is_not_publicly_resolvable(): void
    {
        SitePage::query()->create([
            'title' => 'Scheduled',
            'slug' => 'scheduled-page',
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => [
                'html' => '<section>Soon</section>',
                'css' => '',
                'js' => '',
            ],
            'layout' => 'page',
            'published' => true,
            'published_at' => now()->addDay(),
        ]);

        $this->assertFalse(
            SitePage::query()->published()->where('slug', 'scheduled-page')->exists(),
        );

        $this->get('/pages/scheduled-page')->assertNotFound();
    }

    public function test_past_published_at_is_publicly_resolvable(): void
    {
        SitePage::query()->create([
            'title' => 'Live',
            'slug' => 'live-page',
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => [
                'html' => '<section class="voodbuilder-gjs-section">Live</section>',
                'css' => '',
                'js' => '',
            ],
            'layout' => 'page',
            'published' => true,
            'published_at' => now()->subMinute(),
        ]);

        $this->assertTrue(
            SitePage::query()->published()->where('slug', 'live-page')->exists(),
        );

        $this->get('/pages/live-page')
            ->assertOk()
            ->assertSee('Live', false);
    }

    public function test_null_published_at_with_published_flag_is_resolvable(): void
    {
        SitePage::query()->create([
            'title' => 'Immediate',
            'slug' => 'immediate-page',
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => [
                'html' => '<section class="voodbuilder-gjs-section">Now</section>',
                'css' => '',
                'js' => '',
            ],
            'layout' => 'page',
            'published' => true,
            'published_at' => null,
        ]);

        $this->assertTrue(
            SitePage::query()->published()->where('slug', 'immediate-page')->exists(),
        );

        $this->get('/pages/immediate-page')
            ->assertOk()
            ->assertSee('Now', false);
    }
}
