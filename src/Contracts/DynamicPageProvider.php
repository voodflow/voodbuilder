<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Companion contract for dynamic SitePage templates that claim public routes.
 *
 * Register with {@see Voodbuilder::dynamicPageProvider()}. Extend
 * {@see \Voodflow\Voodbuilder\Support\DynamicPages\AbstractDynamicPageProvider}
 * so optional contract methods keep working when the interface grows.
 *
 * A published dynamic SitePage for this channel + claimable route wins over
 * the companion Blade view.
 *
 * Route patterns with 1+n parameters stay on the companion routes
 * (e.g. /exhibitors/{slug}/events/{eventSlug}); the SitePage only declares
 * which claimable route names it owns.
 */
interface DynamicPageProvider
{
    /** Channel id aligned with contentChannel (e.g. exhibitors, events). */
    public function channelId(): string;

    public function channelLabel(): string;

    /**
     * Claimable Laravel route names → admin labels (include path pattern hints).
     *
     * Use stable logical names (e.g. vevents.show). Locale-prefixed names are
     * normalized via {@see normalizeRouteName()}.
     *
     * @return array<string, string>
     */
    public function claimableRoutes(): array;

    /**
     * Entity bag keys companions pass to DynamicPageResolver (e.g. exhibitor, event).
     *
     * @return list<string>
     */
    public function entityKeys(): array;

    /**
     * Map a matched route name to the logical claimable key used on SitePage.
     */
    public function normalizeRouteName(string $routeName): string;

    /** Example public URL for Filament / editor preview. */
    public function previewUrl(?SitePage $page = null): ?string;

    /**
     * Sample entities for editor preview when no request context is bound.
     *
     * @return array<string, Model|null>
     */
    public function previewEntities(?SitePage $page = null): array;

    /**
     * Resolve entities from public-route slugs (editor binding preview AJAX).
     *
     * @param  array<string, string>  $slugs
     * @return array<string, Model|null>
     */
    public function resolveRouteEntitiesFromSlugs(array $slugs): array;

    /**
     * Editor binding source id for this channel's ".current" context (e.g. vexhibitors.current).
     */
    public function currentBindingSourceId(): string;

    /**
     * Pathname regex patterns for editor binding preview when route_entities are absent.
     *
     * Regex strings are JavaScript-compatible (e.g. ^/exhibitors/([^/]+)$).
     *
     * @return list<array{regex: string, keys: list<string>}>
     */
    public function editorRouteEntityPatterns(): array;
}
