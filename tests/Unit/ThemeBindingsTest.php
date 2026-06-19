<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Tests\Unit;

use Voodflow\Vpress\Enums\SubThemeCapability;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Support\ThemeBindings;
use Voodflow\Vpress\Tests\TestCase;

class ThemeBindingsTest extends TestCase
{
    public function test_site_pages_require_landing_capability(): void
    {
        $this->assertSame(SubThemeCapability::Landing, ThemeBindings::sitePagesCapability());
        $this->assertTrue(ThemeBindings::themeSupportsCapability('events', SubThemeCapability::Landing));
        $this->assertFalse(ThemeBindings::themeSupportsCapability('default', SubThemeCapability::Landing));
    }

    public function test_channel_binding_rejects_incompatible_theme(): void
    {
        $this->assertFalse(ThemeBindings::isValidChannelBinding('events', 'news'));
        $this->assertTrue(ThemeBindings::isValidChannelBinding('events', 'events'));
    }

    public function test_custom_landing_theme_can_bind_to_events_channel(): void
    {
        app(SubThemeRegistry::class)->register('showcase-alt', [
            'label' => 'Showcase alt',
            'capabilities' => ['landing'],
            'layouts' => [
                'landing' => 'vpress::themes.events.layouts.landing',
            ],
        ]);

        $this->assertTrue(ThemeBindings::isValidChannelBinding('events', 'showcase-alt'));
    }
}
