<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\License;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class VoodbuilderLicense
{
    public static function isEnforced(): bool
    {
        return (bool) config('voodbuilder.license.enforce', false);
    }

    public static function isValid(): bool
    {
        if (! self::isEnforced()) {
            return true;
        }

        $key = trim((string) config('voodbuilder.license.key', ''));

        if ($key === '') {
            return false;
        }

        return Cache::remember('voodbuilder.license.valid', 3600, function () use ($key): bool {
            return self::validateKey($key);
        });
    }

    public static function invalidateCache(): void
    {
        Cache::forget('voodbuilder.license.valid');
    }

    protected static function validateKey(string $key): bool
    {
        $secret = (string) config('voodbuilder.license.secret', '');

        if ($secret !== '' && hash_equals($secret, $key)) {
            return true;
        }

        return Str::startsWith($key, 'vb_') && strlen($key) >= 24;
    }
}
