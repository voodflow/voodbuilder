<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Illuminate\Support\Facades\Route;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Modules\Conditions\ConditionsModule;
use Voodflow\Voodbuilder\Modules\History\HistoryModule;
use Voodflow\Voodbuilder\Modules\Pages\PagesModule;
use Voodflow\Voodbuilder\Modules\Templates\TemplatesModule;
use Voodflow\Voodbuilder\Support\ChromeLayoutContentWidth;
use Voodflow\Voodbuilder\Support\ChromeLayoutEditorPreview;
use Voodflow\Voodbuilder\Support\ChromeLayoutManagedContent;
use Voodflow\Voodbuilder\Support\ChromeLayoutRenderer;
use Voodflow\Voodbuilder\Support\ChromeLayoutSubThemeResolver;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageBindingCatalog;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPagePreviewEntityResolver;
use Voodflow\Voodbuilder\Support\DynamicPages\DynamicPageRegistry;
use Voodflow\Voodbuilder\Support\Editor\Bindings\EditorBindingNormalizer;
use Voodflow\Voodbuilder\Support\Editor\Bindings\EditorBindingRenderer;
use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorConditionHooks;
use Voodflow\Voodbuilder\Support\Editor\Conditions\EditorConditionsAttributeNormalizer;
use Voodflow\Voodbuilder\Support\Fonts\FontStylesheets;
use Voodflow\Voodbuilder\Support\GlobalTextTags;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\SiteFooterColumnPlacements;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;
use Voodflow\Voodbuilder\Support\VoodbuilderTheme;
use Voodflow\Voodbuilder\Voodbuilder;

/**
 * Editor Gate.
 */
final class EditorGate
{
    /** @var (callable(SitePage): bool)|null */
    private static ?\Closure $authorizer = null;

    /** @var list<callable(): array<string, mixed>> */
    private static array $labelProviders = [];

    /** @var list<callable(): array<string, mixed>> */
    private static array $configProviders = [];

    /**
     * Register additional visual editor labels (official/third-party plugins).
     *
     * @param  callable(): array<string, mixed>  $provider
     */
    public static function registerLabelProvider(callable $provider): void
    {
        self::$labelProviders[] = $provider;
    }

    /**
     * Merge extra keys into the visual editor bootstrap payload.
     *
     * @param  callable(): array<string, mixed>  $provider
     */
    public static function registerConfigProvider(callable $provider): void
    {
        self::$configProviders[] = $provider;
    }

    public static function flushLabelProviders(): void
    {
        self::$labelProviders = [];
    }

    public static function flushConfigProviders(): void
    {
        self::$configProviders = [];
    }

    public static function authorizeUsing(?callable $callback): void
    {
        self::$authorizer = $callback !== null ? \Closure::fromCallable($callback) : null;
    }

    public static function canEdit(SitePage $page): bool
    {
        if (self::$authorizer instanceof \Closure) {
            return (self::$authorizer)($page);
        }

        return self::userCanEdit($page);
    }

    public static function userCanEdit(SitePage $page): bool
    {
        return config('voodbuilder.editor.enabled', true)
            && PagesModule::isEnabled()
            && $page->usesEditorBuilder()
            && PageBuilderAccess::userCanUsePageBuilder();
    }

    public static function isEditing(SitePage $page): bool
    {
        return self::canEdit($page) && request()->boolean('edit');
    }

