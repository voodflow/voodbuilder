<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Voodflow\Voodbuilder\Support\Editor\EditorSmartButtonAnnotator;
use Voodflow\Voodbuilder\Tests\TestCase;

final class EditorSmartButtonAnnotatorTest extends TestCase
{
    #[Test]
    public function it_skips_gallery_thumb_buttons(): void
    {
        $html = '<section data-vx-gallery><button type="button" data-vx-gallery-index="0" class="vx-gallery__thumb"><img src="/x.jpg" alt=""></button></section>';
        $out = EditorSmartButtonAnnotator::annotate($html);

        $this->assertStringNotContainsString('data-voodbuilder-cta', $out);
        $this->assertStringContainsString('data-vx-gallery-index="0"', $out);
    }

    #[Test]
    public function it_promotes_standalone_buttons_to_smart_cta_anchors(): void
    {
        $html = '<section><button class="text-white bg-indigo-500 px-8 py-2 rounded">Buy now</button></section>';
        $out = EditorSmartButtonAnnotator::annotate($html);

        $this->assertStringContainsString('data-voodbuilder-cta="true"', $out);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Buy now"', $out);
        $this->assertStringContainsString('<a ', $out);
        $this->assertStringNotContainsString('<button', $out);
    }

    #[Test]
    public function it_skips_form_submit_buttons(): void
    {
        $html = '<form><button type="submit" class="bg-indigo-500 px-4 py-2 rounded">Send</button></form>';
        $out = EditorSmartButtonAnnotator::annotate($html);

        $this->assertStringNotContainsString('data-voodbuilder-cta', $out);
        $this->assertStringContainsString('<button', $out);
    }

    #[Test]
    public function it_promotes_button_like_anchors(): void
    {
        $html = '<a class="btn bg-indigo-500 px-8 py-2 rounded text-white" href="/x">Go</a>';
        $out = EditorSmartButtonAnnotator::annotate($html);

        $this->assertStringContainsString('data-voodbuilder-cta="true"', $out);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Go"', $out);
    }

    #[Test]
    public function it_promotes_outline_cta_links(): void
    {
        $html = '<a class="inline-flex items-center rounded-lg border border-vp-divider px-5 py-3 text-base font-medium" href="#">Learn more</a>';
        $out = EditorSmartButtonAnnotator::annotate($html);

        $this->assertStringContainsString('data-voodbuilder-cta="true"', $out);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Learn more"', $out);
    }

    #[Test]
    public function it_promotes_height_based_cta_anchors_without_py(): void
    {
        $html = '<a href="#" class="inline-flex h-11 items-center justify-center rounded-full bg-vp-brand-1 px-8 text-base font-semibold text-white">Get started</a>';
        $out = EditorSmartButtonAnnotator::annotate($html);

        $this->assertStringContainsString('data-voodbuilder-cta="true"', $out);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Get started"', $out);
        $this->assertStringContainsString('data-vb-link-type="url"', $out);
        $this->assertStringContainsString('role="button"', $out);
    }

    #[Test]
    public function it_promotes_plain_text_links_without_making_them_ctas(): void
    {
        $html = '<a class="text-vp-brand-1 underline underline-offset-2" href="/docs">Docs</a>';
        $out = EditorSmartButtonAnnotator::annotate($html);

        $this->assertStringNotContainsString('data-voodbuilder-cta', $out);
        $this->assertStringContainsString('vb-text-link', $out);
        $this->assertStringContainsString('data-vb-link-type="url"', $out);
        $this->assertStringContainsString('href="/docs"', $out);
    }

    #[Test]
    public function it_promotes_gallery_read_more_anchors_as_text_links(): void
    {
        $html = <<<'HTML'
<section class="voodbuilder-editor-section">
  <a class="text-indigo-500 inline-flex items-center" href="#">Read more<svg class="w-4 h-4 ml-2"></svg></a>
</section>
HTML;
        $out = EditorSmartButtonAnnotator::annotate($html);

        $this->assertStringNotContainsString('data-voodbuilder-cta', $out);
        $this->assertStringContainsString('vb-text-link', $out);
        $this->assertStringContainsString('data-vb-link-type="url"', $out);
    }

    #[Test]
    public function it_keeps_empty_color_swatch_buttons_native(): void
    {
        $html = '<button class="border-2 border-gray-300 rounded-full w-6 h-6 focus:outline-none"></button>';
        $out = EditorSmartButtonAnnotator::annotate($html);

        $this->assertStringNotContainsString('data-voodbuilder-cta', $out);
        $this->assertStringNotContainsString('Button', $out);
        $this->assertStringContainsString('<button', $out);
    }

    #[Test]
    public function it_keeps_icon_only_buttons_native(): void
    {
        $html = '<button class="rounded-full w-10 h-10 bg-gray-200 p-0 border-0 inline-flex items-center justify-center"><svg viewBox="0 0 24 24"><path d="M12 2z"></path></svg></button>';
        $out = EditorSmartButtonAnnotator::annotate($html);

        $this->assertStringNotContainsString('data-voodbuilder-cta', $out);
        $this->assertStringNotContainsString('Button', $out);
        $this->assertStringContainsString('<button', $out);
        $this->assertStringContainsString('<svg', $out);
    }

    #[Test]
    public function it_promotes_text_button_including_literal_button_label(): void
    {
        $html = '<button class="flex ml-auto text-white bg-indigo-500 border-0 py-2 px-6 rounded">Button</button>';
        $out = EditorSmartButtonAnnotator::annotate($html);

        $this->assertStringContainsString('data-voodbuilder-cta="true"', $out);
        $this->assertStringContainsString('data-voodbuilder-cta-label="Button"', $out);
        $this->assertStringContainsString('>Button</a>', $out);
    }
}
