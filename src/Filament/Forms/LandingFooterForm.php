<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Voodflow\Vevents\Models\Organizer;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\LandingFooterSupport;
use Voodflow\Voodbuilder\Support\LandingMenuPlacements;

/**
 * Landing Footer Form.
 */
final class LandingFooterForm
{
    /** @return array<int, mixed> */
    public static function fields(): array
    {
        return [
            Select::make('variant')
                ->label(__('voodbuilder::landing.footer.variant'))
                ->options(LandingFooterSupport::layoutVariantOptions())
                ->default('a')
                ->native(false),
            LandingBlockForm::imageUpload(
                'logo_path',
                __('voodbuilder::landing.footer.logo'),
                [
                    'preview_height' => '64',
                    'helper' => __('voodbuilder::landing.footer.logo_help'),
                ],
            ),
            TextInput::make('brand_name')
                ->label(__('voodbuilder::landing.footer.brand_name'))
                ->helperText(__('voodbuilder::landing.footer.brand_name_help'))
                ->maxLength(120),
            TextInput::make('brand_tagline')
                ->label(__('voodbuilder::landing.footer.brand_tagline'))
                ->maxLength(255),
            ...self::organizerFields(),
            ...self::columnTitleFields(),
            Select::make('menu_slug')
                ->label(__('voodbuilder::landing.footer.legacy_menu_placement'))
                ->options(fn (): array => self::menuPlacementOptions())
                ->default('landing_footer')
                ->native(false)
                ->helperText(__('voodbuilder::landing.footer.legacy_menu_placement_help')),
            TextInput::make('copyright_year')
                ->label(__('voodbuilder::landing.footer.copyright_year'))
                ->numeric()
                ->minValue(2000)
                ->maxValue(2100),
            TextInput::make('copyright_brand')
                ->label(__('voodbuilder::landing.footer.copyright_brand'))
                ->maxLength(120),
            TextInput::make('copyright_claim')
                ->label(__('voodbuilder::landing.footer.copyright_claim'))
                ->maxLength(255),
            TextInput::make('copyright_highlight')
                ->label(__('voodbuilder::landing.footer.copyright_highlight'))
                ->helperText(__('voodbuilder::landing.footer.copyright_highlight_help'))
                ->maxLength(120),
        ];
    }

    /** @return array<int, mixed> */
    protected static function columnTitleFields(): array
    {
        $fields = [];

        for ($index = 1; $index <= LandingMenuPlacements::FOOTER_COLUMN_COUNT; $index++) {
            $fields[] = TextInput::make("column_{$index}_title")
                ->label(__('voodbuilder::landing.footer.column_title', ['number' => $index]))
                ->helperText(__('voodbuilder::landing.footer.column_title_help', [
                    'placement' => LandingMenuPlacements::footerColumnSlug($index),
                ]))
                ->maxLength(120);
        }

        return $fields;
    }

    /** @return array<int, mixed> */
    protected static function organizerFields(): array
    {
        if (class_exists(Organizer::class)) {
            return [
                Select::make('organizer_id')
                    ->label(__('voodbuilder::landing.footer.organizer'))
                    ->options(fn (): array => Organizer::query()
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (Organizer $organizer): array => [
                            $organizer->getKey() => $organizer->getTranslation('name', app()->getLocale()) ?? (string) $organizer->getKey(),
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->helperText(__('voodbuilder::landing.footer.organizer_help')),
                TextInput::make('organizer_legal')
                    ->label(__('voodbuilder::landing.footer.organizer_legal'))
                    ->maxLength(255),
            ];
        }

        return [
            TextInput::make('organizer_line_1')
                ->label(__('voodbuilder::landing.footer.organizer_line_1'))
                ->maxLength(255),
            TextInput::make('organizer_line_2')
                ->label(__('voodbuilder::landing.footer.organizer_line_2'))
                ->maxLength(255),
            TextInput::make('organizer_email')
                ->label(__('voodbuilder::landing.footer.organizer_email'))
                ->email(),
        ];
    }

    /** @return array<string, string> */
    protected static function menuPlacementOptions(): array
    {
        $options = NavigationMenu::query()
            ->orderBy('name')
            ->pluck('name', 'slug')
            ->all();

        if ($options === []) {
            return [
                'landing_footer' => __('voodbuilder::landing.footer.default_menu'),
            ];
        }

        return $options;
    }
}
