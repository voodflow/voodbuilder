<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DynamicPages;

use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Optional extra ".current" binding sources for a dynamic page channel.
 *
 * Companions register related sources (e.g. vevents.current on exhibitor templates).
 */
final class DynamicPageRelatedBindingSourceRegistry
{
    /** @var array<string, list<array{sourceId: string, when: callable(SitePage): bool}>> */
    private array $entries = [];

    public function register(string $channelId, string $sourceId, callable $when): void
    {
        $this->entries[$channelId][] = [
            'sourceId' => $sourceId,
            'when' => $when,
        ];
    }

    /**
     * @return list<string>
     */
    public function sourceIdsFor(string $channelId, SitePage $page): array
    {
        $sourceIds = [];

        foreach ($this->entries[$channelId] ?? [] as $entry) {
            if (($entry['when'])($page)) {
                $sourceIds[] = $entry['sourceId'];
            }
        }

        return array_values(array_unique($sourceIds));
    }
}
