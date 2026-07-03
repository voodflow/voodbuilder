<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use SolutionForest\FilamentNestableTree\Tree;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Models\NavigationMenuItem;

class NavigationMenuItemTree
{
    public const MAX_DEPTH = 3;

    public static function configure(Tree $tree, NavigationMenu $menu, object $livewire): Tree
    {
        return $tree
            ->labelField('label')
            ->parentKeyField('parent_id')
            ->records(fn (): array => static::build($menu))
            ->maxDepth(static::MAX_DEPTH)
            ->searchable()
            ->getRecordUsing(
                fn (int|string $id): ?NavigationMenuItem => NavigationMenuItem::query()
                    ->where('menu_id', $menu->id)
                    ->whereKey($id)
                    ->first(),
            )
            ->saveOrderUsing(function (array $nodes) use ($menu): void {
                static::saveOrder($menu, $nodes);
                Navigation::clearCache($menu->slug);
            })
            ->nodeActions([
                EditAction::make('edit_menu_item')
                    ->label(__('Edit'))
                    ->icon('heroicon-m-pencil-square')
                    ->color('gray')
                    ->iconButton()
                    ->fillForm(
                        fn (mixed $record): array => $record instanceof NavigationMenuItem
                            ? MenuRouteParameterField::expandForFill($record->toArray())
                            : [],
                    )
                    ->schema(
                        fn (Schema $schema, mixed $record): Schema => $schema->components(
                            NavigationMenuResource::menuItemFormSchema(
                                $record instanceof NavigationMenuItem && filled($record->parent_id),
                            ),
                        ),
                    )
                    ->action(function (array $data, mixed $record) use ($menu): void {
                        if (! $record instanceof NavigationMenuItem) {
                            return;
                        }

                        $record->update(MenuRouteParameterField::compressForSave($data));
                        Navigation::clearCache($menu->slug);
                    })
                    ->after(fn () => $livewire->dispatch('tree-refresh')),

                DeleteAction::make('delete_menu_item')
                    ->label(__('Delete'))
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->iconButton()
                    ->after(function () use ($livewire, $menu): void {
                        $livewire->dispatch('tree-refresh');
                        Navigation::clearCache($menu->slug);
                    }),
            ])
            ->appendToolbarActions([
                CreateAction::make('create_menu_item')
                    ->label(__('Add menu item'))
                    ->model(NavigationMenuItem::class)
                    ->schema(
                        fn (Schema $schema): Schema => $schema->components(
                            NavigationMenuResource::menuItemFormSchema(isChild: false),
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
                        Navigation::clearCache($menu->slug);
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
                return array_merge($item->toArray(), [
                    'children' => static::nestItems($items, $item->id, $depth + 1),
                ]);
            })
            ->all();
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
