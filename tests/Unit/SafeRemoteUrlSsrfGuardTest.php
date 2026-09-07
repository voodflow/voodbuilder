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
}
