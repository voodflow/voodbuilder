<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsSmartButtonAnnotator;
use Voodflow\Voodbuilder\Tests\TestCase;

final class GrapesJsSmartButtonAnnotatorTest extends TestCase
{
    #[Test]
    public function it_promotes_standalone_buttons_to_smart_cta_anchors(): void
    {
        $html = '<section><button class="text-white bg-indigo-500 px-8 py-2 rounded">Buy now</button></section>';
        $out = GrapesJsSmartButtonAnnotator::annotate($html);

        $this->assertStringContainsString('data-voodbuilder-cta="true"', $out);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Buy now"', $out);
        $this->assertStringContainsString('<a ', $out);
        $this->assertStringNotContainsString('<button', $out);
    }

    #[Test]
    public function it_skips_form_submit_buttons(): void
    {
        $html = '<form><button type="submit" class="bg-indigo-500 px-4 py-2 rounded">Send</button></form>';
        $out = GrapesJsSmartButtonAnnotator::annotate($html);

        $this->assertStringNotContainsString('data-voodbuilder-cta', $out);
        $this->assertStringContainsString('<button', $out);
    }

    #[Test]
    public function it_promotes_button_like_anchors(): void
    {
        $html = '<a class="btn bg-indigo-500 px-8 py-2 rounded text-white" href="/x">Go</a>';
        $out = GrapesJsSmartButtonAnnotator::annotate($html);

        $this->assertStringContainsString('data-voodbuilder-cta="true"', $out);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Go"', $out);
    }

    #[Test]
    public function it_promotes_outline_cta_links(): void
    {
        $html = '<a class="inline-flex items-center rounded-lg border border-vp-divider px-5 py-3 text-base font-medium" href="#">Learn more</a>';
        $out = GrapesJsSmartButtonAnnotator::annotate($html);

        $this->assertStringContainsString('data-voodbuilder-cta="true"', $out);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Learn more"', $out);
    }

    #[Test]
    public function it_promotes_height_based_cta_anchors_without_py(): void
    {
        $html = '<a href="#" class="inline-flex h-11 items-center justify-center rounded-full bg-vp-brand-1 px-8 text-base font-semibold text-white">Get started</a>';
        $out = GrapesJsSmartButtonAnnotator::annotate($html);

        $this->assertStringContainsString('data-voodbuilder-cta="true"', $out);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Get started"', $out);
        $this->assertStringContainsString('data-vb-link-type="url"', $out);
        $this->assertStringContainsString('role="button"', $out);
    }

    #[Test]
    public function it_skips_plain_text_links(): void
    {
        $html = '<a class="text-vp-brand-1 underline underline-offset-2" href="/docs">Docs</a>';
        $out = GrapesJsSmartButtonAnnotator::annotate($html);

        $this->assertStringNotContainsString('data-voodbuilder-cta', $out);
    }
}
