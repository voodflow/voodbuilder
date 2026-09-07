<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Enums\SitePageRevisionKind;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\SitePageRevision;
use Voodflow\Voodbuilder\Modules\History\HistoryModule;

/**
 * Site Page Revision Recorder.
 */
final class SitePageRevisionRecorder
{
    /**
     * Keep the state a deliberate save replaced.
     *
     * @param  array<string, mixed>  $previousPayload
     */
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
            'kind' => SitePageRevisionKind::Manual,
            'builder_payload' => $previousPayload,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        $this->trimOldRevisions($page, SitePageRevisionKind::Manual);
    }

    /**
     * Park work in progress without publishing it.
     *
     * Unlike a manual revision this stores the *current* editor state, not the state being
     * replaced: the point is to recover what was never saved. `builder_payload` is left
     * untouched, so an autosave never reaches the live site.
     *
     * @param  array<string, mixed>  $payload
     */
    public function recordAutosave(SitePage $page, array $payload): ?SitePageRevision
    {
        if (! HistoryModule::isEnabled()) {
            return null;
        }

        $latest = $this->latestAutosave($page);

        // Idle editors, background tabs and a canvas that merely re-serializes itself all
        // tick the same timer; writing an identical row each time would evict the older
        // autosaves that still hold recoverable work.
        if ($latest !== null && ! $this->payloadDiffers($latest->builder_payload ?? [], $payload)) {
            return $latest;
        }

        if (! $this->payloadDiffers($page->builder_payload ?? [], $payload)) {
            return null;
        }

        $revision = SitePageRevision::query()->create([
            'site_page_id' => $page->getKey(),
            'kind' => SitePageRevisionKind::Autosave,
            'builder_payload' => $payload,
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        $this->trimOldRevisions($page, SitePageRevisionKind::Autosave);

        return $revision;
    }

    public function latestAutosave(SitePage $page): ?SitePageRevision
    {
        return SitePageRevision::query()
            ->where('site_page_id', $page->getKey())
            ->autosaves()
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Autosaves only describe work that is still unsaved.
     */
    public function discardAutosaves(SitePage $page): void
    {
        SitePageRevision::query()
            ->where('site_page_id', $page->getKey())
            ->autosaves()
            ->delete();
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

    protected function trimOldRevisions(SitePage $page, SitePageRevisionKind $kind): void
    {
        $max = $kind->maxToKeep();

        if ($max <= 0) {
            return;
        }

        // Scoped to one kind: a shared budget would let autosaves evict the history an
        // author actually curated.
        $idsToKeep = SitePageRevision::query()
            ->where('site_page_id', $page->getKey())
            ->where('kind', $kind)
            ->orderByDesc('id')
            ->limit($max)
            ->pluck('id');

        if ($idsToKeep->isEmpty()) {
            return;
        }

        SitePageRevision::query()
            ->where('site_page_id', $page->getKey())
            ->where('kind', $kind)
            ->whereNotIn('id', $idsToKeep)
            ->delete();
    }
}
