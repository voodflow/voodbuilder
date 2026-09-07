<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Voodflow\Voodbuilder\Filament\Forms\LandingBlockForm;
use Voodflow\Voodbuilder\Filament\Forms\ResolvableLinkForm;
use Voodflow\Voodbuilder\Support\LandingBlockContent;
use Voodflow\Voodbuilder\Support\LandingBlockSupport;
use Voodflow\Voodbuilder\Support\RichContentBlockPreview;

/**
 * Rich content / landing block: Landing Split Block.
 */
class LandingSplitBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_split';
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::landing.blocks.split');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return ResolvableLinkForm::configureAction(
            $action->schema([
                TextInput::make('eyebrow')
                    ->label(__('voodbuilder::landing.fields.eyebrow'))
                    ->maxLength(120),
                TextInput::make('heading')
                    ->label(__('voodbuilder::landing.fields.heading'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('body')
                    ->label(__('voodbuilder::landing.fields.body'))
                    ->rows(5)
                    ->columnSpanFull(),
                LandingBlockForm::imageUpload(
                    LandingBlockContent::FIELD_IMAGE,
                    __('voodbuilder::landing.fields.image'),
                    ['helper' => __('voodbuilder::landing.helpers.content_image')],
                ),
                Select::make('image_position')
                    ->label(__('voodbuilder::landing.fields.image_position'))
                    ->options(LandingBlockSupport::imagePositionOptions())
                    ->default('left'),
                TextInput::make('button_label')
                    ->label(__('voodbuilder::landing.fields.primary_button_label')),
                ...ResolvableLinkForm::fields('button', [
                    'type_label' => __('voodbuilder::landing.fields.primary_button_url'),
                ]),
                ...LandingBlockForm::sectionLayoutFields(),
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
        return view('voodbuilder::blocks.landing.split', ['config' => $config])->render();
    }
}
