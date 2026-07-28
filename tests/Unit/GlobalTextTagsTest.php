<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\GlobalTextTags;
use Voodflow\Voodbuilder\Tests\TestCase;

class GlobalTextTagsTest extends TestCase
{
    #[Test]
    public function it_replaces_current_year(): void
    {
        $this->assertSame(
            '© '.date('Y').' VoodBuilder',
            GlobalTextTags::replace('© {current_year} VoodBuilder'),
        );
    }

    #[Test]
    public function it_replaces_brand_and_site_tags_with_overrides(): void
    {
        $resolved = GlobalTextTags::replace(
            '{brand_name} · {site_name} · {site_url}',
            [
                'brand_name' => 'Acme CMS',
                'site_name' => 'Acme Site',
                'site_url' => 'https://example.test',
            ],
        );

        $this->assertSame('Acme CMS · Acme Site · https://example.test', $resolved);
    }

    #[Test]
    public function it_lists_known_keys(): void
    {
        $keys = GlobalTextTags::keys();

        $this->assertContains('current_year', $keys);
        $this->assertContains('brand_name', $keys);
        $this->assertContains('site_name', $keys);
        $this->assertContains('site_url', $keys);
    }

    #[Test]
    public function replace_in_html_swaps_tokens_in_markup(): void
    {
        $html = '<p>© {current_year} {brand_name}</p>';

        $this->assertSame(
            '<p>© '.date('Y').' Studio</p>',
            GlobalTextTags::replaceInHtml($html, ['brand_name' => 'Studio']),
        );
    }
}
