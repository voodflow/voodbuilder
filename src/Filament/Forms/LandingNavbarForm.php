<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Voodflow\Vpress\Models\NavigationMenu;
use Voodflow\Vpress\Support\LandingMenuPlacements;
use Voodflow\Vpress\Support\LandingNavbarSupport;

final class LandingNavbarForm
{
    /** @return array<int, mixed> */
    public static function fields(): array
    {
        return [
            Select::make('variant')
                ->label(__('vpress::landing.navbar.variant'))
                ->options(LandingNavbarSupport::variantOptions())
                ->default('a')
                ->native(false),
            LandingBlockForm::imageUpload(
                'logo_path',
                __('vpress::landing.navbar.logo'),
                [
                    'preview_height' => '64',
                    'helper' => __('vpress::landing.navbar.logo_help'),
                ],
            ),
            TextInput::make('brand_name')
                ->label(__('vpress::landing.navbar.brand_name'))
                ->maxLength(120),
            Select::make('menu_slug')
                ->label(__('vpress::landing.navbar.menu_placement'))
                ->options(fn (): array => self::menuPlacementOptions())
                ->default(LandingMenuPlacements::NAV_MENU)
                ->native(false)
                ->helperText(__('vpress::landing.navbar.menu_placement_help')),
            TextInput::make('cta_label')
                ->label(__('vpress::landing.navbar.cta_label'))
                ->maxLength(80),
            TextInput::make('cta_url')
                ->label(__('vpress::landing.navbar.cta_url'))
                ->maxLength(255),
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
                LandingMenuPlacements::NAV_MENU => __('vpress::landing.navbar.default_menu'),
            ];
        }

        return $options;
    }
}
