<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Voodflow\Voodbuilder\Support\Editor\EditorHtmlSecuritySanitizer;
use Voodflow\Voodbuilder\Tests\TestCase;

final class EditorHtmlSecuritySanitizerTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function xssVectorProvider(): array
    {
        return [
            'script tag' => ['<div><script>alert(1)</script></div>', 'alert(1)'],
            'unquoted handler' => ['<div onclick=alert(1)>x</div>', 'onclick'],
            'svg onload' => ['<svg/onload=alert(1)></svg>', 'onload'],
            'img onerror' => ['<img src=x onerror="alert(1)">', 'onerror'],
            'body onload uppercase' => ['<div ONLOAD="alert(1)">x</div>', 'onload'],
            'iframe srcdoc' => ['<iframe srcdoc="<script>alert(1)</script>"></iframe>', 'srcdoc'],
            'javascript href' => ['<a href="javascript:alert(1)">x</a>', 'javascript:'],
            'javascript href entity encoded' => ['<a href="&#106;avascript:alert(1)">x</a>', 'javascript:'],
            'javascript href with newline' => ["<a href=\"java\nscript:alert(1)\">x</a>", 'script:'],
            'javascript href tab padded' => ['<a href="  javascript:alert(1)">x</a>', 'javascript:'],
            'vbscript href' => ['<a href="vbscript:msgbox(1)">x</a>', 'vbscript:'],
            'data html href' => ['<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>', 'data:text/html'],
            'object tag' => ['<object data="evil.swf"></object>', '<object'],
            'embed tag' => ['<embed src="evil.swf">', '<embed'],
            'base tag' => ['<base href="https://evil.test/">', '<base'],
            'meta refresh' => ['<meta http-equiv="refresh" content="0;url=javascript:alert(1)">', '<meta'],
            'svg foreignObject' => ['<svg><foreignObject><script>alert(1)</script></foreignObject></svg>', 'alert(1)'],
            'style expression' => ['<div style="width:expression(alert(1))">x</div>', 'expression'],
            'style javascript url' => ['<div style="background:url(javascript:alert(1))">x</div>', 'javascript:'],
            'form javascript action' => ['<form action="javascript:alert(1)"></form>', 'javascript:'],
            'xlink javascript' => ['<svg><a xlink:href="javascript:alert(1)">x</a></svg>', 'javascript:'],
            'formaction javascript' => ['<button formaction="javascript:alert(1)">x</button>', 'javascript:'],
        ];
    }

    #[DataProvider('xssVectorProvider')]
    public function test_strips_xss_vectors(string $html, string $mustNotContain): void
    {
        $sanitized = EditorHtmlSecuritySanitizer::sanitize($html);

        $this->assertStringNotContainsStringIgnoringCase(
            $mustNotContain,
            $sanitized,
            "Vector survived sanitization: {$html}",
        );
    }

    /**
     * The block contract lives in custom data attributes that companions extend without the
     * core knowing their names. Losing them silently corrupts every saved page.
     */
    public function test_preserves_builder_and_companion_data_attributes(): void
    {
        $html = '<section data-voodbuilder-block="hero" data-voodbuilder-config="{&quot;a&quot;:1}" '
            .'class="flex items-center" data-vforms-visibility="auth" data-vb-bg-opacity="50" '
            .'data-gjs-type="voodbuilder-section" data-acme-thirdparty="keep-me">'
            .'<h1 data-voodbuilder-bind="demo.latest.title">Title</h1>'
            .'</section>';

        $sanitized = EditorHtmlSecuritySanitizer::sanitize($html);

        foreach ([
            'data-voodbuilder-block="hero"',
            'data-voodbuilder-config',
            'class="flex items-center"',
            'data-vforms-visibility="auth"',
            'data-vb-bg-opacity="50"',
            'data-gjs-type="voodbuilder-section"',
            'data-acme-thirdparty="keep-me"',
            'data-voodbuilder-bind="demo.latest.title"',
        ] as $needle) {
            $this->assertStringContainsString($needle, $sanitized);
        }
    }

    public function test_preserves_inline_svg_placeholder_images(): void
    {
        $html = '<img src="data:image/svg+xml,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3C%2Fsvg%3E" alt="Hero">';

        $this->assertStringContainsString(
            'data:image/svg+xml',
            EditorHtmlSecuritySanitizer::sanitize($html),
        );
    }

    public function test_preserves_legitimate_links_and_media(): void
    {
        $html = '<a href="/pages/about">About</a>'
            .'<a href="https://example.com">External</a>'
            .'<a href="mailto:hi@example.com">Mail</a>'
            .'<a href="tel:+390123">Call</a>'
            .'<a href="#section">Anchor</a>'
            .'<img src="/storage/hero.jpg" alt="Hero" loading="lazy">'
            .'<iframe src="https://www.youtube.com/embed/x" allowfullscreen></iframe>';

        $sanitized = EditorHtmlSecuritySanitizer::sanitize($html);

        foreach ([
            'href="/pages/about"',
            'href="https://example.com"',
            'href="mailto:hi@example.com"',
            'href="tel:+390123"',
            'href="#section"',
            'src="/storage/hero.jpg"',
            'loading="lazy"',
            'youtube.com/embed/x',
        ] as $needle) {
            $this->assertStringContainsString($needle, $sanitized);
        }
    }

    public function test_preserves_author_css_in_style_element(): void
    {
        $html = '<style>.hero{color:red}</style><div class="hero">x</div>';

        $this->assertStringContainsString('.hero{color:red}', EditorHtmlSecuritySanitizer::sanitize($html));
    }

    public function test_keeps_author_scripts_only_when_capability_is_granted(): void
    {
        $html = '<div>x</div><script>console.log(1)</script>';

        $this->assertStringNotContainsString(
            'console.log',
            EditorHtmlSecuritySanitizer::sanitize($html),
        );

        $this->assertStringContainsString(
            'console.log',
            EditorHtmlSecuritySanitizer::sanitize($html, allowAuthorScripts: true),
        );
    }

    public function test_strips_handlers_even_when_author_scripts_are_allowed(): void
    {
        $sanitized = EditorHtmlSecuritySanitizer::sanitize(
            '<div onclick="alert(1)">x</div>',
            allowAuthorScripts: true,
        );

        $this->assertStringNotContainsStringIgnoringCase('onclick', $sanitized);
    }

    public function test_leaves_empty_input_untouched(): void
    {
        $this->assertSame('', EditorHtmlSecuritySanitizer::sanitize(''));
        $this->assertSame('   ', EditorHtmlSecuritySanitizer::sanitize('   '));
    }
}
