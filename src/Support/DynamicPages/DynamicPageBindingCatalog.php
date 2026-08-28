<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DynamicPages;

use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Maps dynamic SitePage channels to editor binding source ids (.current).
 */
final class DynamicPageBindingCatalog
{
    /**
     * @return list<string>
     */
    public static function currentSourceIds(?SitePage $page): array
    {
        if ($page === null || ! $page->is_dynamic || blank($page->dynamic_channel)) {
            return [];
        }

        $channel = (string) $page->dynamic_channel;
        $provider = app(DynamicPageRegistry::class)->get($channel);

        if ($provider === null) {
            return [];
        }

        $sources = [$provider->currentBindingSourceId()];

        $related = app(DynamicPageRelatedBindingSourceRegistry::class)
            ->sourceIdsFor($channel, $page);

        return array_values(array_unique([...$sources, ...$related]));
    }
}
