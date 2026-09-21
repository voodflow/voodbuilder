<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Voodflow\Voodbuilder\Support\VoodbuilderApiEndpoints;

/**
 * Calls api.voodflow.com for the latest published voodbuilder tag (Anystack releases).
 */
final class VoodbuilderPortalClient
{
    public const LATEST_CACHE_KEY = 'voodbuilder_portal_latest_tag';

    public function getLatestPublishedTag(): ?string
    {
        $hours = max(1, (int) config('voodbuilder.portal.version_cache_ttl_hours', 24));
        $timeout = (int) config('voodbuilder.portal.http_timeout', 10);

        return Cache::remember(self::LATEST_CACHE_KEY, now()->addHours($hours), function () use ($timeout) {
            return $this->fetchLatestTag($timeout);
        });
    }

    public function forgetLatestCache(): void
    {
        Cache::forget(self::LATEST_CACHE_KEY);
    }

    protected function fetchLatestTag(int $timeout): ?string
    {
        try {
            $response = Http::timeout($timeout)
                ->acceptJson()
                ->get(VoodbuilderApiEndpoints::latestVersionUrl());

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
                    return $value;
                }
            }

            $data = $json['data'] ?? null;
            if (is_array($data)) {
                $tag = $data['tag'] ?? null;
                if (is_string($tag) && $tag !== '') {
                    return $tag;
                }
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }
}
