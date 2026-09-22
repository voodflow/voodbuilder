<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use SolutionForest\FilamentNestableTree\Tree;
use Voodflow\Voodbuilder\Enums\MenuItemType;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;

/**
 * Navigation Menu Item Tree.
 */
class NavigationMenuItemTree
{
    public const MAX_DEPTH = 2;

    public static function configure(Tree $tree, NavigationMenu $menu, object $livewire): Tree
    {
        return $tree
            ->labelField('label')
            ->parentKeyField('parent_id')
            ->records(fn (): array => static::build($menu))
            ->maxDepth(static::MAX_DEPTH)
            ->maxVisibleDepth(static::MAX_DEPTH)
            ->searchable()
            ->getRecordUsing(
                fn (int|string $id): ?NavigationMenuItem => NavigationMenuItem::query()
                    ->where('menu_id', $menu->id)
                    ->whereKey($id)
                    ->first(),
            )
            ->saveOrderUsing(function (array $nodes) use ($menu): void {
                static::saveOrder($menu, $nodes);
                Navigation::clearCache($menu->slug, $menu->locale);
            })
            ->nodeActions([
                CreateAction::make('create_menu_sub_item')
                    ->label(__('voodbuilder::admin.actions.add_menu_sub_item'))
                    ->icon('heroicon-m-plus')
                    ->color('gray')
                    ->iconButton()
                    ->visible(fn (mixed $record): bool => $record instanceof NavigationMenuItem
                        && blank($record->parent_id))
                    ->model(NavigationMenuItem::class)
                    ->schema(
                        fn (Schema $schema): Schema => $schema->components(
                            NavigationMenuResource::menuItemFormSchema(
                                isChild: true,
                                menuSlug: $menu->slug,
                                menuLocale: $menu->locale,
                            ),
                        ),
                    )
                    ->mutateFormDataUsing(function (array $data, CreateAction $action) use ($menu): array {
                        $data = MenuRouteParameterField::compressForSave($data);
                        $parent = $action->getRecord();
                        $data['menu_id'] = $menu->id;
                        $data['parent_id'] = $parent instanceof NavigationMenuItem
                            ? $parent->getKey()
                            : null;
                        $data['sort_order'] = (int) NavigationMenuItem::query()
                            ->where('menu_id', $menu->id)
                            ->where('parent_id', $data['parent_id'])
                            ->max('sort_order') + 1;

                        return $data;
                    })
                    ->using(fn (array $data): NavigationMenuItem => NavigationMenuItem::create($data))
                    ->after(function () use ($livewire, $menu): void {
                        $livewire->dispatch('tree-refresh');
                        Navigation::clearCache($menu->slug, $menu->locale);
                    }),

                Action::make('promote_menu_item')
                    ->label(__('voodbuilder::admin.actions.promote_menu_item'))
                    ->icon('heroicon-m-arrow-uturn-up')
                    ->color('gray')
                    ->iconButton()
                    ->visible(fn (mixed $record): bool => $record instanceof NavigationMenuItem
                        && filled($record->parent_id))
                    ->action(function (mixed $record) use ($menu, $livewire): void {
                        if (! $record instanceof NavigationMenuItem) {
                            return;
                        }

                        $record->update([
                            'parent_id' => null,
                            'sort_order' => (int) NavigationMenuItem::query()
                                ->where('menu_id', $menu->id)
                                ->whereNull('parent_id')
                                ->max('sort_order') + 1,
                        ]);

                        $livewire->dispatch('tree-refresh');
                        Navigation::clearCache($menu->slug, $menu->locale);
                    }),

                EditAction::make('edit_menu_item')
                    ->label(__('Edit'))
                    ->icon('heroicon-m-pencil-square')
                    ->color('gray')
                    ->iconButton()
                    ->fillForm(
                        function (mixed $record): array {
                            if (! $record instanceof NavigationMenuItem) {
                                return [];
                            }

                            $data = MenuRouteParameterField::expandForFill($record->toArray());
                            $data['type'] = $record->typeKey();

                            return $data;
                        },
                    )
                    ->schema(
                        fn (Schema $schema, mixed $record): Schema => $schema->components(
                            NavigationMenuResource::menuItemFormSchema(
                                $record instanceof NavigationMenuItem && filled($record->parent_id),
                                $menu->slug,
                                $menu->locale,
                            ),
                        ),
                    )
                    ->action(function (array $data, mixed $record) use ($menu): void {
                        if (! $record instanceof NavigationMenuItem) {
                            return;
                        }

                        $record->update(MenuRouteParameterField::compressForSave($data));
                        Navigation::clearCache($menu->slug, $menu->locale);
                    })
                    ->after(fn () => $livewire->dispatch('tree-refresh')),

                DeleteAction::make('delete_menu_item')
                    ->label(__('Delete'))
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->iconButton()
                    ->after(function () use ($livewire, $menu): void {
                        $livewire->dispatch('tree-refresh');
                        Navigation::clearCache($menu->slug, $menu->locale);
                    }),
            ])
            ->appendToolbarActions([
                CreateAction::make('create_menu_item')
                    ->label(__('Add menu item'))
                    ->model(NavigationMenuItem::class)
                    ->schema(
                        fn (Schema $schema): Schema => $schema->components(
                            NavigationMenuResource::menuItemFormSchema(isChild: false, menuSlug: $menu->slug, menuLocale: $menu->locale),
                        ),
                    )
                    ->mutateFormDataUsing(function (array $data) use ($menu): array {
                        $data = MenuRouteParameterField::compressForSave($data);
                        $data['menu_id'] = $menu->id;
                        $data['parent_id'] = null;
                        $data['sort_order'] = (int) NavigationMenuItem::query()
                            ->where('menu_id', $menu->id)
                            ->whereNull('parent_id')
                            ->max('sort_order') + 1;

                        return $data;
                    })
                    ->using(fn (array $data): NavigationMenuItem => NavigationMenuItem::create($data))
                    ->after(function () use ($livewire, $menu): void {
                        $livewire->dispatch('tree-refresh');
                        Navigation::clearCache($menu->slug, $menu->locale);
                    }),
            ]);
    }

