<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\CustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class FeaturesGridBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'features_grid';
    }

    public static function getLabel(): string
    {
        return 'Features grid';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('Grid of feature cards (icon, title, text).'))
            ->schema([
                TextInput::make('title')
                    ->label(__('Section title'))
                    ->default(__('Why Cosmolab'))
                    ->required(),
                Repeater::make('features')
                    ->label(__('Features'))
                    ->schema([
                        TextInput::make('icon')
                            ->label(__('Icon (emoji)'))
                            ->maxLength(4),
                        TextInput::make('title')
                            ->required(),
                        Textarea::make('text')
                            ->rows(2)
                            ->required(),
                    ])
                    ->defaultItems(3)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
            ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('vpress::blocks.features-grid', ['config' => $config])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('vpress::blocks.features-grid', ['config' => $config])->render();
    }
}
