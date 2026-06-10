<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\SubThemeResolver;
use Voodflow\Vpress\Tests\TestCase;

class SubThemeResolverTest extends TestCase
{
    public function test_page_inherits_site_default_when_sub_theme_is_empty(): void
    {
        VpressSettings::query()->create([
            'data' => array_merge(VpressSettings::defaults(), ['sub_theme' => 'news']),
        ]);
        VpressSettings::clearCache();

        $page = SitePage::query()->create([
            'title' => 'About',
            'slug' => 'about',
            'content' => [],
            'layout' => 'page',
            'sub_theme' => null,
            'is_home' => false,
            'published' => true,
        ]);

        $this->assertSame('news', SubThemeResolver::forPage($page));
    }

    public function test_page_override_takes_precedence_over_site_default(): void
    {
        VpressSettings::query()->create([
            'data' => array_merge(VpressSettings::defaults(), ['sub_theme' => 'default']),
        ]);
        VpressSettings::clearCache();

        $page = SitePage::query()->create([
            'title' => 'Journal',
            'slug' => 'journal',
            'content' => [],
            'layout' => 'page',
            'sub_theme' => 'blog',
            'is_home' => false,
            'published' => true,
        ]);

        $this->assertSame('blog', SubThemeResolver::forPage($page));
        $this->assertSame('vpress::sub-themes.blog.layouts.page', $page->layoutView());
    }

    public function test_invalid_sub_theme_falls_back_to_default(): void
    {
        $page = SitePage::query()->create([
            'title' => 'Broken',
            'slug' => 'broken',
            'content' => [],
            'layout' => 'page',
            'sub_theme' => 'does-not-exist',
            'is_home' => false,
            'published' => true,
        ]);

        $this->assertSame('default', SubThemeResolver::forPage($page));
    }
}
