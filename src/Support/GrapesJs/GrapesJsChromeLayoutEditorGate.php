<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\GrapesJs\PageTemplateCategories;
use Voodflow\Voodbuilder\Support\ChromeLayoutDefaults;
use Voodflow\Voodbuilder\Support\ChromeLayoutSubThemeResolver;
use Voodflow\Voodbuilder\Support\ChromeLayoutHtmlSanitizer;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsChromeHtmlPipeline;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingStorageNormalizer;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;
use Voodflow\Voodbuilder\Support\VoodbuilderTheme;

final class GrapesJsChromeLayoutEditorGate
{
    public static function canEdit(ChromeLayout $layout): bool
    {
        return config('voodbuilder.chrome_layouts.enabled', true)
            && PageBuilderAccess::userCanUsePageBuilder();
    }

    public static function isEditing(ChromeLayout $layout): bool
    {
        return self::canEdit($layout) && request()->boolean('edit');
    }

    /**
     * @return array<string, mixed>
     */
    public static function config(ChromeLayout $layout): array
    {
        $subTheme = ChromeLayoutSubThemeResolver::forChromeLayout($layout);

        return [
            'chromeLayoutMode' => true,
            'chromeLayoutName' => $layout->name,
            'saveUrl' => self::editorRoute('voodbuilder.grapesjs.chrome-layouts.content.update', $layout),
            'exitUrl' => route('voodbuilder.chrome-layouts.editor', $layout),
            'viewPageUrl' => route('voodbuilder.chrome-layouts.editor', ['chromeLayout' => $layout, 'edit' => 1]),
            'uploadUrl' => self::editorRoute('voodbuilder.grapesjs.upload'),
            'csrf' => csrf_token(),
            'initial' => self::initialPayload($layout),
            'blocksUrl' => self::editorRoute('voodbuilder.grapesjs.blocks'),
            'bindingsUrl' => self::editorRoute('voodbuilder.grapesjs.bindings'),
            'linkTargetsUrl' => self::editorRoute('voodbuilder.grapesjs.link-targets'),
            'blocksRenderUrl' => self::editorRoute('voodbuilder.grapesjs.blocks.render'),
            'codeHighlightUrl' => self::editorRoute('voodbuilder.grapesjs.code.highlight'),
            'globalClassesUrl' => self::editorRoute('voodbuilder.grapesjs.global-classes.index'),
            'componentsUrl' => self::editorRoute('voodbuilder.grapesjs.components.index'),
            'pageTemplatesUrl' => self::editorRoute('voodbuilder.grapesjs.page-templates.index'),
            'pageTemplatesCatalogUrl' => filled(config('voodbuilder.page_templates.catalog_url'))
                ? self::editorRoute('voodbuilder.grapesjs.page-templates.catalog')
                : null,
            'templateCategories' => PageTemplateCategories::all(),
            'packageVersion' => VoodbuilderPackageVersion::current(),
            'componentCategories' => GrapesJsComponentCategoryNormalizer::categories(),
            'plugins' => config('voodbuilder.grapesjs.plugins', []),
            'canvasStyles' => GrapesJsCanvas::styleUrls(),
            'canvasFrameStyle' => GrapesJsCanvas::frameStyle($subTheme),
            'subTheme' => $subTheme,
            'canvasPrefersDark' => VoodbuilderTheme::serverInitialDark(),
            'landingCanvas' => true,
            'themePaletteCss' => trim(implode("\n\n", array_filter([
                ThemePalette::cssForCanvas($subTheme),
                ThemePalette::criticalChromeShellCss($subTheme),
            ]))),
            'builderBrand' => config('voodbuilder.grapesjs.builder.brand', 'VoodBuilder'),
            'labels' => GrapesJsEditorGate::sharedEditorLabels(),
        ];
    }

    /**
     * @return array{html: string, css: string, js: string, project: null}
     */
    public static function initialPayload(ChromeLayout $layout): array
    {
        $payload = $layout->builderPayload();
        $html = $payload['html'] !== '' ? $payload['html'] : ChromeLayoutDefaults::starterHtml();
        $html = ChromeLayoutHtmlSanitizer::normalizeStoredHtml($html);
        $html = GrapesJsChromeHtmlPipeline::render($html, canvasPreview: true);

        // Theme / critical chrome CSS is injected via themePaletteCss + canvas styles —
        // never bake it into the CssComposer payload (that caused massive save duplication).
        return [
            'html' => $html,
            'css' => ThemePalette::stripEmbeddedPaletteOverrides($payload['css']),
            'js' => $payload['js'],
            'project' => null,
        ];
    }

    /**
     * @param  array{html?: string, css?: string, js?: string, project?: mixed}  $payload
     * @return array{html: string, css: string, js: string}
     */
    public static function normalizePayload(array $payload): array
    {
        $normalized = GrapesJsEditorGate::normalizePayload([
            'html' => $payload['html'] ?? '',
            'css' => $payload['css'] ?? '',
            'js' => $payload['js'] ?? '',
            'project' => null,
        ], recompilePageCss: true);

        $normalized['html'] = app(GrapesJsBindingStorageNormalizer::class)->normalizeHtml($normalized['html']);
        $normalized['html'] = ChromeLayoutHtmlSanitizer::normalizeStoredHtml($normalized['html']);

        if (! str_contains($normalized['html'], 'data-voodbuilder-content-slot')) {
            $normalized['html'] = ChromeLayoutDefaults::starterHtml();
        }

        return [
            'html' => $normalized['html'],
            'css' => $normalized['css'],
            'js' => $normalized['js'] ?? '',
        ];
    }

    private static function editorRoute(string $name, mixed $parameters = []): string
    {
        return route($name, $parameters, absolute: false);
    }
}
