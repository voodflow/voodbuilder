<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\EditorSvgSanitizer;
use Voodflow\Voodbuilder\Support\SafeRemoteUrl;
use Voodflow\Voodbuilder\Tests\TestCase;

final class EditorSvgSanitizerTest extends TestCase
{
    public function test_strips_script_from_svg(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script><rect width="10" height="10"/></svg>';

        $sanitized = EditorSvgSanitizer::sanitize($svg);

        $this->assertStringNotContainsString('alert(1)', $sanitized);
        $this->assertStringContainsString('<rect', $sanitized);
    }

    public function test_strips_event_handlers_and_foreign_object(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">'
            .'<foreignObject><body xmlns="http://www.w3.org/1999/xhtml">x</body></foreignObject>'
            .'<circle cx="5" cy="5" r="4" onclick="alert(2)"/>'
            .'</svg>';

        $sanitized = EditorSvgSanitizer::sanitize($svg);

        $this->assertStringNotContainsStringIgnoringCase('onload', $sanitized);
        $this->assertStringNotContainsStringIgnoringCase('onclick', $sanitized);
        $this->assertStringNotContainsStringIgnoringCase('foreignObject', $sanitized);
        $this->assertStringContainsString('<circle', $sanitized);
    }

    public function test_strips_javascript_hrefs_but_keeps_fragment_references(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'
            .'<a xlink:href="javascript:alert(1)"><rect width="1" height="1"/></a>'
            .'<use xlink:href="#icon"/>'
            .'</svg>';

        $sanitized = EditorSvgSanitizer::sanitize($svg);

        $this->assertStringNotContainsString('javascript:', $sanitized);
        $this->assertStringContainsString('#icon', $sanitized);
    }

    public function test_rejects_entity_declarations(): void
    {
        $svg = '<?xml version="1.0"?>'
            .'<!DOCTYPE svg [<!ENTITY xxe SYSTEM "file:///etc/passwd">]>'
            .'<svg xmlns="http://www.w3.org/2000/svg"><text>&xxe;</text></svg>';

        $sanitized = EditorSvgSanitizer::sanitize($svg);

        $this->assertStringNotContainsString('/etc/passwd', $sanitized);
        $this->assertStringNotContainsString('ENTITY', $sanitized);
    }

    public function test_preserves_a_normal_logo(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">'
            .'<path d="M4 4h16v16H4z" stroke="currentColor" stroke-width="2"/>'
            .'</svg>';

        $sanitized = EditorSvgSanitizer::sanitize($svg);

        $this->assertStringContainsString('viewBox="0 0 24 24"', $sanitized);
        $this->assertStringContainsString('stroke="currentColor"', $sanitized);
        $this->assertStringContainsString('M4 4h16v16H4z', $sanitized);
    }

    public function test_rejects_unparseable_input(): void
    {
        $this->assertSame('', EditorSvgSanitizer::sanitize('<svg><unclosed>'));
    }

    public function test_blocks_internal_hosts_for_remote_fetches(): void
    {
        foreach ([
            'http://localhost/catalog.json',
            'https://127.0.0.1/catalog.json',
            'https://169.254.169.254/latest/meta-data/',
            'https://10.0.0.5/catalog.json',
            'https://192.168.1.1/catalog.json',
            'https://catalog.internal/index.json',
            'https://box.local/index.json',
        ] as $url) {
            $this->assertFalse(
                SafeRemoteUrl::isAllowed($url, ['https', 'http']),
                "Should have been blocked: {$url}",
            );
        }
    }

    public function test_allows_public_catalog_hosts(): void
    {
        $this->assertTrue(SafeRemoteUrl::isAllowed('https://api.voodflow.com/elements/index.json'));
        $this->assertFalse(SafeRemoteUrl::isAllowed('http://api.voodflow.com/x.json'), 'http must not pass an https-only check');
        $this->assertTrue(SafeRemoteUrl::isAllowed('http://api.voodflow.com/x.json', ['https', 'http']));
    }
}
