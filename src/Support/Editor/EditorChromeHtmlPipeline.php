<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Support\GlobalTextTags;
use Voodflow\Voodbuilder\Support\Editor\Bindings\EditorBindingRenderer;
use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorElementConditionRenderer;

/**
 * Renders stored Editor HTML for site chrome layouts (no SitePage context).
 */
final class EditorChromeHtmlPipeline
{
    public static function render(string $html, bool $canvasPreview = false): string
    {
        if ($html === '') {
            return '';
        }

        $html = EditorHtmlSanitizer::stripEditorOnlyElements($html);
        $html = EditorPlaceholderNormalizer::normalizeHtml(
            EditorStepTabsNormalizer::normalize(
                EditorCodeBlockNormalizer::normalize(
                    VoodbuilderThemeTokenMigrator::migrateHtml(
                        EditorHtmlSanitizer::sanitize($html),
                    ),
                ),
            ),
        );

        $html = app(EditorElementConditionRenderer::class)->render($html, null);
        $html = ComponentRuntimeBridge::renderComponentHtml($html, null);
        $html = app(EditorBindingRenderer::class)->render($html, null);

        $html = app(EditorDynamicBlockRenderer::class)->render($html, null, $canvasPreview);

        return GlobalTextTags::replaceInHtml($html);
    }
}
