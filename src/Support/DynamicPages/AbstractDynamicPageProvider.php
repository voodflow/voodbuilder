<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DynamicPages;

use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Contracts\DynamicPageProvider;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Base dynamic page provider with safe defaults for optional contract methods.
 *
 * Companion packages should extend this class so interface extensions do not
 * break save/render when a method is added with a default implementation.
 */
abstract class AbstractDynamicPageProvider implements DynamicPageProvider
{
    abstract public function channelId(): string;

    abstract public function channelLabel(): string;

    abstract public function claimableRoutes(): array;

    abstract public function entityKeys(): array;

    abstract public function currentBindingSourceId(): string;

    public function normalizeRouteName(string $routeName): string
    {
        return $routeName;
    }

    public function previewUrl(?SitePage $page = null): ?string
    {
        return null;
    }

    /**
     * @return array<string, Model|null>
     */
    public function previewEntities(?SitePage $page = null): array
    {
        return [];
    }

    /**
     * @param  array<string, string>  $slugs
     * @return array<string, Model|null>
     */
    public function resolveRouteEntitiesFromSlugs(array $slugs): array
    {
        return [];
    }

    /**
     * @return list<array{regex: string, keys: list<string>}>
     */
    public function editorRouteEntityPatterns(): array
    {
        return [];
    }

    /**
     * Build editor pathname patterns for /{prefix}/{slug} and optional /events/{event}.
     *
     * @param  list<string>  $entityKeys  e.g. ['exhibitor', 'event']
     * @return list<array{regex: string, keys: list<string>}>
     */
    protected function routePatternsForPrefix(string $prefix, array $entityKeys): array
    {
        $quoted = preg_quote(trim($prefix, '/'), '/');
        $patterns = [];
        $primary = $entityKeys[0] ?? null;

        if ($primary === null) {
            return [];
        }

        if (in_array('event', $entityKeys, true)) {
            $patterns[] = [
                'regex' => "^/{$quoted}/([^/]+)/events/([^/]+)$",
                'keys' => [$primary, 'event'],
            ];
        }

        $patterns[] = [
            'regex' => "^/{$quoted}/([^/]+)$",
            'keys' => [$primary],
        ];

        return $patterns;
    }

    /**
     * Single-segment slug pattern (e.g. /events/{event}).
     *
     * @return list<array{regex: string, keys: list<string>}>
     */
    protected function singleSlugPatternsForPrefix(string $prefix, string $entityKey): array
    {
        $quoted = preg_quote(trim($prefix, '/'), '/');

        return [
            [
                'regex' => "^/{$quoted}/([^/]+)$",
                'keys' => [$entityKey],
            ],
        ];
    }
}
