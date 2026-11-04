<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterColumnsContactBlock;
use Voodflow\Voodbuilder\Support\Editor\SiteFooterConfig;
use Voodflow\Voodbuilder\Tests\TestCase;

class SiteFooterContactItemsTest extends TestCase
{
    #[Test]
    public function contact_items_accept_icon_and_text_rows(): void
    {
        $normalized = SiteFooterConfig::normalize([
            'contact_items' => [
                ['icon' => 'Mail', 'text' => ' led@polito.it '],
                ['text' => '+39 011 090 4000'],
            ],
        ]);

        $this->assertSame([
            ['icon' => 'mail', 'text' => 'led@polito.it'],
            ['icon' => SiteFooterConfig::DEFAULT_CONTACT_ITEM_ICON, 'text' => '+39 011 090 4000'],
        ], $normalized['contact_items']);
    }

    #[Test]
    public function contact_items_accept_a_json_string(): void
    {
        $normalized = SiteFooterConfig::normalize([
            'contact_items' => '[{"icon":"phone","text":"+39 011 090 4000"}]',
        ]);

        $this->assertSame([
            ['icon' => 'phone', 'text' => '+39 011 090 4000'],
        ], $normalized['contact_items']);
    }

    #[Test]
    public function legacy_plain_lines_become_rows_without_icons(): void
    {
        $normalized = SiteFooterConfig::normalize([
            'contact_items' => ['led@polito.it', '', 'Corso Duca degli Abruzzi, 24'],
        ]);

        $this->assertSame([
            ['icon' => SiteFooterConfig::DEFAULT_CONTACT_ITEM_ICON, 'text' => 'led@polito.it'],
            ['icon' => SiteFooterConfig::DEFAULT_CONTACT_ITEM_ICON, 'text' => 'Corso Duca degli Abruzzi, 24'],
        ], $normalized['contact_items']);
    }

    #[Test]
    public function explicit_empty_icon_keeps_the_row_without_a_glyph(): void
    {
        $normalized = SiteFooterConfig::normalize([
            'contact_items' => [
                ['icon' => '', 'text' => '10129 Torino'],
            ],
        ]);

        $this->assertSame([
            ['icon' => '', 'text' => '10129 Torino'],
        ], $normalized['contact_items']);

        $html = SiteFooterColumnsContactBlock::toHtml([
            'contact_items' => [['icon' => '', 'text' => '10129 Torino']],
        ], []);

        $this->assertStringContainsString('10129 Torino', $html);
        $this->assertStringNotContainsString('data-voodbuilder-footer-text-icon', $html);
    }

    #[Test]
    public function contact_items_are_capped(): void
    {
        $rows = [];

        for ($index = 0; $index < SiteFooterConfig::MAX_CONTACT_ITEMS + 4; $index++) {
            $rows[] = ['icon' => 'point', 'text' => 'Row ' . $index];
        }

        $normalized = SiteFooterConfig::normalize(['contact_items' => $rows]);

        $this->assertCount(SiteFooterConfig::MAX_CONTACT_ITEMS, $normalized['contact_items']);
    }

    #[Test]
    public function contact_items_resolve_links_from_the_text(): void
    {
        $items = SiteFooterConfig::contactItems([
            'contact_items' => [
                ['icon' => 'mail', 'text' => 'led@polito.it'],
                ['icon' => 'phone', 'text' => '+39 011 090 4000'],
                ['icon' => 'map-pin', 'text' => 'Corso Duca degli Abruzzi, 24'],
            ],
        ]);

        $this->assertSame('mailto:led@polito.it', $items[0]['href']);
        $this->assertSame('tel:+390110904000', $items[1]['href']);
        $this->assertNull($items[2]['href']);
    }

    #[Test]
    public function contacts_column_renders_rows_with_tabler_icons(): void
    {
        $html = SiteFooterColumnsContactBlock::toHtml([
            'contact_items' => [
                ['icon' => 'mail', 'text' => 'led@polito.it'],
                ['icon' => 'phone', 'text' => '+39 011 090 4000'],
            ],
        ], []);

        $this->assertStringContainsString('data-voodbuilder-footer-text-item', $html);
        $this->assertStringContainsString('data-voodbuilder-footer-text-icon="mail"', $html);
        $this->assertStringContainsString('data-voodbuilder-footer-text-icon="phone"', $html);
        $this->assertStringContainsString('href="mailto:led@polito.it"', $html);
        $this->assertStringContainsString('href="tel:+390110904000"', $html);
        $this->assertStringContainsString('<svg', $html);
    }

    #[Test]
    public function vertical_dividers_add_both_rules_and_move_copyright_to_the_end_column(): void
    {
        $html = SiteFooterColumnsContactBlock::toHtml([
            'vertical_dividers' => true,
            'show_copyright' => true,
            'contact_items' => [
                ['icon' => 'mail', 'text' => 'led@polito.it'],
            ],
            'details_lines' => ['DET - Dipartimento di Elettronica e Telecomunicazioni'],
        ], []);

        // Brand rule + copyright rule.
        $this->assertMatchesRegularExpression(
            '/data-voodbuilder-footer-brand-col[\s\S]*?lg:border-r[\s\S]*?lg:border-vp-divider|lg:border-r[^"]*lg:border-vp-divider/',
            $html,
        );
        $this->assertStringContainsString('data-voodbuilder-footer-copyright-col', $html);
        $this->assertStringContainsString('lg:border-l', $html);

        // One copyright node, inside the end column.
        $this->assertSame(1, preg_match_all('/data-voodbuilder-footer-copyright(?![-\\w])/', $html));
        $this->assertMatchesRegularExpression(
            '/data-voodbuilder-footer-copyright-col[\s\S]*data-voodbuilder-footer-copyright/',
            $html,
        );

        // Details lines join the end column instead of rendering a separate column.
        $this->assertStringNotContainsString('data-voodbuilder-footer-text-col="details"', $html);
        $this->assertStringContainsString('DET - Dipartimento di Elettronica e Telecomunicazioni', $html);
    }

    #[Test]
    public function split_layout_is_off_by_default(): void
    {
        $html = SiteFooterColumnsContactBlock::toHtml([], []);

        $this->assertStringContainsString('data-voodbuilder-footer-text-col="details"', $html);
        $this->assertStringNotContainsString('data-voodbuilder-footer-copyright-col', $html);
        $this->assertStringContainsString('data-voodbuilder-chrome="copyright"', $html);
    }
}
