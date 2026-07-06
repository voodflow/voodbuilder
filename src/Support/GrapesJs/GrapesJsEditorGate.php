<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingNormalizer;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingRenderer;
use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsConditionHooks;
use Voodflow\Voodbuilder\Support\GrapesJs\Conditions\GrapesJsConditionsAttributeNormalizer;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\SiteFooterColumnPlacements;
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
        $subTheme = $page->resolvedSubTheme();

        return [
            'pageId' => $page->getKey(),
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
            'packageVersion' => VoodbuilderPackageVersion::current(),
            'componentCategories' => GrapesJsComponentCategoryNormalizer::categories(),
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
            'themePaletteCss' => ThemePalette::cssForCanvas($subTheme),
            'builderBrand' => config('voodbuilder.grapesjs.builder.brand', 'VoodBuilder'),
            'labels' => [
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
                'tabContent' => __('voodbuilder::pro.editor_ui.tab_content'),
                'tabStyle' => __('voodbuilder::pro.editor_ui.tab_style'),
                'styleClassesTitle' => __('voodbuilder::pro.editor_ui.style_classes_title'),
                'tabDynamic' => __('voodbuilder::pro.editor_ui.tab_dynamic'),
                'tabLayers' => __('voodbuilder::pro.editor_ui.tab_layers'),
                'tabConditions' => __('voodbuilder::pro.editor_ui.tab_conditions'),
                'exitEditor' => __('voodbuilder::pro.frontend.exit_editor'),
                'deviceDesktop' => __('voodbuilder::pro.editor_ui.device_desktop'),
                'deviceTablet' => __('voodbuilder::pro.editor_ui.device_tablet'),
                'deviceMobile' => __('voodbuilder::pro.editor_ui.device_mobile'),
                'undo' => __('voodbuilder::pro.editor_ui.undo'),
                'redo' => __('voodbuilder::pro.editor_ui.redo'),
                'outline' => __('voodbuilder::pro.editor_ui.outline'),
                'preview' => __('voodbuilder::pro.editor_ui.preview'),
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
                'componentsSave' => __('voodbuilder::pro.components.save_as'),
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
                'componentsCompatibilityNoReview' => __('voodbuilder::pro.components.compatibility_no_review'),
                'componentsCompatibilityReadySample' => __('voodbuilder::pro.components.compatibility_ready_sample'),
                'componentsCompatibilityStatusExcellent' => __('voodbuilder::pro.components.compatibility_status_excellent'),
                'componentsCompatibilityStatusGood' => __('voodbuilder::pro.components.compatibility_status_good'),
                'componentsCompatibilityStatusPartial' => __('voodbuilder::pro.components.compatibility_status_partial'),
                'componentsCompatibilityStatusPoor' => __('voodbuilder::pro.components.compatibility_status_poor'),
                'componentsCompileError' => __('voodbuilder::pro.components.compile_error'),
                'componentsCompilePending' => __('voodbuilder::pro.components.compile_pending'),
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
                'newsletterList' => __('voodbuilder::pro.grapesjs.newsletter_settings.list'),
                'newsletterTitle' => __('voodbuilder::pro.grapesjs.newsletter_settings.title'),
                'newsletterHint' => __('voodbuilder::pro.grapesjs.newsletter_settings.hint'),
                'buttonLinkUrl' => __('voodbuilder::pro.grapesjs.button_link.url'),
                'buttonLinkUrlPlaceholder' => __('voodbuilder::pro.grapesjs.button_link.url_placeholder'),
                'buttonLinkTarget' => __('voodbuilder::pro.grapesjs.button_link.target'),
                'buttonLinkSameTab' => __('voodbuilder::pro.grapesjs.button_link.same_tab'),
                'buttonLinkNewTab' => __('voodbuilder::pro.grapesjs.button_link.new_tab'),
            ],
        ];
    }

    /**
     * @return array{html: string, css: string, js: string, project: mixed}
     */
    public static function initialPayload(SitePage $page): array
    {
        $payload = $page->builder_payload ?? [];
        $normalized = self::normalizePayload([
            'html' => $payload['html'] ?? '',
            'css' => $payload['css'] ?? '',
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
    public static function normalizePayload(array $payload): array
    {
        $html = GrapesJsHtmlSanitizer::sanitize((string) ($payload['html'] ?? ''));
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

        return [
            'html' => $migratedHtml,
            'css' => GrapesJsPastedComponentNormalizer::resolvePublishedPageCss($migratedHtml, $css),
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
