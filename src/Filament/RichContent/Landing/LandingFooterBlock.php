<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Voodflow\Voodbuilder\Filament\Forms\LandingFooterForm;
use Voodflow\Voodbuilder\Support\LandingFooterSupport;

/**
 * Rich content / landing block: Landing Footer Block.
 */
class LandingFooterBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_footer';
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::landing.blocks.footer');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema(LandingFooterForm::fields());
    }

    public static function toPreviewHtml(array $config): string
    {
        return static::toHtml($config, []);
    }

    public static function toHtml(array $config, array $data): string
    {
        $variant = LandingFooterSupport::resolveVariant($config);

        $view = $variant === 'legacy'
            ? 'voodbuilder::blocks.landing.footer'
            : 'voodbuilder::blocks.landing.footer-layout.'.$variant;

        return view($view, static::viewData($config))->render();
    }

    /** @return array<string, mixed> */
    public static function viewData(array $config): array
    {
        $organizer = static::footerSupport()::organizerColumn($config);

        return [
            'config' => $config,
            'organizer' => $organizer,
            'brandTagline' => static::brandTagline($config, $organizer),
            'menuColumns' => static::footerSupport()::menuColumns($config),
            'copyrightSegments' => static::footerSupport()::copyrightSegments($config),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array{logo_url: ?string, brand_name: ?string, lines: list<array{label: string, url: ?string, is_email: bool}>}  $organizer
     */
    protected static function brandTagline(array $config, array $organizer): ?string
    {
        if (filled($config['brand_tagline'] ?? null)) {
            return (string) $config['brand_tagline'];
        }

        $firstLine = $organizer['lines'][0]['label'] ?? null;

        return is_string($firstLine) && $firstLine !== '' ? $firstLine : null;
    }

    /** @return class-string */
    protected static function footerSupport(): string
    {
        return LandingFooterSupport::class;
    }
}
