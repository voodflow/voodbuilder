<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsHtmlSanitizer;
use Voodflow\Voodbuilder\Tests\TestCase;

class GrapesJsHtmlSanitizerTest extends TestCase
{
    public function test_sanitizes_malformed_percent_in_google_maps_src(): void
    {
        $html = '<iframe src="https://maps.google.com/maps?width=100%&amp;height=600&amp;q=test"></iframe>';

        $sanitized = GrapesJsHtmlSanitizer::sanitize($html);

        $this->assertStringContainsString('width=100%25&', $sanitized);
        $this->assertStringNotContainsString('width=100%&', $sanitized);
    }

    public function test_preserves_valid_percent_encoding(): void
    {
        $value = 'https://example.test/path?q=%C4%B0zmir';

        $this->assertSame(
            'https://example.test/path?q=%C4%B0zmir',
            GrapesJsHtmlSanitizer::encodeMalformedPercentSequences($value),
        );
    }
}
