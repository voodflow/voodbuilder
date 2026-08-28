<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DynamicPages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Resolves dynamic route entities for editor binding preview (including AJAX).
 */
final class DynamicPagePreviewEntityResolver
{
    /**
     * @return array<string, Model|null>
     */
    public static function entitiesForEditorPreview(SitePage $page, ?Request $request = null): array
    {
        $slugBag = self::slugBagFromRequest($request);

        if ($slugBag !== []) {
            $resolved = self::resolveSlugBag($page, $slugBag);

            if (self::hasResolvedModel($resolved)) {
                return $resolved;
            }
        }

        if (DynamicPageRequestContext::isBound()) {
            return DynamicPageRequestContext::entities();
        }

        $provider = app(DynamicPageRegistry::class)->get((string) $page->dynamic_channel);

        return $provider?->previewEntities($page) ?? [];
    }

    /**
     * @param  array<string, Model|null>  $entities
     * @return array<string, string>
     */
    public static function slugBagFromModels(array $entities): array
    {
        $bag = [];

        foreach ($entities as $key => $model) {
            if (! $model instanceof Model) {
                continue;
            }

            $slug = $model->getAttribute('slug');

            if (is_string($slug) && $slug !== '') {
                $bag[$key] = $slug;
            }
        }

        return $bag;
    }

    /**
     * @return array<string, string>
     */
    public static function slugBagFromRequestContext(): array
    {
        if (! DynamicPageRequestContext::isBound()) {
            return [];
        }

        return self::slugBagFromModels(DynamicPageRequestContext::entities());
    }

    /**
     * @return array<string, string>
     */
    private static function slugBagFromRequest(?Request $request): array
    {
        if ($request === null) {
            return [];
        }

        $raw = $request->query('route_entities');

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        $bag = [];

        foreach ($decoded as $key => $value) {
            $key = trim((string) $key);
            $value = trim((string) $value);

            if ($key === '' || $value === '') {
                continue;
            }

            $bag[$key] = $value;
        }

        return $bag;
    }

    /**
     * @param  array<string, string>  $slugBag
     * @return array<string, Model|null>
     */
    private static function resolveSlugBag(SitePage $page, array $slugBag): array
    {
        if (! $page->is_dynamic || blank($page->dynamic_channel)) {
            return [];
        }

        $provider = app(DynamicPageRegistry::class)->get((string) $page->dynamic_channel);

        return $provider?->resolveRouteEntitiesFromSlugs($slugBag) ?? [];
    }

    /**
     * @param  array<string, Model|null>  $entities
     */
    private static function hasResolvedModel(array $entities): bool
    {
        foreach ($entities as $entity) {
            if ($entity instanceof Model) {
                return true;
            }
        }

        return false;
    }
}
