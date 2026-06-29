<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Voodflow\Voodbuilder\Filament\Forms\LandingBlockForm;
use Voodflow\Voodbuilder\Support\LandingBlockContent;
use Voodflow\Voodbuilder\Support\RichContentBlockPreview;

class LandingLogoRowBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_logo_row';
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::landing.blocks.logo_row');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('heading')
                ->label(__('voodbuilder::landing.fields.heading'))
                ->maxLength(255),
            Toggle::make('grayscale')
                ->label(__('voodbuilder::landing.fields.logo_grayscale'))
                ->default(true),
            Repeater::make('logos')
                ->label(__('voodbuilder::landing.fields.logos'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('voodbuilder::landing.fields.logo_name'))
                        ->maxLength(120),
                    LandingBlockForm::imageUpload(
                        LandingBlockContent::FIELD_LOGO_IMAGE,
                        __('voodbuilder::landing.fields.logo_image'),
                        ['required' => true, 'preview_height' => '48'],
                    ),
                    TextInput::make('url')
                        ->label(__('voodbuilder::landing.fields.link_url'))
                        ->url(),
                    Toggle::make('open_in_new_tab')
                        ->label(__('voodbuilder::landing.footer.open_in_new_tab')),
                ])
                ->defaultItems(6)
                ->minItems(1)
                ->columnSpanFull(),
            ...LandingBlockForm::sectionLayoutFields('contained'),
        ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('voodbuilder::blocks.preview-placeholder', [
            'title' => $config['heading'] ?? static::getLabel(),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('voodbuilder::blocks.landing.logo-row', ['config' => $config])->render();
    }
}
