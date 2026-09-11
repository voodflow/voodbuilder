<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Enums;

enum ApiDataSourceDriver: string
{
    case Http = 'http';
    case Static = 'static';
    case Eloquent = 'eloquent';
    case Callback = 'callback';

    public function label(): string
    {
        return match ($this) {
            self::Http => 'HTTP API',
            self::Static => 'Static rows',
            self::Eloquent => 'Model integration',
            self::Callback => 'Named callback',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }
}
