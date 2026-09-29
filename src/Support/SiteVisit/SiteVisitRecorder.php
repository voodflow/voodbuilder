<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\SiteVisit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Throwable;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;
use Voodflow\Voodbuilder\Support\VoodflowIntegration;
use Voodflow\Vcookiebar\Vcookiebar;

/**
 * Record a public site visit for Voodflow Site Visit triggers.
 *
 * Prefer calling this from page controllers (server-side) so workflows do not
 * depend on the browser beacon / CSRF. The JS beacon remains for SPA navigations
 * and non-VoodBuilder URLs matched by menu/path.
 */
final class SiteVisitRecorder
{
    public static function recordCurrentRequest(?SitePage $page = null): void
    {
        if (! VoodflowIntegration::enabled()) {
            return;
        }

        $request = request();

        if (! $request instanceof Request) {
            return;
        }

        // Editor / preview must not fire marketing / popup workflows.
        if ($request->boolean('edit') || $request->boolean('preview')) {
            return;
        }

        // Visitor id + site-visit workflows require marketing consent (vcookiebar).
        if (! Vcookiebar::allows('marketing')) {
            return;
        }

        try {
            $visitorKey = self::resolveVisitorKey($request);
            $path = SiteVisitPath::normalize('/'.$request->path());
            $pageId = $page instanceof SitePage ? (string) $page->getKey() : null;

            if ($page instanceof SitePage) {
                $path = SiteVisitPath::fromUrl(VoodbuilderUrls::page($page));
            }

            app(SiteVisitWorkflowRunner::class)->handle([
                'path' => $path,
                'url' => $request->fullUrl(),
                'page_id' => $pageId,
                'visitor_key' => $visitorKey,
                'user_id' => Auth::id() !== null ? (string) Auth::id() : null,
                'locale' => $page instanceof SitePage
                    ? (string) $page->locale
                    : (string) app()->getLocale(),
            ]);

            Cookie::queue(
                cookie(
                    SiteVisitWorkflowRunner::VISITOR_COOKIE,
                    $visitorKey,
                    60 * 24 * 365,
                    '/',
                    null,
                    $request->isSecure(),
                    false,
                    false,
                    'Lax',
                ),
            );
        } catch (Throwable) {
            // Visits are best-effort — never break public page rendering.
        }
    }

    private static function resolveVisitorKey(Request $request): string
    {
        $fromCookie = trim((string) $request->cookie(SiteVisitWorkflowRunner::VISITOR_COOKIE));

        if ($fromCookie !== '' && strcasecmp($fromCookie, SiteVisitWorkflowRunner::VISITOR_COOKIE) !== 0) {
            return $fromCookie;
        }

        return (string) Str::uuid();
    }
}
