<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\DynamicPages;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

/**
 * Companion controllers: claim a dynamic SitePage template when published.
 */
trait RendersDynamicSitePage
{
    /**
     * @param  array<string, Model|null>  $entities
     */
    protected function renderDynamicSitePage(string $channel, string $routeName, array $entities = []): ?View
    {
        return DynamicPageResolver::maybeRender($channel, $routeName, $entities);
    }
}
