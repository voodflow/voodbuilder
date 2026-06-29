<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Voodflow\Voodbuilder\Http\Controllers\SitePageController;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Tests\TestCase;

class SitePageSecurityTest extends TestCase
{
    protected function defineWebRoutes($router): void
    {
        $router->get('pages/{slug}', [SitePageController::class, 'show'])->name('voodbuilder.pages.show');
    }

    public function test_unpublished_site_pages_are_not_accessible(): void
    {
        SitePage::query()->create([
            'title' => 'Draft',
            'slug' => 'draft-page',
            'content' => [],
            'layout' => 'page',
            'is_home' => false,
            'published' => false,
        ]);

        $this->get('/pages/draft-page')->assertNotFound();
    }

    public function test_published_scope_excludes_unpublished_pages(): void
    {
        SitePage::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'content' => [],
            'layout' => 'page',
            'is_home' => false,
            'published' => true,
            'published_at' => now(),
        ]);

        SitePage::query()->create([
            'title' => 'Draft',
            'slug' => 'draft-page',
            'content' => [],
            'layout' => 'page',
            'is_home' => false,
            'published' => false,
        ]);

        $slugs = SitePage::query()->published()->pluck('slug')->all();

        $this->assertSame(['about'], $slugs);
    }
}
