<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\SubThemeResolver;
use Voodflow\Voodbuilder\Tests\TestCase;

class SubThemeResolverTest extends TestCase
{
    public function test_page_inherits_site_default_when_sub_theme_is_empty(): void
    {
        VoodbuilderSettings::query()->create([
            'data' => array_merge(VoodbuilderSettings::defaults(), ['sub_theme' => 'site']),
        ]);
        VoodbuilderSettings::clearCache();

        $page = SitePage::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'content' => [],
            'layout' => 'page',
            'sub_theme' => null,
            'is_home' => false,
            'published' => true,
        ]);

        $this->assertSame('site', SubThemeResolver::forPage($page));
    }

    public function test_page_override_takes_precedence_over_site_default(): void
    {
        VoodbuilderSettings::query()->create([
            'data' => array_merge(VoodbuilderSettings::defaults(), ['sub_theme' => 'default']),
        ]);
        VoodbuilderSettings::clearCache();

        $page = SitePage::query()->create([
            'title' => 'Journal',
            'slug' => 'journal',
            'content' => [],
            'layout' => 'page',
            'sub_theme' => 'site',
            'is_home' => false,
            'published' => true,
        ]);

        $this->assertSame('site', SubThemeResolver::forPage($page));
        $this->assertSame('voodbuilder::themes.site.layouts.page', $page->layoutView());
    }

    public function test_invalid_sub_theme_falls_back_to_site_default(): void
    {
        VoodbuilderSettings::query()->create([
            'data' => VoodbuilderSettings::defaults(),
        ]);
        VoodbuilderSettings::clearCache();

        $page = SitePage::query()->create([
            'title' => 'Broken',
            'slug' => 'broken',
            'content' => [],
            'layout' => 'page',
            'sub_theme' => 'does-not-exist',
            'is_home' => false,
            'published' => true,
        ]);

        $this->assertSame('site', SubThemeResolver::forPage($page));
    }
}
