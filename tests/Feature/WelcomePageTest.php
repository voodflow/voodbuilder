<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\EmptySiteGuidance;
use Voodflow\Voodbuilder\Tests\TestCase;

class WelcomePageTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('voodbuilder.home.route_enabled', true);
    }

    public function test_home_shows_layout_guidance_when_empty(): void
    {
        $this->assertSame(0, SitePage::query()->count());

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(EmptySiteGuidance::title(), false);
        $response->assertSee(__('voodbuilder::home.empty.description_needs_layout'), false);
        $response->assertDontSee('Footer column', false);
    }

    public function test_home_shows_page_guidance_when_layout_exists(): void
    {
        ChromeLayout::query()->create([
            'name' => 'Site shell',
            'slug' => 'site-shell',
            'enabled' => true,
            'is_default' => true,
            'html' => '<div data-voodbuilder-chrome-content-slot></div>',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(__('voodbuilder::home.empty.title_needs_page'), false);
        $response->assertSee(__('voodbuilder::home.empty.description_needs_page'), false);
    }
}
