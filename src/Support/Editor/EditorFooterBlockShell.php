<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

final class EditorFooterBlockShell
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
<footer data-voodbuilder-block="{$safeId}" data-voodbuilder-config="{$encodedConfig}" data-voodbuilder-hydrate-slots="1" class="voodbuilder-editor-dynamic voodbuilder-editor-footer w-full border-t border-vp-divider bg-vp-bg text-vp-text-2 body-font" role="contentinfo">
{$innerHtml}
</footer>
HTML;
    }
}
