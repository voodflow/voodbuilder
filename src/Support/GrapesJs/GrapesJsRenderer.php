<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Voodflow\Vpress\Models\SitePage;

final class GrapesJsRenderer
{
    public function html(SitePage $page): string
    {
        $payload = $page->builder_payload ?? [];

        return (string) ($payload['html'] ?? '');
    }

    public function css(SitePage $page): ?string
    {
        $css = $page->builder_payload['css'] ?? null;

        return filled($css) ? (string) $css : null;
    }

    public function render(SitePage $page): string
    {
        return app(GrapesJsDynamicBlockRenderer::class)->render($this->html($page), $page);
    }
}
