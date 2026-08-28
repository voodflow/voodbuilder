<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor\Conditions;

use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageRegistry;

/**
 * Normalize locale-prefixed route names for editor conditions and visibility rules.
 */
final class LogicalRouteName
{
    public static function normalize(string $routeName): string
    {
        if ($routeName === '') {
            return '';
        }

        $normalized = $routeName;

        if (class_exists(DynamicPageRegistry::class)) {
            foreach (app(DynamicPageRegistry::class)->all() as $provider) {
                $normalized = $provider->normalizeRouteName($normalized);
            }
        }

        if (preg_match('/^vevents\.[^.]+\.(.+)$/', $normalized, $matches) === 1) {
            return 'vevents.'.$matches[1];
        }

        return $normalized;
    }
}