    /** @return array<int, array<string, mixed>> */
    public static function build(NavigationMenu $menu): array
    {
        static::flattenItemsBeyondMaxDepth($menu);

        $items = NavigationMenuItem::query()
            ->where('menu_id', $menu->id)
            ->orderBy('sort_order')
            ->get();

        return static::nestItems($items);
    }

    /**
     * @param  Collection<int, NavigationMenuItem>  $items
     * @return array<int, array<string, mixed>>
     */
    protected static function nestItems(Collection $items, ?int $parentId = null, int $depth = 1): array
    {
        if ($depth > static::MAX_DEPTH) {
            return [];
        }

        return $items
            ->where('parent_id', $parentId)
            ->values()
            ->map(function (NavigationMenuItem $item) use ($items, $depth): array {
                $data = $item->toArray();
                // Livewire EnumSynth metadata is path/index-based. Mixing MenuItemType
                // enums with registered string types (e.g. "docs") breaks on drag-reorder
                // when indices shift — always expose type as a plain string in tree state.
                $data['type'] = $item->typeKey();
                $children = static::nestItems($items, $item->id, $depth + 1);

                return array_merge($data, [
                    'type_label' => static::resolveTypeLabel($item),
                    // Keep parents with children expanded so nested items stay visible
                    // after drag/create (otherwise they look “missing”).
                    'expanded' => $children !== [],
                    'children' => $children,
                ]);
            })
            ->all();
    }

    protected static function resolveTypeLabel(NavigationMenuItem $item): string
    {
        if ($item->type instanceof MenuItemType) {
            return (string) $item->type->getLabel();
        }

        $handler = $item->registeredTypeHandler();

        if ($handler !== null) {
            return $handler->label();
        }

        return filled($item->type) ? (string) $item->type : '';
    }

    /** @param  array<int, array<string, mixed>>  $nodes */
    public static function saveOrder(
        NavigationMenu $menu,
        array $nodes,
        ?int $parentId = null,
        int $start = 0,
        int $depth = 1,
    ): void {
        $hadDeeperThanMaxDepth = false;

        foreach ($nodes as $index => $node) {
            $itemId = $node['id'] ?? null;

            if ($itemId === null) {
                continue;
            }

            NavigationMenuItem::query()
                ->where('menu_id', $menu->id)
                ->whereKey($itemId)
                ->update([
                    'parent_id' => $parentId,
                    'sort_order' => $start + $index,
                ]);

            $children = $node['children'] ?? [];

            if (is_array($children) && $children !== []) {
                if ($depth < static::MAX_DEPTH) {
                    static::saveOrder($menu, $children, (int) $itemId, 0, $depth + 1);
                } else {
                    // The UI may still allow nesting deeper; we'll flatten after save.
                    $hadDeeperThanMaxDepth = true;
                }
            }
        }

        if ($depth === 1) {
            static::flattenItemsBeyondMaxDepth($menu);

            if ($hadDeeperThanMaxDepth) {
                Notification::make()
                    ->title(__('voodbuilder::admin.validation.menu_max_depth', ['max' => static::MAX_DEPTH]))
                    ->warning()
                    ->send();
            }
        }
    }

    public static function flattenItemsBeyondMaxDepth(NavigationMenu $menu): void
    {
        NavigationMenuItem::query()
            ->where('menu_id', $menu->id)
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('sort_order')
            ->get()
            ->each(function (NavigationMenuItem $item): void {
                $parent = $item->parent;

                if ($parent?->parent_id === null) {
                    return;
                }

                $item->forceFill(['parent_id' => $parent->parent_id])->saveQuietly();
            });
    }
}
