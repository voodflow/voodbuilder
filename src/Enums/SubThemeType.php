<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Enums;

enum SubThemeType: string
{
    case Content = 'content';
    case Marketing = 'marketing';

    public function label(): string
    {
        return match ($this) {
            self::Content => __('voodbuilder::sub_themes.types.content'),
            self::Marketing => __('voodbuilder::sub_themes.types.marketing'),
        };
    }

    public static function fromDefinition(array $definition): self
    {
        $type = $definition['type'] ?? null;

        if ($type instanceof self) {
            return $type;
        }

        if (is_string($type)) {
            return self::tryFrom($type) ?? self::Marketing;
        }

        return self::Marketing;
    }
}
