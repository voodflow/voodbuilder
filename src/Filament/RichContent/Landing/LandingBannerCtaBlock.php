<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Voodflow\Voodbuilder\Filament\Forms\LandingBlockForm;
use Voodflow\Voodbuilder\Filament\Forms\ResolvableLinkForm;
use Voodflow\Voodbuilder\Support\LandingBlockSupport;
use Voodflow\Voodbuilder\Support\RichContentBlockPreview;

/**
 * Rich content / landing block: Landing Banner Cta Block.
 */
class LandingBannerCtaBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_banner_cta';
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::landing.blocks.banner_cta');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return ResolvableLinkForm::configureAction(
            $action->schema([
                TextInput::make('heading')
                    ->label(__('voodbuilder::landing.fields.heading'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('subheading')
                    ->label(__('voodbuilder::landing.fields.subheading'))
                    ->maxLength(500),
                ...LandingBlockForm::backgroundFields('brand'),
                ...LandingBlockForm::textAlignField(),
                TextInput::make('button_label')
                    ->label(__('voodbuilder::landing.fields.primary_button_label'))
                    ->required(),
                ...ResolvableLinkForm::fields('button', [
                    'type_label' => __('voodbuilder::landing.fields.primary_button_url'),
                    'required' => true,
                ]),
                Select::make('button_style')
                    ->label(__('voodbuilder::landing.fields.button_style'))
                    ->options(LandingBlockSupport::buttonStyleOptions())
                    ->default('solid'),
                ...LandingBlockForm::sectionLayoutFields('contained'),
            ]),
            ['button'],
        );
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('voodbuilder::blocks.preview-placeholder', [
            'title' => $config['heading'] ?? static::getLabel(),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('voodbuilder::blocks.landing.banner-cta', ['config' => $config])->render();
    }
}
