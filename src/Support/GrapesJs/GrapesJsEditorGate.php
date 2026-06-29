<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\GrapesJs\Bindings\GrapesJsBindingNormalizer;
use Voodflow\Vpress\Support\GrapesJs\Bindings\GrapesJsBindingRenderer;
use Voodflow\Vpress\Support\PageBuilderAccess;
use Voodflow\Vpress\Support\ThemePalette;

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
        return config('vpress.grapesjs.enabled', true)
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
            'saveUrl' => route('vpress.grapesjs.pages.update', $page),
            'exitUrl' => request()->url(),
            'uploadUrl' => route('vpress.grapesjs.upload'),
            'csrf' => csrf_token(),
            'initial' => self::initialPayload($page),
            'blocksUrl' => route('vpress.grapesjs.blocks'),
            'bindingsUrl' => route('vpress.grapesjs.bindings'),
            'bindingsPreviewUrl' => route('vpress.grapesjs.bindings.preview', $page),
            'blocksRenderUrl' => route('vpress.grapesjs.blocks.render'),
            'formSubmitUrl' => route('vpress.grapesjs.forms.submit', $page),
            'plugins' => config('vpress.grapesjs.plugins', []),
            'canvasStyles' => GrapesJsCanvas::styleUrls(),
            'canvasFrameStyle' => GrapesJsCanvas::frameStyle($subTheme),
            'subTheme' => $subTheme,
            'landingCanvas' => $page->usesLandingCanvas() || $subTheme === 'site',
            'themePaletteCss' => ThemePalette::cssForCanvas($subTheme),
            'builderBrand' => config('vpress.grapesjs.builder.brand', 'VoodBuilder'),
            'labels' => [
                'save' => __('vpress::pro.frontend.save'),
                'saving' => __('vpress::pro.frontend.saving'),
                'saved' => __('vpress::pro.frontend.saved'),
                'error' => __('vpress::pro.frontend.error'),
                'makeDynamic' => __('vpress::pro.bindings.make_dynamic'),
                'clearDynamic' => __('vpress::pro.bindings.clear_dynamic'),
                'modalTitle' => __('vpress::pro.bindings.modal_title'),
                'modalSource' => __('vpress::pro.bindings.modal_source'),
                'modalField' => __('vpress::pro.bindings.modal_field'),
                'modalApply' => __('vpress::pro.bindings.modal_apply'),
                'modalCancel' => __('vpress::pro.bindings.modal_cancel'),
                'selectComponent' => __('vpress::pro.bindings.select_component'),
                'noSources' => __('vpress::pro.bindings.no_sources'),
                'inspectorHint' => __('vpress::pro.bindings.inspector_hint'),
                'currentBinding' => __('vpress::pro.bindings.current_binding'),
                'repeatSource' => __('vpress::pro.bindings.repeat_source'),
                'repeatLimit' => __('vpress::pro.bindings.repeat_limit'),
                'repeatSort' => __('vpress::pro.bindings.repeat_sort'),
                'repeatSortDir' => __('vpress::pro.bindings.repeat_sort_dir'),
                'repeatSortAsc' => __('vpress::pro.bindings.repeat_sort_asc'),
                'repeatSortDesc' => __('vpress::pro.bindings.repeat_sort_desc'),
                'applyRepeat' => __('vpress::pro.bindings.apply_repeat'),
                'clearRepeat' => __('vpress::pro.bindings.clear_repeat'),
                'currentRepeat' => __('vpress::pro.bindings.current_repeat'),
                'repeatList' => __('vpress::pro.bindings.repeat_list'),
                'repeatContainerHint' => __('vpress::pro.bindings.repeat_container_hint'),
                'bindingNeedsLeaf' => __('vpress::pro.bindings.binding_needs_leaf'),
                'repeatContainerNoBind' => __('vpress::pro.bindings.repeat_container_no_bind'),
                'repeatListNotField' => __('vpress::pro.bindings.repeat_list_not_field'),
                'panelBlocks' => __('vpress::pro.editor_ui.panel_blocks'),
                'panelInspector' => __('vpress::pro.editor_ui.panel_inspector'),
                'blockSearch' => __('vpress::pro.editor_ui.block_search'),
                'tabContent' => __('vpress::pro.editor_ui.tab_content'),
                'tabStyle' => __('vpress::pro.editor_ui.tab_style'),
                'tabDynamic' => __('vpress::pro.editor_ui.tab_dynamic'),
                'tabLayers' => __('vpress::pro.editor_ui.tab_layers'),
                'exitEditor' => __('vpress::pro.frontend.exit_editor'),
                'deviceDesktop' => __('vpress::pro.editor_ui.device_desktop'),
                'deviceTablet' => __('vpress::pro.editor_ui.device_tablet'),
                'deviceMobile' => __('vpress::pro.editor_ui.device_mobile'),
                'undo' => __('vpress::pro.editor_ui.undo'),
                'redo' => __('vpress::pro.editor_ui.redo'),
                'outline' => __('vpress::pro.editor_ui.outline'),
                'preview' => __('vpress::pro.editor_ui.preview'),
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
