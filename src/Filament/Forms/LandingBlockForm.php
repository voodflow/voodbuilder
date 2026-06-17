<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Voodflow\Vpress\Support\LandingBlockSupport;

final class LandingBlockForm
{
    /** @return array<int, mixed> */
    public static function backgroundFields(string $defaultTone = 'brand'): array
    {
        return [
            Select::make('background_style')
                ->label(__('vpress::landing.fields.background_style'))
                ->options(LandingBlockSupport::backgroundStyleOptions())
                ->default('solid')
                ->live(),
            Select::make('background_tone')
                ->label(__('vpress::landing.fields.background_tone'))
                ->options(LandingBlockSupport::backgroundToneOptions())
                ->default($defaultTone)
                ->visible(fn (Get $get): bool => $get('background_style') !== 'image'),
            TextInput::make('background_image_url')
                ->label(__('vpress::landing.fields.background_image_url'))
                ->url()
                ->visible(fn (Get $get): bool => $get('background_style') === 'image'),
            TextInput::make('overlay_opacity')
                ->label(__('vpress::landing.fields.overlay_opacity'))
                ->numeric()
                ->default(55)
                ->minValue(0)
                ->maxValue(100)
                ->visible(fn (Get $get): bool => $get('background_style') === 'image'),
        ];
    }

    /** @return array<int, mixed> */
    public static function textAlignField(): array
    {
        return [
            Select::make('text_align')
                ->label(__('vpress::landing.fields.text_align'))
                ->options(LandingBlockSupport::textAlignOptions())
                ->default('center'),
        ];
    }

    /** @return array<int, mixed> */
    public static function sectionLayoutFields(string $defaultWidth = 'contained', string $defaultPadding = 'default'): array
    {
        return [
            Select::make('section_width')
                ->label(__('vpress::landing.fields.section_width'))
                ->options(LandingBlockSupport::sectionWidthOptions())
                ->default($defaultWidth)
                ->helperText(__('vpress::landing.helpers.section_width')),
            Select::make('section_padding')
                ->label(__('vpress::landing.fields.section_padding'))
                ->options(LandingBlockSupport::sectionPaddingOptions())
                ->default($defaultPadding),
        ];
    }

    /** @return array<int, mixed> */
    public static function contentWidthField(string $default = 'wide'): array
    {
        return [
            Select::make('content_width')
                ->label(__('vpress::landing.fields.content_width'))
                ->options([
                    'wide' => __('vpress::landing.content_width.wide'),
                    'narrow' => __('vpress::landing.content_width.narrow'),
                ])
                ->default($default),
        ];
    }

    /** @return array<int, mixed> */
    public static function primaryButtonFields(): array
    {
        return [
            TextInput::make('primary_button_label')
                ->label(__('vpress::landing.fields.primary_button_label')),
            TextInput::make('primary_button_url')
                ->label(__('vpress::landing.fields.primary_button_url'))
                ->url(),
            Select::make('primary_button_style')
                ->label(__('vpress::landing.fields.button_style'))
                ->options(LandingBlockSupport::buttonStyleOptions())
                ->default('solid'),
        ];
    }

    /** @return array<int, mixed> */
    public static function secondaryButtonFields(): array
    {
        return [
            TextInput::make('secondary_button_label')
                ->label(__('vpress::landing.fields.secondary_button_label')),
            TextInput::make('secondary_button_url')
                ->label(__('vpress::landing.fields.secondary_button_url'))
                ->url(),
        ];
    }

    /** @return array<int, mixed> */
    public static function tallToggle(): array
    {
        return [
            Toggle::make('tall')
                ->label(__('vpress::landing.fields.tall_hero')),
        ];
    }
}
