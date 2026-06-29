<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\LandingMenuPlacements;
use Voodflow\Voodbuilder\Support\LandingNavbarSupport;

final class LandingNavbarForm
{
    /** @return array<int, mixed> */
    public static function fields(): array
    {
        return [
            Select::make('variant')
                ->label(__('voodbuilder::landing.navbar.variant'))
                ->options(LandingNavbarSupport::variantOptions())
                ->default('a')
                ->native(false),
            LandingBlockForm::imageUpload(
                'logo_path',
                __('voodbuilder::landing.navbar.logo'),
                [
                    'preview_height' => '64',
                    'helper' => __('voodbuilder::landing.navbar.logo_help'),
                ],
            ),
            TextInput::make('brand_name')
                ->label(__('voodbuilder::landing.navbar.brand_name'))
                ->maxLength(120),
            Select::make('menu_slug')
                ->label(__('voodbuilder::landing.navbar.menu_placement'))
                ->options(fn (): array => self::menuPlacementOptions())
                ->default(LandingMenuPlacements::NAV_MENU)
                ->native(false)
                ->helperText(__('voodbuilder::landing.navbar.menu_placement_help')),
            TextInput::make('cta_label')
                ->label(__('voodbuilder::landing.navbar.cta_label'))
                ->maxLength(80),
            TextInput::make('cta_url')
                ->label(__('voodbuilder::landing.navbar.cta_url'))
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
                LandingMenuPlacements::NAV_MENU => __('voodbuilder::landing.navbar.default_menu'),
            ];
        }

        return $options;
    }
}
