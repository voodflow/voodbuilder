<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use SolutionForest\FilamentNestableTree\Concerns\InteractsWithTree;
use SolutionForest\FilamentNestableTree\Tree;
use Voodflow\Voodbuilder\Filament\Actions\CloneNavigationMenuAction;
use Voodflow\Voodbuilder\Filament\Actions\CreateNavigationMenuTranslationAction;
use Voodflow\Voodbuilder\Filament\Resources\NavigationMenuResource;
use Voodflow\Voodbuilder\Models\NavigationMenu;
use Voodflow\Voodbuilder\Support\NavigationMenuItemTree;
use Voodflow\Voodbuilder\Support\NavigationMenuPreview;

class EditNavigationMenu extends EditRecord
{
    use InteractsWithTree;

    protected static string $resource = NavigationMenuResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateNavigationMenuTranslationAction::make(),
            CloneNavigationMenuAction::make(),
            DeleteAction::make(),
        ];
    }

    private static bool $previewAssetsRegistered = false;

    protected static function registerPreviewAssets(): void
    {
        if (static::$previewAssetsRegistered) {
            return;
        }

        static::$previewAssetsRegistered = true;

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): string => view('voodbuilder::filament.partials.menu-preview-assets')->render(),
        );
    }

    public function bootInteractsWithTree(): void
    {
        if (! $this->record instanceof NavigationMenu) {
            return;
        }

        $this->cacheTreeActions();
    }

    public function mount(int|string $record): void
    {
        static::registerPreviewAssets();

        parent::mount($record);

        $this->cacheTreeActions();
    }

    public function getMountedAction(?int $actionNestingIndex = null): ?Action
    {
        $action = parent::getMountedAction($actionNestingIndex);
        $arguments = $action?->getArguments() ?? [];
        $treeKey = $arguments['treeKey'] ?? null;

        /**
         * Some action mounts may miss the `tree: true` argument depending on how the
         * tree view renders/caches actions. If we have a node id, resolve the node
         * record directly to avoid falling back to the page record (NavigationMenu).
         */
        $nodeId = $arguments['nodeId'] ?? null;

        if ($action !== null && $nodeId !== null && method_exists($action, 'record')) {
            $treeConfig = $treeKey !== null
                ? $this->getCachedTreeByKey((string) $treeKey)
                : $this->getCachedTree();

            $nodeRecord = $treeConfig->getNodeRecord($nodeId);

            if ($nodeRecord !== null) {
                $action->record($nodeRecord);
            }
        } else {
            $this->injectNodeRecordIntoAction($action, $treeKey !== null ? (string) $treeKey : null);
        }

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
                Section::make(__('voodbuilder::admin.menu_preview.heading'))
                    ->description(__('voodbuilder::admin.menu_preview.description'))
                    ->schema([
                        View::make('voodbuilder::filament.navigation-menu-preview-inline')
                            ->viewData(fn (): array => [
                                'previewHtml' => NavigationMenuPreview::renderContent($this->record),
                                'previewKey' => $this->record->updated_at?->getTimestamp() ?? time(),
                                'menuName' => $this->record->name,
                            ]),
                    ])
                    ->collapsible(),
                $this->getFormContentComponent(),
                Section::make(__('Menu items'))
                    ->description(__('voodbuilder::admin.helpers.menu_tree'))
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
}
