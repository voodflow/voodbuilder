<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

use Voodflow\Vpress\Filament\RichContent\Landing\LandingBannerCtaBlock;
use Voodflow\Vpress\Filament\RichContent\Landing\LandingFaqBlock;
use Voodflow\Vpress\Filament\RichContent\Landing\LandingFeatureGridBlock;
use Voodflow\Vpress\Filament\RichContent\Landing\LandingFooterBlock;
use Voodflow\Vpress\Filament\RichContent\Landing\LandingHeroBlock;
use Voodflow\Vpress\Filament\RichContent\Landing\LandingLogoRowBlock;
use Voodflow\Vpress\Filament\RichContent\Landing\LandingSocialShareBlock;
use Voodflow\Vpress\Filament\RichContent\Landing\LandingSplitBlock;
use Voodflow\Vpress\Filament\RichContent\Landing\LandingStatsBlock;
use Voodflow\Vpress\Filament\RichContent\Landing\LandingStepsBlock;
use Voodflow\Vpress\Filament\RichContent\Landing\LandingTextSectionBlock;
use Voodflow\Vpress\Filament\RichContent\Landing\LandingVideoBlock;

final class VpressLandingBlocks
{
    /** @return list<class-string> */
    public static function blockClasses(): array
    {
        return [
            LandingHeroBlock::class,
            LandingBannerCtaBlock::class,
            LandingSplitBlock::class,
            LandingFeatureGridBlock::class,
            LandingStatsBlock::class,
            LandingLogoRowBlock::class,
            LandingStepsBlock::class,
            LandingFaqBlock::class,
            LandingTextSectionBlock::class,
            LandingVideoBlock::class,
            LandingSocialShareBlock::class,
            LandingFooterBlock::class,
        ];
    }
}
