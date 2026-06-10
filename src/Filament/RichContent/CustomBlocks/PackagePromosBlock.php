<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\CustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Voodflow\Vpress\Support\RichContentBlockPreview;

class PackagePromosBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'package_promos';
    }

    public static function getLabel(): string
    {
        return 'Package promos';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('Grid of product cards with title, description, and CTA button.'))
            ->schema([
                TextInput::make('title')
                    ->label(__('Section title'))
                    ->required(),
                Repeater::make('packages')
                    ->label(__('Packages'))
                    ->schema([
                        TextInput::make('title')
                            ->required(),
                        Textarea::make('text')
                            ->rows(3)
                            ->required(),
                        TextInput::make('button_label')
                            ->label(__('Button label'))
                            ->required(),
                        TextInput::make('button_url')
                            ->label(__('Button URL'))
                            ->url()
                            ->required(),
                    ])
                    ->defaultItems(3)
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
            ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        $title = $config['title'] ?? static::getLabel();
        $count = count($config['packages'] ?? []);

        return __(':title — :count packages', ['title' => $title, 'count' => $count]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('vpress::blocks.package-promos', ['config' => $config]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('vpress::blocks.package-promos', ['config' => $config])->render();
    }
}
