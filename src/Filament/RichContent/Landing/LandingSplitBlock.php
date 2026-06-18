<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Voodflow\Vpress\Filament\Forms\LandingBlockForm;
use Voodflow\Vpress\Filament\Forms\ResolvableLinkForm;
use Voodflow\Vpress\Support\LandingBlockContent;
use Voodflow\Vpress\Support\LandingBlockSupport;
use Voodflow\Vpress\Support\RichContentBlockPreview;

class LandingSplitBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_split';
    }

    public static function getLabel(): string
    {
        return __('vpress::landing.blocks.split');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return ResolvableLinkForm::configureAction(
            $action->schema([
                TextInput::make('eyebrow')
                    ->label(__('vpress::landing.fields.eyebrow'))
                    ->maxLength(120),
                TextInput::make('heading')
                    ->label(__('vpress::landing.fields.heading'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('body')
                    ->label(__('vpress::landing.fields.body'))
                    ->rows(5)
                    ->columnSpanFull(),
                LandingBlockForm::imageUpload(
                    LandingBlockContent::FIELD_IMAGE,
                    __('vpress::landing.fields.image'),
                    ['helper' => __('vpress::landing.helpers.content_image')],
                ),
                Select::make('image_position')
                    ->label(__('vpress::landing.fields.image_position'))
                    ->options(LandingBlockSupport::imagePositionOptions())
                    ->default('left'),
                TextInput::make('button_label')
                    ->label(__('vpress::landing.fields.primary_button_label')),
                ...ResolvableLinkForm::fields('button', [
                    'type_label' => __('vpress::landing.fields.primary_button_url'),
                ]),
                ...LandingBlockForm::sectionLayoutFields(),
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
        return view('vpress::blocks.landing.split', ['config' => $config])->render();
    }
}
