<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingNormalizer;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsConditionHooks;
use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsConditionsAttributeNormalizer;
use Voodflow\Voodbuilder\Support\ChromeLayoutEditorPreview;
use Voodflow\Voodbuilder\Support\ChromeLayoutManagedContent;
use Voodflow\Voodbuilder\Support\ChromeLayoutRenderer;
use Voodflow\Voodbuilder\Support\ChromeLayoutSubThemeResolver;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\SiteFooterColumnPlacements;
use Voodflow\Voodbuilder\Support\SubThemeResolver;
use Voodflow\Voodbuilder\Support\ThemePalette;
use Voodflow\Voodbuilder\Support\VoodbuilderPackageVersion;
use Voodflow\Voodbuilder\Support\VoodbuilderTheme;

final class GrapesJsEditorGate
{
    /** @var (callable(SitePage): bool)|null */
    private static ?\Closure $authorizer = null;

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
        return config('voodbuilder.grapesjs.enabled', true)
            && $page->usesGrapesJsBuilder()
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

        return [
            'pageId' => $page->getKey(),
            'chromeShellMode' => $chromeShellMode,
            'chromeShellName' => $chromeLayout?->name,
            'chromeShellParts' => $chromeShellParts,
            'chromeLayoutCss' => $chromeLayoutCss,
            'savedPageHtml' => (string) (($page->builder_payload ?? [])['html'] ?? ''),
            'saveUrl' => self::editorRoute('voodbuilder.grapesjs.pages.update', $page),
            'exitUrl' => $page->getUrl(),
            'viewPageUrl' => $page->getUrl(),
            'uploadUrl' => self::editorRoute('voodbuilder.grapesjs.upload'),
            'csrf' => csrf_token(),
            'initial' => self::initialPayload($page),
            'blocksUrl' => self::editorRoute('voodbuilder.grapesjs.blocks'),
            'bindingsUrl' => self::editorRoute('voodbuilder.grapesjs.bindings'),
            'bindingsPreviewUrl' => self::editorRoute('voodbuilder.grapesjs.bindings.preview', $page),
            'blocksRenderUrl' => self::editorRoute('voodbuilder.grapesjs.blocks.render'),
            'codeHighlightUrl' => self::editorRoute('voodbuilder.grapesjs.code.highlight'),
            'formSubmitUrl' => self::editorRoute('voodbuilder.grapesjs.forms.submit', $page),
            'newsletterLists' => self::newsletterListOptions(),
            'revisionsUrl' => self::editorRoute('voodbuilder.grapesjs.pages.revisions.index', $page),
            'revisionsRestoreUrl' => self::editorRoute('voodbuilder.grapesjs.pages.revisions.restore', [
                'sitePage' => $page,
                'revision' => '__REVISION__',
            ]),
            'globalClassesUrl' => self::editorRoute('voodbuilder.grapesjs.global-classes.index'),
            'componentsUrl' => self::editorRoute('voodbuilder.grapesjs.components.index'),
            'pageTemplatesUrl' => self::editorRoute('voodbuilder.grapesjs.page-templates.index'),
            'pageTemplatesCatalogUrl' => filled(config('voodbuilder.page_templates.catalog_url'))
                ? self::editorRoute('voodbuilder.grapesjs.page-templates.catalog')
                : null,
            'popupsUrl' => config('voodbuilder.popups.enabled', true)
                ? self::editorRoute('voodbuilder.grapesjs.popups.index')
                : null,
            'popupsPagePathsUrl' => config('voodbuilder.popups.enabled', true)
                ? self::editorRoute('voodbuilder.grapesjs.popups.page-paths')
                : null,
            'packageVersion' => VoodbuilderPackageVersion::current(),
            'componentCategories' => GrapesJsComponentCategoryNormalizer::categories(),
            'templateCategories' => PageTemplateCategories::all(),
            'conditionOptions' => GrapesJsConditionHooks::options(),
            'plugins' => config('voodbuilder.grapesjs.plugins', []),
            'canvasStyles' => GrapesJsCanvas::styleUrls(),
            'canvasFrameStyle' => GrapesJsCanvas::frameStyle($subTheme),
            'subTheme' => $subTheme,
            'canvasPrefersDark' => VoodbuilderTheme::serverInitialDark(),
            'landingCanvas' => $page->usesLandingCanvas() || $subTheme === 'site',
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
            'builderBrand' => config('voodbuilder.grapesjs.builder.brand', 'VoodBuilder'),
            'labels' => self::sharedEditorLabels(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function sharedEditorLabels(): array
    {
        return [
                'save' => __('voodbuilder::pro.frontend.save'),
                'saving' => __('voodbuilder::pro.frontend.saving'),
                'saved' => __('voodbuilder::pro.frontend.saved'),
                'error' => __('voodbuilder::pro.frontend.error'),
                'makeDynamic' => __('voodbuilder::pro.bindings.make_dynamic'),
                'clearDynamic' => __('voodbuilder::pro.bindings.clear_dynamic'),
                'selectParent' => __('voodbuilder::pro.grapesjs.toolbar.select_parent'),
                'drag' => __('voodbuilder::pro.grapesjs.toolbar.drag'),
                'clone' => __('voodbuilder::pro.grapesjs.toolbar.clone'),
                'delete' => __('voodbuilder::pro.grapesjs.toolbar.delete'),
                'modalTitle' => __('voodbuilder::pro.bindings.modal_title'),
                'modalSource' => __('voodbuilder::pro.bindings.modal_source'),
                'modalField' => __('voodbuilder::pro.bindings.modal_field'),
                'modalApply' => __('voodbuilder::pro.bindings.modal_apply'),
                'modalCancel' => __('voodbuilder::pro.bindings.modal_cancel'),
                'selectComponent' => __('voodbuilder::pro.bindings.select_component'),
                'noSources' => __('voodbuilder::pro.bindings.no_sources'),
                'inspectorHint' => __('voodbuilder::pro.bindings.inspector_hint'),
                'currentBinding' => __('voodbuilder::pro.bindings.current_binding'),
                'repeatSource' => __('voodbuilder::pro.bindings.repeat_source'),
                'repeatLimit' => __('voodbuilder::pro.bindings.repeat_limit'),
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
                'tabDynamic' => __('voodbuilder::pro.editor_ui.tab_dynamic'),
                'tabLayers' => __('voodbuilder::pro.editor_ui.tab_layers'),
                'tabConditions' => __('voodbuilder::pro.editor_ui.tab_conditions'),
                'chromeLayoutStructureInspectorNotice' => __('voodbuilder::pro.editor_ui.chrome_layout_structure_inspector_notice'),
                'chromeLayoutContentSlotInspectorNotice' => __('voodbuilder::pro.editor_ui.chrome_layout_content_slot_inspector_notice'),
                'chromeShellManagedInspectorNotice' => __('voodbuilder::pro.editor_ui.chrome_shell_managed_inspector_notice'),
                'chromeShellManagedInspectorNoticeFallback' => __('voodbuilder::pro.editor_ui.chrome_shell_managed_inspector_notice_fallback'),
                'contentNoSettings' => __('voodbuilder::pro.editor_ui.content_no_settings'),
                'exitEditor' => __('voodbuilder::pro.frontend.exit_editor'),
                'deviceDesktop' => __('voodbuilder::pro.editor_ui.device_desktop'),
                'deviceTablet' => __('voodbuilder::pro.editor_ui.device_tablet'),
                'deviceMobile' => __('voodbuilder::pro.editor_ui.device_mobile'),
                'undo' => __('voodbuilder::pro.editor_ui.undo'),
                'redo' => __('voodbuilder::pro.editor_ui.redo'),
                'outline' => __('voodbuilder::pro.editor_ui.outline'),
                'preview' => __('voodbuilder::pro.editor_ui.preview'),
                'themeDark' => __('voodbuilder::pro.editor_ui.theme_dark'),
                'themeLight' => __('voodbuilder::pro.editor_ui.theme_light'),
                'viewPage' => __('voodbuilder::pro.editor_ui.view_page'),
                'zoomIn' => __('voodbuilder::pro.editor_ui.zoom_in'),
                'zoomOut' => __('voodbuilder::pro.editor_ui.zoom_out'),
                'revisions' => __('voodbuilder::pro.editor_ui.revisions'),
                'compilingStyles' => __('voodbuilder::pro.editor_ui.compiling_styles'),
                'loadingEditor' => __('voodbuilder::pro.editor_ui.loading_editor'),
                'toggleLibraryPanel' => __('voodbuilder::pro.editor_ui.toggle_library_panel'),
                'toggleInspectorPanel' => __('voodbuilder::pro.editor_ui.toggle_inspector_panel'),
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
                'componentsTitle' => __('voodbuilder::pro.components.title'),
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
                'layerRename' => __('voodbuilder::pro.grapesjs.layer_rename'),
                'layerRenameHint' => __('voodbuilder::pro.grapesjs.layer_rename_hint'),
                'layerRenamePlaceholder' => __('voodbuilder::pro.grapesjs.layer_rename_placeholder'),
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
        'popupsTitle' => __('voodbuilder::popups.editor.title'),
        'popupsHint' => __('voodbuilder::popups.editor.hint'),
        'popupsCreate' => __('voodbuilder::popups.editor.create'),
        'popupsCreateTitle' => __('voodbuilder::popups.editor.create_title'),
        'popupsEditTitle' => __('voodbuilder::popups.editor.edit_title'),
        'popupsEditRules' => __('voodbuilder::popups.editor.edit_rules'),
        'popupsTest' => __('voodbuilder::popups.editor.test'),
        'popupsOpenEditor' => __('voodbuilder::popups.editor.open_editor'),
        'popupsDelete' => __('voodbuilder::popups.editor.delete'),
        'popupsDeleteConfirm' => __('voodbuilder::popups.editor.delete_confirm'),
        'popupsDeleteError' => __('voodbuilder::popups.editor.delete_error'),
        'popupsSaveError' => __('voodbuilder::popups.editor.save_error'),
        'popupsLoadError' => __('voodbuilder::popups.editor.load_error'),
        'popupsEmpty' => __('voodbuilder::popups.editor.empty'),
        'popupsEnabled' => __('voodbuilder::popups.editor.enabled'),
        'popupsDisabled' => __('voodbuilder::popups.editor.disabled'),
        'popupsFieldName' => __('voodbuilder::popups.fields.name'),
        'popupsFieldEnabled' => __('voodbuilder::popups.fields.enabled'),
        'popupsFieldPriority' => __('voodbuilder::popups.fields.priority'),
        'popupsSectionTrigger' => __('voodbuilder::popups.sections.trigger'),
        'popupsFieldTriggerType' => __('voodbuilder::popups.fields.trigger_type'),
        'popupsTriggerLoad' => __('voodbuilder::popups.triggers.load'),
        'popupsTriggerDelay' => __('voodbuilder::popups.triggers.delay'),
        'popupsTriggerScroll' => __('voodbuilder::popups.triggers.scroll'),
        'popupsTriggerExit' => __('voodbuilder::popups.triggers.exit_intent'),
        'popupsTriggerClick' => __('voodbuilder::popups.triggers.click'),
        'popupsFieldDelay' => __('voodbuilder::popups.fields.delay_seconds'),
        'popupsFieldScroll' => __('voodbuilder::popups.fields.scroll_percent'),
        'popupsFieldClickSelector' => __('voodbuilder::popups.fields.click_selector'),
        'popupsSectionFrequency' => __('voodbuilder::popups.sections.frequency'),
        'popupsFieldFrequency' => __('voodbuilder::popups.fields.frequency_mode'),
        'popupsFrequencyAlways' => __('voodbuilder::popups.frequency.always'),
        'popupsFrequencyOnce' => __('voodbuilder::popups.frequency.once'),
        'popupsFrequencySession' => __('voodbuilder::popups.frequency.session'),
        'popupsFrequencyDays' => __('voodbuilder::popups.frequency.days'),
        'popupsFieldFrequencyDays' => __('voodbuilder::popups.fields.frequency_days'),
        'popupsSectionTargeting' => __('voodbuilder::popups.sections.targeting'),
        'popupsFieldAudience' => __('voodbuilder::popups.fields.logged_in'),
        'popupsTargetingAny' => __('voodbuilder::popups.targeting.any'),
        'popupsTargetingLoggedIn' => __('voodbuilder::popups.targeting.logged_in'),
        'popupsTargetingLoggedOut' => __('voodbuilder::popups.targeting.logged_out'),
        'popupsFieldPagePath' => __('voodbuilder::popups.fields.page_path'),
        'popupsSectionDisplay' => __('voodbuilder::popups.sections.display'),
        'popupsFieldWidth' => __('voodbuilder::popups.fields.width'),
        'popupsWidthSm' => __('voodbuilder::popups.width.sm'),
        'popupsWidthMd' => __('voodbuilder::popups.width.md'),
        'popupsWidthLg' => __('voodbuilder::popups.width.lg'),
        'popupsWidthXl' => __('voodbuilder::popups.width.xl'),
        'popupsFieldOverlay' => __('voodbuilder::popups.fields.overlay'),
        'popupsFieldCloseOverlay' => __('voodbuilder::popups.fields.close_on_overlay'),
        'popupsFieldCloseEscape' => __('voodbuilder::popups.fields.close_on_escape'),
        'popupsEditingBadge' => __('voodbuilder::popups.editor.editing_badge'),
        'chromeLayoutEditingBadge' => __('voodbuilder::chrome_layouts.editor.layout_editing_badge'),
        'chromeLayoutEditingHint' => __('voodbuilder::chrome_layouts.editor.layout_editing_hint'),
        'pageContentPlaceholder' => __('voodbuilder::chrome_layouts.editor.page_content_placeholder'),
        'layoutContentSlotPlaceholder' => __('voodbuilder::chrome_layouts.editor.layout_content_slot_placeholder'),
        'layoutNavZonePlaceholder' => __('voodbuilder::chrome_layouts.editor.layout_nav_zone_placeholder'),
        'layoutFooterZonePlaceholder' => __('voodbuilder::chrome_layouts.editor.layout_footer_zone_placeholder'),
        'popupsPagePathCustom' => __('voodbuilder::popups.page_paths.custom'),
        'dialogOk' => __('voodbuilder::pro.editor_ui.dialog_ok'),
                'dialogCancel' => __('voodbuilder::pro.editor_ui.dialog_cancel'),
                'dialogConfirm' => __('voodbuilder::pro.editor_ui.dialog_confirm'),
                'dialogDelete' => __('voodbuilder::pro.editor_ui.dialog_delete'),
                'dialogAlertTitle' => __('voodbuilder::pro.editor_ui.dialog_alert_title'),
                'dialogConfirmTitle' => __('voodbuilder::pro.editor_ui.dialog_confirm_title'),
                'dialogPromptTitle' => __('voodbuilder::pro.editor_ui.dialog_prompt_title'),
                'sessionExpired' => __('voodbuilder::pro.frontend.session_expired'),
                'forbidden' => __('voodbuilder::pro.frontend.forbidden'),
                'footerShowLogo' => __('voodbuilder::pro.grapesjs.footer_settings.show_logo'),
                'footerShowCopyright' => __('voodbuilder::pro.grapesjs.footer_settings.show_copyright'),
                'footerShowMenu' => __('voodbuilder::pro.grapesjs.footer_settings.show_footer_menu'),
                'footerShowTagline' => __('voodbuilder::pro.grapesjs.footer_settings.show_tagline'),
                'footerShowSocial' => __('voodbuilder::pro.grapesjs.footer_settings.show_social'),
                'footerShowNewsletter' => __('voodbuilder::pro.grapesjs.footer_settings.show_newsletter'),
                'footerColumnsRedistribute' => __('voodbuilder::pro.grapesjs.footer_settings.columns_redistribute'),
                'footerDefaultTagline' => __('voodbuilder::pro.grapesjs.blocks.footer_default_tagline'),
                'logoDesktopLight' => __('voodbuilder::pro.grapesjs.footer_settings.logo_desktop_light'),
                'logoDesktopDark' => __('voodbuilder::pro.grapesjs.footer_settings.logo_desktop_dark'),
                'logoMobileLight' => __('voodbuilder::pro.grapesjs.footer_settings.logo_mobile_light'),
                'logoMobileDark' => __('voodbuilder::pro.grapesjs.footer_settings.logo_mobile_dark'),
                'logoChoose' => __('voodbuilder::pro.grapesjs.footer_settings.logo_choose'),
                'logoClear' => __('voodbuilder::pro.grapesjs.footer_settings.logo_clear'),
                'navSettingsTitle' => __('voodbuilder::pro.grapesjs.nav_settings.title'),
                'navShowLogo' => __('voodbuilder::pro.grapesjs.nav_settings.show_logo'),
                'navShowSiteName' => __('voodbuilder::pro.grapesjs.nav_settings.show_site_name'),
                'navMenuPosition' => __('voodbuilder::pro.grapesjs.nav_settings.menu_position'),
                'navMenuLeft' => __('voodbuilder::pro.grapesjs.nav_settings.menu_left'),
                'navMenuCenter' => __('voodbuilder::pro.grapesjs.nav_settings.menu_center'),
                'navSticky' => __('voodbuilder::pro.grapesjs.nav_settings.sticky'),
                'navStickyInherit' => __('voodbuilder::pro.grapesjs.nav_settings.sticky_inherit'),
                'navStickyOn' => __('voodbuilder::pro.grapesjs.nav_settings.sticky_on'),
                'navStickyOff' => __('voodbuilder::pro.grapesjs.nav_settings.sticky_off'),
                'navShowSearch' => __('voodbuilder::pro.grapesjs.nav_settings.show_search'),
                'navShowNotifications' => __('voodbuilder::pro.grapesjs.nav_settings.show_notifications'),
                'navShowProfile' => __('voodbuilder::pro.grapesjs.nav_settings.show_profile'),
                'newsletterList' => __('voodbuilder::pro.grapesjs.newsletter_settings.list'),
                'newsletterTitle' => __('voodbuilder::pro.grapesjs.newsletter_settings.title'),
                'newsletterHint' => __('voodbuilder::pro.grapesjs.newsletter_settings.hint'),
                'buttonLinkLabel' => __('voodbuilder::pro.grapesjs.button_link.label'),
                'buttonLinkUrl' => __('voodbuilder::pro.grapesjs.button_link.url'),
                'buttonLinkUrlPlaceholder' => __('voodbuilder::pro.grapesjs.button_link.url_placeholder'),
                'buttonLinkTarget' => __('voodbuilder::pro.grapesjs.button_link.target'),
                'buttonLinkSameTab' => __('voodbuilder::pro.grapesjs.button_link.same_tab'),
                'buttonLinkNewTab' => __('voodbuilder::pro.grapesjs.button_link.new_tab'),
        ];
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
        $componentCss = app(GrapesJsComponentCssRenderer::class)->cssForHtml($html);

        if ($componentCss !== '') {
            $css = trim(implode("\n\n", array_filter([$css, $componentCss])));
        }

        if (self::isEditing($page)) {
            $html = app(GrapesJsBindingRenderer::class)->render($html, $page);
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
     * @return array{html: string, css: string, js: string, project: mixed}
     */
    public static function normalizePayload(array $payload, bool $recompilePageCss = false): array
    {
        $html = GrapesJsHtmlSanitizer::sanitize((string) ($payload['html'] ?? ''));
        // Drop data-gjs-* before editor reload/save. Stale props (e.g. corrupted
        // data-gjs-droppable="e=>!x7(e)") and content/ctaLabel fights blank CTAs.
        $html = GrapesJsHtmlSanitizer::stripEditorOnlyAttributes($html);
        $html = GrapesJsHtmlSanitizer::restoreEmptyCtaLabels($html);
        $html = GrapesJsDynamicBlockAttributeNormalizer::normalize($html);
        $html = GrapesJsConditionsAttributeNormalizer::normalize($html);
        $html = GrapesJsCustomCodeSanitizer::sanitize($html);
        $html = GrapesJsCodeBlockNormalizer::normalize($html);
        $css = (string) ($payload['css'] ?? '');
        $js = (string) ($payload['js'] ?? '');
        $project = $payload['project'] ?? null;

        $migratedHtml = GrapesJsImportedTailwindSupport::bakeSvgPaintInHtml(
            GrapesJsImportedTailwindSupport::stripSpuriousSvgBakedPaint(
                app(GrapesJsBindingNormalizer::class)->normalizeHtml(
                    GrapesJsPlaceholderNormalizer::normalizeHtml(
                        VoodbuilderThemeTokenMigrator::migrateHtml($html),
                    ),
                ),
            ),
        );

        $resolvedCss = $recompilePageCss
            ? GrapesJsPastedComponentNormalizer::resolvePublishedPageCssForSave($migratedHtml, $css)
            : GrapesJsPastedComponentNormalizer::resolvePublishedPageCss($migratedHtml, $css);

        return [
            'html' => $migratedHtml,
            'css' => ThemePalette::stripEmbeddedPaletteOverrides($resolvedCss),
            'js' => GrapesJsJsSanitizer::sanitize($js),
            'project' => is_array($project)
                ? VoodbuilderThemeTokenMigrator::migrateProject($project)
                : $project,
        ];
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
        $lists = config('voodbuilder.grapesjs.newsletter_lists', []);

        if ($lists === []) {
            return [
                'default' => __('voodbuilder::pro.grapesjs.newsletter_settings.default_list'),
            ];
        }

        return $lists;
    }
}
