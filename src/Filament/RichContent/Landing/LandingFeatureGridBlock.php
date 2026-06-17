<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Voodflow\Vpress\Filament\Forms\LandingBlockForm;
use Voodflow\Vpress\Support\RichContentBlockPreview;

class LandingFeatureGridBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_feature_grid';
    }

    public static function getLabel(): string
    {
        return __('vpress::landing.blocks.feature_grid');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('heading')
                ->label(__('vpress::landing.fields.heading'))
                ->maxLength(255),
            TextInput::make('subheading')
                ->label(__('vpress::landing.fields.subheading'))
                ->maxLength(500),
            Select::make('columns')
                ->label(__('vpress::landing.fields.columns'))
                ->options(['2' => '2', '3' => '3', '4' => '4'])
                ->default('3'),
            Repeater::make('items')
                ->label(__('vpress::landing.fields.features'))
                ->schema([
                    TextInput::make('icon')
                        ->label(__('vpress::landing.fields.icon'))
                        ->helperText(__('vpress::landing.helpers.icon'))
                        ->maxLength(8),
                    TextInput::make('title')
                        ->label(__('vpress::landing.fields.title'))
                        ->required()
                        ->maxLength(120),
                    Textarea::make('description')
                        ->label(__('vpress::landing.fields.description'))
                        ->rows(3),
                    TextInput::make('link_label')
                        ->label(__('vpress::landing.fields.link_label'))
                        ->maxLength(80),
                    TextInput::make('link_url')
                        ->label(__('vpress::landing.fields.link_url'))
                        ->url(),
                ])
                ->defaultItems(3)
                ->minItems(1)
                ->columnSpanFull(),
            ...LandingBlockForm::sectionLayoutFields(),
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
        return view('vpress::blocks.landing.feature-grid', ['config' => $config])->render();
    }
}
