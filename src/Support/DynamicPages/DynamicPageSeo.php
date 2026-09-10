<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DynamicPages;

use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Pick the best SEO subject when a dynamic SitePage template wraps route entities.
 */
final class DynamicPageSeo
{
    /**
     * Prefer the most specific bound entity that exposes SEO (HasSEO / getDynamicSEOData),
     * otherwise fall back to the template page.
     *
     * @param  array<string, Model|null>  $entities
     */
    public static function subject(SitePage $page, array $entities): Model
    {
        $candidates = [];

        foreach ($entities as $entity) {
            if (! $entity instanceof Model) {
                continue;
            }

            if (method_exists($entity, 'getDynamicSEOData') || method_exists($entity, 'seo')) {
                $candidates[] = $entity;
            }
        }

        if ($candidates === []) {
            return $page;
        }

        return $candidates[array_key_last($candidates)];
    }
}
