<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\ChromeLayoutManagedContent;
use Voodflow\Voodbuilder\Support\GlobalTextTags;
use Voodflow\Voodbuilder\Support\Editor\Bindings\EditorBindingRenderer;
use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorElementConditionRenderer;
use Voodflow\Voodbuilder\Support\ThemePalette;

final class EditorRenderer
{
    public function html(SitePage $page): string
    {
        $payload = $page->builder_payload ?? [];
        $html = (string) ($payload['html'] ?? '');

        if ($html === '') {
            return '';
        }

        if (ChromeLayoutManagedContent::sitePageUsesChromeShell($page)) {
            $html = ChromeLayoutManagedContent::stripSiteChromeFromPageHtml($html);
        }

        $html = ChromeLayoutManagedContent::stripChromeEditorBleedFromPageHtml($html);

        $html = EditorHtmlSanitizer::stripEditorOnlyElements($html);

        return EditorPlaceholderNormalizer::normalizeHtml(
            EditorFormNormalizer::normalizeForPage(
                EditorStepTabsNormalizer::normalize(
                    EditorCodeBlockNormalizer::normalize(
                        VoodbuilderThemeTokenMigrator::migrateHtml(
                            EditorHtmlSanitizer::sanitize($html),
                        ),
                    ),
                ),
                $page,
            ),
        );
    }

    public function css(SitePage $page): ?string
    {
        $globalCss = ComponentRuntimeBridge::globalClassCss();
        $payload = $page->builder_payload ?? [];
        $html = (string) ($payload['html'] ?? '');
        $componentCss = ComponentRuntimeBridge::componentCssForHtml($html);
        $storedPageCss = $payload['css'] ?? null;
        $pageCss = EditorPastedComponentNormalizer::resolvePublishedPageCss(
            $html,
            filled($storedPageCss) ? (string) $storedPageCss : null,
        );

        // Stale page CSS may bake --vx-header-bg from an old palette save; strip so
        // ThemePalette / admin colors (e.g. header blue) win on the frontend.
        $pageCss = ThemePalette::stripEmbeddedPaletteOverrides((string) ($pageCss ?? ''));

        $combined = trim(implode("\n", array_filter([$globalCss, $componentCss, $pageCss])));

        return $combined !== '' ? $combined : null;
    }

    public function js(SitePage $page): ?string
    {
        $js = $page->builder_payload['js'] ?? null;

        if (! filled($js)) {
            return null;
        }

        return EditorJsSanitizer::sanitize((string) $js);
    }

    public function render(SitePage $page): string
    {
        $html = $this->html($page);
        $html = app(EditorElementConditionRenderer::class)->render($html, $page);
        $html = ComponentRuntimeBridge::renderComponentHtml($html, $page);
        $html = app(EditorBindingRenderer::class)->render($html, $page);
        $html = app(EditorDynamicBlockRenderer::class)->render($html, $page);

        return GlobalTextTags::replaceInHtml($html);
    }
}
