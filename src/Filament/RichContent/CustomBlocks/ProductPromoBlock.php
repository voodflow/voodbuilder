<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\CustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Voodflow\Vpress\Support\RichContentBlockPreview;

class ProductPromoBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'product_promo';
    }

    public static function getLabel(): string
    {
        return 'Product promo';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('Call-to-action banner with title, short description, and link.'))
            ->schema([
                TextInput::make('title')
                    ->label(__('Title'))
                    ->required(),
                Textarea::make('text')
                    ->label(__('Description'))
                    ->rows(4)
                    ->required(),
                TextInput::make('button_label')
                    ->label(__('Button label'))
                    ->required(),
                TextInput::make('button_url')
                    ->label(__('Button URL'))
                    ->url()
                    ->required(),
            ]);
    }

    public static function getPreviewLabel(array $config): string
    {
        $title = $config['title'] ?? null;

        return filled($title)
            ? __('Product promo: :title', ['title' => $title])
            : static::getLabel();
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('vpress::blocks.product-promo', ['config' => $config]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('vpress::blocks.product-promo', ['config' => $config])->render();
    }
}
