<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Closure;
use RuntimeException;

/**
 * Shared SSRF guard for every outbound fetch driven by stored or remote data.
 *
 * Extracted from EditorPageTemplateRemoteImporter, which had the only copy: the remote
 * elements catalog followed absolute `content_url` values with no host check at all, so a
 * compromised catalog could point the server at cloud metadata endpoints.
 */
final class SafeRemoteUrl
{
    /**
     * Loopback, link-local and cloud metadata addresses that must never be fetched.
     */
    private const BLOCKED_HOSTS = ['localhost', '127.0.0.1', '0.0.0.0', '::1', '169.254.169.254'];

    private const MAX_REDIRECTS = 3;

    /** @var (Closure(string): list<string>)|null */
    private static ?Closure $resolver = null;

    public static function isBlockedHost(string $host): bool
    {
        $host = strtolower(trim($host, " \t\n\r\0\x0B[]"));

        if ($host === '' || in_array($host, self::BLOCKED_HOSTS, true)) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return ! self::isPublicIp($host);
        }

        return str_ends_with($host, '.local')
            || str_ends_with($host, '.internal')
            || str_ends_with($host, '.localhost');
    }

    /**
     * Whether a fully qualified URL is safe to fetch server-side.
     *
     * @param  list<string>  $schemes  acceptable URL schemes
     */
    public static function isAllowed(string $url, array $schemes = ['https']): bool
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts)) {
            return false;
        }

        if (! in_array(strtolower((string) ($parts['scheme'] ?? '')), $schemes, true)) {
            return false;
        }

        return ! self::isBlockedHost((string) ($parts['host'] ?? ''));
    }

    /**
     * Resolved addresses of a host name, all of which must be public.
     *
     * Catches names that only look external (`2130706433`, `0x7f.1`, a DNS record pointing at
     * 10.0.0.5). Returns null when the host is blocked; an empty list when it does not resolve.
     *
     * @return list<string>|null
     */
    public static function resolvePublicAddresses(string $host): ?array
    {
        $host = strtolower(trim($host, " \t\n\r\0\x0B[]"));

        if (self::isBlockedHost($host)) {
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $addresses = (self::$resolver ?? self::systemResolver(...))($host);

        foreach ($addresses as $address) {
            if (! self::isPublicIp($address)) {
                return null;
            }
        }

        return $addresses;
    }

    /**
     * Guzzle options that pin the checked address (no DNS rebinding between check and
     * connect) and re-validate every redirect hop.
     *
     * @param  list<string>  $schemes
     * @return array<string, mixed>
     *
     * @throws RuntimeException when the host is blocked or resolves to a private address
     */
    public static function httpOptions(string $url, array $schemes = ['https']): array
    {
        $parts = parse_url(trim($url));
        $host = is_array($parts) ? (string) ($parts['host'] ?? '') : '';
        $addresses = self::resolvePublicAddresses($host);

        if ($addresses === null) {
            throw new RuntimeException('Blocked outbound host.');
        }

        $options = [
            'allow_redirects' => [
                'max' => self::MAX_REDIRECTS,
                'protocols' => $schemes,
                'on_redirect' => static function ($request, $response, $uri) use ($schemes): void {
                    if (! self::isAllowed((string) $uri, $schemes)
                        || self::resolvePublicAddresses((string) $uri->getHost()) === null) {
                        throw new RuntimeException('Blocked outbound redirect.');
                    }
                },
            ],
        ];

        if ($addresses !== [] && defined('CURLOPT_RESOLVE') && filter_var($host, FILTER_VALIDATE_IP) === false) {
            $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
            $port = (int) ($parts['port'] ?? ($scheme === 'http' ? 80 : 443));
            $options['curl'] = [
                CURLOPT_RESOLVE => [sprintf('%s:%d:%s', $host, $port, $addresses[0])],
            ];
        }

        return $options;
    }

    /**
     * @param  (Closure(string): list<string>)|null  $resolver
     */
    public static function resolveUsing(?Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    private static function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        ) !== false;
    }

    /**
     * @return list<string>
     */
    private static function systemResolver(string $host): array
    {
        $addresses = gethostbynamel($host) ?: [];

        if (function_exists('dns_get_record')) {
            foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
                if (isset($record['ipv6'])) {
                    $addresses[] = (string) $record['ipv6'];
                }
            }
        }

        return array_values(array_unique($addresses));
    }
}
