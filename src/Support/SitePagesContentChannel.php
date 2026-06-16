<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Support\Collection;
use Voodflow\Vpress\Contracts\PublicContentChannel;
use Voodflow\Vpress\Models\SitePage;

final class SitePagesContentChannel implements PublicContentChannel
{
    public function id(): string
    {
        return 'pages';
    }

    public function label(): string
    {
        return __('vpress::search.filters.pages');
    }

    public function routePatterns(): array
    {
        return ['vpress.pages.*'];
    }

    public function subTheme(): ?string
    {
        return null;
    }

    public function search(string $term, int $limit = 20): Collection
    {
        $term = trim($term);

        if ($term === '' || ! config('vpress.pages.enabled', true)) {
            return collect();
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return SitePage::query()
            ->published()
            ->where(function ($query) use ($like): void {
                $query->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            })
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(fn (SitePage $page): array => [
                'title' => $page->title,
                'url' => $page->getUrl(),
                'excerpt' => null,
            ]);
    }
}
