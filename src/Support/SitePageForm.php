<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Schema;
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

    public static function chromeLayoutsEnabled(): bool
    {
        return (bool) config('voodbuilder.chrome_layouts.enabled', true)
            && Schema::hasTable('voodbuilder_chrome_layouts');
    }

    public static function chromeLayoutForPages(): ?ChromeLayout
    {
        return ChromeLayoutResolver::resolveForChannel('pages');
    }

    public static function chromeLayoutManagesShell(): bool
    {
        if (! self::chromeLayoutsEnabled()) {
            return false;
        }

        return self::chromeLayoutForPages() !== null
            || ChromeLayout::query()->where('enabled', true)->exists();
    }

    /**
     * @return array<string, string>
     */
    public static function chromeLayoutSelectOptions(): array
    {
        $channelDefault = self::chromeLayoutForPages();
        $inheritLabel = $channelDefault !== null
            ? __('voodbuilder::chrome_layouts.page_form.layout_inherit_named', ['name' => $channelDefault->name])
            : __('voodbuilder::chrome_layouts.page_form.layout_inherit_default');

        $options = ['' => $inheritLabel];

        ChromeLayout::query()
            ->where('enabled', true)
            ->orderBy('name')
            ->get()
            ->each(function (ChromeLayout $layout) use (&$options): void {
                $options[$layout->id] = $layout->name;
            });

        return $options;
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
