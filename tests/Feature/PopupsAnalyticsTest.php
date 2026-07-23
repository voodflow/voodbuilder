<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Feature;

use Voodflow\Voodbuilder\Enums\PopupAnalyticsEvent;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Models\BuilderPopupEvent;
use Voodflow\Voodbuilder\Support\GrapesJs\Popups\PopupAnalytics;
use Voodflow\Voodbuilder\Tests\TestCase;

class PopupsAnalyticsTest extends TestCase
{
    public function test_can_track_popup_events(): void
    {
        $popup = BuilderPopup::query()->create([
            'name' => 'Promo',
            'enabled' => true,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>Promo</div>',
        ]);

        $this->postJson(route('voodbuilder.popups.events'), [
            'popup_id' => $popup->getKey(),
            'event' => PopupAnalyticsEvent::Shown->value,
            'page_path' => '/welcome',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->postJson(route('voodbuilder.popups.events'), [
            'popup_id' => $popup->getKey(),
            'event' => PopupAnalyticsEvent::Closed->value,
            'close_reason' => 'overlay',
        ])->assertOk();

        $this->postJson(route('voodbuilder.popups.events'), [
            'popup_id' => $popup->getKey(),
            'event' => PopupAnalyticsEvent::CtaClick->value,
        ])->assertOk();

        $this->assertDatabaseCount('voodbuilder_popup_events', 3);
        $this->assertDatabaseHas('voodbuilder_popup_events', [
            'popup_id' => $popup->getKey(),
            'event' => PopupAnalyticsEvent::Shown->value,
            'page_path' => '/welcome',
        ]);
        $this->assertDatabaseHas('voodbuilder_popup_events', [
            'popup_id' => $popup->getKey(),
            'event' => PopupAnalyticsEvent::Closed->value,
            'close_reason' => 'overlay',
        ]);
    }

    public function test_rejects_invalid_event_payload(): void
    {
        $popup = BuilderPopup::query()->create([
            'name' => 'Promo',
            'enabled' => true,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>Promo</div>',
        ]);

        $this->postJson(route('voodbuilder.popups.events'), [
            'popup_id' => $popup->getKey(),
            'event' => 'unknown',
        ])->assertUnprocessable();
    }

    public function test_summarize_filters_by_period(): void
    {
        $popup = BuilderPopup::query()->create([
            'name' => 'Promo',
            'enabled' => true,
            'rules' => BuilderPopup::defaultRules(),
            'html' => '<div>Promo</div>',
        ]);

        BuilderPopupEvent::query()->create([
            'popup_id' => $popup->getKey(),
            'event' => PopupAnalyticsEvent::Shown,
            'occurred_at' => now()->subDays(40),
        ]);

        BuilderPopupEvent::query()->create([
            'popup_id' => $popup->getKey(),
            'event' => PopupAnalyticsEvent::Shown,
            'occurred_at' => now()->subDays(2),
        ]);

        BuilderPopupEvent::query()->create([
            'popup_id' => $popup->getKey(),
            'event' => PopupAnalyticsEvent::CtaClick,
            'occurred_at' => now()->subDay(),
        ]);

        $analytics = app(PopupAnalytics::class);
        $range = $analytics->resolvePeriod('30d');
        $stats = $analytics->summarize($popup, $range['from'], $range['until']);

        $this->assertSame(1, $stats['shown']);
        $this->assertSame(1, $stats['cta_click']);
        $this->assertSame(100.0, $stats['conversion_rate']);

        $allTime = $analytics->summarize($popup);

        $this->assertSame(2, $allTime['shown']);
        $this->assertSame(1, $allTime['cta_click']);
        $this->assertSame(50.0, $allTime['conversion_rate']);
    }
}
