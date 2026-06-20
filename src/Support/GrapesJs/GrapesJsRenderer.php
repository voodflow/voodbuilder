<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Voodflow\Vpress\Models\SitePage;

final class GrapesJsRenderer
{
    public function html(SitePage $page): string
    {
        $payload = $page->builder_payload ?? [];
        $html = (string) ($payload['html'] ?? '');

        if ($html === '') {
            return '';
        }

        return TailblocksThemeTokenMigrator::migrateHtml(
            GrapesJsHtmlSanitizer::sanitize($html),
        );
    }

    public function css(SitePage $page): ?string
    {
        $css = $page->builder_payload['css'] ?? null;

        if (! filled($css)) {
            return null;
        }

        return TailblocksThemeTokenMigrator::migrateCss((string) $css);
    }

    public function render(SitePage $page): string
    {
        return app(GrapesJsDynamicBlockRenderer::class)->render($this->html($page), $page);
    }
}
