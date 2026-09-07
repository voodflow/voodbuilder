<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\EmptySiteGuidance;
use Voodflow\Voodbuilder\Tests\TestCase;

class EmptySiteGuidanceTest extends TestCase
{
    public function test_needs_chrome_layout_when_none_enabled(): void
    {
        $this->assertTrue(EmptySiteGuidance::needsChromeLayout());
        $this->assertSame(
            __('voodbuilder::home.empty.title_needs_layout'),
            EmptySiteGuidance::title(),
        );
        $this->assertSame(
            __('voodbuilder::home.empty.cta_layout'),
            EmptySiteGuidance::ctaLabel(true),
        );
    }

    public function test_needs_page_when_an_enabled_layout_exists(): void
    {
        ChromeLayout::query()->create([
            'name' => 'Site shell',
            'slug' => 'site-shell',
            'enabled' => true,
            'is_default' => true,
            'html' => '<div data-voodbuilder-chrome-content-slot></div>',
        ]);

        $this->assertFalse(EmptySiteGuidance::needsChromeLayout());
        $this->assertSame(
            __('voodbuilder::home.empty.title_needs_page'),
            EmptySiteGuidance::title(),
        );
        $this->assertSame(
            __('voodbuilder::home.empty.cta_page'),
            EmptySiteGuidance::ctaLabel(true),
        );
    }

    public function test_skips_layout_step_when_chrome_layouts_disabled(): void
    {
        config(['voodbuilder.chrome_layouts.enabled' => false]);

        $this->assertFalse(EmptySiteGuidance::needsChromeLayout());
        $this->assertSame(
            __('voodbuilder::home.empty.title_needs_page'),
            EmptySiteGuidance::title(),
        );
    }
}
