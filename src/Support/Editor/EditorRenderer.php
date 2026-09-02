<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\ChromeLayoutManagedContent;
use Voodflow\Voodbuilder\Support\Editor\Bindings\EditorBindingRenderer;
use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorElementConditionRenderer;
use Voodflow\Voodbuilder\Support\GlobalTextTags;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Editor Renderer.
 */
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
                        EditorHeroBackgroundNormalizer::normalize(
                            EditorLibraryLayoutNormalizer::normalize(
                                VoodbuilderThemeTokenMigrator::migrateHtml(
                                    EditorHtmlSanitizer::sanitize($html),
                                ),
                            ),
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
        $html = EditorLibraryLayoutNormalizer::normalize((string) ($payload['html'] ?? ''));
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

        // This lands in a <script> on the public page. EditorJsSanitizer is a regex
        // deny-list (it blocks fetch and eval, not the rest of the DOM API), so the channel
        // stays shut unless the installation explicitly holds the capability.
        if (! Voodbuilder::can('pages.custom-js')) {
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
        $html = GlobalTextTags::replaceInHtml($html);

        // Last gate before `{!! !!}`. The save path sanitizes too, but the render must never
        // trust the database: seeders, template imports, revisions restored from older
        // versions and direct writes all reach this point without passing through EditorGate.
        // Author JS has its own channel (`js()`), so body scripts are never legitimate here.
        return EditorHtmlSecuritySanitizer::sanitize($html);
    }
}
