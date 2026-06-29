<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingNormalizer;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\GrapesJsBindingRenderer;
use Voodflow\Voodbuilder\Support\PageBuilderAccess;
use Voodflow\Voodbuilder\Support\ThemePalette;

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
            'saveUrl' => route('voodbuilder.grapesjs.pages.update', $page),
            'exitUrl' => request()->url(),
            'uploadUrl' => route('voodbuilder.grapesjs.upload'),
            'csrf' => csrf_token(),
            'initial' => self::initialPayload($page),
            'blocksUrl' => route('voodbuilder.grapesjs.blocks'),
            'bindingsUrl' => route('voodbuilder.grapesjs.bindings'),
            'bindingsPreviewUrl' => route('voodbuilder.grapesjs.bindings.preview', $page),
            'blocksRenderUrl' => route('voodbuilder.grapesjs.blocks.render'),
            'formSubmitUrl' => route('voodbuilder.grapesjs.forms.submit', $page),
            'plugins' => config('voodbuilder.grapesjs.plugins', []),
            'canvasStyles' => GrapesJsCanvas::styleUrls(),
            'canvasFrameStyle' => GrapesJsCanvas::frameStyle($subTheme),
            'subTheme' => $subTheme,
            'landingCanvas' => $page->usesLandingCanvas() || $subTheme === 'site',
            'themePaletteCss' => ThemePalette::cssForCanvas($subTheme),
            'builderBrand' => config('voodbuilder.grapesjs.builder.brand', 'VoodBuilder'),
            'labels' => [
                'save' => __('voodbuilder::pro.frontend.save'),
                'saving' => __('voodbuilder::pro.frontend.saving'),
                'saved' => __('voodbuilder::pro.frontend.saved'),
                'error' => __('voodbuilder::pro.frontend.error'),
                'makeDynamic' => __('voodbuilder::pro.bindings.make_dynamic'),
                'clearDynamic' => __('voodbuilder::pro.bindings.clear_dynamic'),
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
                'repeatContainerNoBind' => __('voodbuilder::pro.bindings.repeat_container_no_bind'),
                'repeatListNotField' => __('voodbuilder::pro.bindings.repeat_list_not_field'),
                'panelBlocks' => __('voodbuilder::pro.editor_ui.panel_blocks'),
                'panelInspector' => __('voodbuilder::pro.editor_ui.panel_inspector'),
                'blockSearch' => __('voodbuilder::pro.editor_ui.block_search'),
                'tabContent' => __('voodbuilder::pro.editor_ui.tab_content'),
                'tabStyle' => __('voodbuilder::pro.editor_ui.tab_style'),
                'tabDynamic' => __('voodbuilder::pro.editor_ui.tab_dynamic'),
                'tabLayers' => __('voodbuilder::pro.editor_ui.tab_layers'),
                'exitEditor' => __('voodbuilder::pro.frontend.exit_editor'),
                'deviceDesktop' => __('voodbuilder::pro.editor_ui.device_desktop'),
                'deviceTablet' => __('voodbuilder::pro.editor_ui.device_tablet'),
                'deviceMobile' => __('voodbuilder::pro.editor_ui.device_mobile'),
                'undo' => __('voodbuilder::pro.editor_ui.undo'),
                'redo' => __('voodbuilder::pro.editor_ui.redo'),
                'outline' => __('voodbuilder::pro.editor_ui.outline'),
                'preview' => __('voodbuilder::pro.editor_ui.preview'),
            ],
        ];
    }

    /**
     * @return array{html: string, css: string, project: mixed}
     */
    public static function initialPayload(SitePage $page): array
    {
        $payload = $page->builder_payload ?? [];
        $normalized = self::normalizePayload([
            'html' => $payload['html'] ?? '',
            'css' => $payload['css'] ?? '',
            'project' => $payload['project'] ?? null,
        ]);

        $html = $normalized['html'];
        $css = $normalized['css'];

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
     * @param  array{html?: string, css?: string, project?: mixed}  $payload
     * @return array{html: string, css: string, project: mixed}
     */
    public static function normalizePayload(array $payload): array
    {
        $html = GrapesJsHtmlSanitizer::sanitize((string) ($payload['html'] ?? ''));
        $html = GrapesJsDynamicBlockAttributeNormalizer::normalize($html);
        $html = GrapesJsCustomCodeSanitizer::sanitize($html);
        $html = GrapesJsCodeBlockNormalizer::normalize($html);
        $css = (string) ($payload['css'] ?? '');
        $project = $payload['project'] ?? null;

        return [
            'html' => app(GrapesJsBindingNormalizer::class)->normalizeHtml(
                GrapesJsPlaceholderNormalizer::normalizeHtml(
                    TailblocksThemeTokenMigrator::migrateHtml($html),
                ),
            ),
            'css' => GrapesJsCssSanitizer::sanitize(
                TailblocksThemeTokenMigrator::migrateCss($css),
            ),
            'project' => is_array($project)
                ? TailblocksThemeTokenMigrator::migrateProject($project)
                : $project,
        ];
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
}
