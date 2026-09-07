<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Support\Editor\DemoEditorLanding;
use Voodflow\Voodbuilder\Tests\TestCase;

class DemoEditorLandingTest extends TestCase
{
    public function test_payload_includes_demo_hero_and_sections(): void
    {
        $payload = DemoEditorLanding::payload();

        $this->assertArrayHasKey('html', $payload);
        $this->assertArrayHasKey('css', $payload);
        $this->assertStringContainsString('Demo Expo 2026', $payload['html']);
        $this->assertStringContainsString('Why Exhibit at Demo Expo?', $payload['html']);
        $this->assertStringContainsString('20,000+', $payload['html']);
        $this->assertStringContainsString('Apply to exhibit', $payload['html']);
    }
}
