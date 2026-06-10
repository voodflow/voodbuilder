<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Support\DemoSubThemeContent;
use Voodflow\Vpress\Tests\TestCase;

class DemoSubThemeContentTest extends TestCase
{
    public function test_demo_sections_define_five_posts_each(): void
    {
        $this->assertCount(5, DemoSubThemeContent::blogPostDefinitions());
        $this->assertCount(5, DemoSubThemeContent::newsArticleDefinitions());
    }

    public function test_blog_and_news_content_are_valid_rich_editor_documents(): void
    {
        $blog = DemoSubThemeContent::blogPost('welcome');
        $news = DemoSubThemeContent::newsArticle('briefing');

        foreach ([$blog['content'], $news['content']] as $content) {
            $this->assertSame('doc', $content['type']);
            $this->assertNotEmpty($content['content']);
            $this->assertSame('heading', $content['content'][0]['type']);
        }
    }
}
