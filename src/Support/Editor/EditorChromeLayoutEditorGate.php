<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Modules\Layouts\LayoutsModule;
use Voodflow\Voodbuilder\Modules\Templates\TemplatesModule;
use Voodflow\Voodbuilder\Support\ChromeLayoutContentWidth;
use Voodflow\Voodbuilder\Support\ChromeLayoutDefaults;
use Voodflow\Voodbuilder\Support\ChromeLayoutHtmlSanitizer;
use Voodflow\Voodbuilder\Support\ChromeLayoutSubThemeResolver;
use Voodflow\Voodbuilder\Support\Editor\Bindings\EditorBindingStorageNormalizer;
use Voodflow\Voodbuilder\Support\GlobalTextTags;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
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

        return [
            'chromeLayoutMode' => true,
            'chromeLayoutId' => $layout->getKey(),
            'chromeLayoutName' => $layout->name,
            'saveUrl' => self::optionalEditorRoute('voodbuilder.editor.chrome-layouts.content.update', $layout)
                ?? '/voodbuilder/editor/chrome-layouts/'.$layout->getKey().'/content',
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
            'imageEditor' => (bool) config('voodbuilder.editor.image_editor', true),
            'initial' => self::initialPayload($layout),
            'blocksUrl' => (self::optionalEditorRoute('voodbuilder.editor.blocks') ?? '/voodbuilder/editor/blocks').'?chrome=1',
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
            // Allow "Templates" tab in chrome layout editor too.
            // Even without the optional templates authoring plugin, we can still load base templates.
            'pageTemplatesUrl' => TemplatesModule::isEnabled()
                ? self::optionalEditorRoute('voodbuilder.editor.page-templates.index')
                : null,
            'pageTemplatesCatalogUrl' => null,
            'templateCategories' => PageTemplateCategories::all(),
            'packageVersion' => VoodbuilderPackageVersion::current(),
            'componentCategories' => EditorComponentCategoryNormalizer::categories(),
            'plugins' => config('voodbuilder.editor.plugins', []),
            'canvasStyles' => EditorCanvas::styleUrls(),
            'canvasFrameStyle' => EditorCanvas::frameStyle($subTheme),
            'subTheme' => $subTheme,
            'canvasPrefersDark' => VoodbuilderTheme::serverInitialDark(),
            'landingCanvas' => true,
            'themePaletteCss' => trim(implode("\n\n", array_filter([
                ThemePalette::cssForCanvas($subTheme),
                ThemePalette::criticalChromeShellCss($subTheme),
            ]))),
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
        foreach (['vmedia.media.upload', 'voodbuilder.editor.upload'] as $name) {
            if (Route::has($name)) {
                return self::editorRoute($name);
            }
        }

        return null;
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
