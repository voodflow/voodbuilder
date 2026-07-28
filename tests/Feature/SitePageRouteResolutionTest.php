<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Http\Controllers\SitePageController;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Tests\TestCase;

class SitePageRouteResolutionTest extends TestCase
{
    protected function defineWebRoutes($router): void
    {
        $router->get('pages/{slug}', [SitePageController::class, 'show'])->name('voodbuilder.pages.show');
    }

    public function test_published_grapesjs_page_resolves_by_slug_from_fixture(): void
    {
        $fixture = json_decode(
            (string) file_get_contents(dirname(__DIR__).'/Fixtures/0.0.11/sample-page.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $pageData = $fixture['page'];

        SitePage::query()->create([
            'title' => $pageData['title'],
            'slug' => $pageData['slug'],
            'locale' => $pageData['locale'],
            'layout' => $pageData['layout'],
            'builder' => PageBuilder::GrapesJs,
            'builder_payload' => [
                'html' => $pageData['html'],
                'css' => $pageData['css'],
                'js' => $pageData['js'],
            ],
            'published' => true,
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/pages/'.$pageData['slug'])
            ->assertOk()
            ->assertSee('Baseline', false);
    }
}
