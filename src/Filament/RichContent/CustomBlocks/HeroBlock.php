<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\CustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class HeroBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'hero';
    }

    public static function getLabel(): string
    {
        return 'Hero';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('Main hero section with headline and call-to-action buttons.'))
            ->schema([
                TextInput::make('name')
                    ->label(__('Brand name'))
                    ->default(config('app.name'))
                    ->required(),
                TextInput::make('headline')
                    ->label(__('Headline'))
                    ->required(),
                Textarea::make('tagline')
                    ->label(__('Tagline'))
                    ->rows(3),
                TextInput::make('primary_label')
                    ->label(__('Primary button'))
                    ->default(__('Get started')),
                TextInput::make('primary_url')
                    ->label(__('Primary URL'))
                    ->default(config('cosmolab.docs_url')),
                TextInput::make('secondary_label')
                    ->label(__('Secondary button'))
                    ->default(__('Buy the kit')),
                TextInput::make('secondary_url')
                    ->label(__('Secondary URL'))
                    ->default(config('cosmolab.shop_url')),
            ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('vpress::blocks.hero', ['config' => $config])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('vpress::blocks.hero', ['config' => $config])->render();
    }
}
