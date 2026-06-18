<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Voodflow\Vpress\Models\NavigationMenu;

final class LandingFooterForm
{
    /** @return array<int, mixed> */
    public static function fields(): array
    {
        return [
            LandingBlockForm::imageUpload(
                'logo_path',
                __('vpress::landing.footer.logo'),
                [
                    'preview_height' => '64',
                    'helper' => __('vpress::landing.footer.logo_help'),
                ],
            ),
            TextInput::make('brand_name')
                ->label(__('vpress::landing.footer.brand_name'))
                ->helperText(__('vpress::landing.footer.brand_name_help'))
                ->maxLength(120),
            ...static::organizerFields(),
            Select::make('menu_slug')
                ->label(__('vpress::landing.footer.menu_placement'))
                ->options(fn (): array => static::menuPlacementOptions())
                ->default('landing_footer')
                ->native(false)
                ->helperText(__('vpress::landing.footer.menu_placement_help')),
            TextInput::make('copyright_year')
                ->label(__('vpress::landing.footer.copyright_year'))
                ->numeric()
                ->minValue(2000)
                ->maxValue(2100),
            TextInput::make('copyright_brand')
                ->label(__('vpress::landing.footer.copyright_brand'))
                ->maxLength(120),
            TextInput::make('copyright_claim')
                ->label(__('vpress::landing.footer.copyright_claim'))
                ->maxLength(255),
            TextInput::make('copyright_highlight')
                ->label(__('vpress::landing.footer.copyright_highlight'))
                ->helperText(__('vpress::landing.footer.copyright_highlight_help'))
                ->maxLength(120),
        ];
    }

    /** @return array<int, mixed> */
    protected static function organizerFields(): array
    {
        if (class_exists(\Voodflow\Vevents\Models\Organizer::class)) {
            return [
                Select::make('organizer_id')
                    ->label(__('vpress::landing.footer.organizer'))
                    ->options(fn (): array => \Voodflow\Vevents\Models\Organizer::query()
                        ->orderBy('name')
                        ->get()
                        ->mapWithKeys(fn (\Voodflow\Vevents\Models\Organizer $organizer): array => [
                            $organizer->getKey() => $organizer->getTranslation('name', app()->getLocale()) ?? (string) $organizer->getKey(),
                        ])
                        ->all())
                    ->searchable()
                    ->preload()
                    ->helperText(__('vpress::landing.footer.organizer_help')),
                TextInput::make('organizer_legal')
                    ->label(__('vpress::landing.footer.organizer_legal'))
                    ->maxLength(255),
            ];
        }

        return [
            TextInput::make('organizer_line_1')
                ->label(__('vpress::landing.footer.organizer_line_1'))
                ->maxLength(255),
            TextInput::make('organizer_line_2')
                ->label(__('vpress::landing.footer.organizer_line_2'))
                ->maxLength(255),
            TextInput::make('organizer_email')
                ->label(__('vpress::landing.footer.organizer_email'))
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
                'landing_footer' => __('vpress::landing.footer.default_menu'),
            ];
        }

        return $options;
    }
}
