<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Support\GlobalTextTags;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsElementConditionRenderer;

/**
 * Renders stored GrapesJS HTML for site chrome layouts (no SitePage context).
 */
final class GrapesJsChromeHtmlPipeline
{
    public static function render(string $html, bool $canvasPreview = false): string
    {
        if ($html === '') {
            return '';
        }

        $html = GrapesJsHtmlSanitizer::stripEditorOnlyElements($html);
        $html = GrapesJsPlaceholderNormalizer::normalizeHtml(
            GrapesJsStepTabsNormalizer::normalize(
                GrapesJsCodeBlockNormalizer::normalize(
                    VoodbuilderThemeTokenMigrator::migrateHtml(
                        GrapesJsHtmlSanitizer::sanitize($html),
                    ),
                ),
            ),
        );

        $html = app(GrapesJsElementConditionRenderer::class)->render($html, null);
        $html = ComponentRuntimeBridge::renderComponentHtml($html, null);
        $html = app(GrapesJsBindingRenderer::class)->render($html, null);

        $html = app(GrapesJsDynamicBlockRenderer::class)->render($html, null, $canvasPreview);

        return GlobalTextTags::replaceInHtml($html);
    }
}
