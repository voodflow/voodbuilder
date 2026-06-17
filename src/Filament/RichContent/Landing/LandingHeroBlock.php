<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Voodflow\Vpress\Filament\Forms\LandingBlockForm;
use Voodflow\Vpress\Support\RichContentBlockPreview;

class LandingHeroBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_hero';
    }

    public static function getLabel(): string
    {
        return __('vpress::landing.blocks.hero');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('eyebrow')
                ->label(__('vpress::landing.fields.eyebrow'))
                ->maxLength(120),
            TextInput::make('heading')
                ->label(__('vpress::landing.fields.heading'))
                ->required()
                ->maxLength(255),
            TextInput::make('subheading')
                ->label(__('vpress::landing.fields.subheading'))
                ->maxLength(500),
            ...LandingBlockForm::backgroundFields(),
            ...LandingBlockForm::textAlignField(),
            ...LandingBlockForm::tallToggle(),
            ...LandingBlockForm::primaryButtonFields(),
            ...LandingBlockForm::secondaryButtonFields(),
            ...LandingBlockForm::sectionLayoutFields('bleed', 'large'),
            ...LandingBlockForm::contentWidthField(),
        ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('vpress::blocks.hero', ['config' => $config]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('vpress::blocks.landing.hero', ['config' => $config])->render();
    }
}
