<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Voodflow\Vpress\Models\SitePage;
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
            'uploadUrl' => route('vpress.grapesjs.upload'),
            'csrf' => csrf_token(),
            'initial' => self::initialPayload($page),
            'blocksUrl' => route('vpress.grapesjs.blocks'),
            'blocksRenderUrl' => route('vpress.grapesjs.blocks.render'),
            'formSubmitUrl' => route('vpress.grapesjs.forms.submit', $page),
            'plugins' => config('vpress.grapesjs.plugins', []),
            'canvasStyles' => GrapesJsCanvas::styleUrls(),
            'canvasFrameStyle' => GrapesJsCanvas::frameStyle($subTheme),
            'subTheme' => $subTheme,
            'landingCanvas' => $page->usesLandingCanvas() || $subTheme === 'site',
            'themePaletteCss' => ThemePalette::cssForCanvas($subTheme),
            'labels' => [
                'save' => __('vpress::pro.frontend.save'),
                'saving' => __('vpress::pro.frontend.saving'),
                'saved' => __('vpress::pro.frontend.saved'),
                'error' => __('vpress::pro.frontend.error'),
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
        $css = (string) ($payload['css'] ?? '');
        $project = $payload['project'] ?? null;

        return [
            'html' => TailblocksThemeTokenMigrator::migrateHtml($html),
            'css' => TailblocksThemeTokenMigrator::migrateCss($css),
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
