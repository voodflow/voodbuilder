<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\ChromeLayoutEditorPreview;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeLayoutEditorPreviewTest extends TestCase
{
    public function test_compose_wraps_page_html_between_chrome_before_and_after(): void
    {
        $layout = ChromeLayout::query()->create([
            'name' => 'Basic shell',
            'slug' => 'basic-shell',
            'html' => '<header>Nav</header><div data-voodbuilder-content-slot="main"></div><footer>Foot</footer>',
            'css' => '.chrome { color: red; }',
            'enabled' => true,
        ]);

        $composed = ChromeLayoutEditorPreview::compose($layout, '<section>Hero</section>', '.page { margin: 0; }');

        $this->assertStringContainsString('Nav', $composed['html']);
        $this->assertStringContainsString('Foot', $composed['html']);
        $this->assertStringContainsString('data-voodbuilder-chrome-shell-part="before"', $composed['html']);
        $this->assertStringContainsString('data-voodbuilder-chrome-shell-part="after"', $composed['html']);
        $this->assertStringContainsString('data-voodbuilder-chrome-shell="1"', $composed['html']);
        $this->assertStringContainsString('data-voodbuilder-page-content="1"', $composed['html']);
        $this->assertStringContainsString('<section>Hero</section>', $composed['html']);
        $this->assertStringContainsString('.chrome { color: red; }', $composed['css']);
        $this->assertStringContainsString('.page { margin: 0; }', $composed['css']);
    }

    public function test_compose_for_page_returns_null_without_chrome_layout(): void
    {
        $page = new SitePage;

        $this->assertNull(ChromeLayoutEditorPreview::composeForPage($page, '<p>Body</p>', ''));
    }

    public function test_compose_for_page_uses_pages_channel_layout(): void
    {
        ChromeLayout::query()->create([
            'name' => 'Pages shell',
            'slug' => 'pages-shell',
            'html' => '<header>Top</header><div data-voodbuilder-content-slot="main"></div><footer>Bottom</footer>',
            'enabled' => true,
            'channel_ids' => ['pages'],
        ]);

        ChromeLayoutResolver::forgetCache();

        $page = new SitePage;

        $composed = ChromeLayoutEditorPreview::composeForPage($page, '<h1>Title</h1>', '');

        $this->assertNotNull($composed);
        $this->assertStringContainsString('Top', $composed['html']);
        $this->assertStringContainsString('Bottom', $composed['html']);
        $this->assertStringContainsString('<h1>Title</h1>', $composed['html']);
    }

    public function test_shell_preview_wrapper_round_trips(): void
    {
        $inner = '<div data-voodbuilder-content-slot="main"></div>';
        $wrapped = ChromeLayoutEditorPreview::wrapShellPreview($inner, 'site');

        $this->assertStringContainsString('data-voodbuilder-chrome-shell', $wrapped);
        $this->assertSame($inner, ChromeLayoutEditorPreview::unwrapShellPreview($wrapped));
    }
}
