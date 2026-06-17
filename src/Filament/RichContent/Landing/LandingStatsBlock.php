<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Voodflow\Vpress\Filament\Forms\LandingBlockForm;
use Voodflow\Vpress\Support\RichContentBlockPreview;

class LandingStatsBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_stats';
    }

    public static function getLabel(): string
    {
        return __('vpress::landing.blocks.stats');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('heading')
                ->label(__('vpress::landing.fields.heading'))
                ->maxLength(255),
            Repeater::make('items')
                ->label(__('vpress::landing.fields.stats'))
                ->schema([
                    TextInput::make('value')
                        ->label(__('vpress::landing.fields.stat_value'))
                        ->required()
                        ->maxLength(32),
                    TextInput::make('label')
                        ->label(__('vpress::landing.fields.stat_label'))
                        ->required()
                        ->maxLength(120),
                    Textarea::make('description')
                        ->label(__('vpress::landing.fields.description'))
                        ->rows(2),
                ])
                ->defaultItems(4)
                ->minItems(1)
                ->maxItems(4)
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
        return view('vpress::blocks.landing.stats', ['config' => $config])->render();
    }
}
