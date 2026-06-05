<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\CustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

class PartnerBannerBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'partner_banner';
    }

    public static function getLabel(): string
    {
        return 'Partner banner';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalDescription(__('Callout for the manufacturing partner.'))
            ->schema([
                TextInput::make('title')
                    ->default(__('Turn your Cosmolab project into a product')),
                Textarea::make('text')
                    ->rows(4)
                    ->default(__('Built something with Cosmolab and want to bring it to market? Our partner :partner supports you from industrial design through manufacturing and certification.', [
                        'partner' => config('cosmolab.partner_name'),
                    ])),
                TextInput::make('email')
                    ->default(config('cosmolab.partner_email')),
            ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return view('vpress::blocks.partner-banner', ['config' => $config])->render();
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('vpress::blocks.partner-banner', ['config' => $config])->render();
    }
}
