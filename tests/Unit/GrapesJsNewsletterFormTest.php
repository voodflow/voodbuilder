<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockPreview;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsFormNormalizer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsServerBlockAdapter;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsSiteChromeSidebarPreview;
use Voodflow\Voodbuilder\Support\GrapesJs\SiteFooterColumnsNewsletterBlock;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsNewsletterFormTest extends TestCase
{
    public function test_newsletter_form_is_wired_for_published_page(): void
    {
        $page = SitePage::query()->create([
            'title' => 'Newsletter test',
            'slug' => 'newsletter-test',
            'published' => true,
        ]);

        $html = '<form data-voodbuilder-form="newsletter" class="vb-gjs-newsletter-form" onsubmit="return false;" method="post" action="#"><input type="email" name="email"></form>';

        $normalized = GrapesJsFormNormalizer::normalizeForPage($html, $page);

        $this->assertStringContainsString('name="form_type"', $normalized);
        $this->assertStringContainsString('value="newsletter"', $normalized);
        $this->assertStringNotContainsString('onsubmit', $normalized);
        $this->assertStringContainsString(route('voodbuilder.grapesjs.forms.submit', $page), $normalized);
    }

    public function test_site_footer_block_uses_html_sidebar_preview(): void
    {
        $definition = GrapesJsServerBlockAdapter::toDefinition(
            SiteFooterColumnsNewsletterBlock::class,
            'Site',
        );

        $this->assertStringContainsString('voodbuilder-gjs-block-preview--site', (string) ($definition->preview ?? ''));
        $this->assertStringContainsString('voodbuilder-gjs-block-preview__scale', (string) ($definition->preview ?? ''));
        $this->assertStringContainsString('voodbuilder-gjs-block-preview-compact', (string) ($definition->preview ?? ''));
        $this->assertStringContainsString('data-voodbuilder-block', (string) ($definition->content ?? ''));
    }

    public function test_site_chrome_sidebar_preview_compacts_padding(): void
    {
        $wrapped = GrapesJsSiteChromeSidebarPreview::fromPreviewHtml(
            '<footer><div class="container py-24">Footer</div></footer>',
            'site_footer_columns_simple',
        );

        $this->assertStringContainsString('py-6', $wrapped);
        $this->assertStringNotContainsString('py-24', $wrapped);
    }

    public function test_block_preview_wrapper_matches_sections(): void
    {
        $wrapped = GrapesJsBlockPreview::wrapHtml('<section>Hero</section>');

        $this->assertStringContainsString('voodbuilder-gjs-block-preview__scale', (string) $wrapped);
    }
}
