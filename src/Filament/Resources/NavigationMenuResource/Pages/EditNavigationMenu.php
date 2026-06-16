<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Resources\NavigationMenuResource\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use SolutionForest\FilamentNestableTree\Concerns\InteractsWithTree;
use SolutionForest\FilamentNestableTree\Tree;
use Voodflow\Vpress\Filament\Resources\NavigationMenuResource;
use Voodflow\Vpress\Models\NavigationMenu;
use Voodflow\Vpress\Support\Navigation;
use Voodflow\Vpress\Support\NavigationMenuItemTree;

class EditNavigationMenu extends EditRecord
{
    use InteractsWithTree;

    protected static string $resource = NavigationMenuResource::class;

    public function bootInteractsWithTree(): void
    {
        if (! $this->record instanceof NavigationMenu) {
            return;
        }

        $this->cacheTreeActions();
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->cacheTreeActions();
    }

    public function getMountedAction(?int $actionNestingIndex = null): ?Action
    {
        $action = parent::getMountedAction($actionNestingIndex);
        $this->injectNodeRecordIntoAction($action);

        return $action;
    }

    public function tree(Tree $tree): Tree
    {
        if (! $this->record instanceof NavigationMenu) {
            return $tree;
        }

        return NavigationMenuItemTree::configure($tree, $this->record, $this);
    }

    public function content(Schema $schema): Schema
    {
        $treeConfig = $this->getCachedTree();

        return $schema
            ->components([
                $this->getFormContentComponent(),
                Section::make(__('Menu items'))
                    ->description(__('vpress::admin.helpers.menu_tree'))
                    ->schema([
                        View::make('filament-nestable-tree::livewire.components.tree')
                            ->viewData([
                                'wireNodesProperty' => 'treeNodes',
                                'treeConfig' => $treeConfig,
                                'treeKeyName' => null,
                                'isSearchable' => $treeConfig->isSearchable(),
                                'allowDragDrop' => $treeConfig->isDraggable(),
                                'allowCrossCategory' => $treeConfig->isCrossCategoryAllowed(),
                                'toolbarActions' => $treeConfig->getToolbarActions(),
                                'lazy' => $treeConfig->isLazy(),
                                'hasNodeActions' => $treeConfig->getNodeActions() !== [],
                            ]),
                    ]),
                $this->getRelationManagersContentComponent(),
            ]);
    }

    protected function afterSave(): void
    {
        /** @var NavigationMenu $record */
        $record = $this->record;

        Navigation::clearCache($record->slug);
    }
}
