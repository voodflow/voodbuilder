<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

final class GrapesJsSiteChromeSidebarPreview
{
    public static function fromPreviewHtml(string $html, string $blockId): string
    {
        if ($html === '') {
            return GrapesJsBlockThumbnail::forBlockId($blockId);
        }

        $wrapped = GrapesJsBlockPreview::wrapSiteChromeHtml(self::compactMarkup($html), $blockId);

        return $wrapped ?? GrapesJsBlockThumbnail::forBlockId($blockId);
    }

    private static function compactMarkup(string $html): string
    {
        $compact = $html;

        $replacements = [
            '/\spy-24\b/' => ' py-6',
            '/\spy-16\b/' => ' py-4',
            '/\spy-8\b/' => ' py-3',
            '/\smb-12\b/' => ' mb-4',
            '/\smt-10\b/' => ' mt-3',
            '/\smt-8\b/' => ' mt-3',
            '/\smt-6\b/' => ' mt-2',
            '/\smt-4\b/' => ' mt-2',
            '/\sgap-4\b/' => ' gap-2',
            '/\spx-6\b/' => ' px-3',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $compact = (string) preg_replace($pattern, $replacement, $compact);
        }

        $compact = (string) preg_replace(
            '/<div class="pointer-events-none absolute[^"]*"[^>]*>.*?<\/div>\s*/s',
            '',
            $compact,
        );

        return '<div class="voodbuilder-gjs-block-preview-compact">'.$compact.'</div>';
    }
}
