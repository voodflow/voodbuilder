<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Models\SitePage;

/**
 * Site Page Form.
 */
final class SitePageForm
{
    public static function editorOnly(): bool
    {
        return (bool) config('voodbuilder.pages.editor_only', false);
    }

    public static function defaultBuilder(): PageBuilder
    {
        $configured = config('voodbuilder.pages.default_builder', PageBuilder::Visual->value);

        return PageBuilder::tryFrom((string) $configured) ?? PageBuilder::Visual;
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
        if (! self::editorOnly()) {
            return true;
        }

        return $record !== null && ! $record->usesEditorBuilder();
    }

    public static function showEditorHint(?SitePage $record): bool
    {
        if ($record === null) {
            return self::editorOnly() || self::defaultBuilder() === PageBuilder::Visual;
        }

        return $record->usesEditorBuilder();
    }
}
