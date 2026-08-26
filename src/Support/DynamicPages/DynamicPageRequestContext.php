<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DynamicPages;

use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Request-scoped bag for the active dynamic SitePage template + route entities.
 */
final class DynamicPageRequestContext
{
    private static ?SitePage $page = null;

    private static ?string $channel = null;

    /** @var array<string, Model|null> */
    private static array $entities = [];

    private static bool $editorPreview = false;

    /**
     * @param  array<string, Model|null>  $entities
     */
    public static function bind(
        SitePage $page,
        string $channel,
        array $entities = [],
        bool $editorPreview = false,
    ): void {
        self::$page = $page;
        self::$channel = $channel;
        self::$entities = $entities;
        self::$editorPreview = $editorPreview;
    }

    public static function flush(): void
    {
        self::$page = null;
        self::$channel = null;
        self::$entities = [];
        self::$editorPreview = false;
    }

    public static function page(): ?SitePage
    {
        return self::$page;
    }

    public static function channel(): ?string
    {
        return self::$channel;
    }

    /**
     * @return array<string, Model|null>
     */
    public static function entities(): array
    {
        return self::$entities;
    }

    public static function entity(string $key): ?Model
    {
        $entity = self::$entities[$key] ?? null;

        return $entity instanceof Model ? $entity : null;
    }

    /**
     * Primary entity: first non-null value in the bag (stable insertion order).
     */
    public static function primaryEntity(): ?Model
    {
        foreach (self::$entities as $entity) {
            if ($entity instanceof Model) {
                return $entity;
            }
        }

        return null;
    }

    public static function isEditorPreview(): bool
    {
        return self::$editorPreview;
    }

    public static function isBound(): bool
    {
        return self::$page !== null;
    }
}
