<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

/**
 * Menu Route Catalog.
 */
final class MenuRouteCatalog
{
    /** @var Closure(string, string): (Field|null)|null */
    protected static ?Closure $parameterFieldResolver = null;

    /**
     * @param  Closure(string, string): (Field|null)  $resolver
     */
    public static function parameterFieldUsing(Closure $resolver): void
    {
        self::$parameterFieldResolver = $resolver;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (RouteFacade::getRoutes() as $route) {
            if (! $route instanceof Route) {
                continue;
            }

            $name = $route->getName();

            if (! is_string($name) || $name === '') {
                continue;
            }

            if (! self::isSelectable($name, $route)) {
                continue;
            }

            $options[$name] = self::formatLabel($name, $route);
        }

        ksort($options);

        return $options;
    }

    /** @return list<string> */
    public static function requiredParameterNames(string $routeName): array
    {
        $route = RouteFacade::getRoutes()->getByName($routeName);

        if (! $route instanceof Route) {
            return [];
        }

        $uri = $route->uri();

        return array_values(array_filter(
            $route->parameterNames(),
            static fn (string $name): bool => ! preg_match('/\{'.preg_quote($name, '/').'\?\}/', $uri),
        ));
    }

    /** @return list<string> */
    public static function allRequiredParameterNames(): array
    {
        $names = [];

        foreach (array_keys(self::options()) as $routeName) {
            foreach (self::requiredParameterNames($routeName) as $parameterName) {
                $names[$parameterName] = true;
            }
        }

        return array_keys($names);
    }

    public static function parameterField(string $routeName, string $parameterName): ?Field
    {
        if (self::$parameterFieldResolver !== null) {
            $field = (self::$parameterFieldResolver)($routeName, $parameterName);

            if ($field instanceof Field) {
                return $field;
            }
        }

        return null;
    }

    public static function activePattern(string $routeName): string
    {
        if (! str_contains($routeName, '.')) {
            return $routeName;
        }

        $withoutAction = preg_replace(
            '/\.(index|show|create|edit|store|update|destroy|report|program|editions)$/',
            '',
            $routeName,
        );

        if (is_string($withoutAction) && $withoutAction !== '' && $withoutAction !== $routeName) {
            return $withoutAction.'.*';
        }

        $segments = explode('.', $routeName);

        if (count($segments) > 2) {
            array_pop($segments);

            return implode('.', $segments).'.*';
        }

        return $segments[0].'.*';
    }

    protected static function isSelectable(string $name, Route $route): bool
    {
        if (! self::acceptsHttpMethod($route)) {
            return false;
        }

        foreach (self::excludePatterns() as $pattern) {
            if (Str::is($pattern, $name)) {
                return false;
            }
        }

        return true;
    }

    protected static function acceptsHttpMethod(Route $route): bool
    {
        $methods = array_map('strtoupper', $route->methods());

        return in_array('GET', $methods, true) || in_array('HEAD', $methods, true);
    }

    /** @return list<string> */
    protected static function excludePatterns(): array
    {
        /** @var list<string> $patterns */
        $patterns = config('voodbuilder.menus.route_exclude_patterns', [
            'filament.*',
            'livewire.*',
            'debugbar.*',
            'horizon.*',
            'telescope.*',
            'sanctum.*',
            'storage.*',
            'ignition.*',
            'vapor*',
            'cashier.*',
            'stripe.*',
            'password.*',
            'verification.*',
            'two-factor.*',
            'profile.*',
            'boost.*',
        ]);

        return $patterns;
    }

    protected static function formatLabel(string $name, Route $route): string
    {
        $uri = '/'.ltrim($route->uri(), '/');
        $requiredParameters = self::requiredParameterNames($name);

        if ($requiredParameters !== []) {
            $uri .= ' ['.implode(', ', $requiredParameters).']';
        }

        return "{$name} ({$uri})";
    }
}
