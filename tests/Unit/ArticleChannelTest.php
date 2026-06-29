<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\ArticleChannel;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Tests\TestCase;

class ArticleChannelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app(SubThemeRegistry::class)->register('magazine', [
            'label' => 'Magazine',
            'capabilities' => ['article'],
            'layouts' => [
                'article' => 'voodbuilder::themes.news.layouts.article',
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
            'voodbuilder::article-channel.ink.news.post-list',
            ArticleChannel::inkPartialFor('news', 'post-list'),
        );
    }

    public function test_ink_partial_falls_back_to_blog_family(): void
    {
        $this->assertSame(
            'voodbuilder::article-channel.ink.blog.post-list',
            ArticleChannel::inkPartialFor('unknown', 'post-list'),
        );
    }
}
