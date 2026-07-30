<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Voodflow\Voodbuilder\Filament\Forms\LandingBlockForm;
use Voodflow\Voodbuilder\Filament\Forms\ResolvableLinkForm;
use Voodflow\Voodbuilder\Support\RichContentBlockPreview;

/**
 * Rich content / landing block: Landing Hero Block.
 */
class LandingHeroBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_hero';
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::landing.blocks.hero');
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
                TextInput::make('subheading')
                    ->label(__('voodbuilder::landing.fields.subheading'))
                    ->maxLength(500),
                ...LandingBlockForm::backgroundFields(),
                ...LandingBlockForm::textAlignField(),
                ...LandingBlockForm::tallToggle(),
                ...LandingBlockForm::primaryButtonFields(),
                ...LandingBlockForm::secondaryButtonFields(),
                ...LandingBlockForm::sectionLayoutFields('bleed', 'large'),
                ...LandingBlockForm::contentWidthField(),
            ]),
            ['primary_button', 'secondary_button'],
        );
    }

    public static function getPreviewLabel(array $config): string
    {
        $heading = $config['heading'] ?? null;

        return filled($heading)
            ? __('voodbuilder::landing.blocks.hero').': '.$heading
            : static::getLabel();
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('voodbuilder::blocks.landing.hero', ['config' => $config]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('voodbuilder::blocks.landing.hero', ['config' => $config])->render();
    }
}
