<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Contracts\PublicContentChannel;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Vtuts\Support\Locales;

/**
 * Site Pages Content Channel.
 */
final class SitePagesContentChannel implements PublicContentChannel
{
    public function id(): string
    {
        return 'pages';
    }

    public function label(): string
    {
        return __('voodbuilder::search.filters.pages');
    }

    public function routePatterns(): array
    {
        return ['voodbuilder.pages.*', 'home', 'home.localized'];
    }

    public function subTheme(): ?string
    {
        return null;
    }

    public function search(string $term, int $limit = 20): Collection
    {
        $term = trim($term);

        if ($term === '' || ! config('voodbuilder.pages.enabled', true)) {
            return collect();
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return SitePage::query()
            ->published()
            ->when(
                class_exists(Locales::class),
                fn ($query) => $query->where('locale', SitePageResolver::preferredLocale()),
            )
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
