<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Closure;
use Filament\Forms\Components\Component;
use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Contracts\MenuItemTypeHandler;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;

final class ConfiguredMenuItemType implements MenuItemTypeHandler
{
    /**
     * @param  string|Closure(): string  $label
     * @param  list<Component>|Closure(): list<Component>  $formSchema
     * @param  Closure(NavigationMenuItem): string|null  $resolveUrl
     * @param  Closure(NavigationMenuItem): Collection<int, NavigationMenuItem>|null  $resolveChildren
     * @param  Closure(NavigationMenuItem): bool|null  $isActive
     * @param  Closure(NavigationMenuItem): bool|null  $hasResolvableLink
     */
    public function __construct(
        protected string $key,
        protected string|Closure $label,
        protected bool $allowsRoot = true,
        protected bool $allowsChild = true,
        protected array|Closure $formSchema = [],
        protected ?Closure $resolveUrl = null,
        protected ?Closure $resolveChildren = null,
        protected ?Closure $isActive = null,
        protected ?Closure $hasResolvableLink = null,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return (string) value($this->label);
    }

    public function allowsRoot(): bool
    {
        return $this->allowsRoot;
    }

    public function allowsChild(): bool
    {
        return $this->allowsChild;
    }

    /** @return list<Component> */
    public function formSchema(): array
    {
        $schema = value($this->formSchema);

        return is_array($schema) ? array_values($schema) : [];
    }

    public function resolveUrl(NavigationMenuItem $item): string
    {
        if ($this->resolveUrl === null) {
            return '#';
        }

        return (string) ($this->resolveUrl)($item);
    }

    /** @return Collection<int, NavigationMenuItem> */
    public function resolveChildren(NavigationMenuItem $item): Collection
    {
        if ($this->resolveChildren === null) {
            return collect();
        }

        $children = ($this->resolveChildren)($item);

        return $children instanceof Collection ? $children : collect($children);
    }

    public function isActive(NavigationMenuItem $item): bool
    {
        if ($this->isActive === null) {
            return false;
        }

        return (bool) ($this->isActive)($item);
    }

    public function hasResolvableLink(NavigationMenuItem $item): bool
    {
        if ($this->hasResolvableLink !== null) {
            return (bool) ($this->hasResolvableLink)($item);
        }

        return $this->resolveUrl($item) !== '#';
    }
}
