<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;

/**
 * Gate the editor HTTP surface behind the page builder permission.
 *
 * `auth` alone is not a meaningful boundary here: any registered site user (a newsletter
 * subscriber, a customer account) would otherwise reach endpoints that spawn Node processes,
 * write to the public disk and enumerate the block catalog.
 */
class EnsurePageBuilderAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(PageBuilderAccess::userCanUsePageBuilder(), 403);

        return $next($request);
    }
}
