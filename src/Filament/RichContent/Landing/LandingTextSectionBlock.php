<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Voodflow\Voodbuilder\Filament\Forms\LandingBlockForm;
use Voodflow\Voodbuilder\Support\LandingBlockSupport;
use Voodflow\Voodbuilder\Support\RichContentBlockPreview;

class LandingTextSectionBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_text';
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::landing.blocks.text');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('eyebrow')
                ->label(__('voodbuilder::landing.fields.eyebrow'))
                ->maxLength(120),
            TextInput::make('heading')
                ->label(__('voodbuilder::landing.fields.heading'))
                ->maxLength(255),
            TextInput::make('intro')
                ->label(__('voodbuilder::landing.fields.intro'))
                ->maxLength(500),
            Textarea::make('body')
                ->label(__('voodbuilder::landing.fields.body'))
                ->rows(6)
                ->columnSpanFull(),
            Select::make('text_align')
                ->label(__('voodbuilder::landing.fields.text_align'))
                ->options(LandingBlockSupport::textAlignOptions())
                ->default('left'),
            Select::make('width')
                ->label(__('voodbuilder::landing.fields.content_width'))
                ->options([
                    'wide' => __('voodbuilder::landing.content_width.wide'),
                    'narrow' => __('voodbuilder::landing.content_width.narrow'),
                ])
                ->default('wide'),
            ...LandingBlockForm::backgroundToneFields('light'),
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
        return view('voodbuilder::blocks.landing.text-section', ['config' => $config])->render();
    }
}
