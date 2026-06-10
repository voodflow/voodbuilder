<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Feature;

use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\SitePageSection;
use Voodflow\Vpress\Tests\TestCase;

class SitePageSectionTest extends TestCase
{
    public function test_section_home_uses_section_index_layout(): void
    {
        $page = SitePage::query()->create([
            'title' => 'Journal',
            'slug' => 'blog',
            'section' => SitePageSection::BLOG,
            'section_home' => true,
            'sub_theme' => 'blog',
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        $this->assertSame(
            'vpress::sub-themes.blog.layouts.section-index',
            $page->layoutView(),
        );
    }

    public function test_section_article_uses_article_layout(): void
    {
        $page = SitePage::query()->create([
            'title' => 'First post',
            'slug' => 'blog-welcome',
            'section' => SitePageSection::BLOG,
            'sub_theme' => 'blog',
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        $this->assertSame(
            'vpress::sub-themes.blog.layouts.article',
            $page->layoutView(),
        );
    }

    public function test_section_articles_excludes_section_home(): void
    {
        SitePage::query()->create([
            'title' => 'Journal',
            'slug' => 'blog',
            'section' => SitePageSection::BLOG,
            'section_home' => true,
            'sub_theme' => 'blog',
            'layout' => 'page',
            'published' => true,
            'published_at' => now(),
        ]);

        SitePage::query()->create([
            'title' => 'First post',
            'slug' => 'blog-welcome',
            'section' => SitePageSection::BLOG,
            'sub_theme' => 'blog',
            'layout' => 'page',
            'published' => true,
            'published_at' => now()->subDay(),
        ]);

        $slugs = SitePage::sectionArticles(SitePageSection::BLOG)->pluck('slug')->all();

        $this->assertSame(['blog-welcome'], $slugs);
    }
}
