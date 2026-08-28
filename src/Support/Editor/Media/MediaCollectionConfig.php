<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Media;

/**
 * Normalized config for attached-media gallery blocks.
 */
final class MediaCollectionConfig
{
    /** @var list<string> */
    public const ALLOWED_COLLECTIONS = ['gallery', 'logo', 'attachments', 'event_gallery'];

    /**
     * @param  array<string, mixed>  $config
     * @return array{
     *     collections: list<string>,
     *     collection: string,
     *     layout: string,
     *     columns: int,
     *     limit: int,
     *     order: string,
     *     heading: string|null,
     *     show_captions: bool,
     *     lightbox: bool
     * }
     */
    public static function normalize(array $config): array
    {
        $layout = (string) ($config['layout'] ?? 'grid');

        if (! in_array($layout, ['grid', 'masonry'], true)) {
            $layout = 'grid';
        }

        $order = (string) ($config['order'] ?? 'manual');

        if (! in_array($order, ['manual', 'random'], true)) {
            $order = 'manual';
        }

        $collections = self::normalizeCollections($config);
        $limit = max(0, min(48, (int) ($config['limit'] ?? 0)));
        $columns = max(1, min(6, (int) ($config['columns'] ?? 3)));
        $heading = filled($config['heading'] ?? null) ? trim((string) $config['heading']) : null;

        return [
            'collections' => $collections,
            'collection' => $collections[0],
            'layout' => $layout,
            'columns' => $columns,
            'limit' => $limit,
            'order' => $order,
            'heading' => $heading,
            'show_captions' => (bool) ($config['show_captions'] ?? true),
            'lightbox' => (bool) ($config['lightbox'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<string>
     */
    public static function normalizeCollections(array $config): array
    {
        $raw = $config['collections'] ?? $config['collection'] ?? 'gallery';

        if (is_string($raw)) {
            $raw = [$raw];
        }

        if (! is_array($raw)) {
            $raw = ['gallery'];
        }

        $collections = [];

        foreach ($raw as $item) {
            $name = is_string($item) ? trim($item) : '';

            if ($name !== '' && in_array($name, self::ALLOWED_COLLECTIONS, true)) {
                $collections[] = $name;
            }
        }

        $collections = array_values(array_unique($collections));

        if ($collections === []) {
            return ['gallery'];
        }

        return $collections;
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return self::normalize([]);
    }
}
