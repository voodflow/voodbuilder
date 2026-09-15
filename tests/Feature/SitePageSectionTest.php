<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SitePageSection;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;

class SitePageSectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Blog/news skins live outside Community core; register like a companion would.
        Voodbuilder::subTheme('blog', [
            'label' => 'Blog',
            'type' => 'content',
            'capabilities' => ['article'],
            'css' => 'themes/blog/theme.css',
            'layouts' => [
                'section_index' => 'voodbuilder::themes.blog.layouts.section-index',
                'article' => 'voodbuilder::themes.blog.layouts.article',
                'page' => 'voodbuilder::themes.blog.layouts.page',
            ],
        ]);
    }

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
            'voodbuilder::themes.blog.layouts.section-index',
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
            'voodbuilder::themes.blog.layouts.article',
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
