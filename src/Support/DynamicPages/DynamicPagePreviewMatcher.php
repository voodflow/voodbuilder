<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DynamicPages;

use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Heuristics to pick a preview record from SitePage metadata (title, slug).
 */
final class DynamicPagePreviewMatcher
{
    /**
     * @return list<string>
     */
    public static function slugCandidates(?SitePage $page): array
    {
        if ($page === null) {
            return [];
        }

        $candidates = [];

        $slug = trim((string) $page->slug);

        if ($slug !== '') {
            $candidates[] = $slug;
        }

        $title = trim((string) $page->title);

        if ($title !== '') {
            $candidates[] = Str::slug($title);
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    public static function titleCandidate(?SitePage $page): ?string
    {
        $title = trim((string) ($page?->title ?? ''));

        return $title !== '' ? $title : null;
    }
}
