<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Voodflow\Vpress\Filament\Forms\LandingNavbarForm;
use Voodflow\Vpress\Support\LandingNavbarSupport;

class LandingNavbarBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_navbar';
    }

    public static function getLabel(): string
    {
        return __('vpress::landing.blocks.navbar');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action->schema(LandingNavbarForm::fields());
    }

    public static function toPreviewHtml(array $config): string
    {
        return static::toHtml($config, []);
    }

    public static function toHtml(array $config, array $data): string
    {
        $variant = LandingNavbarSupport::resolveVariant($config);

        return view('vpress::blocks.landing.navbar-tailblocks.'.$variant, [
            'config' => $config,
            'navbar' => LandingNavbarSupport::viewData($config),
        ])->render();
    }
}
