<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Support\SiteVisit\SiteVisitPath;
use Voodflow\Voodbuilder\Support\SiteVisit\SiteVisitWorkflowRunner;
use Voodflow\Voodbuilder\Support\VoodflowIntegration;
use Voodflow\Vcookiebar\Vcookiebar;

final class SiteVisitPublicController extends Controller
{
    public function __construct(
        private readonly SiteVisitWorkflowRunner $runner,
    ) {}

    public function store(Request $request): JsonResponse
    {
        if (! VoodflowIntegration::enabled()) {
            return response()->json(['queued' => []]);
        }

        if (! Vcookiebar::allows('marketing')) {
            return response()->json(['queued' => [], 'blocked' => 'marketing_consent']);
        }

        $validated = $request->validate([
            'path' => ['required', 'string', 'max:2048'],
            'url' => ['nullable', 'string', 'max:2048'],
            'page_id' => ['nullable', 'string', 'max:64'],
            'menu_item_id' => ['nullable', 'string', 'max:64'],
            'visitor_key' => ['nullable', 'string', 'max:128'],
            'locale' => ['nullable', 'string', 'max:16'],
        ]);

        $visitorKey = $this->resolveVisitorKey($request, $validated['visitor_key'] ?? null);

        if ($visitorKey === '') {
            return response()->json(['queued' => [], 'visitor_key' => null], 422);
        }

        $path = SiteVisitPath::normalize((string) $validated['path']);
        $queued = $this->runner->handle([
            'path' => $path,
            'url' => (string) ($validated['url'] ?? $request->headers->get('referer') ?? $path),
            'page_id' => $validated['page_id'] ?? null,
            'menu_item_id' => $validated['menu_item_id'] ?? null,
            'visitor_key' => $visitorKey,
            'user_id' => Auth::id() !== null ? (string) Auth::id() : null,
            'locale' => $validated['locale'] ?? app()->getLocale(),
        ]);

        \Illuminate\Support\Facades\Log::info('Voodbuilder: site visit beacon', [
            'path' => $path,
            'page_id' => $validated['page_id'] ?? null,
            'queued' => count($queued),
            'visitor_key' => $visitorKey,
        ]);

        $response = response()->json([
            'queued' => count($queued),
            'visitor_key' => $visitorKey,
        ]);

        return $response->cookie(
            SiteVisitWorkflowRunner::VISITOR_COOKIE,
            $visitorKey,
            60 * 24 * 365,
            '/',
            null,
            $request->isSecure(),
            false,
            false,
            'Lax',
        );
    }

    private function resolveVisitorKey(Request $request, ?string $fromBody): string
    {
        $candidates = [
            trim((string) $fromBody),
            trim((string) $request->cookie(SiteVisitWorkflowRunner::VISITOR_COOKIE)),
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && strcasecmp($candidate, SiteVisitWorkflowRunner::VISITOR_COOKIE) !== 0) {
                return $candidate;
            }
        }

        return (string) Str::uuid();
    }
}
