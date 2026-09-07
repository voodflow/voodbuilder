<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Forms;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Voodflow\Voodbuilder\Support\LandingBlockContent;
use Voodflow\Voodbuilder\Support\LandingBlockMedia;
use Voodflow\Voodbuilder\Support\LandingBlockSupport;

/**
 * Landing Block Form.
 */
final class LandingBlockForm
{
    /** @return array<int, mixed> */
    public static function backgroundFields(string $defaultTone = 'brand'): array
    {
        return [
            Select::make('background_style')
                ->label(__('voodbuilder::landing.fields.background_style'))
                ->options(LandingBlockSupport::backgroundStyleOptions())
                ->default('solid')
                ->live(),
            ...self::backgroundToneFields($defaultTone, fn (Get $get): bool => $get('background_style') !== 'image'),
            self::imageUpload(
                LandingBlockContent::FIELD_BACKGROUND_IMAGE,
                __('voodbuilder::landing.fields.background_image'),
                [
                    'visible' => fn (Get $get): bool => $get('background_style') === 'image',
                    'required' => fn (Get $get): bool => $get('background_style') === 'image',
                    'preview_height' => '120',
                    'helper' => __('voodbuilder::landing.helpers.background_image'),
                ],
            ),
            TextInput::make('overlay_opacity')
                ->label(__('voodbuilder::landing.fields.overlay_opacity'))
                ->numeric()
                ->default(55)
                ->minValue(0)
                ->maxValue(100)
                ->visible(fn (Get $get): bool => $get('background_style') === 'image'),
        ];
    }

    /**
     * @param  (callable(Get): bool)|null  $visible
     * @return array<int, mixed>
     */
    public static function backgroundToneFields(string $defaultTone = 'brand', ?callable $visible = null): array
    {
        return [
            Select::make('background_tone')
                ->label(__('voodbuilder::landing.fields.background_tone'))
                ->options(LandingBlockSupport::backgroundToneOptions())
                ->default($defaultTone)
                ->live()
                ->visible($visible ?? true),
            ColorPicker::make('background_color')
                ->label(__('voodbuilder::landing.fields.background_color'))
                ->helperText(__('voodbuilder::landing.helpers.background_color'))
                ->visible(fn (Get $get): bool => ($visible === null || $visible($get)) && $get('background_tone') === 'custom')
                ->required(fn (Get $get): bool => ($visible === null || $visible($get)) && $get('background_tone') === 'custom'),
        ];
    }

    /** @return array<int, mixed> */
    public static function textAlignField(): array
    {
        return [
            Select::make('text_align')
                ->label(__('voodbuilder::landing.fields.text_align'))
                ->options(LandingBlockSupport::textAlignOptions())
                ->default('center'),
        ];
    }

    /** @return array<int, mixed> */
    public static function sectionLayoutFields(string $defaultWidth = 'contained', string $defaultPadding = 'default'): array
    {
        return [
            Select::make('section_width')
                ->label(__('voodbuilder::landing.fields.section_width'))
                ->options(LandingBlockSupport::sectionWidthOptions())
                ->default($defaultWidth)
                ->helperText(__('voodbuilder::landing.helpers.section_width')),
            Select::make('section_padding')
                ->label(__('voodbuilder::landing.fields.section_padding'))
                ->options(LandingBlockSupport::sectionPaddingOptions())
                ->default($defaultPadding),
        ];
    }

    /** @return array<int, mixed> */
    public static function contentWidthField(string $default = 'wide'): array
    {
        return [
            Select::make('content_width')
                ->label(__('voodbuilder::landing.fields.content_width'))
                ->options([
                    'wide' => __('voodbuilder::landing.content_width.wide'),
                    'narrow' => __('voodbuilder::landing.content_width.narrow'),
                ])
                ->default($default),
        ];
    }

    /** @return array<int, mixed> */
    public static function primaryButtonFields(): array
    {
        return [
            TextInput::make('primary_button_label')
                ->label(__('voodbuilder::landing.fields.primary_button_label')),
            ...ResolvableLinkForm::fields('primary_button', [
                'type_label' => __('voodbuilder::landing.fields.primary_button_url'),
            ]),
            Select::make('primary_button_style')
                ->label(__('voodbuilder::landing.fields.button_style'))
                ->options(LandingBlockSupport::buttonStyleOptions())
                ->default('solid'),
        ];
    }

    /** @return array<int, mixed> */
    public static function secondaryButtonFields(): array
    {
        return [
            TextInput::make('secondary_button_label')
                ->label(__('voodbuilder::landing.fields.secondary_button_label')),
            ...ResolvableLinkForm::fields('secondary_button', [
                'type_label' => __('voodbuilder::landing.fields.secondary_button_url'),
            ]),
        ];
    }

    /** @return array<int, mixed> */
    public static function tallToggle(): array
    {
        return [
            Toggle::make('tall')
                ->label(__('voodbuilder::landing.fields.tall_hero')),
        ];
    }

    /**
     * @param  array{
     *     visible?: (callable(Get): bool)|bool,
     *     required?: (callable(Get): bool)|bool,
     *     helper?: string,
     *     preview_height?: string,
     * }  $options
     */
    public static function imageUpload(string $field, string $label, array $options = []): FileUpload
    {
        $disk = (string) config('voodbuilder.uploads.disk', 'public');

        $upload = FileUpload::make($field)
            ->label($label)
            ->disk($disk)
            ->directory(LandingBlockMedia::uploadDirectory())
            ->visibility('public')
            ->acceptedFileTypes(LandingBlockMedia::imageMimeTypes())
            ->maxSize((int) config('voodbuilder.uploads.max_size', 2048))
            ->imagePreviewHeight($options['preview_height'] ?? '150');

        if (isset($options['helper'])) {
            $upload->helperText($options['helper']);
        }

        if (array_key_exists('visible', $options)) {
            $upload->visible($options['visible']);
        }

        if (array_key_exists('required', $options)) {
            $upload->required($options['required']);
        }

        return $upload;
    }
}
