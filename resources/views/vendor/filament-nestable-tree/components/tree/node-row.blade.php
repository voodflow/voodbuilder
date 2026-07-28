@props([
    'allowDragDrop' => false,
    'hasNodeActions' => false,
    'treeKeyName' => null,
])

{{--
    Voodbuilder override of solution-forest/filament-nestable-tree node-row.
    Upstream ships an empty .fi-tree-drag-handle span (no SVG), so the grip is invisible.
--}}
<div
    {{
        $attributes->class([
            'fi-tree-node-row',
        ])
    }}
    :class="{
        'fi-tree-node-row--selected': selectedNode === node[idField],
        'fi-tree-node-row--dragging': draggedNodeId === String(node[idField]),
        'fi-tree-node-row--drop-inside': dropTargetId === String(node[idField]) && dropPosition === 'inside',
        'fi-tree-node-row--drop-before': dropTargetId === String(node[idField]) && dropPosition === 'before',
        'fi-tree-node-row--drop-after': dropTargetId === String(node[idField]) && dropPosition === 'after',
        'fi-tree-node-row--nest-target': draggedNodeId && draggedNodeId !== String(node[idField]) && node._depth === 0,
        'fi-tree-node-row--has-descendant-match': node._hasDescendantMatch && !node._isExpanded && searchQuery.trim(),
        'fi-tree-node-row--loading': loadingNodeId === String(node[idField]),
        'fi-tree-node-row--nested': node._depth > 0,
    }"
    :style="'padding-left: ' + ((node._depth * 20) + 12) + 'px'"
    :draggable="allowDragDrop ? 'true' : 'false'"
    @dragstart="dragStart($event, node[idField])"
    @dragover="dragOver($event, node._index, node._parentId, node[idField], node._depth)"
    @dragleave="dragLeave($event)"
    @drop="drop($event, node._index, node._parentId, node[idField], node._depth)"
    @dragend="dragEnd()"
    @click="selectNode(node[idField])"
>
    {{-- Expand / Collapse toggle --}}
    <button
        type="button"
        class="fi-tree-node-toggle"
        :class="{ 'fi-tree-node-toggle--hidden': !node._hasChildren }"
        @click.stop="toggleNode(node[idField])"
    >
        <svg
            class="fi-tree-toggle-icon fi-tree-toggle-icon--loading"
            x-show="loadingNodeId === String(node[idField])"
            x-cloak
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <svg
            class="fi-tree-toggle-icon"
            x-show="loadingNodeId !== String(node[idField])"
            :class="{ 'fi-tree-toggle-icon--expanded': node._isExpanded }"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            fill="currentColor"
            aria-hidden="true"
        >
            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
        </svg>
    </button>

    {{-- Drag handle (Bars2 — same cue as Filament table reorder) --}}
    @if ($allowDragDrop)
        <span
            class="fi-tree-drag-handle"
            title="{{ __('voodbuilder::admin.helpers.menu_tree_drag') }}"
            aria-label="{{ __('voodbuilder::admin.helpers.menu_tree_drag') }}"
            role="img"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 20 20"
                fill="currentColor"
                aria-hidden="true"
            >
                <path fill-rule="evenodd" d="M2 6.75A.75.75 0 012.75 6h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 6.75zm0 6.5a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
            </svg>
        </span>
    @endif

    {{-- Node label + type hint --}}
    <span class="fi-tree-node-content">
        <span
            class="fi-tree-node-label"
            :class="{ 'fi-tree-node-label--match': node._matchesSearch && searchQuery }"
            x-html="formatNodeText(node)"
        ></span>
        <span
            class="fi-tree-node-type"
            x-show="node.type_label"
            x-text="node.type_label"
            x-cloak
        ></span>
    </span>

    {{-- Nest-inside hint (shown while dragging over a top-level row) --}}
    @if ($allowDragDrop)
        <span
            class="fi-tree-nest-hint"
            x-show="dropTargetId === String(node[idField]) && dropPosition === 'inside' && node._depth === 0"
            x-cloak
        >
            {{ __('voodbuilder::admin.helpers.menu_tree_nest_here') }}
        </span>
    @endif

    {{-- Actions --}}
    @if ($hasNodeActions)
        <div
            class="fi-tree-node-actions"
            @click.stop
            x-data="{
                actions: null,
                loading: false,
                async fetchActions() {
                    if (this.actions !== null) return;
                    this.loading = true;
                    try {
                        this.actions = await $wire.call('loadTreeNodeActions', node[idField], @js($treeKeyName));
                    } finally {
                        this.loading = false;
                    }
                },
                init () {
                    $nextTick(async () => {
                        await this.fetchActions();
                    })
                }
            }"
        >
            <svg
                x-show="loading"
                class="fi-tree-node-action-spinner animate-spin h-3 w-3 text-gray-400 dark:text-gray-500"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                aria-hidden="true"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.961 3 8.118l3-2.827z" />
            </svg>

            <template x-if="actions !== null && !loading">
                <x-filament::actions class="fi-tree-node-actions-list">
                    <template x-for="(action, idx) in actions" :key="idx">
                        <div x-html="action" class="fi-tree-node-action-item"></div>
                    </template>
                </x-filament::actions>
            </template>
        </div>
    @endif
</div>
