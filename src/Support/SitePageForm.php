<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;

final class SitePageForm
{
    public static function grapesJsOnly(): bool
    {
        return (bool) config('voodbuilder.pages.grapesjs_only', false);
    }

    public static function defaultBuilder(): PageBuilder
    {
        $configured = config('voodbuilder.pages.default_builder', PageBuilder::GrapesJs->value);

        return PageBuilder::tryFrom((string) $configured) ?? PageBuilder::GrapesJs;
    }

    public static function allowsSubThemeOverride(): bool
    {
        return (bool) config('voodbuilder.pages.allow_sub_theme_override', true);
    }

    public static function chromeLayoutForPages(): ?ChromeLayout
    {
        return ChromeLayoutResolver::resolveForChannel('pages');
    }

    public static function chromeLayoutManagesShell(): bool
    {
        return self::chromeLayoutForPages() !== null;
    }

    public static function showRichEditor(?SitePage $record): bool
    {
        if (! self::grapesJsOnly()) {
            return true;
        }

        return $record !== null && ! $record->usesGrapesJsBuilder();
    }

    public static function showGrapesJsHint(?SitePage $record): bool
    {
        if ($record === null) {
            return self::grapesJsOnly() || self::defaultBuilder() === PageBuilder::GrapesJs;
        }

        return $record->usesGrapesJsBuilder();
    }
}
