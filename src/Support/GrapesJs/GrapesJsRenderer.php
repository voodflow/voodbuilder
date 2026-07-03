<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsElementConditionRenderer;

final class GrapesJsRenderer
{
    public function html(SitePage $page): string
    {
        $payload = $page->builder_payload ?? [];
        $html = (string) ($payload['html'] ?? '');

        if ($html === '') {
            return '';
        }

        $html = GrapesJsHtmlSanitizer::stripEditorOnlyElements($html);

        return GrapesJsPlaceholderNormalizer::normalizeHtml(
            GrapesJsFormNormalizer::normalize(
                GrapesJsStepTabsNormalizer::normalize(
                    GrapesJsCodeBlockNormalizer::normalize(
                        VoodbuilderThemeTokenMigrator::migrateHtml(
                            GrapesJsHtmlSanitizer::sanitize($html),
                        ),
                    ),
                ),
            ),
        );
    }

    public function css(SitePage $page): ?string
    {
        $globalCss = app(GrapesJsGlobalClassRenderer::class)->css();
        $payload = $page->builder_payload ?? [];
        $html = (string) ($payload['html'] ?? '');
        $componentCss = app(GrapesJsComponentCssRenderer::class)->cssForHtml($html);
        $storedPageCss = $payload['css'] ?? null;
        $pageCss = GrapesJsPastedComponentNormalizer::resolvePublishedPageCss(
            $html,
            filled($storedPageCss) ? (string) $storedPageCss : null,
        );

        $combined = trim(implode("\n", array_filter([$globalCss, $componentCss, $pageCss])));

        return $combined !== '' ? $combined : null;
    }

    public function js(SitePage $page): ?string
    {
        $js = $page->builder_payload['js'] ?? null;

        if (! filled($js)) {
            return null;
        }

        return GrapesJsJsSanitizer::sanitize((string) $js);
    }

    public function render(SitePage $page): string
    {
        $html = $this->html($page);
        $html = app(GrapesJsElementConditionRenderer::class)->render($html, $page);
        $html = app(GrapesJsComponentRenderer::class)->render($html, $page);
        $html = app(GrapesJsBindingRenderer::class)->render($html, $page);

        return app(GrapesJsDynamicBlockRenderer::class)->render($html, $page);
    }
}
