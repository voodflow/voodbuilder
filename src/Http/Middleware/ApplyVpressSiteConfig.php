<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Voodflow\Vpress\Models\VpressSettings;

class ApplyVpressSiteConfig
{
    public function handle(Request $request, Closure $next): Response
    {
        config([
            'seo.canonical_link' => (bool) VpressSettings::get('seo_canonical_enabled', true),
        ]);

        return $next($request);
    }
}
