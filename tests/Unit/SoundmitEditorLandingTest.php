<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\SoundmitEditorLanding;
use Voodflow\Voodbuilder\Tests\TestCase;

class SoundmitEditorLandingTest extends TestCase
{
    public function test_payload_includes_soundmit_hero_and_sections(): void
    {
        $payload = SoundmitEditorLanding::payload();

        $this->assertArrayHasKey('html', $payload);
        $this->assertArrayHasKey('css', $payload);
        $this->assertStringContainsString('Soundmit 2026', $payload['html']);
        $this->assertStringContainsString('Why Exhibit at Soundmit?', $payload['html']);
        $this->assertStringContainsString('20,000+', $payload['html']);
        $this->assertStringContainsString('Apply to exhibit', $payload['html']);
    }
}
