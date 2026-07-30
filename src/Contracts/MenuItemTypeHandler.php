<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Contracts;

use Filament\Forms\Components\Component;
use Illuminate\Support\Collection;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;

/**
 * Contract for a custom navigation menu item type (form + URL resolution).
 */
interface MenuItemTypeHandler
{
    public function key(): string;

    public function label(): string;

    public function allowsRoot(): bool;

    public function allowsChild(): bool;

    /** @return list<Component> */
    public function formSchema(): array;

    public function resolveUrl(NavigationMenuItem $item): string;

    /**
     * Dynamic children rendered in place of (or instead of) persisted children.
     *
     * @return Collection<int, NavigationMenuItem>
     */
    public function resolveChildren(NavigationMenuItem $item): Collection;

    public function isActive(NavigationMenuItem $item): bool;

    public function hasResolvableLink(NavigationMenuItem $item): bool;
}
