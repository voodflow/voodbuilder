<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

final class MenuRouteCatalog
{
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

            if (! static::isSelectable($name, $route)) {
                continue;
            }

            $options[$name] = static::formatLabel($name, $route);
        }

        ksort($options);

        return $options;
    }

    public static function activePattern(string $routeName): string
    {
        if (! str_contains($routeName, '.')) {
            return $routeName;
        }

        $withoutAction = preg_replace(
            '/\.(index|show|create|edit|store|update|destroy)$/',
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
        if (! static::acceptsHttpMethod($route)) {
            return false;
        }

        foreach (static::excludePatterns() as $pattern) {
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
        $patterns = config('vpress.menus.route_exclude_patterns', [
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

        return "{$name} ({$uri})";
    }
}
