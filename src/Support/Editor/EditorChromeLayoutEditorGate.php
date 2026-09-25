<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Licensing\EditorEditionSummary;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Modules\Layouts\LayoutsModule;
use Voodflow\Voodbuilder\Support\AppTypography;
use Voodflow\Voodbuilder\Support\ChromeLayoutContentWidth;
use Voodflow\Voodbuilder\Support\ChromeLayoutDefaults;
use Voodflow\Voodbuilder\Support\ChromeLayoutHtmlSanitizer;
use Voodflow\Voodbuilder\Support\ChromeLayoutReadingTypography;
use Voodflow\Voodbuilder\Support\ChromeLayoutSubThemeResolver;
use Voodflow\Voodbuilder\Support\Editor\Bindings\EditorBindingStorageNormalizer;
use Voodflow\Voodbuilder\Support\GlobalTextTags;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\ReadingPreviewRegistry;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;
use Voodflow\Voodbuilder\Support\VoodbuilderTheme;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Editor Chrome Layout Editor Gate.
 */
final class EditorChromeLayoutEditorGate
{
    public static function canEdit(ChromeLayout $layout): bool
    {
        return config('voodbuilder.chrome_layouts.enabled', true)
            && LayoutsModule::isEnabled()
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
        $contentWidth = ChromeLayoutContentWidth::fromLayout($layout);
        $chromeWidth = ChromeLayoutContentWidth::resolveChromeWidth($layout);
        $readingTypography = ChromeLayoutReadingTypography::resolve($layout);
        $readingPreviews = app(ReadingPreviewRegistry::class)->all();
        $appTypography = AppTypography::resolve();

        return [
            'chromeLayoutMode' => true,
            'chromeLayoutId' => $layout->getKey(),
            'chromeLayoutName' => $layout->name,
            'saveUrl' => self::optionalEditorRoute('voodbuilder.editor.chrome-layouts.content.update', $layout)
                ?? '/voodbuilder/editor/chrome-layouts/' . $layout->getKey() . '/content',
            'csrf' => csrf_token(),
            'exitUrl' => route('voodbuilder.chrome-layouts.editor', $layout),
            'viewPageUrl' => route('voodbuilder.chrome-layouts.editor', ['chromeLayout' => $layout, 'edit' => 1]),
            'pageContentWidth' => $contentWidth,
            'chromeWidth' => $chromeWidth,
            'fullWidthPage' => ChromeLayoutContentWidth::allowsElementContentWidthToolbar(
                $contentWidth,
                $chromeWidth,
            ),
            'uploadUrl' => self::mediaUploadUrl() ?? '',
            'mediaReplaceUrl' => self::mediaReplaceUrl(),
            'mediaLibraryUrl' => self::mediaLibraryIndexUrl(),
            // Same as page editor: vmedia browser needs both library + galleries routes.
            'mediaGalleriesUrl' => self::mediaCompanionBrowserEnabled()
                ? self::mediaGalleriesIndexUrl()
                : null,
            'imageEditor' => (bool) config('voodbuilder.editor.image_editor', true),
            'initial' => self::initialPayload($layout),
            'blocksUrl' => (self::optionalEditorRoute('voodbuilder.editor.blocks') ?? '/voodbuilder/editor/blocks') . '?chrome=1',
            'blockAllowlist' => EditorCommunityBlockCatalog::sidebarAllowlist(chromeLayoutEditor: true),
            'bindingsUrl' => self::dynamicDataEnabled()
                ? self::optionalEditorRoute('voodbuilder.editor.bindings')
                : null,
            'linkTargetsUrl' => self::optionalEditorRoute('voodbuilder.editor.link-targets') ?? '',
            'blocksRenderUrl' => self::optionalEditorRoute('voodbuilder.editor.blocks.render') ?? '',
            'codeHighlightUrl' => self::optionalEditorRoute('voodbuilder.editor.code.highlight') ?? '',
            'globalClassesUrl' => ComponentRuntimeBridge::moduleEnabled()
                ? self::optionalEditorRoute('voodbuilder.editor.global-classes.index')
                : null,
            'componentsUrl' => ComponentRuntimeBridge::moduleEnabled()
                ? self::optionalEditorRoute('voodbuilder.editor.components.index')
                : null,
            'compileCssUrl' => self::optionalEditorRoute('voodbuilder.editor.compile-css') ?? '',
            // Layout editor is chrome shell + foundation blocks (not page templates).
            'pageTemplatesUrl' => null,
            'pageTemplatesCatalogUrl' => null,
            'hideTemplates' => true,
            'templateCategories' => [],
            'packageVersion' => VoodbuilderPackageVersion::current(),
            'editionSummary' => ($editionSummary = EditorEditionSummary::make()),
            'editionLabel' => $editionSummary['label'],
            'componentCategories' => EditorComponentCategoryNormalizer::categories(),
            'plugins' => config('voodbuilder.editor.plugins', []),
            'canvasStyles' => array_values(array_unique(array_filter([
                ...EditorCanvas::styleUrls(),
                ...$appTypography['stylesheetUrls'],
                ...$readingTypography['stylesheetUrls'],
            ]))),
            'canvasFrameStyle' => EditorCanvas::frameStyle($subTheme)
                . "\n" . $appTypography['editorCanvasCss']
                . self::readingTypographyCanvasCss($readingTypography),
            'subTheme' => $subTheme,
            'canvasPrefersDark' => VoodbuilderTheme::serverInitialDark(),
            'landingCanvas' => true,
            'themePaletteCss' => trim(implode("\n\n", array_filter([
                ThemePalette::cssForCanvas($subTheme),
                ThemePalette::criticalChromeShellCss($subTheme),
            ]))),
            'readingTypography' => [
                'font' => $readingTypography['font'],
                'headingFont' => $readingTypography['headingFont'],
                'typeScale' => $readingTypography['typeScale'],
                'inherited' => $readingTypography['inherited'],
                'stylesheetUrls' => $readingTypography['stylesheetUrls'],
                'cssVariables' => $readingTypography['cssVariables'],
            ],
            'readingPreviews' => $readingPreviews,
            'fonts' => Voodbuilder::fonts()->toEditorPayload(),
            'builderBrand' => config('voodbuilder.editor.builder.brand', 'VoodBuilder'),
            'globalTextTags' => GlobalTextTags::values(),
            'marketingUrl' => (string) config('voodbuilder.marketing_url', 'https://voodflow.com/voodbuilder'),
            'entitlements' => [
                'edition' => Voodbuilder::entitlements()->edition(),
                'blocksOfficialComplete' => Voodbuilder::can(EditorCommunityBlockCatalog::CAPABILITY_FULL_LIBRARY),
                'elementsLibrary' => EditorCommunityBlockCatalog::elementsLibraryActive(),
                'componentsLibrary' => ComponentRuntimeBridge::moduleEnabled(),
                'dynamicDataSingle' => self::dynamicDataEnabled(),
                'dynamicDataCollections' => false,
            ],
            'labels' => EditorGate::sharedEditorLabels(),
        ];
    }

