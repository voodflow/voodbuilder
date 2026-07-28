<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Contracts\GrapesJsBindingSource;
use Voodflow\Voodbuilder\Contracts\GrapesJsServerBlock;
use Voodflow\Voodbuilder\Contracts\PublicContentChannel;
use Voodflow\Voodbuilder\Contracts\MenuItemTypeHandler;
use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingImageResolverRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\RepeatListRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockDefinition;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsServerBlockRegistry;
use Voodflow\Voodbuilder\Support\MenuItemTypeRegistry;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;
use Voodflow\Voodbuilder\Support\RichContentBlockRegistry;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Licensing\EntitlementManager;

class Voodbuilder
{
    public static function modules(): ModuleRegistry
    {
        return app(ModuleRegistry::class);
    }

    public static function entitlements(): EntitlementManager
    {
        return app(EntitlementManager::class);
    }

    public static function can(string $capability): bool
    {
        return self::entitlements()->can($capability);
    }

    public static function cannot(string $capability): bool
    {
        return self::entitlements()->cannot($capability);
    }

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

    /**
     * Register a plugin-handled navigation menu item type (e.g. docs nav from vdocs).
     *
     * @param  array{
     *     label: string|\Closure(): string,
     *     allows_root?: bool,
     *     allows_child?: bool,
     *     form?: list<\Filament\Forms\Components\Component>|\Closure(): list<\Filament\Forms\Components\Component>,
     *     resolve_url?: \Closure(\Voodflow\Voodbuilder\Models\NavigationMenuItem): string,
     *     resolve_children?: \Closure(\Voodflow\Voodbuilder\Models\NavigationMenuItem): \Illuminate\Support\Collection,
     *     is_active?: \Closure(\Voodflow\Voodbuilder\Models\NavigationMenuItem): bool,
     *     has_resolvable_link?: \Closure(\Voodflow\Voodbuilder\Models\NavigationMenuItem): bool,
     * }|MenuItemTypeHandler  $definition
     */
    public static function menuItemType(string $key, array|MenuItemTypeHandler $definition): void
    {
        app(MenuItemTypeRegistry::class)->register($key, $definition);
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
     * Register a List repeat query (package lists outside Model Integrations).
     *
     * @param  callable(int $limit, int $offset, string $sort, string $direction): list<Model>  $resolver
     * @param  list<array{id: string, label: string}>  $sortFields
     * @param  list<string>  $aliases  Legacy repeat keys mapped to this list (e.g. vtuts.latest_list)
     */
    public static function grapesJsRepeatList(
        string $id,
        string $label,
        callable $resolver,
        array $sortFields = [],
        string $defaultSort = 'id',
        string $defaultDirection = 'desc',
        array $aliases = [],
    ): void {
        $registry = app(RepeatListRegistry::class);
        $registry->register($id, $label, $resolver, $sortFields, $defaultSort, $defaultDirection);

        foreach ($aliases as $alias) {
            $registry->alias((string) $alias, $id);
        }
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
