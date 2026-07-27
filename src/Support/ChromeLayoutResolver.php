<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\ChromeLayout;

/**
 * Resolves GrapesJS chrome layouts (header/footer shell) per content channel.
 *
 * Priority:
 * 1. Enabled non-default layout whose channel_ids includes the current channel
 * 2. Peer channel (docs ↔ tutorials) with an explicit non-default assignment
 * 3. Enabled layout marked as default (site-wide fallback)
 * 4. null → classic app shell
 */
final class ChromeLayoutResolver
{
    /**
     * Channels that share documentation chrome when only one side is assigned.
     *
     * @var array<string, string>
     */
    private const PEER_CHANNELS = [
        'docs' => 'tutorials',
        'tutorials' => 'docs',
    ];

    public static function enabled(): bool
    {
        return (bool) config('voodbuilder.chrome_layouts.enabled', true);
    }

    public static function activeLayout(): ?ChromeLayout
    {
        if (! self::enabled() || ! Schema::hasTable('voodbuilder_chrome_layouts')) {
            return null;
        }

        $channelId = app(ContentChannelRegistry::class)->matchesCurrentRequest()?->id();

        return self::resolveForChannel($channelId);
    }

    public static function resolveForChannel(?string $channelId): ?ChromeLayout
    {
        if (! self::enabled() || ! Schema::hasTable('voodbuilder_chrome_layouts')) {
            return null;
        }

        $cacheKey = 'voodbuilder.chrome_layout_id.'.($channelId ?? 'default');

        /** @var string|null $layoutId */
        $layoutId = Cache::remember($cacheKey, 60, function () use ($channelId): ?string {
            return self::resolveLayoutIdForChannel($channelId);
        });

        if ($layoutId === null) {
            return null;
        }

        return ChromeLayout::query()->find($layoutId);
    }

    public static function forgetCache(?string $channelId = null): void
    {
        if ($channelId !== null) {
            Cache::forget('voodbuilder.chrome_layout_id.'.$channelId);
            Cache::forget('voodbuilder.chrome_layout.'.$channelId);
        }

        Cache::forget('voodbuilder.chrome_layout_id.default');
        Cache::forget('voodbuilder.chrome_layout.default');

        foreach (array_keys(app(ContentChannelRegistry::class)->all()) as $id) {
            Cache::forget('voodbuilder.chrome_layout_id.'.$id);
            Cache::forget('voodbuilder.chrome_layout.'.$id);
        }
    }

    /**
     * @return list<string>
     */
    public static function peerChannelIds(string $channelId): array
    {
        $peer = self::PEER_CHANNELS[$channelId] ?? null;

        return is_string($peer) ? [$peer] : [];
    }

    protected static function resolveLayoutIdForChannel(?string $channelId): ?string
    {
        $layouts = ChromeLayout::query()
            ->where('enabled', true)
            ->orderBy('name')
            ->get();

        if ($channelId !== null) {
            $specific = self::firstSpecificLayoutForChannel($layouts, $channelId);

            if ($specific !== null) {
                return $specific->id;
            }

            // docs ↔ tutorials: one Documentation chrome covers both unless overridden.
            foreach (self::peerChannelIds($channelId) as $peerId) {
                $peerLayout = self::firstSpecificLayoutForChannel($layouts, $peerId);

                if ($peerLayout !== null) {
                    return $peerLayout->id;
                }
            }
        }

        return $layouts->firstWhere('is_default', true)?->id;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ChromeLayout>  $layouts
     */
    protected static function firstSpecificLayoutForChannel($layouts, string $channelId): ?ChromeLayout
    {
        $match = $layouts->first(
            static fn (ChromeLayout $layout): bool => ! $layout->is_default
                && in_array($channelId, $layout->assignedChannelIds(), true),
        );

        return $match instanceof ChromeLayout ? $match : null;
    }
}
