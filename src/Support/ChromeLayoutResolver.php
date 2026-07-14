<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\ChromeLayout;

/**
 * Resolves GrapesJS chrome layouts (header/footer shell) per content channel.
 */
final class ChromeLayoutResolver
{
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

    protected static function resolveLayoutIdForChannel(?string $channelId): ?string
    {
        $layouts = ChromeLayout::query()
            ->where('enabled', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        if ($channelId !== null) {
            foreach ($layouts as $layout) {
                if (in_array($channelId, $layout->assignedChannelIds(), true)) {
                    return $layout->id;
                }
            }
        }

        return $layouts->firstWhere('is_default', true)?->id;
    }
}
