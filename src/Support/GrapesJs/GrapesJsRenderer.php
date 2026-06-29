<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingRenderer;

final class GrapesJsRenderer
{
    public function html(SitePage $page): string
    {
        $payload = $page->builder_payload ?? [];
        $html = (string) ($payload['html'] ?? '');

        if ($html === '') {
            return '';
        }

        return GrapesJsPlaceholderNormalizer::normalizeHtml(
            GrapesJsCodeBlockNormalizer::normalize(
                TailblocksThemeTokenMigrator::migrateHtml(
                    GrapesJsHtmlSanitizer::sanitize($html),
                ),
            ),
        );
    }

    public function css(SitePage $page): ?string
    {
        $css = $page->builder_payload['css'] ?? null;

        if (! filled($css)) {
            return null;
        }

        return GrapesJsCssSanitizer::sanitize(
            TailblocksThemeTokenMigrator::migrateCss((string) $css),
        );
    }

    public function render(SitePage $page): string
    {
        $html = app(GrapesJsBindingRenderer::class)->render($this->html($page), $page);

        return app(GrapesJsDynamicBlockRenderer::class)->render($html, $page);
    }
}
