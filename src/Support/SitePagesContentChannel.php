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
        $snippetLength = SearchSettings::snippetLength();

        return SitePage::query()
            ->published()
            ->when(
                class_exists(Locales::class),
                fn ($query) => $query->where('locale', SitePageResolver::preferredLocale()),
            )
            ->where(function ($query) use ($like): void {
                $query->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like)
                    ->orWhere('excerpt', 'like', $like);
            })
            ->orderBy('title')
            ->limit($limit)
            ->get()
            ->map(function (SitePage $page) use ($term, $snippetLength): array {
                $preferred = filled($page->excerpt) ? (string) $page->excerpt : null;
                $body = SearchExcerpt::plainFromHtml($page->displayExcerpt());
                $presented = SearchExcerpt::present($term, [$preferred], $body, $snippetLength);

                return [
                    'title' => $page->title,
                    'url' => $page->getUrl(),
                    'meta' => filled($page->section) ? (string) $page->section : null,
                    'excerpt' => $presented['excerpt'],
                    'excerpt_html' => $presented['excerpt_html'],
                    'body' => $body,
                    'preferred_excerpt' => $preferred,
                    'score' => SearchExcerpt::score($term, (string) $page->title, $page->section, $body ?? $preferred),
                ];
            });
    }
}
