<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Editor Default Block Config.
 */
final class EditorDefaultBlockConfig
{
    /**
     * @param  class-string<\Filament\Forms\Components\RichEditor\RichContentCustomBlock>  $blockClass
     * @return array<string, mixed>
     */
    public static function for(string $blockClass, ?int $eventId = null): array
    {
        $blockId = $blockClass::getId();
        $eventId ??= self::firstPublishedEventId();

        $defaults = self::defaults();

        $config = $defaults[$blockId] ?? [];

        if ($config === [] && str_starts_with($blockId, 'event_landing_')) {
            $landingId = 'landing_'.substr($blockId, strlen('event_landing_'));
            $config = $defaults[$landingId] ?? [];
        }

        if ($eventId > 0 && self::needsEventId($blockId) && empty($config['event_id'])) {
            $config['event_id'] = $eventId;
        }

        return $config;
    }

    public static function needsEventId(string $blockId): bool
    {
        return app(EditorBlockConfigRegistry::class)->requiresEventId($blockId);
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function mergeEventId(array $config, string $blockId, ?int $eventId): array
    {
        if ($eventId > 0 && self::needsEventId($blockId) && empty($config['event_id'])) {
            $config['event_id'] = $eventId;
        }

        return $config;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return app(EditorBlockConfigRegistry::class)->allDefaults();
    }

    public static function firstPublishedEventId(): ?int
    {
        return app(EditorBlockConfigRegistry::class)->resolvePublishedEventId();
    }
}
