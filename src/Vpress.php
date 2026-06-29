<?php

declare(strict_types=1);

namespace Voodflow\Vpress;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Voodflow\Vpress\Contracts\PublicContentChannel;
use Voodflow\Vpress\Support\ContentChannelRegistry;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsBlockDefinition;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Vpress\Contracts\GrapesJsBindingSource;
use Voodflow\Vpress\Support\GrapesJs\Bindings\BindingRegistry;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsServerBlockRegistry;
use Voodflow\Vpress\Support\RichContentBlockRegistry;
use Voodflow\Vpress\Support\SubThemeRegistry;

class Vpress
{
    /**
     * @param  class-string<RichContentCustomBlock>  $blockClass
     */
    public static function richContentBlock(string $group, string $blockClass): void
    {
        app(RichContentBlockRegistry::class)->register($group, $blockClass);
    }

    /**
     * @param  array{label?: string, description?: string, layouts?: array<string, string>, css?: string}  $definition
     */
    public static function subTheme(string $id, array $definition): void
    {
        app(SubThemeRegistry::class)->register($id, $definition);
    }

    /**
     * @param  array{
     *     label?: string,
     *     routes?: list<string>,
     *     sub_theme?: string|null,
     *     search?: \Closure|string|null,
     * }|PublicContentChannel  $definition
     */
    public static function contentChannel(string $id, array|PublicContentChannel $definition): void
    {
        $registry = app(ContentChannelRegistry::class);

        if ($definition instanceof PublicContentChannel) {
            $registry->register($definition);

            return;
        }

        $registry->registerFromArray($id, $definition);
    }

    public static function grapesJsBlock(
        string $id,
        string $label,
        string $category,
        string $content,
        array $attributes = [],
    ): void {
        app(GrapesJsBlockRegistry::class)->register(new GrapesJsBlockDefinition(
            id: $id,
            label: $label,
            category: $category,
            content: $content,
            attributes: $attributes,
        ));
    }

    /**
     * @param  class-string<RichContentCustomBlock>  $blockClass
     */
    public static function grapesJsRichContentBlock(string $category, string $blockClass): void
    {
        app(GrapesJsDynamicBlockRegistry::class)->register($category, $blockClass);
    }

    /**
     * @param  class-string<\Voodflow\Vpress\Contracts\GrapesJsServerBlock>  $blockClass
     */
    public static function grapesJsServerBlock(string $category, string $blockClass): void
    {
        app(GrapesJsServerBlockRegistry::class)->register($category, $blockClass);
    }

    public static function grapesJsBindingSource(GrapesJsBindingSource $source): void
    {
        app(BindingRegistry::class)->register($source);
    }
}
