<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Site Page Form helpers.
 *
 * Pages always use the Visual Editor. Rich editor remains only for legacy rows.
 */
final class SitePageForm
{
    public static function editorOnly(): bool
    {
        return true;
    }

    public static function defaultBuilder(): PageBuilder
    {
        return PageBuilder::Visual;
    }

    public static function showRichEditor(?SitePage $record): bool
    {
        return $record !== null && ! $record->usesEditorBuilder();
    }

    public static function showEditorHint(?SitePage $record): bool
    {
        if ($record === null) {
            return true;
        }

        return $record->usesEditorBuilder();
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
}
