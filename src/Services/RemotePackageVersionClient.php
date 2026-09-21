<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Voodflow\Voodbuilder\Support\VoodbuilderApiEndpoints;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;

/**
 * Latest published tag for a package — Packagist (public) or api.voodflow.com (Anystack portal).
 */
final class RemotePackageVersionClient
{
    public const CACHE_PREFIX = 'voodbuilder_remote_package_latest.';

    /**
     * @param  string|null  $comparedToInstalled  When local is ahead of a cached remote tag, refresh once
     *                                            (avoids stale "Ahead of Packagist" after a new release).
     */
    public function latest(string $channel, string $identifier, ?string $comparedToInstalled = null): ?string
    {
        $channel = strtolower(trim($channel));
        $identifier = trim($identifier);

        if ($identifier === '' || ! in_array($channel, ['portal', 'packagist'], true)) {
            return null;
        }

        $hours = max(1, (int) config('voodbuilder.portal.version_cache_ttl_hours', 24));
        $cacheKey = self::CACHE_PREFIX.$channel.'.'.str_replace('/', '.', $identifier);

        $cached = Cache::get($cacheKey);

        if (is_string($cached) && $cached !== '' && $comparedToInstalled !== null && trim($comparedToInstalled) !== '') {
            $local = VoodbuilderPackageVersion::normalize($comparedToInstalled);
            $remote = VoodbuilderPackageVersion::normalize($cached);

            if ($local !== '0.0.0' && $remote !== '0.0.0' && version_compare($local, $remote, '>')) {
                Cache::forget($cacheKey);
            }
        }

        return Cache::remember($cacheKey, now()->addHours($hours), function () use ($channel, $identifier): ?string {
            return $channel === 'packagist'
                ? $this->fetchPackagist($identifier)
                : $this->fetchPortal($identifier);
        });
    }

    public function forget(string $channel, string $identifier): void
    {
        Cache::forget(self::CACHE_PREFIX.strtolower(trim($channel)).'.'.str_replace('/', '.', trim($identifier)));
    }

    public function forgetAllKnown(): void
    {
        Cache::forget(VoodbuilderPortalClient::LATEST_CACHE_KEY);
    }

    private function fetchPortal(string $slug): ?string
    {
        $timeout = (int) config('voodbuilder.portal.http_timeout', 10);

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get(VoodbuilderApiEndpoints::latestVersionUrlFor($slug));

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();

            if (! is_array($json)) {
                return null;
            }

            foreach (['tag', 'latest_tag', 'version', 'latest'] as $key) {
                $value = $json[$key] ?? null;

                if (is_string($value) && $value !== '') {
                    return VoodbuilderPackageVersion::normalize($value) !== '0.0.0'
                        ? VoodbuilderPackageVersion::normalize($value)
                        : ltrim(trim($value), 'vV');
                }
            }

            $data = $json['data'] ?? null;

            if (is_array($data) && is_string($data['tag'] ?? null) && $data['tag'] !== '') {
                return VoodbuilderPackageVersion::normalize((string) $data['tag']);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function fetchPackagist(string $composerName): ?string
    {
        if (! str_contains($composerName, '/')) {
            return null;
        }

        $timeout = (int) config('voodbuilder.portal.http_timeout', 10);
        $url = 'https://repo.packagist.org/p2/'.$composerName.'.json';

        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $json = $response->json();
            $packages = is_array($json) ? ($json['packages'][$composerName] ?? null) : null;

            if (! is_array($packages) || $packages === []) {
                return null;
            }

            $highest = null;

            foreach ($packages as $release) {
                if (! is_array($release)) {
                    continue;
                }

                $raw = $release['version'] ?? null;

                if (! is_string($raw) || $raw === '') {
                    continue;
                }

                // Skip Composer branch aliases / dev builds.
                if (str_starts_with($raw, 'dev-') || str_ends_with($raw, '-dev')) {
                    continue;
                }

                $normalized = VoodbuilderPackageVersion::normalize($raw);

                if ($normalized === '0.0.0') {
                    continue;
                }

                if ($highest === null || version_compare($normalized, $highest, '>')) {
                    $highest = $normalized;
                }
            }

            return $highest;
        } catch (\Throwable) {
            return null;
        }
    }
}
