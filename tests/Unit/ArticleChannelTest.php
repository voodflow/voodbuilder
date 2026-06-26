<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Support\ArticleChannel;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Tests\TestCase;

class ArticleChannelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(SubThemeRegistry::class)->register('magazine', [
            'label' => 'Magazine',
            'capabilities' => ['article'],
            'layouts' => [
                'article' => 'vpress::themes.news.layouts.article',
            ],
        ]);
    }

    public function test_presentation_family_for_bundled_news_theme(): void
    {
        $this->assertSame('news', ArticleChannel::presentationFamilyFor('news'));
    }

    public function test_presentation_family_for_custom_theme_with_news_layout(): void
    {
        $this->assertSame('news', ArticleChannel::presentationFamilyFor('magazine'));
    }

    public function test_presentation_family_defaults_to_blog(): void
    {
        $this->assertSame('blog', ArticleChannel::presentationFamilyFor('blog'));
        $this->assertSame('blog', ArticleChannel::presentationFamilyFor('blog-custom'));
    }

    public function test_ink_partial_for_news_family(): void
    {
        $this->assertSame(
            'vpress::article-channel.ink.news.post-list',
            ArticleChannel::inkPartialFor('news', 'post-list'),
        );
    }

    public function test_ink_partial_falls_back_to_blog_family(): void
    {
        $this->assertSame(
            'vpress::article-channel.ink.blog.post-list',
            ArticleChannel::inkPartialFor('unknown', 'post-list'),
        );
    }
}
