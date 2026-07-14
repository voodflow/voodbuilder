<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsEditorGate;

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
            'voodbuilderSubTheme' => ChromeLayoutManagedContent::sitePageUsesChromeShell($page)
                ? ChromeLayoutSubThemeResolver::forSitePage($page)
                : $page->resolvedSubTheme(),
            'hideSiteNav' => SiteChrome::shouldHideNav($page, $grapesJsEditor),
            'hideSiteFooter' => SiteChrome::shouldHideFooter($page, $grapesJsEditor),
            'canEditGrapesJs' => $canEditGrapesJs,
            'grapesJsEditor' => $grapesJsEditor,
            'grapesJsConfig' => $grapesJsEditor ? GrapesJsEditorGate::config($page) : null,
        ], $extra);
    }
}
