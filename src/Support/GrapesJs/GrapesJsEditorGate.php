<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\AdminAccess;

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

        return config('vpress.grapesjs.enabled', true)
            && $page->usesGrapesJsBuilder()
            && AdminAccess::userCanAccessPanel();
    }

    /**
     * @return array<string, mixed>
     */
    public static function config(SitePage $page): array
    {
        return [
            'pageId' => $page->getKey(),
            'saveUrl' => route('vpress.grapesjs.pages.update', $page),
            'uploadUrl' => route('vpress.grapesjs.upload'),
            'csrf' => csrf_token(),
            'initial' => $page->builder_payload ?? [
                'html' => '',
                'css' => '',
                'project' => null,
            ],
            'blocks' => app(GrapesJsBlockRegistry::class)->toEditorBlocks(),
            'canvasStyles' => GrapesJsCanvas::styleUrls(),
            'labels' => [
                'save' => __('vpress::pro.frontend.save'),
                'saving' => __('vpress::pro.frontend.saving'),
                'saved' => __('vpress::pro.frontend.saved'),
                'error' => __('vpress::pro.frontend.error'),
            ],
        ];
    }
}
