<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Contracts\GrapesJsBindingSource;
use Voodflow\Voodbuilder\Contracts\GrapesJsServerBlock;
use Voodflow\Voodbuilder\Contracts\PublicContentChannel;
use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingImageResolverRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockDefinition;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsServerBlockRegistry;
use Voodflow\Voodbuilder\Support\RichContentBlockRegistry;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;

class Voodbuilder
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
     * @param  class-string<GrapesJsServerBlock>  $blockClass
     */
    public static function grapesJsServerBlock(string $category, string $blockClass): void
    {
        app(GrapesJsServerBlockRegistry::class)->register($category, $blockClass);
    }

    public static function grapesJsBindingSource(GrapesJsBindingSource $source): void
    {
        app(BindingRegistry::class)->register($source);
    }

    /**
     * Register a custom image URL resolver for a model field (level 4 — exotic storage).
     *
     * @param  callable(Model, BindingContext): (?string)  $resolver
     */
    public static function registerBindingImageResolver(string $modelClass, string $fieldId, callable $resolver): void
    {
        app(BindingImageResolverRegistry::class)->register($modelClass, $fieldId, $resolver);
    }
}
