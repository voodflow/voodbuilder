<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Models\ChromeLayout;

/**
 * Copy and CTAs for the public home when no published home page exists yet.
 */
final class EmptySiteGuidance
{
    public static function needsChromeLayout(): bool
    {
        if (! ChromeLayoutResolver::enabled()) {
            return false;
        }

        if (! Schema::hasTable((new ChromeLayout)->getTable())) {
            return false;
        }

        return ! ChromeLayout::query()->where('enabled', true)->exists();
    }

    public static function title(): string
    {
        return self::needsChromeLayout()
            ? (string) __('voodbuilder::home.empty.title_needs_layout')
            : (string) __('voodbuilder::home.empty.title_needs_page');
    }

    public static function description(): string
    {
        return self::needsChromeLayout()
            ? (string) __('voodbuilder::home.empty.description_needs_layout')
            : (string) __('voodbuilder::home.empty.description_needs_page');
    }

    public static function ctaLabel(bool $authenticated): string
    {
        if (! $authenticated) {
            return (string) __('voodbuilder::home.empty.cta_login');
        }

        return self::needsChromeLayout()
            ? (string) __('voodbuilder::home.empty.cta_layout')
            : (string) __('voodbuilder::home.empty.cta_page');
    }
}
