<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Closure;
use Filament\Forms\Components\Component;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Voodflow\Voodbuilder\Contracts\MenuItemTypeHandler;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;

/**
 * Menu Item Type Registry.
 */
final class MenuItemTypeRegistry
{
    /** @var array<string, MenuItemTypeHandler> */
    private array $types = [];

    /**
     * @param  array{
     *     label: string|Closure(): string,
     *     allows_root?: bool,
     *     allows_child?: bool,
     *     form?: list<Component>|Closure(): list<Component>,
     *     resolve_url?: Closure(NavigationMenuItem): string,
     *     resolve_children?: Closure(NavigationMenuItem): Collection,
     *     is_active?: Closure(NavigationMenuItem): bool,
     *     has_resolvable_link?: Closure(NavigationMenuItem): bool,
     * }|MenuItemTypeHandler  $definition
     */
    public function register(string $key, array|MenuItemTypeHandler $definition): self
    {
        if ($definition instanceof MenuItemTypeHandler) {
            $this->types[$definition->key()] = $definition;

            return $this;
        }

        $this->types[$key] = new ConfiguredMenuItemType(
            key: $key,
            label: $definition['label'] ?? str($key)->headline()->toString(),
            allowsRoot: (bool) ($definition['allows_root'] ?? true),
            allowsChild: (bool) ($definition['allows_child'] ?? true),
            formSchema: $definition['form'] ?? [],
            resolveUrl: $definition['resolve_url'] ?? null,
            resolveChildren: $definition['resolve_children'] ?? null,
            isActive: $definition['is_active'] ?? null,
            hasResolvableLink: $definition['has_resolvable_link'] ?? null,
        );

        return $this;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->types);
    }

    public function get(string $key): ?MenuItemTypeHandler
    {
        return $this->types[$key] ?? null;
    }

    /** @return array<string, MenuItemTypeHandler> */
    public function all(): array
    {
        return $this->types;
    }

    /** @return array<string, string> */
    public function options(bool $isChild = false): array
    {
        $options = [];

        foreach ($this->types as $key => $handler) {
            if ($isChild && ! $handler->allowsChild()) {
                continue;
            }

            if (! $isChild && ! $handler->allowsRoot()) {
                continue;
            }

            $options[$key] = $handler->label();
        }

        return $options;
    }

    /** @return list<Component> */
    public function formComponents(): array
    {
        $components = [];

        foreach ($this->types as $key => $handler) {
            foreach ($handler->formSchema() as $component) {
                $components[] = $component->visible(
                    fn (Get $get): bool => (string) $get('type') === $key,
                );
            }
        }

        return $components;
    }

    public function menuContains(string $typeKey, string $menuSlug = 'main'): bool
    {
        if (! function_exists('app') || ! app()->bound('db')) {
            return false;
        }

        if (! NavigationMenuResolver::menusTableExists() || ! Schema::hasTable('voodbuilder_menu_items')) {
            return false;
        }

        $menu = NavigationMenuResolver::forPlacement($menuSlug);

        if (! $menu instanceof NavigationMenu) {
            return false;
        }

        return NavigationMenuItem::query()
            ->where('menu_id', $menu->getKey())
            ->where('type', $typeKey)
            ->exists();
    }

    public function flush(): void
    {
        $this->types = [];
    }
}
