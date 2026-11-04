<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
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

    public function test_it_uses_pages_channel_theme_when_layout_has_no_channels(): void
    {
        $layout = ChromeLayout::query()->create([
            'name' => 'Orphan shell',
            'slug' => 'orphan-shell',
            'enabled' => true,
            'channel_ids' => [],
        ]);

        $this->assertSame(
            ChromeLayoutSubThemeResolver::forPagesChannel(),
            ChromeLayoutSubThemeResolver::forChromeLayout($layout),
        );
    }

    public function test_it_prefers_pages_channel_theme_when_layout_serves_multiple_channels(): void
    {
        $layout = ChromeLayout::query()->create([
            'name' => 'Shared shell',
            'slug' => 'shared-shell',
            'enabled' => true,
            'channel_ids' => ['docs', 'pages', 'tutorials'],
        ]);

        $this->assertSame('site', ChromeLayoutSubThemeResolver::forChromeLayout($layout));
    }

    public function test_site_pages_follow_the_site_sub_theme_over_a_stale_pages_override(): void
    {
        config()->set('voodbuilder.sub_themes', [
            'site' => ['label' => 'Site', 'capabilities' => ['landing']],
            'docs' => ['label' => 'Docs', 'capabilities' => ['doc']],
        ]);

        VoodbuilderSettings::query()->create([
            'data' => array_merge(VoodbuilderSettings::docss(), [
                'sub_theme' => 'docs',
                'content_channel_sub_themes' => ['pages' => 'site'],
            ]),
        ]);
        VoodbuilderSettings::clearCache();

        $this->assertSame('docs', ChromeLayoutSubThemeResolver::forPagesChannel());
    }

    public function test_it_uses_page_sub_theme_for_site_pages(): void
    {
        $page = new SitePage([
            'sub_theme' => 'docs',
        ]);

        $this->assertSame('docs', ChromeLayoutSubThemeResolver::forSitePage($page));
    }
}