    /**
     * @return array<string, mixed>
     */
    public static function config(SitePage $page): array
    {
        $chromeLayout = ChromeLayoutManagedContent::chromeLayoutForSitePage($page);
        $chromeShellMode = $chromeLayout !== null && self::isEditing($page);
        $contentWidth = ChromeLayoutContentWidth::resolve($chromeLayout, $page);
        $chromeWidth = ChromeLayoutContentWidth::resolveChromeWidth($chromeLayout);
        $subTheme = $chromeShellMode
            ? ChromeLayoutSubThemeResolver::forSitePage($page)
            : $page->resolvedSubTheme();
        $chromeShellParts = null;
        $chromeLayoutCss = '';

        if ($chromeShellMode && $chromeLayout !== null) {
            $rendered = app(ChromeLayoutRenderer::class)->render($chromeLayout, canvasPreview: true);
            $chromeShellParts = [
                'before' => $rendered['before'],
                'after' => $rendered['after'],
            ];
            $chromeLayoutCss = ThemePalette::stripEmbeddedPaletteOverrides(trim($rendered['css']));
        }

        // Keep save/auth fields before large HTML payloads so a truncated config
        // script still exposes the persistence endpoint when possible.
        return [
            'pageId' => $page->getKey(),
            'saveUrl' => self::optionalEditorRoute('voodbuilder.editor.pages.update', $page)
                ?? '/voodbuilder/editor/pages/'.$page->getKey(),
            'csrf' => csrf_token(),
            'exitUrl' => $page->getUrl(),
            'viewPageUrl' => $page->getUrl(),
            'pageTitle' => (string) ($page->title ?? ''),
            'dynamicPage' => $page->is_dynamic ? [
                'channel' => (string) ($page->dynamic_channel ?? ''),
                'currentSourceIds' => DynamicPageBindingCatalog::currentSourceIds($page),
                'routeEntities' => DynamicPagePreviewEntityResolver::slugBagFromRequestContext(),
            ] : null,
            'routeEntityPatterns' => app(DynamicPageRegistry::class)->editorRouteEntityPatterns(),
            'chromeShellMode' => $chromeShellMode,
            'chromeShellName' => $chromeLayout?->name,
            'chromeShellParts' => $chromeShellParts,
            'chromeLayoutCss' => $chromeLayoutCss,
            'pageContentWidth' => $contentWidth,
            'chromeWidth' => $chromeWidth,
            'fullWidthPage' => ChromeLayoutContentWidth::allowsElementContentWidthToolbar(
                $contentWidth,
                $chromeWidth,
            ),
            'savedPageHtml' => (string) (($page->builder_payload ?? [])['html'] ?? ''),
            'uploadUrl' => self::mediaUploadUrl() ?? '',
            'mediaReplaceUrl' => self::mediaReplaceUrl(),
            'mediaLibraryUrl' => self::mediaLibraryIndexUrl(),
            // Galleries API is owned by voodflow/vmedia when the Filament plugin is active.
            'mediaGalleriesUrl' => self::mediaCompanionBrowserEnabled()
                ? self::mediaGalleriesIndexUrl()
                : null,
            'imageEditor' => (bool) config('voodbuilder.editor.image_editor', true),
            'initial' => self::initialPayload($page),
            'blocksUrl' => self::optionalEditorRoute('voodbuilder.editor.blocks') ?? '',
            'blockAllowlist' => EditorCommunityBlockCatalog::sidebarAllowlist(chromeLayoutEditor: false),
            'elementsSourceUrl' => EditorCommunityBlockCatalog::elementsLibraryActive()
                ? self::optionalEditorRoute('voodbuilder.editor.elements.source')
                : null,
            'bindingsUrl' => self::dynamicDataEnabled()
                ? self::optionalEditorRoute('voodbuilder.editor.bindings')
                : null,
            'linkTargetsUrl' => self::optionalEditorRoute('voodbuilder.editor.link-targets') ?? '',
            'bindingsPreviewUrl' => self::dynamicDataEnabled()
                ? self::optionalEditorRoute('voodbuilder.editor.bindings.preview', $page)
                : null,
            'blocksRenderUrl' => self::optionalEditorRoute('voodbuilder.editor.blocks.render') ?? '',
            'codeHighlightUrl' => self::optionalEditorRoute('voodbuilder.editor.code.highlight') ?? '',
            'formSubmitUrl' => self::optionalEditorRoute('voodbuilder.editor.forms.submit', $page) ?? '',
            'newsletterLists' => self::newsletterListOptions(),
            'revisionsUrl' => HistoryModule::isEnabled()
                ? self::optionalEditorRoute('voodbuilder.editor.pages.revisions.index', $page)
                : null,
            'revisionsRestoreUrl' => HistoryModule::isEnabled()
                ? self::optionalEditorRoute('voodbuilder.editor.pages.revisions.restore', [
                    'sitePage' => $page,
                    'revision' => '__REVISION__',
                ])
                : null,
            'globalClassesUrl' => ComponentRuntimeBridge::moduleEnabled()
                ? self::optionalEditorRoute('voodbuilder.editor.global-classes.index')
                : null,
            'componentsUrl' => ComponentRuntimeBridge::moduleEnabled()
                ? self::optionalEditorRoute('voodbuilder.editor.components.index')
                : null,
            // Core JIT endpoint — do not require the Components plugin for canvas compile.
            'compileCssUrl' => self::optionalEditorRoute('voodbuilder.editor.compile-css') ?? '',
            'pageTemplatesUrl' => TemplatesModule::isEnabled()
                ? self::optionalEditorRoute('voodbuilder.editor.page-templates.index')
                : null,
            'pageTemplatesCatalogUrl' => TemplatesModule::isEnabled()
                && Voodbuilder::can('templates.remote-install')
                && filled(config('voodbuilder.page_templates.catalog_url'))
                ? self::optionalEditorRoute('voodbuilder.editor.page-templates.catalog')
                : null,
            'fonts' => Voodbuilder::fonts()->toEditorPayload(),
            'dynamicDataCollections' => DynamicDataCollectionsBridge::moduleEnabled(),
            'entitlements' => [
                'edition' => Voodbuilder::entitlements()->edition(),
                'templatesLocal' => TemplatesModule::isEnabled(),
                'templatesPluginInstalled' => TemplateAuthoringBridge::pluginInstalled(),
                'templatesAuthoring' => TemplateAuthoringBridge::isEnabled(),
                'templatesImport' => TemplateAuthoringBridge::canImportJson(),
                'templatesExport' => TemplateAuthoringBridge::canExport(),
                // Marketplace install-from-URL is always available when Templates module is on.
                'templatesImportUrl' => TemplatesModule::isEnabled(),
                'templatesRemoteInstall' => Voodbuilder::can('templates.remote-install'),
                'componentsLibrary' => ComponentRuntimeBridge::moduleEnabled(),
                'componentsImport' => ComponentRuntimeBridge::moduleEnabled()
                    && Voodbuilder::can('components.import'),
                'componentsExport' => ComponentRuntimeBridge::moduleEnabled()
                    && Voodbuilder::can('components.export'),
                'componentsCodeImport' => ComponentRuntimeBridge::moduleEnabled()
                    && Voodbuilder::can('components.code-import'),
                // Plugin registration unlocks single; collections still need Pro entitlement.
                'dynamicDataSingle' => self::dynamicDataEnabled(),
                'dynamicDataCollections' => DynamicDataCollectionsBridge::moduleEnabled(),
                'popupsBuilder' => Voodbuilder::can('popups.builder'),
                'blocksOfficialComplete' => Voodbuilder::can(EditorCommunityBlockCatalog::CAPABILITY_FULL_LIBRARY),
                'elementsLibrary' => EditorCommunityBlockCatalog::elementsLibraryActive(),
            ],
            'popupsUrl' => Voodbuilder::modules()->isEnabled('popups')
                ? self::optionalEditorRoute('voodbuilder.editor.popups.index')
                : null,
            'popupsPagePathsUrl' => Voodbuilder::modules()->isEnabled('popups')
                ? self::optionalEditorRoute('voodbuilder.editor.popups.page-paths')
                : null,
            'packageVersion' => VoodbuilderPackageVersion::current(),
            'componentCategories' => EditorComponentCategoryNormalizer::categories(),
            'templateCategories' => PageTemplateCategories::all(),
            'conditionOptions' => ConditionsModule::isEnabled()
                ? EditorConditionHooks::options()
                : [],
            'conditionsEnabled' => ConditionsModule::isEnabled(),
            'plugins' => config('voodbuilder.editor.plugins', []),
            'canvasStyles' => EditorCanvas::styleUrls(),
            'canvasFrameStyle' => EditorCanvas::frameStyle($subTheme),
            'subTheme' => $subTheme,
            'canvasPrefersDark' => VoodbuilderTheme::serverInitialDark(),
            'landingCanvas' => ChromeLayoutContentWidth::isFull($contentWidth) || $subTheme === 'site',
            'siteNavDefaults' => [
                'stickyNav' => (bool) VoodbuilderSettings::get('sticky_nav', false),
            ],
            'footerColumnOptions' => SiteFooterColumnPlacements::columnOptionLabels(),
            'themePaletteCss' => $chromeShellMode
                ? trim(implode("\n\n", array_filter([
                    ThemePalette::cssForCanvas($subTheme),
                    ThemePalette::criticalChromeShellCss($subTheme),
                ])))
                : ThemePalette::cssForCanvas($subTheme),
            'builderBrand' => config('voodbuilder.editor.builder.brand', 'VoodBuilder'),
            'globalTextTags' => GlobalTextTags::values(),
            'marketingUrl' => (string) config('voodbuilder.marketing_url', 'https://voodflow.com/voodbuilder'),
            'labels' => self::sharedEditorLabels(),
            ...self::mergedCompanionConfig(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function mergedCompanionConfig(): array
    {
        $merged = [];

        foreach (self::$configProviders as $provider) {
            $chunk = $provider();

            if (! is_array($chunk) || $chunk === []) {
                continue;
            }

            $merged = array_replace_recursive($merged, $chunk);
        }

        return $merged;
    }

    /**
     * @return array<string, string>
     */
    public static function sharedEditorLabels(): array
    {
        $labels = [
            'save' => __('voodbuilder::pro.frontend.save'),
            'saving' => __('voodbuilder::pro.frontend.saving'),
            'saved' => __('voodbuilder::pro.frontend.saved'),
            'error' => __('voodbuilder::pro.frontend.error'),
            'learnMore' => __('voodbuilder::pro.editor_ui.learn_more'),
            'marketingUrl' => (string) config('voodbuilder.marketing_url', 'https://voodflow.com/voodbuilder'),
            'makeDynamic' => __('voodbuilder::pro.bindings.make_dynamic'),
            'clearDynamic' => __('voodbuilder::pro.bindings.clear_dynamic'),
            'selectParent' => __('voodbuilder::pro.editor.toolbar.select_parent'),
            'drag' => __('voodbuilder::pro.editor.toolbar.drag'),
            'moveUp' => __('voodbuilder::pro.editor.toolbar.move_up'),
            'moveDown' => __('voodbuilder::pro.editor.toolbar.move_down'),
            'clone' => __('voodbuilder::pro.editor.toolbar.clone'),
            'delete' => __('voodbuilder::pro.editor.toolbar.delete'),
            'editImage' => __('voodbuilder::pro.editor.toolbar.edit_image'),
            'contentWidthTitle' => __('voodbuilder::pro.editor.toolbar.content_width'),
            'contentWidthFull' => __('voodbuilder::pro.editor.toolbar.content_width_full'),
            'contentWidthNormal' => __('voodbuilder::pro.editor.toolbar.content_width_normal'),
            'contentWidthCustom' => __('voodbuilder::pro.editor.toolbar.content_width_custom'),
            'imageEditorTitle' => __('voodbuilder::pro.editor.image_editor.title'),
            'imageEditorSave' => __('voodbuilder::pro.editor.image_editor.save'),
            'imageEditorSaveAs' => __('voodbuilder::pro.editor.image_editor.save_as'),
            'imageEditorLoading' => __('voodbuilder::pro.editor.image_editor.loading'),
            'imageEditorSaving' => __('voodbuilder::pro.editor.image_editor.saving'),
            'imageEditorLoadError' => __('voodbuilder::pro.editor.image_editor.load_error'),
            'imageEditorUploadError' => __('voodbuilder::pro.editor.image_editor.upload_error'),
            'imageEditorUploadMissing' => __('voodbuilder::pro.editor.image_editor.upload_missing'),
            'imageEditorPlaceholderHint' => __('voodbuilder::pro.editor.image_editor.placeholder_hint'),
            'imageSettingsTitle' => __('voodbuilder::pro.editor.image_settings.title'),
            'imageSettingsHeroTitle' => __('voodbuilder::pro.editor.image_settings.hero_title'),
            'imageSettingsSrc' => __('voodbuilder::pro.editor.image_settings.src'),
            'imageSettingsHeroSrc' => __('voodbuilder::pro.editor.image_settings.hero_src'),
            'imageSettingsChoose' => __('voodbuilder::pro.editor.image_settings.choose'),
            'imageSettingsClear' => __('voodbuilder::pro.editor.image_settings.clear'),
            'imageSettingsAlt' => __('voodbuilder::pro.editor.image_settings.alt'),
            'imageSettingsAltPlaceholder' => __('voodbuilder::pro.editor.image_settings.alt_placeholder'),
            'imageSettingsCaption' => __('voodbuilder::pro.editor.image_settings.caption'),
            'imageSettingsCaptionPlaceholder' => __('voodbuilder::pro.editor.image_settings.caption_placeholder'),
            'imageSettingsCaptionDisplay' => __('voodbuilder::pro.editor.image_settings.caption_display'),
            'imageSettingsCaptionDisplayNone' => __('voodbuilder::pro.editor.image_settings.caption_display_none'),
            'imageSettingsCaptionDisplayBelow' => __('voodbuilder::pro.editor.image_settings.caption_display_below'),
            'imageSettingsCaptionDisplayOverlay' => __('voodbuilder::pro.editor.image_settings.caption_display_overlay'),
            'imageSettingsCaptionHint' => __('voodbuilder::pro.editor.image_settings.caption_hint'),
            'imageSettingsOpacity' => __('voodbuilder::pro.editor.image_settings.opacity'),
            'imageSettingsFit' => __('voodbuilder::pro.editor.image_settings.fit'),
            'imageSettingsFitCover' => __('voodbuilder::pro.editor.image_settings.fit_cover'),
            'imageSettingsFitContain' => __('voodbuilder::pro.editor.image_settings.fit_contain'),
            'imageSettingsFitFill' => __('voodbuilder::pro.editor.image_settings.fit_fill'),
            'imageSettingsPosition' => __('voodbuilder::pro.editor.image_settings.position'),
            'imageSettingsPositionCenter' => __('voodbuilder::pro.editor.image_settings.position_center'),
            'imageSettingsPositionTop' => __('voodbuilder::pro.editor.image_settings.position_top'),
            'imageSettingsPositionBottom' => __('voodbuilder::pro.editor.image_settings.position_bottom'),
            'imageSettingsHint' => __('voodbuilder::pro.editor.image_settings.hint'),
            'imageSettingsHeroHint' => __('voodbuilder::pro.editor.image_settings.hero_hint'),
            'videoSettingsHeroTitle' => __('voodbuilder::pro.editor.video_settings.hero_title'),
            'videoSettingsTitle' => __('voodbuilder::pro.editor.video_settings.title'),
            'videoSettingsSrc' => __('voodbuilder::pro.editor.video_settings.src'),
            'videoSettingsSrcPlaceholder' => __('voodbuilder::pro.editor.video_settings.src_placeholder'),
            'videoSettingsPoster' => __('voodbuilder::pro.editor.video_settings.poster'),
            'videoSettingsChoose' => __('voodbuilder::pro.editor.video_settings.choose'),
            'videoSettingsClear' => __('voodbuilder::pro.editor.video_settings.clear'),
            'videoSettingsOpacity' => __('voodbuilder::pro.editor.video_settings.opacity'),
            'videoSettingsFit' => __('voodbuilder::pro.editor.video_settings.fit'),
            'videoSettingsPosition' => __('voodbuilder::pro.editor.video_settings.position'),
            'videoSettingsMinHeight' => __('voodbuilder::pro.editor.video_settings.min_height'),
            'videoSettingsAutoplay' => __('voodbuilder::pro.editor.video_settings.autoplay'),
            'videoSettingsMuted' => __('voodbuilder::pro.editor.video_settings.muted'),
            'videoSettingsLoop' => __('voodbuilder::pro.editor.video_settings.loop'),
            'videoSettingsControls' => __('voodbuilder::pro.editor.video_settings.controls'),
            'videoSettingsSource' => __('voodbuilder::pro.editor.video_settings.source'),
            'videoSettingsSourceYoutube' => __('voodbuilder::pro.editor.video_settings.source_youtube'),
            'videoSettingsSourceVimeo' => __('voodbuilder::pro.editor.video_settings.source_vimeo'),
            'videoSettingsSourceFile' => __('voodbuilder::pro.editor.video_settings.source_file'),
            'videoSettingsYoutubeId' => __('voodbuilder::pro.editor.video_settings.youtube_id'),
            'videoSettingsVimeoId' => __('voodbuilder::pro.editor.video_settings.vimeo_id'),
            'videoSettingsFileSrc' => __('voodbuilder::pro.editor.video_settings.file_src'),
            'videoSettingsPrivacy' => __('voodbuilder::pro.editor.video_settings.privacy'),
            'videoSettingsHint' => __('voodbuilder::pro.editor.video_settings.hint'),
            'videoSettingsHeroHint' => __('voodbuilder::pro.editor.video_settings.hero_hint'),
            'assetManagerImageTitle' => __('voodbuilder::pro.editor.asset_manager.image_title'),
            'assetManagerImageAdd' => __('voodbuilder::pro.editor.asset_manager.image_add'),
            'assetManagerImageInput' => __('voodbuilder::pro.editor.asset_manager.image_input'),
            'assetManagerImageUpload' => __('voodbuilder::pro.editor.asset_manager.image_upload'),
            'assetManagerVideoTitle' => __('voodbuilder::pro.editor.asset_manager.video_title'),
            'assetManagerVideoAdd' => __('voodbuilder::pro.editor.asset_manager.video_add'),
            'assetManagerVideoInput' => __('voodbuilder::pro.editor.asset_manager.video_input'),
            'assetManagerVideoUpload' => __('voodbuilder::pro.editor.asset_manager.video_upload'),
            'modalTitle' => __('voodbuilder::pro.bindings.modal_title'),
            'modalSource' => __('voodbuilder::pro.bindings.modal_source'),
            'modalField' => __('voodbuilder::pro.bindings.modal_field'),
            'modalApply' => __('voodbuilder::pro.bindings.modal_apply'),
            'modalCancel' => __('voodbuilder::pro.bindings.modal_cancel'),
            'selectComponent' => __('voodbuilder::pro.bindings.select_component'),
            'noSources' => __('voodbuilder::pro.bindings.no_sources'),
            'pluginRequiredTitle' => __('voodbuilder::pro.bindings.plugin_required_title'),
            'pluginRequiredBody' => __('voodbuilder::pro.bindings.plugin_required_body'),
            'inspectorHint' => __('voodbuilder::pro.bindings.inspector_hint'),
            'currentBinding' => __('voodbuilder::pro.bindings.current_binding'),
            'repeatSource' => __('voodbuilder::pro.bindings.repeat_source'),
            'repeatLimit' => __('voodbuilder::pro.bindings.repeat_limit'),
            'repeatOffset' => __('voodbuilder::pro.bindings.repeat_offset'),
            'repeatSort' => __('voodbuilder::pro.bindings.repeat_sort'),
            'repeatSortDir' => __('voodbuilder::pro.bindings.repeat_sort_dir'),
            'repeatSortAsc' => __('voodbuilder::pro.bindings.repeat_sort_asc'),
            'repeatSortDesc' => __('voodbuilder::pro.bindings.repeat_sort_desc'),
            'applyRepeat' => __('voodbuilder::pro.bindings.apply_repeat'),
            'clearRepeat' => __('voodbuilder::pro.bindings.clear_repeat'),
            'currentRepeat' => __('voodbuilder::pro.bindings.current_repeat'),
            'repeatList' => __('voodbuilder::pro.bindings.repeat_list'),
            'repeatContainerHint' => __('voodbuilder::pro.bindings.repeat_container_hint'),
            'bindingNeedsLeaf' => __('voodbuilder::pro.bindings.binding_needs_leaf'),
            'buttonUrlOnly' => __('voodbuilder::pro.bindings.button_url_only'),
            'buttonUrlHint' => __('voodbuilder::pro.bindings.button_url_hint'),
            'repeatListInstead' => __('voodbuilder::pro.bindings.repeat_list_instead'),
            'repeatItemHint' => __('voodbuilder::pro.bindings.repeat_item_hint'),
            'repeatTemplateHint' => __('voodbuilder::pro.bindings.repeat_template_hint'),
            'repeatFilterAny' => __('voodbuilder::pro.bindings.repeat_filter_any'),
            'repeatContainerNoBind' => __('voodbuilder::pro.bindings.repeat_container_no_bind'),
            'repeatListNotField' => __('voodbuilder::pro.bindings.repeat_list_not_field'),
            'fieldSearch' => __('voodbuilder::pro.bindings.field_search'),
            'fieldTypeText' => __('voodbuilder::pro.bindings.field_type_text'),
            'fieldTypeUrl' => __('voodbuilder::pro.bindings.field_type_url'),
            'fieldTypeImage' => __('voodbuilder::pro.bindings.field_type_image'),
            'classInputPlaceholder' => __('voodbuilder::pro.editor_ui.class_input_placeholder'),
            'classPendingCompile' => __('voodbuilder::pro.editor_ui.class_pending_compile'),
            'classCopy' => __('voodbuilder::pro.editor_ui.class_copy'),
            'classCopySuccess' => __('voodbuilder::pro.editor_ui.class_copy_success'),
            'classCopyEmpty' => __('voodbuilder::pro.editor_ui.class_copy_empty'),
            'classCopyFailed' => __('voodbuilder::pro.editor_ui.class_copy_failed'),
            'classCopyAll' => __('voodbuilder::pro.editor_ui.class_copy_all'),
            'classCopyAllSuccess' => __('voodbuilder::pro.editor_ui.class_copy_all_success'),
            'classCopyAllEmpty' => __('voodbuilder::pro.editor_ui.class_copy_all_empty'),
            'classPasteHint' => __('voodbuilder::pro.editor_ui.class_paste_hint'),
            'classPasteApplied' => __('voodbuilder::pro.editor_ui.class_paste_applied'),
            'classPasteTitle' => __('voodbuilder::pro.editor_ui.class_paste_title'),
            'classPasteConflict' => __('voodbuilder::pro.editor_ui.class_paste_conflict'),
            'classPasteKeep' => __('voodbuilder::pro.editor_ui.class_paste_keep'),
            'classPasteReplace' => __('voodbuilder::pro.editor_ui.class_paste_replace'),
            'classPasteReplaced' => __('voodbuilder::pro.editor_ui.class_paste_replaced'),
            'classAnimationTitle' => __('voodbuilder::pro.editor_ui.class_animation_title'),
            'classAnimationAdd' => __('voodbuilder::pro.editor_ui.class_animation_add'),
            'classAnimationType' => __('voodbuilder::pro.editor_ui.class_animation_type'),
            'classAnimationInteraction' => __('voodbuilder::pro.editor_ui.class_animation_interaction'),
            'classAnimationIteration' => __('voodbuilder::pro.editor_ui.class_animation_iteration'),
            'classAnimationDuration' => __('voodbuilder::pro.editor_ui.class_animation_duration'),
            'classAnimationDelay' => __('voodbuilder::pro.editor_ui.class_animation_delay'),
            'classAnimationMotion' => __('voodbuilder::pro.editor_ui.class_animation_motion'),
            'classAnimationDirection' => __('voodbuilder::pro.editor_ui.class_animation_direction'),
            'classAnimationFill' => __('voodbuilder::pro.editor_ui.class_animation_fill'),
            'classAnimationTransition' => __('voodbuilder::pro.editor_ui.class_animation_transition'),
            'classAnimationTransitionDuration' => __('voodbuilder::pro.editor_ui.class_animation_transition_duration'),
            'classAnimationTransitionEase' => __('voodbuilder::pro.editor_ui.class_animation_transition_ease'),
            'classAnimationTransitionDelay' => __('voodbuilder::pro.editor_ui.class_animation_transition_delay'),
            'classAnimationGroupPreset' => __('voodbuilder::pro.editor_ui.class_animation_group_preset'),
            'classAnimationGroupTiming' => __('voodbuilder::pro.editor_ui.class_animation_group_timing'),
            'classAnimationGroupEasing' => __('voodbuilder::pro.editor_ui.class_animation_group_easing'),
            'classAnimationGroupDirection' => __('voodbuilder::pro.editor_ui.class_animation_group_direction'),
            'classAnimationGroupFill' => __('voodbuilder::pro.editor_ui.class_animation_group_fill'),
            'classAnimationGroupTransition' => __('voodbuilder::pro.editor_ui.class_animation_group_transition'),
            'classStyleDimensionTitle' => __('voodbuilder::pro.editor_ui.class_style_dimension_title'),
            'classStyleSpacingTitle' => __('voodbuilder::pro.editor_ui.class_style_spacing_title'),
            'classStyleDecorationsTitle' => __('voodbuilder::pro.editor_ui.class_style_decorations_title'),
            'classStyleTypographyTitle' => __('voodbuilder::pro.editor_ui.class_style_typography_title'),
            'classStyleAdd' => __('voodbuilder::pro.editor_ui.class_style_add'),
            'classStyleWidth' => __('voodbuilder::pro.editor_ui.class_style_width'),
            'classStyleHeight' => __('voodbuilder::pro.editor_ui.class_style_height'),
            'classStyleMaxWidth' => __('voodbuilder::pro.editor_ui.class_style_max_width'),
            'classStyleMargin' => __('voodbuilder::pro.editor_ui.class_style_margin'),
            'classStyleMarginX' => __('voodbuilder::pro.editor_ui.class_style_margin_x'),
            'classStyleMarginY' => __('voodbuilder::pro.editor_ui.class_style_margin_y'),
            'classStyleMarginT' => __('voodbuilder::pro.editor_ui.class_style_margin_t'),
            'classStyleMarginR' => __('voodbuilder::pro.editor_ui.class_style_margin_r'),
            'classStyleMarginB' => __('voodbuilder::pro.editor_ui.class_style_margin_b'),
            'classStyleMarginL' => __('voodbuilder::pro.editor_ui.class_style_margin_l'),
            'classStylePadding' => __('voodbuilder::pro.editor_ui.class_style_padding'),
            'classStylePaddingX' => __('voodbuilder::pro.editor_ui.class_style_padding_x'),
            'classStylePaddingY' => __('voodbuilder::pro.editor_ui.class_style_padding_y'),
            'classStylePaddingT' => __('voodbuilder::pro.editor_ui.class_style_padding_t'),
            'classStylePaddingR' => __('voodbuilder::pro.editor_ui.class_style_padding_r'),
            'classStylePaddingB' => __('voodbuilder::pro.editor_ui.class_style_padding_b'),
            'classStylePaddingL' => __('voodbuilder::pro.editor_ui.class_style_padding_l'),
            'classStyleSpacingLinkSides' => __('voodbuilder::pro.editor_ui.class_style_spacing_link_sides'),
            'classStyleSpacingLinkIndependent' => __('voodbuilder::pro.editor_ui.class_style_spacing_link_independent'),
            'classStyleSpacingLinkOpposites' => __('voodbuilder::pro.editor_ui.class_style_spacing_link_opposites'),
            'classStyleSpacingLinkAll' => __('voodbuilder::pro.editor_ui.class_style_spacing_link_all'),
            'classStyleSpacingScale' => __('voodbuilder::pro.editor_ui.class_style_spacing_scale'),
            'classStyleSpacingSearch' => __('voodbuilder::pro.editor_ui.class_style_spacing_search'),
            'classStyleFontSearch' => __('voodbuilder::pro.editor_ui.class_style_font_search'),
            'classStyleFieldSearch' => __('voodbuilder::pro.editor_ui.class_style_field_search'),
            'classStyleBackground' => __('voodbuilder::pro.editor_ui.class_style_background'),
            'classStyleBackgroundColor' => __('voodbuilder::pro.editor_ui.class_style_background_color'),
            'classStyleGradient' => __('voodbuilder::pro.editor_ui.class_style_gradient'),
            'classStyleGradientDir' => __('voodbuilder::pro.editor_ui.class_style_gradient_dir'),
            'classStyleGradientFrom' => __('voodbuilder::pro.editor_ui.class_style_gradient_from'),
            'classStyleGradientVia' => __('voodbuilder::pro.editor_ui.class_style_gradient_via'),
            'classStyleGradientTo' => __('voodbuilder::pro.editor_ui.class_style_gradient_to'),
            'classStyleBorder' => __('voodbuilder::pro.editor_ui.class_style_border'),
            'classStyleBorderWidth' => __('voodbuilder::pro.editor_ui.class_style_border_width'),
            'classStyleBorderStyle' => __('voodbuilder::pro.editor_ui.class_style_border_style'),
            'classStyleBorderColor' => __('voodbuilder::pro.editor_ui.class_style_border_color'),
            'classStyleBorderT' => __('voodbuilder::pro.editor_ui.class_style_border_t'),
            'classStyleBorderR' => __('voodbuilder::pro.editor_ui.class_style_border_r'),
            'classStyleBorderB' => __('voodbuilder::pro.editor_ui.class_style_border_b'),
            'classStyleBorderL' => __('voodbuilder::pro.editor_ui.class_style_border_l'),
            'classStyleDecorationsLinkSides' => __('voodbuilder::pro.editor_ui.class_style_decorations_link_sides'),
            'classStyleDecorationsLinkCorners' => __('voodbuilder::pro.editor_ui.class_style_decorations_link_corners'),
            'classStyleRounded' => __('voodbuilder::pro.editor_ui.class_style_rounded'),
            'classStyleRoundedTl' => __('voodbuilder::pro.editor_ui.class_style_rounded_tl'),
            'classStyleRoundedTr' => __('voodbuilder::pro.editor_ui.class_style_rounded_tr'),
            'classStyleRoundedBr' => __('voodbuilder::pro.editor_ui.class_style_rounded_br'),
            'classStyleRoundedBl' => __('voodbuilder::pro.editor_ui.class_style_rounded_bl'),
            'classStyleShadow' => __('voodbuilder::pro.editor_ui.class_style_shadow'),
            'classStyleFontFamily' => __('voodbuilder::pro.editor_ui.class_style_font_family'),
            'classStyleFontSize' => __('voodbuilder::pro.editor_ui.class_style_font_size'),
            'classStyleFontWeight' => __('voodbuilder::pro.editor_ui.class_style_font_weight'),
            'classStyleTextAlign' => __('voodbuilder::pro.editor_ui.class_style_text_align'),
            'classStyleTextTransform' => __('voodbuilder::pro.editor_ui.class_style_text_transform'),
            'classStyleTextDecoration' => __('voodbuilder::pro.editor_ui.class_style_text_decoration'),
            'classStyleTextColor' => __('voodbuilder::pro.editor_ui.class_style_text_color'),
            'classStyleLeading' => __('voodbuilder::pro.editor_ui.class_style_leading'),
            'classStyleTracking' => __('voodbuilder::pro.editor_ui.class_style_tracking'),
            'classStyleClear' => __('voodbuilder::pro.editor_ui.class_style_clear'),
            'classStyleAuthoredHint' => __('voodbuilder::pro.editor_ui.class_style_authored_hint'),
            'classStyleEmptyHint' => __('voodbuilder::pro.editor_ui.class_style_empty_hint'),
            'copyComponentClasses' => __('voodbuilder::pro.editor_ui.copy_component_classes'),
            'copyComponentCode' => __('voodbuilder::pro.editor_ui.copy_component_code'),
            'copyComponentCodeSuccess' => __('voodbuilder::pro.editor_ui.copy_component_code_success'),
            'copyComponentCodeFailed' => __('voodbuilder::pro.editor_ui.copy_component_code_failed'),
            'codePreviewIncludesPageCss' => __('voodbuilder::pro.editor_ui.code_preview_includes_page_css'),
            'panelBlocks' => __('voodbuilder::pro.editor_ui.panel_blocks'),
            'panelLibrary' => __('voodbuilder::pro.editor_ui.panel_library'),
            'panelInspector' => __('voodbuilder::pro.editor_ui.panel_inspector'),
            'blockSearch' => __('voodbuilder::pro.editor_ui.block_search'),
            'blocksLoadError' => __('voodbuilder::pro.editor_ui.blocks_load_error'),
            'blockPin' => __('voodbuilder::pro.editor_ui.block_pin'),
            'blockUnpin' => __('voodbuilder::pro.editor_ui.block_unpin'),
            'blockPinnedCategory' => __('voodbuilder::pro.editor_ui.block_pinned_category'),
            'componentSearch' => __('voodbuilder::pro.editor_ui.component_search'),
            'tabElements' => __('voodbuilder::pro.editor_ui.tab_elements'),
            'tabComponents' => __('voodbuilder::pro.editor_ui.tab_components'),
            'tabTemplates' => __('voodbuilder::pro.editor_ui.tab_templates'),
            'templateSearch' => __('voodbuilder::pro.editor_ui.template_search'),
            'tabContent' => __('voodbuilder::pro.editor_ui.tab_content'),
            'tabStyle' => __('voodbuilder::pro.editor_ui.tab_style'),
            'styleClassesTitle' => __('voodbuilder::pro.editor_ui.style_classes_title'),
            'fontSearchPlaceholder' => __('voodbuilder::pro.editor_ui.font_search_placeholder'),
            'tabDynamic' => __('voodbuilder::pro.editor_ui.tab_dynamic'),
            'tabLayers' => __('voodbuilder::pro.editor_ui.tab_layers'),
            'tabConditions' => __('voodbuilder::pro.editor_ui.tab_conditions'),
            'chromeLayoutStructureInspectorNotice' => __('voodbuilder::pro.editor_ui.chrome_layout_structure_inspector_notice'),
            'chromeLayoutContentSlotInspectorNotice' => __('voodbuilder::pro.editor_ui.chrome_layout_content_slot_inspector_notice'),
            'chromeShellManagedInspectorNotice' => __('voodbuilder::pro.editor_ui.chrome_shell_managed_inspector_notice'),
            'chromeShellManagedInspectorNoticeFallback' => __('voodbuilder::pro.editor_ui.chrome_shell_managed_inspector_notice_fallback'),
            'contentNoSettings' => __('voodbuilder::pro.editor_ui.content_no_settings'),
            'exitEditor' => __('voodbuilder::pro.frontend.exit_editor'),
            'editingContextPage' => __('voodbuilder::pro.frontend.editing_context_page'),
            'editingContextLayout' => __('voodbuilder::pro.frontend.editing_context_layout'),
            'editingContextPopup' => __('voodbuilder::pro.frontend.editing_context_popup'),
            'editingContextUntitled' => __('voodbuilder::pro.frontend.editing_context_untitled'),
            'deviceDesktop' => __('voodbuilder::pro.editor_ui.device_desktop'),
            'deviceTablet' => __('voodbuilder::pro.editor_ui.device_tablet'),
            'deviceMobile' => __('voodbuilder::pro.editor_ui.device_mobile'),
            'undo' => __('voodbuilder::pro.editor_ui.undo'),
            'redo' => __('voodbuilder::pro.editor_ui.redo'),
            'outline' => __('voodbuilder::pro.editor_ui.outline'),
            'innerDropSlots' => __('voodbuilder::pro.editor_ui.inner_drop_slots'),
            'classHoverPopover' => __('voodbuilder::pro.editor_ui.class_hover_popover'),
            'preview' => __('voodbuilder::pro.editor_ui.preview'),
            'themeDark' => __('voodbuilder::pro.editor_ui.theme_dark'),
            'themeLight' => __('voodbuilder::pro.editor_ui.theme_light'),
            'viewPage' => __('voodbuilder::pro.editor_ui.view_page'),
            'zoomIn' => __('voodbuilder::pro.editor_ui.zoom_in'),
            'zoomOut' => __('voodbuilder::pro.editor_ui.zoom_out'),
            'globalTextTags' => __('voodbuilder::pro.editor_ui.global_text_tags'),
            'globalTextTagsTitle' => __('voodbuilder::pro.editor_ui.global_text_tags_title'),
            'globalTextTagsHint' => __('voodbuilder::pro.editor_ui.global_text_tags_hint'),
            'globalTextTagsCopy' => __('voodbuilder::pro.editor_ui.global_text_tags_copy'),
            'globalTextTagsCopied' => __('voodbuilder::pro.editor_ui.global_text_tags_copied'),
            'globalTextTagsPreview' => __('voodbuilder::pro.editor_ui.global_text_tags_preview'),
            'globalTextTagCurrentYear' => __('voodbuilder::pro.editor_ui.global_text_tag_current_year'),
            'globalTextTagBrandName' => __('voodbuilder::pro.editor_ui.global_text_tag_brand_name'),
            'globalTextTagSiteName' => __('voodbuilder::pro.editor_ui.global_text_tag_site_name'),
            'globalTextTagSiteUrl' => __('voodbuilder::pro.editor_ui.global_text_tag_site_url'),
            'globalTextTagLoggedUsername' => __('voodbuilder::pro.editor_ui.global_text_tag_logged_username'),
            'revisions' => __('voodbuilder::pro.editor_ui.revisions'),
            'compilingStyles' => __('voodbuilder::pro.editor_ui.compiling_styles'),
            'loadingEditor' => __('voodbuilder::pro.editor_ui.loading_editor'),
            'toggleLibraryPanel' => __('voodbuilder::pro.editor_ui.toggle_library_panel'),
            'toggleInspectorPanel' => __('voodbuilder::pro.editor_ui.toggle_inspector_panel'),
            'resizeLibraryPanel' => __('voodbuilder::pro.editor_ui.resize_library_panel'),
            'resizeInspectorPanel' => __('voodbuilder::pro.editor_ui.resize_inspector_panel'),
            'panelToggles' => __('voodbuilder::pro.editor_ui.panel_toggles'),
            'revisionsTitle' => __('voodbuilder::pro.revisions.title'),
            'revisionsRestore' => __('voodbuilder::pro.revisions.restore'),
            'revisionsRestoreConfirm' => __('voodbuilder::pro.revisions.restore_confirm'),
            'revisionsRestoreError' => __('voodbuilder::pro.revisions.restore_error'),
            'revisionsPreview' => __('voodbuilder::pro.revisions.preview'),
            'revisionsEmpty' => __('voodbuilder::pro.revisions.empty'),
            'conditionsTitle' => __('voodbuilder::pro.conditions.title'),
            'conditionsHint' => __('voodbuilder::pro.conditions.hint'),
            'conditionsEmpty' => __('voodbuilder::pro.conditions.empty'),
            'conditionsSelectFirst' => __('voodbuilder::pro.conditions.select_first'),
            'conditionsMatchLabel' => __('voodbuilder::pro.conditions.match_label'),
            'conditionsMatchAny' => __('voodbuilder::pro.conditions.match_any'),
            'conditionsMatchAll' => __('voodbuilder::pro.conditions.match_all'),
            'conditionsMatchAnyShort' => __('voodbuilder::pro.conditions.match_any_short'),
            'conditionsMatchAllShort' => __('voodbuilder::pro.conditions.match_all_short'),
            'conditionsMatchBetweenGroups' => __('voodbuilder::pro.conditions.match_between_groups'),
            'conditionsAddSet' => __('voodbuilder::pro.conditions.add_set'),
            'conditionsAddCondition' => __('voodbuilder::pro.conditions.add_condition'),
            'conditionsClear' => __('voodbuilder::pro.conditions.clear'),
            'conditionsGroup' => __('voodbuilder::pro.conditions.group'),
            'conditionsRemoveSet' => __('voodbuilder::pro.conditions.remove_set'),
            'conditionsRemoveCondition' => __('voodbuilder::pro.conditions.remove_condition'),
            'conditionsOperatorAnd' => __('voodbuilder::pro.conditions.operator_and'),
            'conditionsOperatorOr' => __('voodbuilder::pro.conditions.operator_or'),
            'conditionsFieldKey' => __('voodbuilder::pro.conditions.field_key'),
            'conditionsFieldCompare' => __('voodbuilder::pro.conditions.field_compare'),
            'conditionsFieldValue' => __('voodbuilder::pro.conditions.field_value'),
            'conditionsValuePlaceholder' => __('voodbuilder::pro.conditions.value_placeholder'),
            'conditionsBoolYes' => __('voodbuilder::pro.conditions.bool_yes'),
            'conditionsBoolNo' => __('voodbuilder::pro.conditions.bool_no'),
            'compare_==' => __('voodbuilder::pro.conditions.compare_=='),
            'compare_!=' => __('voodbuilder::pro.conditions.compare_!='),
            'compare_contains' => __('voodbuilder::pro.conditions.compare_contains'),
            'compare_not_contains' => __('voodbuilder::pro.conditions.compare_not_contains'),
            'globalClassesTitle' => __('voodbuilder::pro.global_classes.title'),
            'globalClassesSave' => __('voodbuilder::pro.global_classes.save'),
            'globalClassesName' => __('voodbuilder::pro.global_classes.name'),
            'globalClassesLabel' => __('voodbuilder::pro.global_classes.label'),
            'globalClassesEmpty' => __('voodbuilder::pro.global_classes.empty'),
            'globalClassesLoadError' => __('voodbuilder::pro.global_classes.load_error'),
            'globalClassesSaveError' => __('voodbuilder::pro.global_classes.save_error'),
            'elementsPluginRequiredTitle' => __('voodbuilder::pro.elements.plugin_required_title'),
            'elementsPluginRequiredBody' => __('voodbuilder::pro.elements.plugin_required_body'),
            'elementsLibrary' => __('voodbuilder::pro.elements.library'),
            'componentsTitle' => __('voodbuilder::pro.components.title'),
            'componentsPluginRequiredTitle' => __('voodbuilder::pro.components.plugin_required_title'),
            'componentsPluginRequiredBody' => __('voodbuilder::pro.components.plugin_required_body'),
            'componentsSave' => __('voodbuilder::pro.components.save_button'),
            'componentsSaveAs' => __('voodbuilder::pro.components.save_as'),
            'componentsSaveNeedSelection' => __('voodbuilder::pro.components.save_need_selection'),
            'componentsSaveChromeBlocked' => __('voodbuilder::pro.components.save_chrome_blocked'),
            'componentsInsert' => __('voodbuilder::pro.components.insert'),
            'componentsProps' => __('voodbuilder::pro.components.props'),
            'componentsEmpty' => __('voodbuilder::pro.components.empty'),
            'componentsEmptyHint' => __('voodbuilder::pro.components.empty_hint'),
            'componentsUncategorized' => __('voodbuilder::pro.components.uncategorized'),
            'componentsNamePrompt' => __('voodbuilder::pro.components.name_prompt'),
            'componentsLoadError' => __('voodbuilder::pro.components.load_error'),
            'componentsLoadErrorHint' => __('voodbuilder::pro.components.load_error_hint'),
            'componentsSaveError' => __('voodbuilder::pro.components.save_error'),
            'componentsDragHint' => __('voodbuilder::pro.components.drag_hint'),
            'componentsMenu' => __('voodbuilder::pro.components.menu'),
            'componentsEdit' => __('voodbuilder::pro.components.edit'),
            'componentsEditTitle' => __('voodbuilder::pro.components.edit_title'),
            'componentsEditHint' => __('voodbuilder::pro.components.edit_hint'),
            'componentsEditSubmit' => __('voodbuilder::pro.components.edit_submit'),
            'componentsDelete' => __('voodbuilder::pro.components.delete'),
            'componentsDeleteConfirm' => __('voodbuilder::pro.components.delete_confirm'),
            'componentsDeleteError' => __('voodbuilder::pro.components.delete_error'),
            'componentsCanvasEdit' => __('voodbuilder::pro.components.canvas_edit'),
            'canvasBlockEditTitle' => __('voodbuilder::pro.components.canvas_block_edit_title'),
            'canvasBlockEditHint' => __('voodbuilder::pro.components.canvas_block_edit_hint'),
            'canvasBlockEditApply' => __('voodbuilder::pro.components.canvas_block_edit_apply'),
            'editBlockCode' => __('voodbuilder::pro.components.canvas_edit'),
            'componentsCanvasSave' => __('voodbuilder::pro.components.canvas_save'),
            'componentsCanvasDelete' => __('voodbuilder::pro.components.canvas_delete'),
            'canvasDuplicate' => __('voodbuilder::pro.components.canvas_duplicate'),
            'canvasDelete' => __('voodbuilder::pro.components.canvas_delete'),
            'layerRename' => __('voodbuilder::pro.editor.layer_rename'),
            'layerRenameHint' => __('voodbuilder::pro.editor.layer_rename_hint'),
            'layerRenamePlaceholder' => __('voodbuilder::pro.editor.layer_rename_placeholder'),
            'contextInsert' => __('voodbuilder::pro.editor.context_insert'),
            'contextInsertButton' => __('voodbuilder::pro.editor.context_insert_button'),
            'contextInsertTextLink' => __('voodbuilder::pro.editor.context_insert_text_link'),
            'contextInsertIcon' => __('voodbuilder::pro.editor.context_insert_icon'),
            'contextInsertDivider' => __('voodbuilder::pro.editor.context_insert_divider'),
            'contextInsertText' => __('voodbuilder::pro.editor.context_insert_text'),
            'contextInsertImage' => __('voodbuilder::pro.editor.context_insert_image'),
            'contextInsertLink' => __('voodbuilder::pro.editor.context_insert_link'),
            'componentsImport' => __('voodbuilder::pro.components.import'),
            'componentsExport' => __('voodbuilder::pro.components.export'),
            'componentsExportAll' => __('voodbuilder::pro.components.export_all'),
            'componentsExportOne' => __('voodbuilder::pro.components.export_one'),
            'componentsExportSelected' => __('voodbuilder::pro.components.export_selected'),
            'componentsDeleteSelected' => __('voodbuilder::pro.components.delete_selected'),
            'componentsDeleteSelectedConfirm' => __('voodbuilder::pro.components.delete_selected_confirm'),
            'componentsSelectMode' => __('voodbuilder::pro.components.select_mode'),
            'componentsSelectCancel' => __('voodbuilder::pro.components.select_cancel'),
            'componentsSelectedCount' => __('voodbuilder::pro.components.selected_count'),
            'componentsSelectedShort' => __('voodbuilder::pro.components.selected_short'),
            'componentsImportTitle' => __('voodbuilder::pro.components.import_title'),
            'componentsImportDrop' => __('voodbuilder::pro.components.import_drop'),
            'componentsImportSelect' => __('voodbuilder::pro.components.import_select'),
            'componentsImportCancel' => __('voodbuilder::pro.components.import_cancel'),
            'componentsImportSuccess' => __('voodbuilder::pro.components.import_success'),
            'componentsImportError' => __('voodbuilder::pro.components.import_error'),
            'componentsImportInvalidFile' => __('voodbuilder::pro.components.import_invalid_file'),
            'componentsExportError' => __('voodbuilder::pro.components.export_error'),
            'componentsCodeImport' => __('voodbuilder::pro.components.code_import'),
            'componentsCodeImportTitle' => __('voodbuilder::pro.components.code_import_title'),
            'componentsCodeImportHint' => __('voodbuilder::pro.components.code_import_hint'),
            'componentsCodeImportNamePlaceholder' => __('voodbuilder::pro.components.code_import_name_placeholder'),
            'componentsCodeImportCategory' => __('voodbuilder::pro.components.code_import_category'),
            'componentsCodeImportHtmlPlaceholder' => __('voodbuilder::pro.components.code_import_html_placeholder'),
            'componentsCodeImportCssOptional' => __('voodbuilder::pro.components.code_import_css_optional'),
            'componentsCodeImportCssPlaceholder' => __('voodbuilder::pro.components.code_import_css_placeholder'),
            'componentsCodeImportPreview' => __('voodbuilder::pro.components.code_import_preview'),
            'componentsCodeImportSubmit' => __('voodbuilder::pro.components.code_import_submit'),
            'componentsCodeImportNameRequired' => __('voodbuilder::pro.components.code_import_name_required'),
            'componentsCodeImportHtmlRequired' => __('voodbuilder::pro.components.code_import_html_required'),
            'componentsCodeImportCompiling' => __('voodbuilder::pro.components.code_import_compiling'),
            'componentsCompatibilityTitle' => __('voodbuilder::pro.components.compatibility_title'),
            'componentsCompatibilityEmpty' => __('voodbuilder::pro.components.compatibility_empty'),
            'componentsCompatibilityClasses' => __('voodbuilder::pro.components.compatibility_classes'),
            'componentsCompatibilityReady' => __('voodbuilder::pro.components.compatibility_ready'),
            'componentsCompatibilityAdapted' => __('voodbuilder::pro.components.compatibility_adapted'),
            'componentsCompatibilityReview' => __('voodbuilder::pro.components.compatibility_review'),
            'componentsCompatibilityAdaptations' => __('voodbuilder::pro.components.compatibility_adaptations'),
            'componentsCompatibilityNoAdaptations' => __('voodbuilder::pro.components.compatibility_no_adaptations'),
            'componentsCompatibilityReviewTitle' => __('voodbuilder::pro.components.compatibility_review_title'),
            'componentsCompatibilityReviewHint' => __('voodbuilder::pro.components.compatibility_review_hint'),
            'componentsCompatibilityChromeReviewHint' => __('voodbuilder::pro.components.compatibility_chrome_review_hint'),
            'componentsCompatibilityChromeBannerTitle' => __('voodbuilder::pro.components.compatibility_chrome_banner_title'),
            'componentsCompatibilityChromeBanner' => __('voodbuilder::pro.components.compatibility_chrome_banner'),
            'componentsCompatibilityThemeReady' => __('voodbuilder::pro.components.compatibility_theme_ready'),
            'componentsCompatibilityReviewJump' => __('voodbuilder::pro.components.compatibility_review_jump'),
            'codeEditorWrap' => __('voodbuilder::pro.components.code_editor_wrap'),
            'codeEditorWrapOn' => __('voodbuilder::pro.components.code_editor_wrap_on'),
            'codeEditorWrapOff' => __('voodbuilder::pro.components.code_editor_wrap_off'),
            'componentsCompatibilityNoReview' => __('voodbuilder::pro.components.compatibility_no_review'),
            'componentsCompatibilityReadySample' => __('voodbuilder::pro.components.compatibility_ready_sample'),
            'componentsCompatibilityStatusExcellent' => __('voodbuilder::pro.components.compatibility_status_excellent'),
            'componentsCompatibilityStatusGood' => __('voodbuilder::pro.components.compatibility_status_good'),
            'componentsCompatibilityStatusPartial' => __('voodbuilder::pro.components.compatibility_status_partial'),
            'componentsCompatibilityStatusPoor' => __('voodbuilder::pro.components.compatibility_status_poor'),
            'componentsCompileError' => __('voodbuilder::pro.components.compile_error'),
            'componentsCompilePending' => __('voodbuilder::pro.components.compile_pending'),
            'pageTemplates' => __('voodbuilder::pro.page_templates.title'),
            'pageTemplatesTitle' => __('voodbuilder::pro.page_templates.title'),
            'pageTemplatesHint' => __('voodbuilder::pro.page_templates.hint'),
            'pageTemplatesSave' => __('voodbuilder::pro.page_templates.save'),
            'pageTemplatesSaveTitle' => __('voodbuilder::pro.page_templates.save_title'),
            'pageTemplatesSaveError' => __('voodbuilder::pro.page_templates.save_error'),
            'pageTemplatesName' => __('voodbuilder::pro.page_templates.name'),
            'pageTemplatesNamePlaceholder' => __('voodbuilder::pro.page_templates.name_placeholder'),
            'pageTemplatesCategory' => __('voodbuilder::pro.page_templates.category'),
            'pageTemplatesApply' => __('voodbuilder::pro.page_templates.apply'),
            'pageTemplatesApplyConfirm' => __('voodbuilder::pro.page_templates.apply_confirm'),
            'pageTemplatesApplyChoiceTitle' => __('voodbuilder::pro.page_templates.apply_choice_title'),
            'pageTemplatesApplyChoiceMessage' => __('voodbuilder::pro.page_templates.apply_choice_message'),
            'pageTemplatesApplyReplace' => __('voodbuilder::pro.page_templates.apply_replace'),
            'pageTemplatesApplyKeep' => __('voodbuilder::pro.page_templates.apply_keep'),
            'pageTemplatesSidebarHint' => __('voodbuilder::pro.page_templates.sidebar_hint'),
            'pageTemplatesDelete' => __('voodbuilder::pro.page_templates.delete'),
            'pageTemplatesDeleteConfirm' => __('voodbuilder::pro.page_templates.delete_confirm'),
            'pageTemplatesDeleteError' => __('voodbuilder::pro.page_templates.delete_error'),
            'pageTemplatesEmpty' => __('voodbuilder::pro.page_templates.empty'),
            'pageTemplatesMarketplaceHint' => __('voodbuilder::pro.page_templates.marketplace_hint'),
            'pageTemplatesPluginTitle' => __('voodbuilder::pro.page_templates.plugin_title'),
            'pageTemplatesPluginHint' => __('voodbuilder::pro.page_templates.plugin_hint'),
            'pageTemplatesLoading' => __('voodbuilder::pro.page_templates.loading'),
            'pageTemplatesLoadError' => __('voodbuilder::pro.page_templates.load_error'),
            'pageTemplatesImport' => __('voodbuilder::pro.page_templates.import'),
            'pageTemplatesExport' => __('voodbuilder::pro.page_templates.export'),
            'pageTemplatesExportSelected' => __('voodbuilder::pro.page_templates.export_selected'),
            'pageTemplatesImportSuccess' => __('voodbuilder::pro.page_templates.import_success'),
            'pageTemplatesImportError' => __('voodbuilder::pro.page_templates.import_error'),
            'pageTemplatesImportInvalidFile' => __('voodbuilder::pro.page_templates.import_invalid_file'),
            'pageTemplatesExportError' => __('voodbuilder::pro.page_templates.export_error'),
            'pageTemplatesImportUrl' => __('voodbuilder::pro.page_templates.import_url'),
            'pageTemplatesImportUrlPrompt' => __('voodbuilder::pro.page_templates.import_url_prompt'),
            'pageTemplatesImportUrlSuccess' => __('voodbuilder::pro.page_templates.import_url_success'),
            'pageTemplatesImportUrlError' => __('voodbuilder::pro.page_templates.import_url_error'),
            'pageTemplatesCatalogTitle' => __('voodbuilder::pro.page_templates.catalog_title'),
            'pageTemplatesCatalogEmpty' => __('voodbuilder::pro.page_templates.catalog_empty'),
            'pageTemplatesCatalogInstall' => __('voodbuilder::pro.page_templates.catalog_install'),
            'pageTemplatesCatalogInstallSuccess' => __('voodbuilder::pro.page_templates.catalog_install_success'),
            'pageTemplatesCatalogInstallError' => __('voodbuilder::pro.page_templates.catalog_install_error'),
            'pageTemplatesSelectMode' => __('voodbuilder::pro.page_templates.select_mode'),
            'pageTemplatesSelectCancel' => __('voodbuilder::pro.page_templates.select_cancel'),
            'pageTemplatesSelectedCount' => __('voodbuilder::pro.page_templates.selected_count'),
            'pageTemplatesDeleteSelected' => __('voodbuilder::pro.page_templates.delete_selected'),
            'pageTemplatesDeleteSelectedConfirm' => __('voodbuilder::pro.page_templates.delete_selected_confirm'),
            'pageTemplatesImportTitle' => __('voodbuilder::pro.page_templates.import_title'),
            'pageTemplatesImportDrop' => __('voodbuilder::pro.page_templates.import_drop'),
            'pageTemplatesImportSelect' => __('voodbuilder::pro.page_templates.import_select'),
            'pageTemplatesImportCancel' => __('voodbuilder::pro.page_templates.import_cancel'),
            'chromeLayoutEditingBadge' => __('voodbuilder::chrome_layouts.editor.layout_editing_badge'),
            'chromeLayoutEditingHint' => __('voodbuilder::chrome_layouts.editor.layout_editing_hint'),
            'pageContentPlaceholder' => __('voodbuilder::chrome_layouts.editor.page_content_placeholder'),
            'layoutContentSlotPlaceholder' => __('voodbuilder::chrome_layouts.editor.layout_content_slot_placeholder'),
            'layoutNavZonePlaceholder' => __('voodbuilder::chrome_layouts.editor.layout_nav_zone_placeholder'),
            'layoutFooterZonePlaceholder' => __('voodbuilder::chrome_layouts.editor.layout_footer_zone_placeholder'),
            'dialogOk' => __('voodbuilder::pro.editor_ui.dialog_ok'),
            'dialogCancel' => __('voodbuilder::pro.editor_ui.dialog_cancel'),
            'dialogConfirm' => __('voodbuilder::pro.editor_ui.dialog_confirm'),
            'dialogDelete' => __('voodbuilder::pro.editor_ui.dialog_delete'),
            'dialogAlertTitle' => __('voodbuilder::pro.editor_ui.dialog_alert_title'),
            'dialogConfirmTitle' => __('voodbuilder::pro.editor_ui.dialog_confirm_title'),
            'dialogPromptTitle' => __('voodbuilder::pro.editor_ui.dialog_prompt_title'),
            'sessionExpired' => __('voodbuilder::pro.frontend.session_expired'),
            'forbidden' => __('voodbuilder::pro.frontend.forbidden'),
            'footerSettingsTitle' => __('voodbuilder::pro.editor.footer_settings.title'),
            'footerTabBrand' => __('voodbuilder::pro.editor.footer_settings.tab_brand'),
            'footerTabLayout' => __('voodbuilder::pro.editor.footer_settings.tab_layout'),
            'footerTabColumns' => __('voodbuilder::pro.editor.footer_settings.tab_columns'),
            'footerShowLogo' => __('voodbuilder::pro.editor.footer_settings.show_logo'),
            'footerShowSiteName' => __('voodbuilder::pro.editor.footer_settings.show_site_name'),
            'footerShowCopyright' => __('voodbuilder::pro.editor.footer_settings.show_copyright'),
            'footerShowMenu' => __('voodbuilder::pro.editor.footer_settings.show_footer_menu'),
            'footerShowTagline' => __('voodbuilder::pro.editor.footer_settings.show_tagline'),
            'footerShowSocial' => __('voodbuilder::pro.editor.footer_settings.show_social'),
            'footerShowNewsletter' => __('voodbuilder::pro.editor.footer_settings.show_newsletter'),
            'footerSocialAlign' => __('voodbuilder::pro.editor.footer_settings.social_align'),
            'footerSocialAlignLeft' => __('voodbuilder::pro.editor.footer_settings.social_align_left'),
            'footerSocialAlignCenter' => __('voodbuilder::pro.editor.footer_settings.social_align_center'),
            'footerSocialAlignRight' => __('voodbuilder::pro.editor.footer_settings.social_align_right'),
            'footerColumnsRedistribute' => __('voodbuilder::pro.editor.footer_settings.columns_redistribute'),
            'footerDefaultTagline' => __('voodbuilder::pro.editor.blocks.footer_default_tagline'),
            'logoDesktopLight' => __('voodbuilder::pro.editor.footer_settings.logo_desktop_light'),
            'logoDesktopDark' => __('voodbuilder::pro.editor.footer_settings.logo_desktop_dark'),
            'logoMobileLight' => __('voodbuilder::pro.editor.footer_settings.logo_mobile_light'),
            'logoMobileDark' => __('voodbuilder::pro.editor.footer_settings.logo_mobile_dark'),
            'logoSize' => __('voodbuilder::pro.editor.footer_settings.logo_size'),
            'logoSizeDesktop' => __('voodbuilder::pro.editor.footer_settings.logo_size'),
            'logoSizeMobile' => __('voodbuilder::pro.editor.footer_settings.logo_size_mobile'),
            'logoFullWidth' => __('voodbuilder::pro.editor.footer_settings.logo_full_width'),
            'logoSizeSm' => __('voodbuilder::pro.editor.footer_settings.logo_size_sm'),
            'logoSizeMd' => __('voodbuilder::pro.editor.footer_settings.logo_size_md'),
            'logoSizeLg' => __('voodbuilder::pro.editor.footer_settings.logo_size_lg'),
            'logoSizeXl' => __('voodbuilder::pro.editor.footer_settings.logo_size_xl'),
            'logoChoose' => __('voodbuilder::pro.editor.footer_settings.logo_choose'),
            'logoClear' => __('voodbuilder::pro.editor.footer_settings.logo_clear'),
            'navSettingsTitle' => __('voodbuilder::pro.editor.nav_settings.title'),
            'navTabLayout' => __('voodbuilder::pro.editor.nav_settings.tab_layout'),
            'navTabBrand' => __('voodbuilder::pro.editor.nav_settings.tab_brand'),
            'navShowLogo' => __('voodbuilder::pro.editor.nav_settings.show_logo'),
            'navShowSiteName' => __('voodbuilder::pro.editor.nav_settings.show_site_name'),
            'navMenuPosition' => __('voodbuilder::pro.editor.nav_settings.menu_position'),
            'navMenuLeft' => __('voodbuilder::pro.editor.nav_settings.menu_left'),
            'navMenuCenter' => __('voodbuilder::pro.editor.nav_settings.menu_center'),
            'navSticky' => __('voodbuilder::pro.editor.nav_settings.sticky'),
            'navStickyInherit' => __('voodbuilder::pro.editor.nav_settings.sticky_inherit'),
            'navStickyOn' => __('voodbuilder::pro.editor.nav_settings.sticky_on'),
            'navStickyOff' => __('voodbuilder::pro.editor.nav_settings.sticky_off'),
            'navShowSearch' => __('voodbuilder::pro.editor.nav_settings.show_search'),
            'navShowNotifications' => __('voodbuilder::pro.editor.nav_settings.show_notifications'),
            'navShowProfile' => __('voodbuilder::pro.editor.nav_settings.show_profile'),
            'newsletterList' => __('voodbuilder::pro.editor.newsletter_settings.list'),
            'newsletterTitle' => __('voodbuilder::pro.editor.newsletter_settings.title'),
            'newsletterHint' => __('voodbuilder::pro.editor.newsletter_settings.hint'),
            'buttonSettingsTitle' => __('voodbuilder::pro.editor.button_link.settings_title'),
            'buttonLinkLabel' => __('voodbuilder::pro.editor.button_link.label'),
            'buttonLinkUrl' => __('voodbuilder::pro.editor.button_link.url'),
            'buttonLinkUrlPlaceholder' => __('voodbuilder::pro.editor.button_link.url_placeholder'),
            'buttonLinkType' => __('voodbuilder::pro.editor.button_link.type'),
            'buttonLinkTypeUrl' => __('voodbuilder::pro.editor.button_link.type_url'),
            'buttonLinkTypePage' => __('voodbuilder::pro.editor.button_link.type_page'),
            'buttonLinkTypeMenu' => __('voodbuilder::pro.editor.button_link.type_menu'),
            'buttonLinkPage' => __('voodbuilder::pro.editor.button_link.page'),
            'buttonLinkMenu' => __('voodbuilder::pro.editor.button_link.menu'),
            'buttonLinkTarget' => __('voodbuilder::pro.editor.button_link.target'),
            'buttonLinkSameTab' => __('voodbuilder::pro.editor.button_link.same_tab'),
            'buttonLinkNewTab' => __('voodbuilder::pro.editor.button_link.new_tab'),
            'buttonDynamicHint' => __('voodbuilder::pro.bindings.button_dynamic_hint'),
            'rteWrapTitle' => __('voodbuilder::pro.editor.rte.wrap_title'),
            'rteLinkTitle' => __('voodbuilder::pro.editor.rte.link_title'),
            'rteLinkPromptTitle' => __('voodbuilder::pro.editor.rte.link_prompt_title'),
            'rteLinkPromptMessage' => __('voodbuilder::pro.editor.rte.link_prompt_message'),
            'rteLinkPlaceholder' => __('voodbuilder::pro.editor.rte.link_placeholder'),
            'rteLinkRemove' => __('voodbuilder::pro.editor.rte.link_remove'),
            'layoutCategory' => __('voodbuilder::pro.editor.layout.category'),
            'layoutSection' => __('voodbuilder::pro.editor.layout.section'),
            'layoutContainer' => __('voodbuilder::pro.editor.layout.container'),
            'layoutBlock' => __('voodbuilder::pro.editor.layout.block'),
            'layoutDiv' => __('voodbuilder::pro.editor.layout.div'),
            'layoutPickerTitle' => __('voodbuilder::pro.editor.layout.picker_title'),
            'layoutPickerCaption' => __('voodbuilder::pro.editor.layout.picker_caption'),
            'layoutSectionSettingsTitle' => __('voodbuilder::pro.editor.layout.section_settings_title'),
            'layoutContainerSettingsTitle' => __('voodbuilder::pro.editor.layout.container_settings_title'),
            'layoutColumns' => __('voodbuilder::pro.editor.layout.columns'),
            'layoutOpenPicker' => __('voodbuilder::pro.editor.layout.open_picker'),
            'layoutSectionPadding' => __('voodbuilder::pro.editor.layout.section_padding'),
            'iconSettingsTitle' => __('voodbuilder::pro.editor.basic.icon_settings_title'),
            'iconName' => __('voodbuilder::pro.editor.basic.icon_name'),
            'iconSet' => __('voodbuilder::pro.editor.basic.icon_set'),
            'iconCategory' => __('voodbuilder::pro.editor.basic.icon_category'),
            'iconCategoryAll' => __('voodbuilder::pro.editor.basic.icon_category_all'),
            'iconStyle' => __('voodbuilder::pro.editor.basic.icon_style'),
            'iconStyleOutline' => __('voodbuilder::pro.editor.basic.icon_style_outline'),
            'iconStyleFilled' => __('voodbuilder::pro.editor.basic.icon_style_filled'),
            'iconStroke' => __('voodbuilder::pro.editor.basic.icon_stroke'),
            'iconColor' => __('voodbuilder::pro.editor.basic.icon_color'),
            'iconColorPlaceholder' => __('voodbuilder::pro.editor.basic.icon_color_placeholder'),
            'iconSearch' => __('voodbuilder::pro.editor.basic.icon_search'),
            'iconSearchPlaceholder' => __('voodbuilder::pro.editor.basic.icon_search_placeholder'),
            'iconSearchEmpty' => __('voodbuilder::pro.editor.basic.icon_search_empty'),
            'iconCatalogLoading' => __('voodbuilder::pro.editor.basic.icon_catalog_loading'),
            'iconCatalogError' => __('voodbuilder::pro.editor.basic.icon_catalog_error'),
            'iconCatalogCount' => __('voodbuilder::pro.editor.basic.icon_catalog_count'),
            'iconLoadMore' => __('voodbuilder::pro.editor.basic.icon_load_more'),
            'iconSize' => __('voodbuilder::pro.editor.basic.icon_size'),
            'iconLinkType' => __('voodbuilder::pro.editor.basic.icon_link'),
            'iconLinkNone' => __('voodbuilder::pro.editor.basic.icon_link_none'),
            'basicTextSettingsTitle' => __('voodbuilder::pro.editor.basic.basic_text_settings_title'),
            'basicTextHint' => __('voodbuilder::pro.editor.basic.basic_text_hint'),
            'richTextSettingsTitle' => __('voodbuilder::pro.editor.basic.rich_text_settings_title'),
            'richTextModeVisual' => __('voodbuilder::pro.editor.basic.rich_text_mode_visual'),
            'richTextModeCode' => __('voodbuilder::pro.editor.basic.rich_text_mode_code'),
            'richTextBold' => __('voodbuilder::pro.editor.basic.rich_text_bold'),
            'richTextItalic' => __('voodbuilder::pro.editor.basic.rich_text_italic'),
            'richTextUnderline' => __('voodbuilder::pro.editor.basic.rich_text_underline'),
            'richTextLink' => __('voodbuilder::pro.editor.basic.rich_text_link'),
            'richTextAlignLeft' => __('voodbuilder::pro.editor.basic.rich_text_align_left'),
            'richTextAlignCenter' => __('voodbuilder::pro.editor.basic.rich_text_align_center'),
            'richTextAlignRight' => __('voodbuilder::pro.editor.basic.rich_text_align_right'),
            'richTextBulletList' => __('voodbuilder::pro.editor.basic.rich_text_bullet_list'),
            'richTextNumberList' => __('voodbuilder::pro.editor.basic.rich_text_number_list'),
            'richTextDynamicData' => __('voodbuilder::pro.editor.basic.rich_text_dynamic_data'),
            'richTextDynamicUserProfile' => __('voodbuilder::pro.editor.basic.rich_text_dynamic_user_profile'),
            'richTextDynamicLatest' => __('voodbuilder::pro.editor.basic.rich_text_dynamic_latest'),
            'richTextDynamicEmpty' => __('voodbuilder::pro.editor.basic.rich_text_dynamic_empty'),
            'textLinkSettingsTitle' => __('voodbuilder::pro.editor.basic.text_link_settings_title'),
            'textLinkLabel' => __('voodbuilder::pro.editor.basic.text_link_label'),
            'dividerSettingsTitle' => __('voodbuilder::pro.editor.basic.divider_settings_title'),
            'dividerColor' => __('voodbuilder::pro.editor.basic.divider_color'),
            'dividerColorHint' => __('voodbuilder::pro.editor.basic.divider_color_hint'),
        ];

        foreach (self::$labelProviders as $provider) {
            $labels = array_merge($labels, $provider());
        }

        return $labels;
    }

    /**
     * @return array{html: string, css: string, js: string, project: mixed}
     */
    public static function initialPayload(SitePage $page): array
    {
        $payload = $page->builder_payload ?? [];
        $html = (string) ($payload['html'] ?? '');
        $css = (string) ($payload['css'] ?? '');

        if (ChromeLayoutManagedContent::sitePageUsesChromeShell($page)) {
            $html = ChromeLayoutManagedContent::stripSiteChromeFromPageHtml($html);
        } else {
            $html = ChromeLayoutManagedContent::stripChromeEditorBleedFromPageHtml($html);
        }

        $normalized = self::normalizePayload([
            'html' => $html,
            'css' => $css,
            'js' => $payload['js'] ?? '',
            'project' => $payload['project'] ?? null,
        ]);

        $html = $normalized['html'];
        $css = $normalized['css'];
        $componentCss = ComponentRuntimeBridge::componentCssForHtml($html) ?? '';

        if ($componentCss !== '') {
            $css = trim(implode("\n\n", array_filter([$css, $componentCss])));
        }

        if (self::isEditing($page)) {
            $html = app(EditorBindingRenderer::class)->render($html, $page);
            $html = GlobalTextTags::replaceInHtml($html);
        }

        if (self::isEditing($page) && ChromeLayoutManagedContent::sitePageUsesChromeShell($page)) {
            $composed = ChromeLayoutEditorPreview::composeForPage($page, $html, $css);

            if ($composed !== null) {
                $html = $composed['html'];
                $css = $composed['css'];
            }
        }

        $pageManager = self::pageManagerFromHtml($html, $css);

        if ($pageManager === null && self::hasPersistedProject($normalized['project'] ?? null)) {
            return [
                'html' => $html,
                'css' => $css,
                'project' => $normalized['project'],
                'pageManager' => null,
            ];
        }

        return [
            'html' => $html,
            'css' => $css,
            'project' => null,
            'pageManager' => $pageManager,
        ];
    }

    /**
     * @return array{pages: list<array{id: string, component: string, styles: string}>}|null
     */
    private static function pageManagerFromHtml(string $html, string $css): ?array
    {
        if (! filled($html)) {
            return null;
        }

        return [
            'pages' => [[
                'id' => 'main',
                'component' => $html,
                'styles' => $css,
            ]],
        ];
    }

    /**
     * @param  array{html?: string, css?: string, js?: string, project?: mixed}  $payload
     * @return array{html: string, css: string, js: string, project: mixed, fonts: list<string>}
     */
    public static function normalizePayload(array $payload, bool $recompilePageCss = false): array
    {
        $html = EditorHtmlSanitizer::sanitize((string) ($payload['html'] ?? ''));
        // Drop data-gjs-* before editor reload/save. Stale props (e.g. corrupted
        // data-gjs-droppable="e=>!x7(e)") and content/ctaLabel fights blank CTAs.
        $html = EditorHtmlSanitizer::stripEditorOnlyAttributes($html);
        $html = EditorHtmlSanitizer::restoreEmptyCtaLabels($html);
        $html = EditorDynamicBlockAttributeNormalizer::normalize($html);
        $html = EditorConditionsAttributeNormalizer::normalize($html);
        $html = EditorCustomCodeSanitizer::sanitize($html);
        $html = EditorCodeBlockNormalizer::normalize($html);
        $css = (string) ($payload['css'] ?? '');
        $js = (string) ($payload['js'] ?? '');
        $project = $payload['project'] ?? null;

        $migratedHtml = EditorImportedTailwindSupport::bakeSvgPaintInHtml(
            EditorImportedTailwindSupport::stripSpuriousSvgBakedPaint(
                app(EditorBindingNormalizer::class)->normalizeHtml(
                    EditorPlaceholderNormalizer::normalizeHtml(
                        EditorLibraryLayoutNormalizer::normalize(
                            VoodbuilderThemeTokenMigrator::migrateHtml($html),
                        ),
                    ),
                ),
            ),
        );

        $resolvedCss = $recompilePageCss
            ? EditorPastedComponentNormalizer::resolvePublishedPageCssForSave($migratedHtml, $css)
            : EditorPastedComponentNormalizer::resolvePublishedPageCss($migratedHtml, $css);

        $resolvedCss = ThemePalette::stripEmbeddedPaletteOverrides($resolvedCss);

        return FontStylesheets::withDetectedFonts([
            'html' => $migratedHtml,
            'css' => $resolvedCss,
            'js' => EditorJsSanitizer::sanitize($js),
            'project' => is_array($project)
                ? VoodbuilderThemeTokenMigrator::migrateProject($project)
                : $project,
        ]);
    }

    /**
     * Relative URLs so the editor works when APP_URL port differs from the browser (e.g. dock WEB_PORT).
     *
     * @param  array<string, mixed>|object|string|int|null  $parameters
     */
    private static function editorRoute(string $name, mixed $parameters = []): string
    {
        return route($name, $parameters, absolute: false);
    }

    /**
     * Resolve a named editor route when registered; never throw (companion packages may lag).
     *
     * @param  array<string, mixed>|object|string|int|null  $parameters
     */
    private static function optionalEditorRoute(string $name, mixed $parameters = []): ?string
    {
        if (! Route::has($name)) {
            return null;
        }

        return self::editorRoute($name, $parameters);
    }

    public static function chromeShellPreviewSubTheme(): string
    {
        return ChromeLayoutSubThemeResolver::forPagesChannel();
    }

    public static function hasPersistedProject(mixed $project): bool
    {
        if ($project === null || ! is_array($project)) {
            return false;
        }

        if ($project === []) {
            return false;
        }

        if (isset($project['pages']) && is_array($project['pages'])) {
            return $project['pages'] !== [];
        }

        return $project !== [];
    }

    /**
     * @return array<string, string>
     */
    private static function newsletterListOptions(): array
    {
        $lists = config('voodbuilder.editor.newsletter_lists', []);

        if ($lists === []) {
            return [
                'default' => __('voodbuilder::pro.editor.newsletter_settings.default_list'),
            ];
        }

        return $lists;
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

    /**
     * Prefer vmedia package routes; fall back to transitional voodbuilder.editor.* aliases.
     */
    private static function mediaUploadUrl(): ?string
    {
        foreach (['vmedia.media.upload', 'voodbuilder.editor.upload'] as $name) {
            if (Route::has($name)) {
                return self::editorRoute($name);
            }
        }

        return null;
    }

    private static function mediaReplaceUrl(): ?string
    {
        foreach (['vmedia.media.replace', 'voodbuilder.editor.media.replace'] as $name) {
            if (Route::has($name)) {
                return self::editorRoute($name);
            }
        }

        return null;
    }

    private static function mediaLibraryIndexUrl(): ?string
    {
        foreach (['vmedia.media.index', 'voodbuilder.editor.media.index'] as $name) {
            if (Route::has($name)) {
                return self::editorRoute($name);
            }
        }

        return null;
    }

    private static function mediaGalleriesIndexUrl(): ?string
    {
        foreach (['vmedia.media.galleries', 'voodbuilder.editor.media.galleries'] as $name) {
            if (Route::has($name)) {
                return self::editorRoute($name);
            }
        }

        return null;
    }

    /**
     * Custom media browser (galleries) requires the Media companion Filament plugin.
     */
    private static function mediaCompanionBrowserEnabled(): bool
    {
        $class = 'Voodflow\\Vmedia\\Vmedia';

        if (! class_exists($class) || ! method_exists($class, 'isActive')) {
            return false;
        }

        try {
            return (bool) $class::isActive()
                && (
                    Route::has('vmedia.media.galleries')
                    || Route::has('voodbuilder.editor.media.galleries')
                );
        } catch (\Throwable) {
            return false;
        }
    }
}
