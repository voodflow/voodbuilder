<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\Editor\EditorGate;

final class SitePageViewData
{
    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public static function make(SitePage $page, array $extra = []): array
    {
        $canEditEditor = EditorGate::canEdit($page);
        $editorEditor = EditorGate::isEditing($page);

        return array_merge([
            'page' => $page,
            'voodbuilderChromeLayout' => ChromeLayoutManagedContent::chromeLayoutForSitePage($page),
            'voodbuilderSubTheme' => ChromeLayoutManagedContent::sitePageUsesChromeShell($page)
                ? ChromeLayoutSubThemeResolver::forSitePage($page)
                : $page->resolvedSubTheme(),
            'hideSiteNav' => SiteChrome::shouldHideNav($page, $editorEditor),
            'hideSiteFooter' => SiteChrome::shouldHideFooter($page, $editorEditor),
            'canEditEditor' => $canEditEditor,
            'editorEditor' => $editorEditor,
            'editorConfig' => $editorEditor ? EditorGate::config($page) : null,
        ], $extra);
    }
}
