<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Voodflow\Vpress\Filament\Forms\LandingFooterForm;
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
        return $action->schema(LandingFooterForm::fields());
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('vpress::blocks.preview-placeholder', [
            'title' => $config['brand_name'] ?? static::getLabel(),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('vpress::blocks.landing.footer', static::viewData($config))->render();
    }

    /** @return array<string, mixed> */
    protected static function viewData(array $config): array
    {
        return [
            'config' => $config,
            'organizer' => static::footerSupport()::organizerColumn($config),
            'menuColumns' => static::footerSupport()::menuColumns($config),
            'copyrightSegments' => static::footerSupport()::copyrightSegments($config),
        ];
    }

    /** @return class-string */
    protected static function footerSupport(): string
    {
        return LandingFooterSupport::class;
    }
}
