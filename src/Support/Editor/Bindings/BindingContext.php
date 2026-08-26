<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageRegistry;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageRequestContext;

/**
 * Binding Context.
 */
final readonly class BindingContext
{
    /**
     * @param  array<string, mixed>  $routeEntities
     */
    public function __construct(
        public ?SitePage $page = null,
        public ?string $locale = null,
        public mixed $repeatItem = null,
        public bool $editorPreview = false,
        public mixed $routeItem = null,
        public array $routeEntities = [],
        public ?string $dynamicChannel = null,
    ) {}

    public static function forPage(?SitePage $page, mixed $repeatItem = null): self
    {
        $entities = DynamicPageRequestContext::entities();
        $preview = DynamicPageRequestContext::isEditorPreview();

        if ($entities === [] && $page?->is_dynamic) {
            $entities = self::previewEntitiesForPage($page);
            $preview = true;
        }

        return new self(
            page: $page,
            locale: $page?->locale ?? app()->getLocale(),
            repeatItem: $repeatItem,
            editorPreview: $preview || DynamicPageRequestContext::isEditorPreview(),
            routeItem: DynamicPageRequestContext::primaryEntity()
                ?? self::firstEntity($entities),
            routeEntities: $entities,
            dynamicChannel: DynamicPageRequestContext::channel() ?? $page?->dynamic_channel,
        );
    }

    public static function forEditorPreview(SitePage $page): self
    {
        $entities = self::previewEntitiesForPage($page);

        return new self(
            page: $page,
            locale: $page->locale ?? app()->getLocale(),
            repeatItem: null,
            editorPreview: true,
            routeItem: self::firstEntity($entities),
            routeEntities: $entities,
            dynamicChannel: $page->dynamic_channel,
        );
    }

    public function withRepeatItem(mixed $repeatItem): self
    {
        return new self(
            page: $this->page,
            locale: $this->locale,
            repeatItem: $repeatItem,
            editorPreview: $this->editorPreview,
            routeItem: $this->routeItem,
            routeEntities: $this->routeEntities,
            dynamicChannel: $this->dynamicChannel,
        );
    }

    /**
     * @param  array<string, mixed>  $routeEntities
     */
    public function withRouteEntities(array $routeEntities, ?string $channel = null): self
    {
        return new self(
            page: $this->page,
            locale: $this->locale,
            repeatItem: $this->repeatItem,
            editorPreview: $this->editorPreview,
            routeItem: self::firstEntity($routeEntities) ?? $this->routeItem,
            routeEntities: $routeEntities,
            dynamicChannel: $channel ?? $this->dynamicChannel,
        );
    }

    public function routeEntity(string $key): mixed
    {
        return $this->routeEntities[$key] ?? null;
    }

    /**
     * @return array<string, Model|null>
     */
    private static function previewEntitiesForPage(SitePage $page): array
    {
        if (! $page->is_dynamic || blank($page->dynamic_channel)) {
            return [];
        }

        $provider = app(DynamicPageRegistry::class)
            ->get((string) $page->dynamic_channel);

        return $provider?->previewEntities($page) ?? [];
    }

    /**
     * @param  array<string, mixed>  $entities
     */
    private static function firstEntity(array $entities): mixed
    {
        foreach ($entities as $entity) {
            if ($entity instanceof Model) {
                return $entity;
            }
        }

        return null;
    }
}
