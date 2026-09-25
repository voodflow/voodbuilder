<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\SafeRemoteUrl;
use Voodflow\Voodbuilder\Tests\TestCase;

class SafeRemoteUrlSsrfGuardTest extends TestCase
{
    public function test_rejects_metadata_loopback_private_and_file_schemes(): void
    {
        $this->assertFalse(SafeRemoteUrl::isAllowed('http://169.254.169.254/latest/meta-data', ['http', 'https']));
        $this->assertFalse(SafeRemoteUrl::isAllowed('http://127.0.0.1/secrets', ['http', 'https']));
        $this->assertFalse(SafeRemoteUrl::isAllowed('http://10.0.0.1/internal', ['http', 'https']));
        $this->assertFalse(SafeRemoteUrl::isAllowed('file:///etc/passwd', ['http', 'https']));
        $this->assertTrue(SafeRemoteUrl::isAllowed('https://api.voodflow.com/voodbuilder/elements/index.json', ['http', 'https']));
    }

    protected function tearDown(): void
    {
        SafeRemoteUrl::resolveUsing(null);

        parent::tearDown();
    }

    public function test_rejects_public_looking_names_that_resolve_to_private_addresses(): void
    {
        SafeRemoteUrl::resolveUsing(static fn (string $host): array => match ($host) {
            'rebind.example.com' => ['93.184.216.34', '10.0.0.5'],
            '2130706433' => ['127.0.0.1'],
            default => ['93.184.216.34'],
        });

        $this->assertNull(SafeRemoteUrl::resolvePublicAddresses('rebind.example.com'));
        $this->assertNull(SafeRemoteUrl::resolvePublicAddresses('2130706433'));
        $this->assertSame(['93.184.216.34'], SafeRemoteUrl::resolvePublicAddresses('templates.example.com'));
    }

    public function test_http_options_pin_the_checked_address_and_guard_redirects(): void
    {
        SafeRemoteUrl::resolveUsing(static fn (string $host): array => ['93.184.216.34']);

        $options = SafeRemoteUrl::httpOptions('https://templates.example.com/catalog.json');

        $this->assertSame(['templates.example.com:443:93.184.216.34'], $options['curl'][CURLOPT_RESOLVE]);
        $this->assertSame(['https'], $options['allow_redirects']['protocols']);
        $this->assertIsCallable($options['allow_redirects']['on_redirect']);
    }

    public function test_http_options_refuse_private_resolution(): void
    {
        SafeRemoteUrl::resolveUsing(static fn (string $host): array => ['192.168.1.10']);

        $this->expectException(\RuntimeException::class);

        SafeRemoteUrl::httpOptions('https://intranet.example.com/catalog.json');
    }
}
