<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Voodflow\Voodbuilder\Filament\Forms\LandingBlockForm;
use Voodflow\Voodbuilder\Support\RichContentBlockPreview;

/**
 * Rich content / landing block: Landing Steps Block.
 */
class LandingStepsBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_steps';
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::landing.blocks.steps');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('heading')
                ->label(__('voodbuilder::landing.fields.heading'))
                ->maxLength(255),
            Repeater::make('items')
                ->label(__('voodbuilder::landing.fields.steps'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('voodbuilder::landing.fields.title'))
                        ->required()
                        ->maxLength(120),
                    Textarea::make('description')
                        ->label(__('voodbuilder::landing.fields.description'))
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
        return RichContentBlockPreview::render('voodbuilder::blocks.preview-placeholder', [
            'title' => $config['heading'] ?? static::getLabel(),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('voodbuilder::blocks.landing.steps', ['config' => $config])->render();
    }
}
