<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Modules\Layouts\LayoutsModule;
use Voodflow\Voodbuilder\Support\ChromeLayoutContentWidth;
use Voodflow\Voodbuilder\Support\ChromeLayoutDefaults;
use Voodflow\Voodbuilder\Support\ChromeLayoutHtmlSanitizer;
use Voodflow\Voodbuilder\Support\ChromeLayoutSubThemeResolver;
use Voodflow\Voodbuilder\Support\GlobalTextTags;
use Voodflow\Voodbuilder\Support\Editor\Bindings\EditorBindingStorageNormalizer;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;
use Voodflow\Voodbuilder\Support\VoodbuilderTheme;
use Voodflow\Voodbuilder\Modules\Templates\TemplatesModule;
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
            'chromeLayoutName' => $layout->name,
            'pageContentWidth' => $contentWidth,
            'chromeWidth' => $chromeWidth,
            'fullWidthPage' => ChromeLayoutContentWidth::allowsElementContentWidthToolbar(
                $contentWidth,
                $chromeWidth,
            ),
            'saveUrl' => self::editorRoute('voodbuilder.editor.chrome-layouts.content.update', $layout),
            'exitUrl' => route('voodbuilder.chrome-layouts.editor', $layout),
            'viewPageUrl' => route('voodbuilder.chrome-layouts.editor', ['chromeLayout' => $layout, 'edit' => 1]),
            'uploadUrl' => self::editorRoute('voodbuilder.editor.upload'),
            'imageEditor' => (bool) config('voodbuilder.editor.image_editor', true),
            'csrf' => csrf_token(),
            'initial' => self::initialPayload($layout),
            'blocksUrl' => self::editorRoute('voodbuilder.editor.blocks').'?chrome=1',
            'blockAllowlist' => EditorCommunityBlockCatalog::sidebarAllowlist(chromeLayoutEditor: true),
            'bindingsUrl' => self::editorRoute('voodbuilder.editor.bindings'),
            'linkTargetsUrl' => self::editorRoute('voodbuilder.editor.link-targets'),
            'blocksRenderUrl' => self::editorRoute('voodbuilder.editor.blocks.render'),
            'codeHighlightUrl' => self::editorRoute('voodbuilder.editor.code.highlight'),
            'globalClassesUrl' => ComponentRuntimeBridge::moduleEnabled()
                ? self::editorRoute('voodbuilder.editor.global-classes.index')
                : null,
            'componentsUrl' => ComponentRuntimeBridge::moduleEnabled()
                ? self::editorRoute('voodbuilder.editor.components.index')
                : null,
            'compileCssUrl' => self::editorRoute('voodbuilder.editor.compile-css'),
            // Allow "Templates" tab in chrome layout editor too.
            // Even without the optional templates authoring plugin, we can still load base templates.
            'pageTemplatesUrl' => TemplatesModule::isEnabled()
                ? self::editorRoute('voodbuilder.editor.page-templates.index')
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
                'componentsLibrary' => ComponentRuntimeBridge::moduleEnabled(),
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
}
