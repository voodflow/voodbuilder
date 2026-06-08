<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;
use Voodflow\Vtuts\Models\Vtut;
use Voodflow\Vtuts\Support\VtutUrls;
use Voodflow\Vdocs\Models\DocPage;
use Voodflow\Vdocs\Support\Locales as DocLocales;
use Voodflow\Vpress\Models\SitePage;

final class SiteSearch
{
    /**
     * @return array{
     *     tutorials: Collection<int, Tutorial>,
     *     docs: Collection<int, DocPage>,
     *     pages: Collection<int, SitePage>,
     * }
     */
    public static function search(string $term, ?string $type = null): array
    {
        $term = trim($term);

        $results = [
            'tutorials' => self::searchTutorials($term),
            'docs' => self::searchDocs($term),
            'pages' => self::searchPages($term),
        ];

        if ($type === null) {
            return $results;
        }

        return match ($type) {
            'tutorials' => ['tutorials' => $results['tutorials'], 'docs' => new Collection, 'pages' => new Collection],
            'docs' => ['tutorials' => new Collection, 'docs' => $results['docs'], 'pages' => new Collection],
            'pages' => ['tutorials' => new Collection, 'docs' => new Collection, 'pages' => $results['pages']],
            default => $results,
        };
    }

    /**
     * @param  array{tutorials: Collection, docs: Collection, pages: Collection}  $results
     */
    public static function totalCount(array $results): int
    {
        return $results['tutorials']->count()
            + $results['docs']->count()
            + $results['pages']->count();
    }

    /** @return list<string> */
    public static function availableTypes(): array
    {
        $types = [];

        if (Route::has('vtuts.index') || Route::has('vtuts.localized.index')) {
            $types[] = 'tutorials';
        }

        if (Route::has('vdocs.index') || Route::has('vdocs.localized.index')) {
            $types[] = 'docs';
        }

        if (config('vpress.pages.enabled', true)) {
            $types[] = 'pages';
        }

        return $types;
    }

    /** @return Collection<int, Tutorial> */
    protected static function searchTutorials(string $term): Collection
    {
        if ($term === '' || ! (Route::has('vtuts.index') || Route::has('vtuts.localized.index'))) {
            return new Collection;
        }

        return Vtut::query()
            ->with(['category', 'author'])
            ->where('locale', VtutUrls::locale())
            ->publiclyListed()
            ->search($term)
            ->latest('published_at')
            ->limit(self::perType())
            ->get();
    }

    /** @return Collection<int, DocPage> */
    protected static function searchDocs(string $term): Collection
    {
        if ($term === '' || ! (Route::has('vdocs.index') || Route::has('vdocs.localized.index'))) {
            return new Collection;
        }

        return DocPage::query()
            ->with('section')
            ->where('locale', DocLocales::default())
            ->published()
            ->search($term)
            ->orderBy('sort_order')
            ->limit(self::perType())
            ->get();
    }

    /** @return Collection<int, SitePage> */
    protected static function searchPages(string $term): Collection
    {
        if ($term === '' || ! config('vpress.pages.enabled', true)) {
            return new Collection;
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';

        return SitePage::query()
            ->published()
            ->where(function ($query) use ($like): void {
                $query->where('title', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            })
            ->orderBy('title')
            ->limit(self::perType())
            ->get();
    }

    protected static function perType(): int
    {
        return max(1, (int) config('vpress.search.per_type', 20));
    }
}