    /**
     * @param  array{cssVariables: array<string, string>, editorCss: string}  $readingTypography
     */
    private static function readingTypographyCanvasCss(array $readingTypography): string
    {
        $variables = $readingTypography['cssVariables'];
        $responsiveSizes = $readingTypography['editorCss'];

        $decls = [];

        foreach ($variables as $name => $value) {
            $decls[] = "{$name}: {$value};";
        }

        $block = implode("\n            ", $decls);

        return <<<CSS

        :root, body, [data-gjs-type="wrapper"] {
            {$block}
        }

        {$responsiveSizes}

        .vp-doc {
            font-family: var(--vp-font-family-doc, var(--font-sans));
            font-size: var(--vp-font-size-doc, 17px);
        }

        .vp-doc-title,
        .vp-doc :is(h1, h2, h3, h4, h5, h6) {
            font-family: var(--vp-font-family-doc-heading, var(--vp-font-family-doc, var(--font-sans)));
        }

        .vp-doc h1 { font-size: var(--vp-doc-h1-size, 2rem); font-weight: var(--vp-doc-h1-weight, 600); line-height: var(--vp-doc-h1-leading, 1.25); }
        .vp-doc h2 { font-size: var(--vp-doc-h2-size, 1.5rem); font-weight: var(--vp-doc-h2-weight, 600); line-height: var(--vp-doc-h2-leading, 1.333); }
        .vp-doc h3 { font-size: var(--vp-doc-h3-size, 1.25rem); font-weight: var(--vp-doc-h3-weight, 600); line-height: var(--vp-doc-h3-leading, 1.4); }
        .vp-doc h4 { font-size: var(--vp-doc-h4-size, 1.125rem); font-weight: var(--vp-doc-h4-weight, 600); line-height: var(--vp-doc-h4-leading, 1.333); }
        .vp-doc p,
        .vp-doc li,
        .vp-doc table { font-size: var(--vp-doc-p-size, 1em); font-weight: var(--vp-doc-p-weight, 400); line-height: var(--vp-doc-p-leading, 1.75); }

        .voodbuilder-doc-sidebar-scroll,
        [data-voodbuilder-reading-sidebar] {
            font-family: var(--vp-font-family-sidebar, var(--vp-font-family-doc, var(--font-sans)));
            font-size: 0.875rem;
        }

        .vp-reading-sidebar-group,
        .vp-outline__title { margin: 0 0 0.5rem; font-family: var(--vp-font-family-doc-heading, inherit); font-size: var(--vp-doc-h5-size, 0.75rem); font-weight: var(--vp-doc-h5-weight, 700); line-height: var(--vp-doc-h5-leading, 1.5); letter-spacing: 0.08em; text-transform: uppercase; }
        .vp-reading-sidebar-link { font-size: var(--vp-doc-link-size, 0.875rem); font-weight: var(--vp-doc-link-weight, 500); line-height: var(--vp-doc-link-leading, 1.5); }
        .vp-outline__link { font-size: var(--vp-doc-link-size, 0.875rem); font-weight: var(--vp-doc-link-weight, 400); line-height: var(--vp-doc-link-leading, 1.5); }
CSS;
    }

