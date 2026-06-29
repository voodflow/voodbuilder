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

class LandingFaqBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_faq';
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::landing.blocks.faq');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('heading')
                ->label(__('voodbuilder::landing.fields.heading'))
                ->maxLength(255),
            Repeater::make('items')
                ->label(__('voodbuilder::landing.fields.faq_items'))
                ->schema([
                    TextInput::make('question')
                        ->label(__('voodbuilder::landing.fields.question'))
                        ->required()
                        ->maxLength(255),
                    Textarea::make('answer')
                        ->label(__('voodbuilder::landing.fields.answer'))
                        ->required()
                        ->rows(3),
                ])
                ->defaultItems(3)
                ->minItems(1)
                ->columnSpanFull(),
            ...LandingBlockForm::sectionLayoutFields('narrow'),
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
        return view('voodbuilder::blocks.landing.faq', ['config' => $config])->render();
    }
}
