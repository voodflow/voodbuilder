<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Support\ChromeLayoutDefaults;

final class ChromeLayoutContentSlotBlock
{
    public const string ID = 'chrome_content_slot';

    public static function definition(): GrapesJsBlockDefinition
    {
        return new GrapesJsBlockDefinition(
            id: self::ID,
            label: __('voodbuilder::pro.grapesjs.blocks.chrome_content_slot'),
            category: 'Site',
            content: ChromeLayoutDefaults::contentSlotHtml(),
            preview: ChromeLayoutDefaults::contentSlotPreviewHtml(),
            attributes: [
                'data-voodbuilder-editor-scope' => 'chrome_layout',
                'title' => __('voodbuilder::pro.grapesjs.blocks.chrome_content_slot_help'),
            ],
        );
    }
}
