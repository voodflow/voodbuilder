<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs\Popups;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Enums\PopupAnalyticsEvent;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Models\BuilderPopupEvent;

class PopupAnalytics
{
    /**
     * @return array{
     *     shown: int,
     *     closed: int,
     *     cta_click: int,
     *     conversion_rate: float|null,
     *     close_rate: float|null,
     *     from: string|null,
     *     until: string|null,
     *     daily: list<array{date: string, shown: int, closed: int, cta_click: int}>
     * }
     */
    public function summarize(
        BuilderPopup $popup,
        ?CarbonInterface $from = null,
        ?CarbonInterface $until = null,
    ): array {
        if (! Schema::hasTable('voodbuilder_popup_events')) {
            return $this->emptySummary($from, $until);
        }

        $fromAt = $from?->copy()->startOfDay();
        $untilAt = $until?->copy()->endOfDay();

        $query = BuilderPopupEvent::query()
            ->where('popup_id', $popup->getKey());

        if ($fromAt !== null) {
            $query->where('occurred_at', '>=', $fromAt);
        }

        if ($untilAt !== null) {
            $query->where('occurred_at', '<=', $untilAt);
        }

        /** @var \Illuminate\Support\Collection<int, object{event: mixed, aggregate: mixed}> $countRows */
        $countRows = $query
            ->clone()
            ->selectRaw('event, COUNT(*) as aggregate')
            ->groupBy('event')
            ->get();

        $counts = [];

        foreach ($countRows as $row) {
            $event = $row->event instanceof PopupAnalyticsEvent
                ? $row->event->value
                : (string) $row->event;
            $counts[$event] = (int) $row->aggregate;
        }

        $shown = $counts[PopupAnalyticsEvent::Shown->value] ?? 0;
        $closed = $counts[PopupAnalyticsEvent::Closed->value] ?? 0;
        $ctaClick = $counts[PopupAnalyticsEvent::CtaClick->value] ?? 0;

        $dailyRows = $query
            ->clone()
            ->selectRaw('DATE(occurred_at) as day, event, COUNT(*) as aggregate')
            ->groupByRaw('DATE(occurred_at), event')
            ->orderBy('day')
            ->get();

        /** @var array<string, array{date: string, shown: int, closed: int, cta_click: int}> $daily */
        $daily = [];

        foreach ($dailyRows as $row) {
            $day = (string) $row->day;

            if (! isset($daily[$day])) {
                $daily[$day] = [
                    'date' => $day,
                    'shown' => 0,
                    'closed' => 0,
                    'cta_click' => 0,
                ];
            }

            $event = $row->event instanceof PopupAnalyticsEvent
                ? $row->event->value
                : (string) $row->event;

            if (array_key_exists($event, $daily[$day])) {
                $daily[$day][$event] = (int) $row->aggregate;
            }
        }

        return [
            'shown' => $shown,
            'closed' => $closed,
            'cta_click' => $ctaClick,
            'conversion_rate' => $shown > 0 ? round(($ctaClick / $shown) * 100, 1) : null,
            'close_rate' => $shown > 0 ? round(($closed / $shown) * 100, 1) : null,
            'from' => $fromAt?->toDateString(),
            'until' => $untilAt?->toDateString(),
            'daily' => array_values($daily),
        ];
    }

    /**
     * @return array{from: CarbonInterface|null, until: CarbonInterface|null}
     */
    public function resolvePeriod(string $period, mixed $from = null, mixed $until = null): array
    {
        $now = Carbon::now();

        return match ($period) {
            '7d' => [
                'from' => $now->copy()->subDays(6)->startOfDay(),
                'until' => $now->copy()->endOfDay(),
            ],
            '30d' => [
                'from' => $now->copy()->subDays(29)->startOfDay(),
                'until' => $now->copy()->endOfDay(),
            ],
            '90d' => [
                'from' => $now->copy()->subDays(89)->startOfDay(),
                'until' => $now->copy()->endOfDay(),
            ],
            'custom' => [
                'from' => filled($from) ? Carbon::parse((string) $from)->startOfDay() : null,
                'until' => filled($until) ? Carbon::parse((string) $until)->endOfDay() : null,
            ],
            default => [
                'from' => null,
                'until' => null,
            ],
        };
    }

    /**
     * @return array{
     *     shown: int,
     *     closed: int,
     *     cta_click: int,
     *     conversion_rate: float|null,
     *     close_rate: float|null,
     *     from: string|null,
     *     until: string|null,
     *     daily: list<array{date: string, shown: int, closed: int, cta_click: int}>
     * }
     */
    private function emptySummary(?CarbonInterface $from, ?CarbonInterface $until): array
    {
        return [
            'shown' => 0,
            'closed' => 0,
            'cta_click' => 0,
            'conversion_rate' => null,
            'close_rate' => null,
            'from' => $from?->toDateString(),
            'until' => $until?->toDateString(),
            'daily' => [],
        ];
    }
}
