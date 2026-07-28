<div
    x-load
    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('menu-tree-view', 'voodbuilder') }}"
    x-data="treeView({
        data: $wire.entangle('{{ $wireNodesProperty ?? 'nodes' }}'),
        treeKey: @js($treeKeyName ?? null),
        maxVisibleDepth: @js($treeConfig->getMaxVisibleDepth()),
        maxDepth: @js($treeConfig->getMaxDepth()),
        idField: @js($treeConfig->getRecordKeyField()),
        nameField: @js($treeConfig->getLabelField()),
        childrenField: @js($treeConfig->getChildrenField()),
        allowDragDrop: @js($allowDragDrop),
        allowCrossCategory: @js($allowCrossCategory),
        asyncChildren: @js($treeConfig->hasAsyncChildren()),
        highlightSearch: true,
        onNodeMove: (node, details) => {
            $wire.dispatch('tree-node-moved', { node: node, details: details });
        },
        onOrderChanged: (node, details) => {
            $wire.dispatch('tree-order-changed', { node: node, details: details });
        },
    })"
    @if ($lazy)
        x-init="$nextTick(() => $wire.loadTreeNodes({{ isset($treeKeyName) ? json_encode($treeKeyName) : 'null' }}))"
    @endif
    class="filament-nestable-tree"
>
    {{-- Toolbar (expand all / collapse all + any user-defined buttons) --}}
    @if (count($toolbarActions) > 0)
        <x-filament::actions 
            class="fi-tree-toolbar" 
            :actions="$toolbarActions" 
            fullWidth
        />
    @endif

    {{-- Search --}}
    @if ($isSearchable)
        <x-filament-nestable-tree::tree.search-bar />
    @endif

    {{-- Tree nodes rendered as flat list with depth-based indentation --}}
    <div class="fi-tree-node-list" style="position: relative;">

        <template x-for="(node, loopIndex) in flattenedVisibleNodes" :key="String(node[idField]) + '-' + loopIndex">
            <x-filament-nestable-tree::tree.node-row
                :allow-drag-drop="$allowDragDrop"
                :has-node-actions="$hasNodeActions"
                :tree-key-name="$treeKeyName ?? null"
            />
        </template>

        {{-- Trailing drop zone: drag any node here to make it the last root item --}}
        @if ($allowDragDrop)
            <div
                class="fi-tree-root-drop-zone"
                x-show="draggedNodeId !== null || crossTreeDragging"
                x-cloak
                @dragover="dragOverRoot($event)"
                @dragleave="if (!$el.contains($event.relatedTarget)) { rootDropZoneActive = false; dropLine.visible = false }"
                @drop.prevent="dropAtRoot($event)"
            ></div>
        @endif
    
        @if ($allowDragDrop)
        <div
            class="drop-line"
            x-show="dropLine.visible && (draggedNodeId !== null || crossTreeDragging)"
            x-cloak
            :style="{ top: dropLine.y + 'px', left: dropLine.left + 'px', width: 'calc(100% - ' + dropLine.left + 'px)' }"
        ></div>
        @endif

        <x-filament-nestable-tree::tree.empty-state />
    </div>

    <x-filament-actions::modals />
</div>