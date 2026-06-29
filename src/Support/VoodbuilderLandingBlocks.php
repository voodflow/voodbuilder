<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingBannerCtaBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingContactCtaBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingFaqBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingFeatureGridBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingFooterBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingHeroBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingLogoRowBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingNavbarBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingSocialShareBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingSplitBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingStatsBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingStepsBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingTextSectionBlock;
use Voodflow\Voodbuilder\Filament\RichContent\Landing\LandingVideoBlock;

final class VoodbuilderLandingBlocks
{
    /** @return list<class-string> */
    public static function blockClasses(): array
    {
        return [
            LandingNavbarBlock::class,
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
            LandingContactCtaBlock::class,
            LandingFooterBlock::class,
        ];
    }
}
