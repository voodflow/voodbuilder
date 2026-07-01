<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Enums\ResolvableLinkType;

final class ResolvableLinkSupport
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function resolve(array $config, string $prefix, ?string $legacyUrlKey = null): ?string
    {
        $type = self::resolveType($config, $prefix, $legacyUrlKey);
        $target = self::target($config, $prefix);

        if ($type === null) {
            return self::resolveLegacyUrl($config, $legacyUrlKey ?? "{$prefix}_url");
        }

        if (blank($target)) {
            return null;
        }

        return match ($type) {
            ResolvableLinkType::Page => self::resolvePageUrl((string) $target),
            ResolvableLinkType::Route => self::resolveRouteUrl((string) $target, self::routeParameters($config, $prefix)),
            ResolvableLinkType::Mail => self::resolveMailUrl((string) $target),
            ResolvableLinkType::Url => (string) $target,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function opensInNewTab(array $config, string $prefix): bool
    {
        $type = self::resolveType($config, $prefix);

        if ($type === ResolvableLinkType::Url) {
            $url = (string) self::target($config, $prefix);

            return str_starts_with($url, 'http');
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $prefixes
     * @return array<string, mixed>
     */
    public static function compressPrefixes(array $data, array $prefixes): array
    {
        foreach ($prefixes as $prefix) {
            $data = self::compressPrefix($data, $prefix);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  list<string>  $prefixes
     * @return array<string, mixed>
     */
    public static function expandPrefixes(array $config, array $prefixes): array
    {
        foreach ($prefixes as $prefix) {
            $config = self::expandPrefix($config, $prefix);
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function compressPrefix(array $data, string $prefix): array
    {
        $parameters = [];

        foreach (MenuRouteParameterField::parameterNames() as $parameterName) {
            $flatKey = self::routeParameterFlatKey($prefix, $parameterName);

            if (! array_key_exists($flatKey, $data)) {
                continue;
            }

            if (filled($data[$flatKey])) {
                $parameters[$parameterName] = $data[$flatKey];
            }

            unset($data[$flatKey]);
        }

        $data[self::routeParametersKey($prefix)] = $parameters === [] ? null : $parameters;

        unset($data["{$prefix}_url"]);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function expandPrefix(array $config, string $prefix): array
    {
        $parameters = $config[self::routeParametersKey($prefix)] ?? null;

        if (! is_array($parameters)) {
            return $config;
        }

        foreach ($parameters as $parameterName => $value) {
            if (! is_string($parameterName)) {
                continue;
            }

            $config[self::routeParameterFlatKey($prefix, $parameterName)] = $value;
        }

        return $config;
    }

    public static function typeField(string $prefix): string
    {
        return "{$prefix}_link_type";
    }

    public static function targetField(string $prefix): string
    {
        return "{$prefix}_link";
    }

    public static function routeParametersKey(string $prefix): string
    {
        return "{$prefix}_route_parameters";
    }

    public static function routeParameterFlatKey(string $prefix, string $parameterName): string
    {
        return "{$prefix}_route_param_{$parameterName}";
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function resolveType(array $config, string $prefix, ?string $legacyUrlKey = null): ?ResolvableLinkType
    {
        $raw = $config[self::typeField($prefix)] ?? null;

        if ($raw instanceof ResolvableLinkType) {
            return $raw;
        }

        if (is_string($raw) && $raw !== '') {
            return ResolvableLinkType::tryFrom($raw);
        }

        $legacyKey = $legacyUrlKey ?? "{$prefix}_url";
        $legacy = $config[$legacyKey] ?? null;

        if (! is_string($legacy) || $legacy === '') {
            return null;
        }

        if (str_starts_with($legacy, 'mailto:')) {
            return ResolvableLinkType::Mail;
        }

        return ResolvableLinkType::Url;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function target(array $config, string $prefix): mixed
    {
        $target = $config[self::targetField($prefix)] ?? null;

        if (filled($target)) {
            return $target;
        }

        $legacy = $config["{$prefix}_url"] ?? null;

        if (! is_string($legacy) || $legacy === '') {
            return null;
        }

        if (str_starts_with($legacy, 'mailto:')) {
            return substr($legacy, 7);
        }

        return $legacy;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    protected static function routeParameters(array $config, string $prefix): array
    {
        $parameters = $config[self::routeParametersKey($prefix)] ?? [];

        if (! is_array($parameters)) {
            $parameters = [];
        }

        foreach (MenuRouteParameterField::parameterNames() as $parameterName) {
            $flatKey = self::routeParameterFlatKey($prefix, $parameterName);

            if (filled($config[$flatKey] ?? null)) {
                $parameters[$parameterName] = $config[$flatKey];
            }
        }

        return array_filter($parameters, fn (mixed $value): bool => filled($value));
    }

    /**
     * @param  array<string, mixed>  $config
     */
    protected static function resolveLegacyUrl(array $config, ?string $legacyUrlKey): ?string
    {
        if ($legacyUrlKey === null) {
            return null;
        }

        $legacy = $config[$legacyUrlKey] ?? null;

        return is_string($legacy) && $legacy !== '' ? $legacy : null;
    }

    protected static function resolvePageUrl(string $slug): ?string
    {
        $page = SitePageResolver::publishedForMenu($slug);

        $url = $page?->getUrl();

        return filled($url) && $url !== '#' ? $url : null;
    }

    /** @param  array<string, mixed>  $parameters */
    protected static function resolveRouteUrl(string $routeName, array $parameters): ?string
    {
        if (! Route::has($routeName)) {
            return null;
        }

        try {
            return route($routeName, $parameters);
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function resolveMailUrl(string $email): string
    {
        return str_starts_with($email, 'mailto:') ? $email : 'mailto:'.$email;
    }
}
