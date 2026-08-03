<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Enums;

/**
 * Page Builder enumeration.
 */
enum PageBuilder: string
{
    case RichEditor = 'rich_editor';
    case Visual = 'visual';

    public function label(): string
    {
        return match ($this) {
            self::RichEditor => __('voodbuilder::pro.builders.rich_editor'),
            self::Visual => __('voodbuilder::pro.builders.visual'),
        };
    }

    public static function matches(mixed $state, self $builder): bool
    {
        if ($state instanceof self) {
            return $state === $builder;
        }

        if (blank($state)) {
            return $builder === self::Visual;
        }

        return self::normalize($state) === $builder;
    }

    /**
     * Dual-read: accept legacy persisted value `grapesjs` as Visual.
     */
    public static function normalize(mixed $state): ?self
    {
        if ($state instanceof self) {
            return $state;
        }

        if (blank($state)) {
            return null;
        }

        $value = (string) $state;

        if ($value === 'grapesjs') {
            return self::Visual;
        }

        return self::tryFrom($value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
