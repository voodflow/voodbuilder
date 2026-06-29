<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Voodflow\Voodbuilder\Filament\Forms\LandingBlockForm;
use Voodflow\Voodbuilder\Support\RichContentBlockPreview;
use Voodflow\Voodbuilder\Support\YoutubeEmbed;

class LandingVideoBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_video';
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::landing.blocks.video');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema([
            TextInput::make('title')
                ->label(__('voodbuilder::landing.fields.title'))
                ->maxLength(255),
            TextInput::make('video_url')
                ->label(__('voodbuilder::landing.fields.video_url'))
                ->helperText(__('voodbuilder::landing.helpers.youtube_url'))
                ->url()
                ->required(),
            Textarea::make('caption')
                ->label(__('voodbuilder::landing.fields.caption'))
                ->rows(2),
            ...LandingBlockForm::sectionLayoutFields(),
        ]);
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('voodbuilder::blocks.preview-placeholder', [
            'title' => $config['title'] ?? static::getLabel(),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        $url = $config['video_url'] ?? $config['embed_url'] ?? null;

        return view('voodbuilder::blocks.landing.video', [
            'config' => $config,
            'embedUrl' => YoutubeEmbed::normalize(is_string($url) ? $url : null),
        ])->render();
    }
}
