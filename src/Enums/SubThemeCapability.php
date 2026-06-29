<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Enums;

enum SubThemeCapability: string
{
    case Doc = 'doc';
    case Article = 'article';
    case Landing = 'landing';

    public function label(): string
    {
        return match ($this) {
            self::Doc => __('voodbuilder::sub_themes.capabilities.doc'),
            self::Article => __('voodbuilder::sub_themes.capabilities.article'),
            self::Landing => __('voodbuilder::sub_themes.capabilities.landing'),
        };
    }

    /**
     * @param  list<string|self>  $values
     * @return list<self>
     */
    public static function parseList(array $values): array
    {
        $parsed = [];

        foreach ($values as $value) {
            $capability = $value instanceof self
                ? $value
                : self::tryFrom((string) $value);

            if ($capability !== null) {
                $parsed[$capability->value] = $capability;
            }
        }

        return array_values($parsed);
    }

    /**
     * @return list<self>
     */
    public static function fromLegacyType(SubThemeType $type): array
    {
        return match ($type) {
            SubThemeType::Content => [self::Doc, self::Article],
            SubThemeType::Marketing => [self::Landing],
        };
    }
}
