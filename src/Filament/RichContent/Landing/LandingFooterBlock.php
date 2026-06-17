<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Voodflow\Vpress\Support\LandingFooterSupport;
use Voodflow\Vpress\Support\RichContentBlockPreview;

class LandingFooterBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_footer';
    }

    public static function getLabel(): string
    {
        return __('vpress::landing.blocks.footer');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('logo_url')
                ->label(__('vpress::landing.footer.logo_url'))
                ->url(),
            TextInput::make('brand_name')
                ->label(__('vpress::landing.footer.brand_name'))
                ->maxLength(120),
            TextInput::make('organizer_line_1')
                ->label(__('vpress::landing.footer.organizer_line_1'))
                ->maxLength(255),
            TextInput::make('organizer_line_2')
                ->label(__('vpress::landing.footer.organizer_line_2'))
                ->maxLength(255),
            TextInput::make('organizer_email')
                ->label(__('vpress::landing.footer.organizer_email'))
                ->email(),
            Repeater::make('menu_columns')
                ->label(__('vpress::landing.footer.menu_columns'))
                ->helperText(__('vpress::landing.footer.menu_columns_help'))
                ->schema([
                    TextInput::make('title')
                        ->label(__('vpress::landing.fields.title'))
                        ->required()
                        ->maxLength(80),
                    Repeater::make('links')
                        ->label(__('vpress::landing.footer.menu_links'))
                        ->schema([
                            TextInput::make('label')
                                ->label(__('vpress::landing.fields.link_label'))
                                ->required()
                                ->maxLength(80),
                            TextInput::make('url')
                                ->label(__('vpress::landing.fields.link_url'))
                                ->url()
                                ->required(),
                            Toggle::make('open_in_new_tab')
                                ->label(__('vpress::landing.footer.open_in_new_tab')),
                        ])
                        ->defaultItems(2)
                        ->columnSpanFull(),
                ])
                ->defaultItems(2)
                ->maxItems(4)
                ->columnSpanFull(),
            TextInput::make('copyright_year')
                ->label(__('vpress::landing.footer.copyright_year'))
                ->numeric()
                ->minValue(2000)
                ->maxValue(2100),
            TextInput::make('copyright_brand')
                ->label(__('vpress::landing.footer.copyright_brand'))
                ->maxLength(120),
            TextInput::make('copyright_claim')
                ->label(__('vpress::landing.footer.copyright_claim'))
                ->maxLength(255),
            TextInput::make('copyright_highlight')
                ->label(__('vpress::landing.footer.copyright_highlight'))
                ->helperText(__('vpress::landing.footer.copyright_highlight_help'))
                ->maxLength(120),
            Repeater::make('copyright_links')
                ->label(__('vpress::landing.footer.copyright_links'))
                ->schema([
                    TextInput::make('label')
                        ->label(__('vpress::landing.fields.link_label'))
                        ->required()
                        ->maxLength(80),
                    TextInput::make('url')
                        ->label(__('vpress::landing.fields.link_url'))
                        ->url(),
                    Toggle::make('highlight')
                        ->label(__('vpress::landing.footer.link_highlight')),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('vpress::blocks.preview-placeholder', [
            'title' => $config['brand_name'] ?? static::getLabel(),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('vpress::blocks.landing.footer', [
            'config' => $config,
            'organizer' => LandingFooterSupport::organizerColumn($config),
            'menuColumns' => LandingFooterSupport::menuColumns($config),
            'copyrightSegments' => LandingFooterSupport::copyrightSegments($config),
        ])->render();
    }
}
