<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\SitePageRevision;
use Voodflow\Voodbuilder\Modules\History\HistoryModule;

final class SitePageRevisionRecorder
{
    public function recordIfChanged(SitePage $page, array $previousPayload): void
    {
        if (! HistoryModule::isEnabled()) {
            return;
        }

        if (! $this->payloadDiffers($previousPayload, $page->builder_payload ?? [])) {
            return;
        }

        SitePageRevision::query()->create([
            'site_page_id' => $page->getKey(),
            'builder_payload' => $previousPayload,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        $this->trimOldRevisions($page);
    }

    /**
     * @param  array<string, mixed>  $previous
     * @param  array<string, mixed>  $current
     */
    protected function payloadDiffers(array $previous, array $current): bool
    {
        $normalize = static fn (array $payload): array => [
            'html' => (string) ($payload['html'] ?? ''),
            'css' => (string) ($payload['css'] ?? ''),
            'js' => (string) ($payload['js'] ?? ''),
        ];

        return $normalize($previous) !== $normalize($current);
    }

    protected function trimOldRevisions(SitePage $page): void
    {
        $max = (int) config('voodbuilder.grapesjs.revisions.max_to_keep', 50);

        if ($max <= 0) {
            return;
        }

        $idsToKeep = SitePageRevision::query()
            ->where('site_page_id', $page->getKey())
            ->orderByDesc('id')
            ->limit($max)
            ->pluck('id');

        if ($idsToKeep->isEmpty()) {
            return;
        }

        SitePageRevision::query()
            ->where('site_page_id', $page->getKey())
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }
}
