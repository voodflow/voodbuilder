<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Voodflow\Vpress\Filament\Forms\LandingBlockForm;
use Voodflow\Vpress\Filament\Forms\ResolvableLinkForm;
use Voodflow\Vpress\Support\LandingBlockSupport;
use Voodflow\Vpress\Support\RichContentBlockPreview;

class LandingBannerCtaBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_banner_cta';
    }

    public static function getLabel(): string
    {
        return __('vpress::landing.blocks.banner_cta');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return ResolvableLinkForm::configureAction(
            $action->schema([
                TextInput::make('heading')
                    ->label(__('vpress::landing.fields.heading'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('subheading')
                    ->label(__('vpress::landing.fields.subheading'))
                    ->maxLength(500),
                ...LandingBlockForm::backgroundFields('brand'),
                ...LandingBlockForm::textAlignField(),
                TextInput::make('button_label')
                    ->label(__('vpress::landing.fields.primary_button_label'))
                    ->required(),
                ...ResolvableLinkForm::fields('button', [
                    'type_label' => __('vpress::landing.fields.primary_button_url'),
                    'required' => true,
                ]),
                Select::make('button_style')
                    ->label(__('vpress::landing.fields.button_style'))
                    ->options(LandingBlockSupport::buttonStyleOptions())
                    ->default('solid'),
                ...LandingBlockForm::sectionLayoutFields('contained'),
            ]),
            ['button'],
        );
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('vpress::blocks.preview-placeholder', [
            'title' => $config['heading'] ?? static::getLabel(),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('vpress::blocks.landing.banner-cta', ['config' => $config])->render();
    }
}
