<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Voodflow\Vpress\Filament\Forms\LandingBlockForm;
use Voodflow\Vpress\Support\RichContentBlockPreview;
use Voodflow\Vpress\Support\SocialShareSupport;

class LandingSocialShareBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_social_share';
    }

    public static function getLabel(): string
    {
        return __('vpress::landing.blocks.social_share');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('heading')
                ->label(__('vpress::landing.fields.heading'))
                ->default(__('vpress::landing.social.default_heading'))
                ->maxLength(120),
            TextInput::make('share_url')
                ->label(__('vpress::landing.social.share_url'))
                ->helperText(__('vpress::landing.social.share_url_help'))
                ->url(),
            TextInput::make('share_title')
                ->label(__('vpress::landing.social.share_title'))
                ->helperText(__('vpress::landing.social.share_title_help'))
                ->maxLength(255),
            Select::make('networks')
                ->label(__('vpress::landing.social.networks_label'))
                ->options(SocialShareSupport::networkOptions())
                ->multiple()
                ->default(SocialShareSupport::defaultNetworks()),
            ...LandingBlockForm::textAlignField(),
            ...LandingBlockForm::backgroundToneFields('light'),
            ...LandingBlockForm::sectionLayoutFields('contained'),
        ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('vpress::blocks.preview-placeholder', [
            'title' => $config['heading'] ?? static::getLabel(),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        $shareUrl = SocialShareSupport::resolveShareUrl($config['share_url'] ?? null);
        $shareTitle = SocialShareSupport::resolveShareTitle($config['share_title'] ?? null);
        $networks = is_array($config['networks'] ?? null) ? $config['networks'] : null;

        return view('vpress::blocks.landing.social-share', [
            'config' => $config,
            'shareUrl' => $shareUrl,
            'links' => SocialShareSupport::links($networks, $shareUrl, $shareTitle),
        ])->render();
    }
}
