<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Voodflow\Voodbuilder\Enums\PopupAnalyticsEvent;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Models\BuilderPopupEvent;

class PopupsAnalyticsController extends Controller
{
    private const CLOSE_REASONS = ['button', 'overlay', 'escape', 'content'];

    public function store(Request $request): JsonResponse
    {
        if (! config('voodbuilder.popups.enabled', true) || ! Schema::hasTable('voodbuilder_popup_events')) {
            return response()->json(['ok' => true]);
        }

        $validated = $request->validate([
            'popup_id' => ['required', 'uuid', 'exists:voodbuilder_popups,id'],
            'event' => ['required', 'string', Rule::in(PopupAnalyticsEvent::values())],
            'close_reason' => ['nullable', 'string', Rule::in(self::CLOSE_REASONS)],
            'page_path' => ['nullable', 'string', 'max:255'],
        ]);

        $popup = BuilderPopup::query()->find($validated['popup_id']);

        if ($popup === null) {
            return response()->json(['ok' => true]);
        }

        $event = PopupAnalyticsEvent::from($validated['event']);
        $closeReason = $event === PopupAnalyticsEvent::Closed
            ? ($validated['close_reason'] ?? 'button')
            : null;

        BuilderPopupEvent::query()->create([
            'popup_id' => $popup->getKey(),
            'event' => $event,
            'close_reason' => $closeReason,
            'page_path' => $this->normalizePagePath($validated['page_path'] ?? null),
            'occurred_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    private function normalizePagePath(?string $pagePath): ?string
    {
        $path = trim((string) $pagePath);

        if ($path === '') {
            return null;
        }

        return mb_substr($path, 0, 255);
    }
}
