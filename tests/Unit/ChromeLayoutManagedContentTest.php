<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\ChromeLayoutManagedContent;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;
use Voodflow\Voodbuilder\Support\SiteChrome;
use Voodflow\Voodbuilder\Tests\TestCase;

class ChromeLayoutManagedContentTest extends TestCase
{
    public function test_strips_nav_and_footer_blocks_from_page_html(): void
    {
        $html = <<<'HTML'
<div data-voodbuilder-block="site_nav_simple" class="voodbuilder-gjs-dynamic"><div data-voodbuilder-gjs-site-header="">Nav</div></div>
<section><h1>Hero</h1></section>
<div data-voodbuilder-block="site_footer_columns_simple" class="voodbuilder-gjs-dynamic">Footer</div>
HTML;

        $stripped = ChromeLayoutManagedContent::stripSiteChromeFromPageHtml($html);

        $this->assertStringContainsString('Hero', $stripped);
        $this->assertStringNotContainsString('site_nav_simple', $stripped);
        $this->assertStringNotContainsString('site_footer_columns_simple', $stripped);
    }

    public function test_strips_chrome_editor_bleed_text_from_page_html(): void
    {
        $html = <<<'HTML'
<section><h1>Hero</h1></section>
ButtonNotificationsButtonButton
HTML;

        $stripped = ChromeLayoutManagedContent::stripChromeEditorBleedFromPageHtml($html);

        $this->assertStringContainsString('Hero', $stripped);
        $this->assertStringNotContainsString('ButtonNotifications', $stripped);
    }

    public function test_strips_single_button_bleed_text_from_page_html(): void
    {
        $html = <<<'HTML'
<section><h1>Hero</h1></section>
<div>Button</div>
HTML;

        $stripped = ChromeLayoutManagedContent::stripChromeEditorBleedFromPageHtml($html);

        $this->assertStringContainsString('Hero', $stripped);
        $this->assertStringNotContainsString('>Button<', $stripped);
    }

    public function test_site_chrome_hidden_when_chrome_layout_assigned_to_pages_channel(): void
    {
        ChromeLayout::query()->create([
            'name' => 'Site shell',
            'slug' => 'site-shell',
            'html' => '<div data-voodbuilder-content-slot="main"></div>',
            'enabled' => true,
            'channel_ids' => ['pages'],
        ]);

        ChromeLayoutResolver::forgetCache();

        $page = new SitePage([
            'hide_site_nav' => false,
            'hide_site_footer' => false,
        ]);

        $this->assertTrue(ChromeLayoutManagedContent::sitePageUsesChromeShell($page));
        $this->assertTrue(SiteChrome::shouldHideNav($page));
        $this->assertTrue(SiteChrome::shouldHideFooter($page));
    }
}