    /**
     * @return array{html: string, css: string, js: string, project: null}
     */
    public static function initialPayload(ChromeLayout $layout): array
    {
        $payload = $layout->builderPayload();
        $html = $payload['html'] !== '' ? $payload['html'] : ChromeLayoutDefaults::starterHtml();
        $html = ChromeLayoutHtmlSanitizer::normalizeStoredHtml($html);
        $html = EditorChromeHtmlPipeline::render($html, canvasPreview: true);

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
        $normalized = EditorGate::normalizePayload([
            'html' => $payload['html'] ?? '',
            'css' => $payload['css'] ?? '',
            'js' => $payload['js'] ?? '',
            'project' => null,
        ], recompilePageCss: true);

        $normalized['html'] = app(EditorBindingStorageNormalizer::class)->normalizeHtml($normalized['html']);
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

    /**
     * @param  array<string, mixed>|object|string|int|null  $parameters
     */
    private static function optionalEditorRoute(string $name, mixed $parameters = []): ?string
    {
        if (! Route::has($name)) {
            return null;
        }

        return self::editorRoute($name, $parameters);
    }

    private static function mediaUploadUrl(): ?string
    {
        if (! Route::has('vmedia.media.upload')) {
            return null;
        }

        return self::editorRoute('vmedia.media.upload');
    }

    private static function mediaReplaceUrl(): ?string
    {
        if (! Route::has('vmedia.media.replace')) {
            return null;
        }

        return self::editorRoute('vmedia.media.replace');
    }

    private static function mediaLibraryIndexUrl(): ?string
    {
        if (! Route::has('vmedia.media.index')) {
            return null;
        }

        return self::editorRoute('vmedia.media.index');
    }

    private static function mediaGalleriesIndexUrl(): ?string
    {
        if (! Route::has('vmedia.media.galleries')) {
            return null;
        }

        return self::editorRoute('vmedia.media.galleries');
    }

    private static function mediaCompanionBrowserEnabled(): bool
    {
        return Route::has('vmedia.media.galleries');
    }

    private static function dynamicDataEnabled(): bool
    {
        $class = 'Voodflow\\Voodbuilder\\Modules\\DynamicData\\DynamicDataModule';

        try {
            return class_exists($class) && $class::isEnabled();
        } catch (\Throwable) {
            return false;
        }
    }
}
