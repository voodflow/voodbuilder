<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class GrapesJsBlockThumbnail
{
    public static function wrap(string $innerSvg): string
    {
        return '<div class="voodbuilder-gjs-block-thumb" aria-hidden="true">'.$innerSvg.'</div>';
    }

    public static function forBlockId(string $blockId): string
    {
        if ($blockId === 'hero' || $blockId === 'voodbuilder-hero') {
            return self::wrap(self::heroSvg());
        }

        if ($blockId === 'section' || $blockId === 'voodbuilder-section') {
            return self::wrap(self::sectionSvg());
        }

        if (str_contains($blockId, 'cta') || $blockId === 'voodbuilder-cta-banner') {
            return self::wrap(self::ctaSvg());
        }

        if ($blockId === 'site_header' || str_starts_with($blockId, 'site_nav_')) {
            return self::wrap(self::headerSvg());
        }

        if ($blockId === 'site_footer' || str_starts_with($blockId, 'site_footer_')) {
            return match (true) {
                in_array($blockId, ['site_footer_social', 'site_footer_d'], true) => self::wrap(self::footerDSvg()),
                in_array($blockId, ['site_footer_centered'], true) => self::wrap(self::footerCenteredSvg()),
                in_array($blockId, ['site_footer_columns_newsletter'], true) => self::wrap(self::footerNewsletterSvg()),
                default => self::wrap(self::footerColumnsSvg()),
            };
        }

        return match ($blockId) {
            'latest_vtuts' => self::wrap(self::tutorialsSvg()),
            default => self::wrap(self::genericSvg()),
        };
    }

    protected static function heroSvg(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 120 72" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="120" height="72" rx="6" fill="#f8fafc"/>
  <rect x="28" y="14" width="64" height="8" rx="2" fill="#cbd5e1"/>
  <rect x="20" y="28" width="80" height="5" rx="2" fill="#e2e8f0"/>
  <rect x="42" y="46" width="36" height="10" rx="3" fill="#3451b2"/>
</svg>
SVG;
    }

    protected static function sectionSvg(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 120 72" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="120" height="72" rx="6" fill="#f8fafc"/>
  <rect x="16" y="16" width="48" height="6" rx="2" fill="#cbd5e1"/>
  <rect x="16" y="28" width="88" height="4" rx="2" fill="#e2e8f0"/>
  <rect x="16" y="36" width="76" height="4" rx="2" fill="#e2e8f0"/>
  <rect x="16" y="44" width="64" height="4" rx="2" fill="#e2e8f0"/>
</svg>
SVG;
    }

    protected static function ctaSvg(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 120 72" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="120" height="72" rx="6" fill="#1f2937"/>
  <rect x="24" y="20" width="72" height="7" rx="2" fill="#f8fafc"/>
  <rect x="34" y="34" width="52" height="4" rx="2" fill="#94a3b8"/>
  <rect x="42" y="46" width="36" height="10" rx="3" fill="#f8fafc"/>
</svg>
SVG;
    }

    protected static function headerSvg(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 120 72" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="120" height="72" rx="6" fill="#f8fafc"/>
  <rect x="0" y="0" width="120" height="72" rx="6" stroke="#e2e8f0"/>
  <circle cx="22" cy="36" r="8" fill="#3451b2"/>
  <rect x="38" y="32" width="30" height="4" rx="2" fill="#cbd5e1"/>
  <rect x="78" y="30" width="14" height="4" rx="2" fill="#e2e8f0"/>
  <rect x="96" y="30" width="14" height="4" rx="2" fill="#e2e8f0"/>
</svg>
SVG;
    }

    protected static function footerASvg(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 120 72" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="120" height="72" rx="6" fill="#f8fafc"/>
  <circle cx="18" cy="24" r="7" fill="#3451b2"/>
  <rect x="12" y="36" width="20" height="3" rx="1" fill="#e2e8f0"/>
  <rect x="40" y="16" width="16" height="3" rx="1" fill="#cbd5e1"/>
  <rect x="40" y="24" width="12" height="2" rx="1" fill="#e2e8f0"/>
  <rect x="40" y="30" width="14" height="2" rx="1" fill="#e2e8f0"/>
  <rect x="62" y="16" width="16" height="3" rx="1" fill="#cbd5e1"/>
  <rect x="62" y="24" width="12" height="2" rx="1" fill="#e2e8f0"/>
  <rect x="84" y="16" width="16" height="3" rx="1" fill="#cbd5e1"/>
  <rect x="84" y="24" width="12" height="2" rx="1" fill="#e2e8f0"/>
</svg>
SVG;
    }

    protected static function footerBSvg(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 120 72" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="120" height="72" rx="6" fill="#f8fafc"/>
  <rect x="12" y="16" width="16" height="3" rx="1" fill="#cbd5e1"/>
  <rect x="12" y="24" width="12" height="2" rx="1" fill="#e2e8f0"/>
  <rect x="34" y="16" width="16" height="3" rx="1" fill="#cbd5e1"/>
  <rect x="56" y="16" width="16" height="3" rx="1" fill="#cbd5e1"/>
  <circle cx="98" cy="24" r="7" fill="#3451b2"/>
  <rect x="90" y="36" width="16" height="3" rx="1" fill="#e2e8f0"/>
</svg>
SVG;
    }

    protected static function footerColumnsSvg(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 120 72" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="120" height="72" rx="6" fill="#f8fafc"/>
  <rect x="10" y="16" width="18" height="3" rx="1" fill="#cbd5e1"/>
  <rect x="10" y="24" width="14" height="2" rx="1" fill="#e2e8f0"/>
  <rect x="10" y="30" width="16" height="2" rx="1" fill="#e2e8f0"/>
  <rect x="34" y="16" width="18" height="3" rx="1" fill="#cbd5e1"/>
  <rect x="34" y="24" width="14" height="2" rx="1" fill="#e2e8f0"/>
  <rect x="58" y="16" width="18" height="3" rx="1" fill="#cbd5e1"/>
  <rect x="58" y="24" width="14" height="2" rx="1" fill="#e2e8f0"/>
  <rect x="82" y="16" width="18" height="3" rx="1" fill="#cbd5e1"/>
  <rect x="82" y="24" width="14" height="2" rx="1" fill="#e2e8f0"/>
</svg>
SVG;
    }

    protected static function footerDSvg(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 120 72" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="120" height="72" rx="6" fill="#f8fafc"/>
  <circle cx="18" cy="36" r="7" fill="#3451b2"/>
  <rect x="34" y="33" width="40" height="4" rx="2" fill="#e2e8f0"/>
  <circle cx="88" cy="36" r="4" fill="#cbd5e1"/>
  <circle cx="100" cy="36" r="4" fill="#cbd5e1"/>
</svg>
SVG;
    }

    protected static function footerCenteredSvg(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 120 72" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="120" height="72" rx="6" fill="#f8fafc"/>
  <rect x="8" y="22" width="104" height="28" rx="4" fill="#f1f5f9"/>
  <circle cx="60" cy="32" r="7" fill="#3451b2"/>
  <rect x="36" y="42" width="48" height="3" rx="1.5" fill="#e2e8f0"/>
</svg>
SVG;
    }

    protected static function footerNewsletterSvg(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 120 72" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="120" height="72" rx="6" fill="#f8fafc"/>
  <circle cx="16" cy="24" r="6" fill="#3451b2"/>
  <rect x="10" y="34" width="16" height="2" rx="1" fill="#e2e8f0"/>
  <rect x="34" y="16" width="14" height="3" rx="1" fill="#cbd5e1"/>
  <rect x="34" y="24" width="10" height="2" rx="1" fill="#e2e8f0"/>
  <rect x="52" y="16" width="14" height="3" rx="1" fill="#cbd5e1"/>
  <rect x="52" y="24" width="10" height="2" rx="1" fill="#e2e8f0"/>
  <rect x="70" y="16" width="14" height="3" rx="1" fill="#cbd5e1"/>
  <rect x="70" y="24" width="10" height="2" rx="1" fill="#e2e8f0"/>
  <rect x="90" y="18" width="22" height="20" rx="3" fill="#e2e8f0"/>
  <rect x="94" y="24" width="14" height="2" rx="1" fill="#cbd5e1"/>
  <rect x="94" y="30" width="10" height="6" rx="2" fill="#3451b2"/>
</svg>
SVG;
    }

    protected static function tutorialsSvg(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 120 72" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="120" height="72" rx="6" fill="#f8fafc"/>
  <rect x="12" y="14" width="40" height="5" rx="2" fill="#cbd5e1"/>
  <rect x="12" y="28" width="28" height="24" rx="3" fill="#e2e8f0"/>
  <rect x="46" y="28" width="28" height="24" rx="3" fill="#e2e8f0"/>
  <rect x="80" y="28" width="28" height="24" rx="3" fill="#e2e8f0"/>
</svg>
SVG;
    }

    protected static function genericSvg(): string
    {
        return <<<'SVG'
<svg viewBox="0 0 120 72" fill="none" xmlns="http://www.w3.org/2000/svg">
  <rect width="120" height="72" rx="6" fill="#f8fafc" stroke="#e2e8f0"/>
  <rect x="20" y="22" width="80" height="28" rx="4" fill="#e2e8f0"/>
</svg>
SVG;
    }
}
