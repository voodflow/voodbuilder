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

class LandingStepsBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_steps';
    }

    public static function getLabel(): string
    {
        return __('vpress::landing.blocks.steps');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('heading')
                ->label(__('vpress::landing.fields.heading'))
                ->maxLength(255),
            Repeater::make('items')
                ->label(__('vpress::landing.fields.steps'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('vpress::landing.fields.title'))
                        ->required()
                        ->maxLength(120),
                    Textarea::make('description')
                        ->label(__('vpress::landing.fields.description'))
                        ->rows(3),
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
        return view('vpress::blocks.landing.steps', ['config' => $config])->render();
    }
}
