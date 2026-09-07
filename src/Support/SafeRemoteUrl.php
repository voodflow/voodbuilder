<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

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

    public static function isBlockedHost(string $host): bool
    {
        $host = strtolower(trim($host, " \t\n\r\0\x0B[]"));

        if ($host === '' || in_array($host, self::BLOCKED_HOSTS, true)) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return ! filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            );
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
}
