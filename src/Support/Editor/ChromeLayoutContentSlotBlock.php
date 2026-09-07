<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

use Voodflow\Voodbuilder\Support\ChromeLayoutDefaults;

/**
 * Rich content / landing block: Chrome Layout Content Slot Block.
 */
final class ChromeLayoutContentSlotBlock
{
    public const string ID = 'chrome_content_slot';

    public static function definition(): EditorBlockDefinition
    {
        return new EditorBlockDefinition(
            id: self::ID,
            label: __('voodbuilder::pro.editor.blocks.chrome_content_slot'),
            category: 'Site',
            content: ChromeLayoutDefaults::contentSlotHtml(),
            preview: ChromeLayoutDefaults::contentSlotPreviewHtml(),
            attributes: [
                'data-voodbuilder-editor-scope' => 'chrome_layout',
                'title' => __('voodbuilder::pro.editor.blocks.chrome_content_slot_help'),
            ],
        );
    }
}
