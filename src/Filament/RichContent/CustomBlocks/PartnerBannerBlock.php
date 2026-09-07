<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\RichContent\CustomBlocks;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Voodflow\Voodbuilder\Support\RichContentBlockPreview;

/**
 * Rich content / landing block: Partner Banner Block.
 */
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

    public static function getPreviewLabel(array $config): string
    {
        $title = $config['title'] ?? null;

        return filled($title)
            ? __('Partner banner: :title', ['title' => $title])
            : static::getLabel();
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('voodbuilder::blocks.partner-banner', ['config' => $config]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('voodbuilder::blocks.partner-banner', ['config' => $config])->render();
    }
}
