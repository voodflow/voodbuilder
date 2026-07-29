<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Bindings;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Resolve binding URLs from model accessors, getUrl(), or stored values.
 */
final class BindingUrlResolver
{
    public static function resolve(Model $record, string $fieldId): ?string
    {
        $urlAccessor = Str::camel($fieldId).'Url';

        if (method_exists($record, $urlAccessor)) {
            $url = $record->{$urlAccessor}();

            if (is_string($url) && $url !== '') {
                return self::normalize($url);
            }

            if ($url === null) {
                return null;
            }
        }

        if (in_array($fieldId, ['url', 'link', 'permalink', 'slug'], true) && method_exists($record, 'getUrl')) {
            $url = $record->getUrl();

            if (is_string($url) && $url !== '' && $url !== '#') {
                return self::normalize($url);
            }
        }

        $value = data_get($record, $fieldId);

        if (! is_scalar($value) || (string) $value === '') {
            return null;
        }

        $stringValue = (string) $value;

        if (self::looksLikeAbsoluteOrRootUrl($stringValue)) {
            return self::normalize($stringValue);
        }

        if (method_exists($record, 'getUrl')) {
            $url = $record->getUrl();

            if (is_string($url) && $url !== '' && $url !== '#') {
                return self::normalize($url);
            }
        }

        return self::normalize($stringValue);
    }

    public static function normalize(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $path = parse_url($url, PHP_URL_PATH);
            $query = parse_url($url, PHP_URL_QUERY);

            if (is_string($path) && $path !== '') {
                return is_string($query) && $query !== '' ? $path.'?'.$query : $path;
            }
        }

        return $url;
    }

    protected static function looksLikeAbsoluteOrRootUrl(string $value): bool
    {
        return (bool) preg_match('#^(https?://|/|mailto:|tel:)#i', $value);
    }
}
