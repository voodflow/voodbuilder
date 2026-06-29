<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\GrapesJs\SoundmitGrapesJsLanding;
use Voodflow\Voodbuilder\Tests\TestCase;

class SoundmitGrapesJsLandingTest extends TestCase
{
    public function test_payload_includes_soundmit_hero_and_sections(): void
    {
        $payload = SoundmitGrapesJsLanding::payload();

        $this->assertArrayHasKey('html', $payload);
        $this->assertArrayHasKey('css', $payload);
        $this->assertStringContainsString('Soundmit 2026', $payload['html']);
        $this->assertStringContainsString('Why Exhibit at Soundmit?', $payload['html']);
        $this->assertStringContainsString('20,000+', $payload['html']);
        $this->assertStringContainsString('Apply to exhibit', $payload['html']);
    }
}
