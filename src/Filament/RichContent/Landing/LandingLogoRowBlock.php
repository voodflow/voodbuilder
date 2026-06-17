<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Voodflow\Vpress\Filament\Forms\LandingBlockForm;
use Voodflow\Vpress\Support\RichContentBlockPreview;

class LandingLogoRowBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_logo_row';
    }

    public static function getLabel(): string
    {
        return __('vpress::landing.blocks.logo_row');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('heading')
                ->label(__('vpress::landing.fields.heading'))
                ->maxLength(255),
            Toggle::make('grayscale')
                ->label(__('vpress::landing.fields.logo_grayscale'))
                ->default(true),
            Repeater::make('logos')
                ->label(__('vpress::landing.fields.logos'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('vpress::landing.fields.logo_name'))
                        ->maxLength(120),
                    TextInput::make('image_url')
                        ->label(__('vpress::landing.fields.logo_image_url'))
                        ->url()
                        ->required(),
                    TextInput::make('url')
                        ->label(__('vpress::landing.fields.link_url'))
                        ->url(),
                    Toggle::make('open_in_new_tab')
                        ->label(__('vpress::landing.footer.open_in_new_tab')),
                ])
                ->defaultItems(6)
                ->minItems(1)
                ->columnSpanFull(),
            ...LandingBlockForm::sectionLayoutFields('contained'),
        ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('vpress::blocks.preview-placeholder', [
            'title' => $config['heading'] ?? static::getLabel(),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('vpress::blocks.landing.logo-row', ['config' => $config])->render();
    }
}
