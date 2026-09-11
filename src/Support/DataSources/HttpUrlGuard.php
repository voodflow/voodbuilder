<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DataSources;

use Illuminate\Support\Str;

/**
 * SSRF hardening for outbound HTTP data sources (parity with vforms).
 */
final class HttpUrlGuard
{
    public static function isSafeHttpUrl(string $url): bool
    {
        if ($url === '' || str_contains($url, "\0")) {
            return false;
        }

        $parts = parse_url($url);
        if (! is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $scheme = Str::lower((string) $parts['scheme']);
        if (! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = Str::lower((string) $parts['host']);
        if (in_array($host, [
            'localhost',
            'metadata.google.internal',
            'metadata',
            'metadata.aws.internal',
            'instance-data',
        ], true)) {
            return false;
        }

        if (str_ends_with($host, '.localhost')
            || str_ends_with($host, '.local')
            || str_ends_with($host, '.internal')
            || str_ends_with($host, '.lan')
        ) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return self::isPublicIp($host);
        }

        $resolved = @gethostbyname($host);
        if ($resolved === $host || $resolved === false || $resolved === '') {
            // Unresolvable — reject rather than guess.
            return false;
        }

        return self::isPublicIp($resolved);
    }

    private static function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return (bool) filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return (bool) filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );
        }

        return false;
    }
}
