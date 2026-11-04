<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Site footer: large brand, two menu columns and two static columns
 * (contacts + details) that are not navigation menus.
 */
final class SiteFooterColumnsContactBlock extends AbstractSiteFooterVariantBlock
{
    public static function variant(): string
    {
        return 'columns_contact';
    }

    public static function defaultColumns(): int
    {
        return 2;
    }

    public static function defaultConfig(): array
    {
        return SiteFooterConfig::normalize([
            'columns' => self::defaultColumns(),
            'show_footer_col_1' => true,
            'show_footer_col_2' => true,
            'show_footer_col_3' => false,
            'show_footer_col_4' => false,
            'show_tagline' => false,
            'show_copyright' => false,
            'show_social' => false,
            'logo_size' => 'xl',
            'logo_size_mobile' => 'xl',
            'contact_title' => __('voodbuilder::pro.editor.footer_settings.contact_default_title'),
            'contact_lines' => (array) __('voodbuilder::pro.editor.footer_settings.contact_default_lines'),
            'details_title' => __('voodbuilder::pro.editor.footer_settings.details_default_title'),
            'details_lines' => (array) __('voodbuilder::pro.editor.footer_settings.details_default_lines'),
        ]);
    }
}
