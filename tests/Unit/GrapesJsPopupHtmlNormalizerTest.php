<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\Popups\GrapesJsPopupHtmlNormalizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsPopupHtmlNormalizerTest extends TestCase
{
    public function test_strips_body_wrapper_from_popup_html(): void
    {
        $html = '<body data-gjs-type="wrapper"><section class="popup-card"><p>Hello</p></section></body>';

        $normalized = GrapesJsPopupHtmlNormalizer::normalize($html);

        $this->assertStringContainsString('<section class="popup-card">', $normalized);
        $this->assertStringNotContainsString('<body', $normalized);
    }
}
