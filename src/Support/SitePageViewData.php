<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsEditorGate;

final class SitePageViewData
{
    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function make(SitePage $page, array $extra = []): array
    {
        $canEditGrapesJs = GrapesJsEditorGate::canEdit($page);
        $grapesJsEditor = GrapesJsEditorGate::isEditing($page);

        return array_merge([
            'page' => $page,
            'vpressSubTheme' => $page->resolvedSubTheme(),
            'hideSiteFooter' => $page->shouldHideSiteFooter() || $grapesJsEditor,
            'canEditGrapesJs' => $canEditGrapesJs,
            'grapesJsEditor' => $grapesJsEditor,
            'grapesJsConfig' => $grapesJsEditor ? GrapesJsEditorGate::config($page) : null,
        ], $extra);
    }
}
