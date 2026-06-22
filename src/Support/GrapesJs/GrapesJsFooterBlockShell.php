<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

final class GrapesJsFooterBlockShell
{
    /**
     * @param  array<string, mixed>  $config
     */
    public static function compose(string $blockId, array $config, string $innerHtml): string
    {
        $encodedConfig = htmlspecialchars(
            json_encode($config, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            ENT_QUOTES | ENT_HTML5,
        );

        $safeId = htmlspecialchars($blockId, ENT_QUOTES | ENT_HTML5);

        return <<<HTML
<footer data-vpress-block="{$safeId}" data-vpress-config="{$encodedConfig}" data-vpress-hydrate-slots="1" class="vpress-gjs-dynamic vpress-gjs-footer w-full border-t border-vp-divider bg-vp-bg text-vp-text-2 body-font">
{$innerHtml}
</footer>
HTML;
    }
}
