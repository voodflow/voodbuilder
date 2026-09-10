<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Enums\PageVisibility;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageSeo;
use Voodflow\Voodbuilder\Support\VoodbuilderSeo;
use Voodflow\Voodbuilder\Tests\TestCase;

class SitePageSeoTest extends TestCase
{
    public function test_dynamic_seo_prefers_seo_relation_over_page_fields(): void
    {
        $page = SitePage::query()->create([
            'title' => 'About us',
            'slug' => 'about-seo-override',
            'excerpt' => 'Page excerpt',
            'published' => true,
            'visibility' => PageVisibility::Public,
        ]);

        $page->seo()->update([
            'title' => 'Custom meta title',
            'description' => 'Custom meta description',
            'robots' => 'noindex, follow',
            'canonical_url' => 'https://example.test/about-canonical',
        ]);

        $seo = $page->fresh(['seo'])->getDynamicSEOData();

        $this->assertSame('Custom meta title', $seo->title);
        $this->assertSame('Custom meta description', $seo->description);
        $this->assertSame('noindex, follow', $seo->robots);
        $this->assertSame('https://example.test/about-canonical', $seo->canonical_url);
    }

    public function test_dynamic_seo_uses_excerpt_and_skips_stripped_html_fallback(): void
    {
        $page = SitePage::query()->create([
            'title' => 'Landing',
            'slug' => 'landing-seo-excerpt',
            'excerpt' => 'Clean excerpt for SEO',
            'published' => true,
            'visibility' => PageVisibility::Public,
            'builder_payload' => [
                'html' => '<div><h1>Huge canvas heading</h1><p>Lots of visual noise</p></div>',
            ],
        ]);

        $seo = $page->getDynamicSEOData();

        $this->assertSame('Landing', $seo->title);
        $this->assertSame('Clean excerpt for SEO', $seo->description);
        $this->assertStringNotContainsString('Huge canvas', (string) $seo->description);
    }

    public function test_dynamic_seo_description_null_allows_site_default(): void
    {
        $page = SitePage::query()->create([
            'title' => 'Bare page',
            'slug' => 'bare-seo',
            'excerpt' => null,
            'published' => true,
            'visibility' => PageVisibility::Public,
        ]);

        $seo = $page->getDynamicSEOData();
        $this->assertNull($seo->description);

        $withDefaults = VoodbuilderSeo::applyDefaults($seo);
        // Without settings, description may stay null — assert we did not invent canvas HTML.
        $this->assertTrue(
            $withDefaults->description === null
            || ! str_contains((string) $withDefaults->description, '<')
        );
    }

    public function test_gated_pages_default_to_noindex(): void
    {
        $page = SitePage::query()->create([
            'title' => 'Members only',
            'slug' => 'members-seo',
            'published' => true,
            'visibility' => PageVisibility::Registered,
        ]);

        $this->assertSame('noindex, nofollow', $page->getDynamicSEOData()->robots);
    }

    public function test_section_articles_use_article_og_type(): void
    {
        $page = SitePage::query()->create([
            'title' => 'Post',
            'slug' => 'section-post',
            'section' => 'blog',
            'section_home' => false,
            'published' => true,
            'visibility' => PageVisibility::Public,
        ]);

        $this->assertSame('article', $page->getDynamicSEOData()->type);
        $this->assertNotNull($page->getDynamicSEOData()->schema);
    }

    public function test_dynamic_page_seo_prefers_most_specific_entity(): void
    {
        $template = SitePage::query()->create([
            'title' => 'Event template',
            'slug' => 'event-template-seo',
            'published' => true,
            'is_dynamic' => true,
            'visibility' => PageVisibility::Public,
        ]);

        $entity = SitePage::query()->create([
            'title' => 'Cosmo Expo 2026',
            'slug' => 'cosmo-expo-seo',
            'published' => true,
            'visibility' => PageVisibility::Public,
        ]);

        $subject = DynamicPageSeo::subject($template, [
            'event' => $entity,
        ]);

        $this->assertTrue($subject->is($entity));

        $nested = DynamicPageSeo::subject($template, [
            'event' => $entity,
            'schedule_item' => null,
        ]);

        $this->assertTrue($nested->is($entity));

        $fallback = DynamicPageSeo::subject($template, []);
        $this->assertTrue($fallback->is($template));
    }
}
