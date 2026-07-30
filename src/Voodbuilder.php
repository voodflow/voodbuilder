<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder;

use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Illuminate\Database\Eloquent\Model;
use Voodflow\Voodbuilder\Contracts\EditorBindingSource;
use Voodflow\Voodbuilder\Contracts\EditorServerBlock;
use Voodflow\Voodbuilder\Contracts\PublicContentChannel;
use Voodflow\Voodbuilder\Contracts\MenuItemTypeHandler;
use Voodflow\Voodbuilder\Support\ContentChannelRegistry;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingImageResolverRegistry;
use Voodflow\Voodbuilder\Support\Editor\Bindings\BindingRegistry;
use Voodflow\Voodbuilder\Support\Editor\Bindings\RepeatListRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorBlockDefinition;
use Voodflow\Voodbuilder\Support\Editor\EditorBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorDynamicBlockRegistry;
use Voodflow\Voodbuilder\Support\Editor\EditorServerBlockRegistry;
use Voodflow\Voodbuilder\Support\Fonts\FontCatalog;
use Voodflow\Voodbuilder\Support\Fonts\FontDefinition;
use Voodflow\Voodbuilder\Support\MenuItemTypeRegistry;
use Voodflow\Voodbuilder\Contracts\VoodBuilderModule;
use Voodflow\Voodbuilder\Modules\ModuleRegistry;
use Voodflow\Voodbuilder\Support\RichContentBlockRegistry;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Licensing\EntitlementManager;

/**
 * Public facade for third-party and host-app registration.
 *
 * Register blocks, bindings, conditions, content channels, menu item types,
 * fonts, sub-themes, and modules from a ServiceProvider — never by editing Core.
 *
 * @see docs/manual/developer/extending-overview.md
 */
class Voodbuilder
{
    public static function modules(): ModuleRegistry
    {
        return app(ModuleRegistry::class);
    }

    /**
     * Register an official or third-party module before ModuleRegistry::boot().
     * Prefer calling from a ServiceProvider Application::booting() callback.
     */
    public static function registerModule(VoodBuilderModule $module, bool $enabled = true): void
    {
        self::modules()->register($module, $enabled);
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

    public static function editorBlock(
        string $id,
        string $label,
        string $category,
        string $content,
        array $attributes = [],
    ): void {
        app(EditorBlockRegistry::class)->register(new EditorBlockDefinition(
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
    public static function editorRichContentBlock(string $category, string $blockClass): void
    {
        app(EditorDynamicBlockRegistry::class)->register($category, $blockClass);
    }

    /**
     * @param  class-string<EditorServerBlock>  $blockClass
     */
    public static function editorServerBlock(string $category, string $blockClass): void
    {
        app(EditorServerBlockRegistry::class)->register($category, $blockClass);
    }

    public static function editorBindingSource(EditorBindingSource $source): void
    {
        app(BindingRegistry::class)->register($source);
    }

    /**
     * Register a custom visual condition evaluator for the Editor conditions UI.
     *
     * @param  callable(array<string, mixed>, ?\Voodflow\Voodbuilder\Models\SitePage): bool  $handler
     */
    public static function editorCondition(string $key, callable $handler): void
    {
        \Voodflow\Voodbuilder\Support\Editor\Conditions\EditorConditionHooks::register($key, $handler);
    }

    /**
     * Contribute labels to the visual editor bootstrap payload.
     *
     * @param  callable(): array<string, mixed>  $provider
     */
    public static function editorLabels(callable $provider): void
    {
        \Voodflow\Voodbuilder\Support\Editor\EditorGate::registerLabelProvider($provider);
    }

    /**
     * Register a List repeat query (package lists outside Model Integrations).
     *
     * @param  callable(int $limit, int $offset, string $sort, string $direction): list<Model>  $resolver
     * @param  list<array{id: string, label: string}>  $sortFields
     * @param  list<string>  $aliases  Legacy repeat keys mapped to this list (e.g. vtuts.latest_list)
     */
    public static function editorRepeatList(
        string $id,
        string $label,
        callable $resolver,
        array $sortFields = [],
        string $defaultSort = 'id',
        string $defaultDirection = 'desc',
        array $aliases = [],
    ): void {
        // No-op without the Dynamic Data collections companion package.
        if (! class_exists(RepeatListRegistry::class)) {
            return;
        }

        $registry = app(RepeatListRegistry::class);
        $registry->register($id, $label, $resolver, $sortFields, $defaultSort, $defaultDirection);

        foreach ($aliases as $alias) {
            $registry->alias((string) $alias, $id);
        }
    }

    /**
     * Extensible webfont catalog (Fontsource core + plugin providers).
     */
    public static function fonts(): FontCatalog
    {
        $catalog = app(FontCatalog::class);
        $catalog->bootCore();

        return $catalog;
    }

    /**
     * @param  list<array<string, mixed>|FontDefinition>|FontDefinition|array<string, mixed>  $fonts
     */
    public static function registerFonts(array|FontDefinition $fonts): void
    {
        $catalog = self::fonts();

        if ($fonts instanceof FontDefinition) {
            $catalog->register($fonts);

            return;
        }

        // Single associative font array vs list of fonts.
        if (array_is_list($fonts)) {
            $catalog->registerMany($fonts);

            return;
        }

        $catalog->register(FontDefinition::fromArray($fonts));
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
