<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\ChromeLayoutSubThemeResolver;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeLayoutSubThemeResolverTest extends TestCase
{
    public function test_it_resolves_sub_theme_from_assigned_channel(): void
    {
        $layout = ChromeLayout::query()->create([
            'name' => 'Docs shell',
            'slug' => 'docs-shell',
            'enabled' => true,
            'channel_ids' => ['pages'],
        ]);

        $this->assertSame('site', ChromeLayoutSubThemeResolver::forChromeLayout($layout));
    }

    public function test_it_uses_page_sub_theme_for_site_pages(): void
    {
        $page = new SitePage([
            'sub_theme' => 'docs',
        ]);

        $this->assertSame('docs', ChromeLayoutSubThemeResolver::forSitePage($page));
    }
}
