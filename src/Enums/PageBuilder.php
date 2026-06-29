<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Enums;

enum PageBuilder: string
{
    case RichEditor = 'rich_editor';
    case GrapesJs = 'grapesjs';

    public function label(): string
    {
        return match ($this) {
            self::RichEditor => __('voodbuilder::pro.builders.rich_editor'),
            self::GrapesJs => __('voodbuilder::pro.builders.grapesjs'),
        };
    }

    public static function matches(mixed $state, self $builder): bool
    {
        if ($state instanceof self) {
            return $state === $builder;
        }

        if (blank($state)) {
            return $builder === self::RichEditor;
        }

        return self::tryFrom((string) $state) === $builder;
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
