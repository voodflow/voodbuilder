<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;
use Voodflow\Vpress\Enums\MenuItemType;

final class MenuRouteParameterField
{
    /** @var list<Closure(string, string, ?Get): array<string, string>> */
    protected static array $optionsResolvers = [];

    /** @var list<Closure(string, string): string> */
    protected static array $labelResolvers = [];

    /** @var list<Closure(string, string): (string|null)> */
    protected static array $defaultValueResolvers = [];

    /** @var list<Closure(array<string, mixed>): array<string, mixed>> */
    protected static array $beforeCompressResolvers = [];

    /**
     * @param  Closure(string, string, ?Get): array<string, string>  $resolver
     */
    public static function optionsUsing(Closure $resolver): void
    {
        static::$optionsResolvers[] = $resolver;
    }

    /**
     * @param  Closure(string, string): string  $resolver
     */
    public static function labelUsing(Closure $resolver): void
    {
        static::$labelResolvers[] = $resolver;
    }

    /**
     * @param  Closure(string, string): (string|null)  $resolver
     */
    public static function defaultValueUsing(Closure $resolver): void
    {
        static::$defaultValueResolvers[] = $resolver;
    }

    /**
     * @param  Closure(array<string, mixed>): array<string, mixed>  $resolver
     */
    public static function beforeCompressUsing(Closure $resolver): void
    {
        static::$beforeCompressResolvers[] = $resolver;
    }

    /** @return list<string> */
    public static function parameterNames(): array
    {
        $names = MenuRouteCatalog::allRequiredParameterNames();

        if ($names === []) {
            return ['slug', 'locale'];
        }

        usort($names, static function (string $left, string $right): int {
            $priority = ['slug' => 0, 'eventSlug' => 1, 'section' => 2, 'seriesSlug' => 3, 'vtutSlug' => 4, 'locale' => 5];

            return ($priority[$left] ?? 99) <=> ($priority[$right] ?? 99) ?: strcmp($left, $right);
        });

        return $names;
    }

    public static function defaultValue(?string $routeName, string $parameterName): ?string
    {
        if (! is_string($routeName) || $routeName === '') {
            return null;
        }

        foreach (static::$defaultValueResolvers as $resolver) {
            $value = $resolver($routeName, $parameterName);

            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /** @return array<string, string> */
    public static function options(?string $routeName, string $parameterName, ?Get $get = null): array
    {
        if (! is_string($routeName) || $routeName === '') {
            return [];
        }

        foreach (static::$optionsResolvers as $resolver) {
            $options = $resolver($routeName, $parameterName, $get);

            if ($options !== []) {
                return $options;
            }
        }

        return [];
    }

    public static function label(?string $routeName, string $parameterName): string
    {
        if (is_string($routeName) && $routeName !== '') {
            foreach (static::$labelResolvers as $resolver) {
                $label = $resolver($routeName, $parameterName);

                if (filled($label)) {
                    return $label;
                }
            }
        }

        return Str::headline($parameterName);
    }

    public static function flatKey(string $parameterName): string
    {
        return "route_param_{$parameterName}";
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function expandForFill(array $data): array
    {
        $parameters = $data['route_parameters'] ?? null;

        if (! is_array($parameters)) {
            return $data;
        }

        foreach ($parameters as $parameterName => $value) {
            if (! is_string($parameterName)) {
                continue;
            }

            $data[static::flatKey($parameterName)] = $value;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function compressForSave(array $data): array
    {
        foreach (static::$beforeCompressResolvers as $resolver) {
            $data = $resolver($data);
        }

        $parameters = is_array($data['route_parameters'] ?? null)
            ? $data['route_parameters']
            : [];

        foreach (static::parameterNames() as $parameterName) {
            $flatKey = static::flatKey($parameterName);

            if (! array_key_exists($flatKey, $data)) {
                continue;
            }

            if (filled($data[$flatKey])) {
                $parameters[$parameterName] = $data[$flatKey];
            } else {
                unset($parameters[$parameterName]);
            }

            unset($data[$flatKey]);
        }

        foreach (array_keys($data) as $key) {
            if (! is_string($key) || ! str_starts_with($key, 'route_param_')) {
                continue;
            }

            $parameterName = substr($key, strlen('route_param_'));

            if ($parameterName === '' || array_key_exists($parameterName, $parameters)) {
                continue;
            }

            if (filled($data[$key])) {
                $parameters[$parameterName] = $data[$key];
            }

            unset($data[$key]);
        }

        $data['route_parameters'] = $parameters === [] ? null : $parameters;

        return $data;
    }

    /** @return array<int, Field> */
    public static function components(): array
    {
        $fields = [];

        foreach (static::parameterNames() as $parameterName) {
            $flatKey = static::flatKey($parameterName);

            $fields[] = Select::make($flatKey)
                ->key("menu_{$flatKey}_select")
                ->label(fn (Get $get): string => static::label($get('link'), $parameterName))
                ->options(fn (Get $get): array => static::options($get('link'), $parameterName, $get))
                ->searchable()
                ->preload()
                ->live()
                ->hidden(fn (Get $get): bool => ! static::isVisible($get, $parameterName) || static::options($get('link'), $parameterName, $get) === [])
                ->required(fn (Get $get): bool => static::isVisible($get, $parameterName) && static::options($get('link'), $parameterName, $get) !== []);

            $fields[] = TextInput::make($flatKey)
                ->key("menu_{$flatKey}_text")
                ->label(fn (Get $get): string => static::label($get('link'), $parameterName))
                ->hidden(fn (Get $get): bool => ! static::isVisible($get, $parameterName) || static::options($get('link'), $parameterName, $get) !== [])
                ->required(fn (Get $get): bool => static::isVisible($get, $parameterName) && static::options($get('link'), $parameterName, $get) === []);
        }

        return $fields;
    }

    public static function isVisible(Get $get, string $parameterName): bool
    {
        $type = $get('type');

        if ($type instanceof MenuItemType) {
            $menuItemType = $type;
        } elseif (is_string($type)) {
            $menuItemType = MenuItemType::tryFrom($type);
        } else {
            $menuItemType = null;
        }

        if ($menuItemType !== MenuItemType::Route) {
            return false;
        }

        $routeName = $get('link');

        if (! is_string($routeName) || $routeName === '') {
            return false;
        }

        return in_array($parameterName, MenuRouteCatalog::requiredParameterNames($routeName), true);
    }
}
