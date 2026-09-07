/**
 * Voodbuilder menu tree: wider "nest inside" drop band + maxDepth-aware drag targets.
 * Based on solution-forest/filament-nestable-tree tree-view.js
 */
function resolveDropPosition(pct, targetDepth, maxDepth) {
    const canNestInside = maxDepth < 0 || targetDepth < maxDepth - 1

    if (!canNestInside) {
        return pct < 0.5 ? 'before' : 'after'
    }

    // Wider centre band (60%) so nesting does not require pixel-perfect aim.
    return pct < 0.2 ? 'before' : pct > 0.8 ? 'after' : 'inside'
}

export default function treeView(config = {}) {
    const defaults = {
        data: [],
        treeKey: null,
        idField: 'id',
        nameField: 'name',
        childrenField: 'children',
        expandedField: 'expanded',
        maxVisibleDepth: 4,
        maxDepth: -1,
        allowDragDrop: true,
        allowCrossCategory: true,
        highlightSearch: true,
        /**
         * When true, children are loaded on-demand via $wire.loadChildren()
         * the first time a folder node is expanded. Set via tree config.
         */
        asyncChildren: false,
        onNodeMove: () => {},
        onOrderChanged: () => {},
    }

    const cfg = { ...defaults, ...config }

    return {
        // State
        treeData: cfg.data || [],
        searchQuery: '',
        selectedNode: null,
        draggedNodeId: null,
        dropTargetId: null,
        dropPosition: 'inside',
        hasUnsavedOrder: false,
        rootDropZoneActive: false,
        dropLine: { visible: false, y: 0, left: 0 },
        /**
         * True when a drag originating in ANOTHER tree is over this tree.
         * Needed so the drop-line and root-drop-zone are shown even though
         * this tree's own draggedNodeId is null during cross-tree drags.
         */
        crossTreeDragging: false,
        /** Node currently loading async children */
        loadingNodeId: null,

        // Exposed to Alpine templates (options closure not accessible in template scope)
        idField: cfg.idField,
        nameField: cfg.nameField,
        allowDragDrop: cfg.allowDragDrop,
        /**
         * Exposed so nested x-data components (e.g. the node-actions panel)
         * can access the tree key without referencing the closure-only `cfg`.
         */
        treeKey: cfg.treeKey,

        // ---- lifecycle ----

        init() {
            // When the server-side resetTree() completes it dispatches a
            // 'tree-reset' Livewire event which is forwarded as a DOM event.
            // Listen for it so we can clear the unsaved-order indicator.
            this.$el.addEventListener('tree-reset', () => {
                this.hasUnsavedOrder = false
            })

            this.$el.addEventListener('tree-order-saved', () => {
                this.hasUnsavedOrder = false
            })

            // Hide drop-line and reset cross-tree flag when the drag fully
            // exits this tree's container (covers the case where dragLeave on
            // individual rows misses the exit, e.g. moving between columns).
            this.$el.addEventListener('dragleave', (event) => {
                if (!this.$el.contains(event.relatedTarget)) {
                    this.dropLine.visible = false
                    this.crossTreeDragging = false
                }
            })

            // Safety-net: capture-phase listener on the tree container fires
            // for dragend from ANY descendant, even if the source row was
            // detached/morphed by Livewire before the event bubbled.
            // (document-level dragend does NOT fire for detached elements.)
            this.$el.addEventListener(
                'dragend',
                () => {
                    if (this.draggedNodeId !== null || this.dropLine.visible) {
                        this.dragEnd()
                    }
                },
                true, // capture phase
            )

            // Global reset: when the SOURCE tree dispatches 'fi-tree-drag-ended'
            // every tree (including trees that were cross-drag targets) resets.
            // Using dispatchEvent from the source prevents calling dragEnd() on
            // trees that never knew about the drag and avoids infinite loops
            // because dragEnd() only dispatches when it IS the source.
            document.addEventListener('fi-tree-drag-ended', () => {
                this.dragEnd()
            })
        },

        // ---- computed ----

        /**
         * DFS-flattened list of visible nodes for single x-for rendering.
         * Each entry carries _depth, _parentId, _index, _hasChildren, _isExpanded, _matchesSearch.
         */
        get flattenedVisibleNodes() {
            const result = []
            const maxDepth = Math.max(1, cfg.maxVisibleDepth || 4)
            const query = this.searchQuery?.trim().toLowerCase()

            const hasDescendantMatch = (node) => {
                const children = node[cfg.childrenField] ?? []
                for (const child of children) {
                    if (
                        String(child[cfg.nameField] ?? '')
                            .toLowerCase()
                            .includes(query)
                    )
                        return true
                    if (hasDescendantMatch(child)) return true
                }
                return false
            }

            const walk = (nodes, depth, parentId) => {
                nodes.forEach((node, index) => {
                    // In async mode: a node is expandable until its children
                    // have been fetched (node._childrenLoaded is set by
                    // _asyncExpandNode after the first server call).
                    const hasChildren =
                        (node[cfg.childrenField]?.length ?? 0) > 0 ||
                        (cfg.asyncChildren && !node._childrenLoaded)
                    const matchesSearch =
                        !query ||
                        String(node[cfg.nameField] ?? '')
                            .toLowerCase()
                            .includes(query)
                    const descendantMatch =
                        !!query && hasChildren && hasDescendantMatch(node)

                    result.push({
                        ...node,
                        _depth: depth,
                        _parentId: parentId,
                        _index: index,
                        _hasChildren: hasChildren,
                        _isExpanded: node[cfg.expandedField] === true,
                        _matchesSearch: matchesSearch,
                        _hasDescendantMatch: descendantMatch,
                    })

                    if (
                        hasChildren &&
                        node[cfg.expandedField] === true &&
                        depth + 1 < maxDepth
                    ) {
                        walk(
                            node[cfg.childrenField],
                            depth + 1,
                            node[cfg.idField],
                        )
                    }
                })
            }

            walk(this.treeData, 0, null)
            return result
        },

        // ---- actions ----

        selectNode(id) {
            this.selectedNode = id
        },

        toggleNode(id) {
            const node = this._findById(this.treeData, id)
            if (!node) return

            const willExpand = node[cfg.expandedField] !== true

            // If async mode: load children from server on first expand.
            if (
                cfg.asyncChildren &&
                willExpand &&
                !(node[cfg.childrenField]?.length > 0) &&
                !node._childrenLoaded
            ) {
                this._asyncExpandNode(id, node)
                return
            }

            node[cfg.expandedField] = !node[cfg.expandedField]
        },

        /**
         * Async expand: call $wire.loadChildren() and inject the result.
         */
        async _asyncExpandNode(id, node) {
            this.loadingNodeId = String(id)
            try {
                const treeKey = cfg.treeKey ?? null
                const children = await this.$wire.call(
                    'loadChildren',
                    id,
                    treeKey,
                )
                node[cfg.childrenField] = Array.isArray(children)
                    ? children
                    : []
                node._childrenLoaded = true
                node[cfg.expandedField] = true
            } catch (e) {
                console.error('[fi-tree] loadChildren failed', e)
            } finally {
                this.loadingNodeId = null
            }
        },

        expandAll() {
            const walk = (nodes) => {
                nodes.forEach((node) => {
                    if (node[cfg.childrenField]?.length > 0) {
                        node[cfg.expandedField] = true
                        walk(node[cfg.childrenField])
                    }
                })
            }
            walk(this.treeData)
        },

        markOrderSaved() {
            this.hasUnsavedOrder = false
        },

        collapseAll() {
            const walk = (nodes) => {
                nodes.forEach((node) => {
                    node[cfg.expandedField] = false
                    if (node[cfg.childrenField]?.length > 0) {
                        walk(node[cfg.childrenField])
                    }
                })
            }
            walk(this.treeData)
        },

        get isAllExpanded() {
            const check = (nodes) => {
                return nodes.every((node) => {
                    if (!(node[cfg.childrenField]?.length > 0)) return true
                    return (
                        node[cfg.expandedField] === true &&
                        check(node[cfg.childrenField])
                    )
                })
            }
            return this.treeData.length > 0 && check(this.treeData)
        },

        get isAllCollapsed() {
            const check = (nodes) => {
                return nodes.every((node) => {
                    if (node[cfg.expandedField] === true) return false
                    if (node[cfg.childrenField]?.length > 0) {
                        return check(node[cfg.childrenField])
                    }
                    return true
                })
            }
            return check(this.treeData)
        },

        // ---- search ----

        formatNodeText(node) {
            const text = String(node[cfg.nameField] ?? '')
            const query = this.searchQuery?.trim()
            if (!cfg.highlightSearch || !query) return this._esc(text)

            const lq = query.toLowerCase()
            const lt = text.toLowerCase()
            let out = ''
            let pos = 0
            let idx
            while ((idx = lt.indexOf(lq, pos)) !== -1) {
                out += this._esc(text.slice(pos, idx))
                out += `<mark class="fi-tree-highlight">${this._esc(text.slice(idx, idx + lq.length))}</mark>`
                pos = idx + lq.length
            }
            return out + this._esc(text.slice(pos))
        },

        _esc(s) {
            return s
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
        },

        // ---- drag & drop ----

        dragStart(event, nodeId) {
            if (!cfg.allowDragDrop) return
            this.draggedNodeId = String(nodeId)
            event.dataTransfer.effectAllowed = 'move'
            event.dataTransfer.setData('text/plain', String(nodeId))

            // Share drag state globally so other tree instances on the same
            // page can detect and participate in cross-tree drag-and-drop.
            const nodeData = this._findById(this.treeData, nodeId)
            window.__fi_tree_dragging = {
                nodeId: String(nodeId),
                treeKey: cfg.treeKey,
                nodeData: nodeData
                    ? JSON.parse(JSON.stringify(nodeData))
                    : null,
            }
        },

        /**
         * Track drop position and update the floating drop-line indicator.
         * Y position is computed from the row's bounding rect relative to the
         * node-list container. For 'inside' drops the line is also indented to
         * the child depth so the user sees exactly where the node will land.
         *
         * @param {DragEvent} event
         * @param {number}    index    sibling index
         * @param {*}         parentId parent node id (null = root)
         * @param {*}         nodeId   id of the target row
         * @param {number}    depth    visual depth of the target row (0 = root)
         */
        dragOver(event, index, parentId, nodeId, depth = 0) {
            if (!cfg.allowDragDrop) return
            // Guard: bail out if no drag is actually in progress so a stale
            // dragover event cannot re-show the drop-line after dragEnd().
            if (!this.draggedNodeId && !window.__fi_tree_dragging) return
            // Track whether this is a cross-tree drag so the drop-line and
            // root-drop-zone remain visible even though draggedNodeId is null.
            this.crossTreeDragging = !this.draggedNodeId
            event.preventDefault()
            event.dataTransfer.dropEffect = 'move'

            const rowRect = event.currentTarget.getBoundingClientRect()
            const pct = (event.clientY - rowRect.top) / rowRect.height
            const position = resolveDropPosition(pct, depth, cfg.maxDepth)

            this.dropTargetId = String(nodeId)
            this.dropPosition = position

            // Compute drop-line position relative to the node-list container.
            // Y: offset ±4 px so the line centres in the gap between rows.
            // X: use depth * 16 (less indentation than the row padding).
            // For 'inside' drops the --drop-inside outline ring is the visual
            // cue; no drop-line needed.
            if (position === 'inside') {
                this.dropLine.visible = false
            } else {
                const listEl = event.currentTarget.closest('.fi-tree-node-list')
                if (listEl) {
                    const listRect = listEl.getBoundingClientRect()
                    const baseIndent = depth * 16
                    // Mutate properties — do NOT replace the object or Alpine
                    // loses reactive tracking and dragEnd() won't hide the line.
                    this.dropLine.visible = true
                    this.dropLine.y =
                        position === 'before'
                            ? rowRect.top - listRect.top - 4
                            : rowRect.bottom - listRect.top + 4
                    this.dropLine.left = baseIndent
                }
            }
        },

        dragLeave(event) {
            if (!cfg.allowDragDrop) return
            if (!event.currentTarget.contains(event.relatedTarget)) {
                this.dropTargetId = null
                this.dropLine.visible = false
            }
        },

        dragEnd() {
            // Remember whether THIS tree was the drag source before clearing
            // the global marker, so we can broadcast to other trees.
            const wasSource = window.__fi_tree_dragging?.treeKey === cfg.treeKey
            this.draggedNodeId = null
            this.dropTargetId = null
            this.dropPosition = 'inside'
            this.rootDropZoneActive = false
            this.crossTreeDragging = false
            // Mutate properties instead of replacing the object so Alpine's
            // reactive tracking on dropLine.visible is never lost.
            this.dropLine.visible = false
            this.dropLine.y = 0
            this.dropLine.left = 0
            // Only clear the global state when it belongs to THIS tree.
            if (window.__fi_tree_dragging?.treeKey === cfg.treeKey) {
                window.__fi_tree_dragging = null
            }
            // Broadcast to all other trees so they clear any cross-tree state
            // (e.g. drop-line was showing in Tree 2 when the drag ended outside
            // the tree container). Guard with wasSource to avoid infinite loops:
            // destination trees call dragEnd() from the event but wasSource is
            // false there so they never re-dispatch.
            if (wasSource) {
                document.dispatchEvent(new CustomEvent('fi-tree-drag-ended'))
            }
        },

        /**
         * Handle dragover on the root trailing drop zone.
         * Positions the drop-line after the last visible node row.
         */
        dragOverRoot(event) {
            if (!cfg.allowDragDrop) return
            if (!this.draggedNodeId && !window.__fi_tree_dragging) return
            this.crossTreeDragging = !this.draggedNodeId
            event.preventDefault()
            event.dataTransfer.dropEffect = 'move'
            this.rootDropZoneActive = true

            const listEl = event.currentTarget.parentElement
            if (listEl) {
                const listRect = listEl.getBoundingClientRect()
                const rows = listEl.querySelectorAll('.fi-tree-node-row')
                const y =
                    rows.length > 0
                        ? rows[rows.length - 1].getBoundingClientRect().bottom -
                          listRect.top
                        : 0
                // Mutate properties — do NOT replace the object or Alpine
                // loses reactive tracking and dragEnd() won't hide the line.
                this.dropLine.visible = true
                this.dropLine.y = y
                this.dropLine.left = 0
            }
        },

        /**
         * Drop handler for the root-level trailing drop zone.
         * Appends the dragged node as the last root item, giving users
         * an intuitive "drag downward to escape a subtree" affordance.
         * Also handles cross-tree drags.
         */
        dropAtRoot(event) {
            event.preventDefault()
            if (!cfg.allowDragDrop) return

            const globalDrag = window.__fi_tree_dragging
            const isCrossTree = !this.draggedNodeId && !!globalDrag
            const effectiveDraggedId =
                this.draggedNodeId ?? globalDrag?.nodeId ?? null
            if (!effectiveDraggedId) return

            let node
            if (isCrossTree) {
                // Reconstruct node from global drag data; strip children so it
                // starts as a leaf in the destination tree.
                node = globalDrag.nodeData
                    ? { ...globalDrag.nodeData, [cfg.childrenField]: [] }
                    : { [cfg.idField]: effectiveDraggedId }
            } else {
                node = this._removeById(this.treeData, effectiveDraggedId)
                if (!node) {
                    this.dragEnd()
                    return
                }
            }

            this.treeData.push(node)

            const moveDetails = {
                newParentId: null,
                newIndex: this.treeData.length - 1,
                position: 'after',
            }

            if (isCrossTree) {
                // Notify the server so it can remove the node from the source
                // tree and update any metadata (e.g. category_id).
                this.$wire.dispatch('tree-cross-move', {
                    fromTreeKey: globalDrag.treeKey,
                    toTreeKey: cfg.treeKey,
                    nodeId: effectiveDraggedId,
                    destinationParentId: null,
                })
                window.__fi_tree_dragging = null
            }

            cfg.onNodeMove(node, moveDetails)
            this.hasUnsavedOrder = true
            cfg.onOrderChanged(node, moveDetails)
            this.$el.dispatchEvent(
                new CustomEvent('tree-order-changed', {
                    bubbles: true,
                    detail: { node, ...moveDetails },
                }),
            )

            this.dragEnd()
        },

        drop(event, index, parentId, nodeId, depth = 0) {
            event.preventDefault()
            if (!cfg.allowDragDrop) return

            // Support cross-tree drag: local draggedNodeId is null when the
            // drag originated in a different Alpine/tree scope.
            const globalDrag = window.__fi_tree_dragging
            const isCrossTree = !this.draggedNodeId && !!globalDrag
            const effectiveDraggedId =
                this.draggedNodeId ?? globalDrag?.nodeId ?? null
            if (!effectiveDraggedId) return

            // Re-derive drop position from current mouse position so we never
            // rely on potentially-stale this.dropPosition (which was set by the
            // last dragOver and could be stale if dragLeave fired between them).
            const rect = event.currentTarget.getBoundingClientRect()
            const pct = (event.clientY - rect.top) / rect.height
            const dropPosition = resolveDropPosition(pct, depth, cfg.maxDepth)

            // Use the nodeId passed directly from the template instead of
            // this.dropTargetId, which dragLeave can clear before drop fires.
            const effectiveTargetId =
                nodeId !== undefined ? String(nodeId) : this.dropTargetId

            // Determine WHERE the node will be placed (its new parent):
            //   'inside'        → becomes a child of the target node
            //   'before'/'after' → becomes a sibling of the target (same parent)
            const destinationParentId =
                dropPosition === 'inside' ? effectiveTargetId : parentId

            // For same-tree drags apply the cross-category restriction.
            // Cross-tree drags are always permitted (the user chose to move
            // across trees deliberately).
            if (!isCrossTree) {
                const draggedRootId = this._getRootAncestorId(
                    this.treeData,
                    effectiveDraggedId,
                )
                const destinationRootId =
                    destinationParentId === null
                        ? null
                        : (this._getRootAncestorId(
                              this.treeData,
                              destinationParentId,
                          ) ?? String(destinationParentId))

                const isDraggedNodeRoot =
                    draggedRootId === null ||
                    draggedRootId === String(effectiveDraggedId)

                if (
                    !cfg.allowCrossCategory &&
                    !isDraggedNodeRoot &&
                    destinationRootId !== null &&
                    draggedRootId !== null &&
                    draggedRootId !== destinationRootId
                ) {
                    this.dragEnd()
                    return
                }
            }

            let node
            if (isCrossTree) {
                // Reconstruct node from global drag data; strip children so it
                // starts as a leaf in the destination tree.
                node = globalDrag.nodeData
                    ? { ...globalDrag.nodeData, [cfg.childrenField]: [] }
                    : { [cfg.idField]: effectiveDraggedId }
            } else {
                node = this._removeById(this.treeData, effectiveDraggedId)
                if (!node) {
                    this.dragEnd()
                    return
                }
            }

            if (dropPosition === 'inside') {
                const siblings =
                    parentId === null
                        ? this.treeData
                        : (this._findById(this.treeData, parentId)?.[
                              cfg.childrenField
                          ] ?? this.treeData)
                // Locate target by ID after removal to avoid stale-index issues
                const target =
                    (effectiveTargetId
                        ? this._findById(this.treeData, effectiveTargetId)
                        : null) ?? siblings[index]
                if (target) {
                    if (!target[cfg.childrenField])
                        target[cfg.childrenField] = []
                    target[cfg.childrenField].push(node)
                    target[cfg.expandedField] = true
                } else {
                    siblings.push(node)
                }
            } else {
                const siblings =
                    parentId === null
                        ? this.treeData
                        : (this._findById(this.treeData, parentId)?.[
                              cfg.childrenField
                          ] ?? this.treeData)
                // Re-resolve the target's current position after _removeById may
                // have shifted indices (e.g. dragged node was before target in
                // the same parent). Use effectiveTargetId (from the template,
                // not potentially-stale this.dropTargetId) for the lookup.
                const targetCurrentIndex = effectiveTargetId
                    ? siblings.findIndex(
                          (n) => String(n[cfg.idField]) === effectiveTargetId,
                      )
                    : -1
                const resolvedIndex =
                    targetCurrentIndex >= 0 ? targetCurrentIndex : index
                const at =
                    dropPosition === 'before'
                        ? resolvedIndex
                        : resolvedIndex + 1
                siblings.splice(Math.min(at, siblings.length), 0, node)
            }

            const moveDetails = {
                newParentId: destinationParentId,
                newIndex: index,
                position: dropPosition,
            }

            if (isCrossTree) {
                // Notify the server so it can remove the node from the source
                // tree and update any metadata (e.g. category_id).
                this.$wire.dispatch('tree-cross-move', {
                    fromTreeKey: globalDrag.treeKey,
                    toTreeKey: cfg.treeKey,
                    nodeId: effectiveDraggedId,
                    destinationParentId: destinationParentId,
                })
                window.__fi_tree_dragging = null
            }

            cfg.onNodeMove(node, moveDetails)

            // Update this.dropPosition to the freshly derived value so that
            // any downstream logic reading it (e.g. CSS indicators) is correct.
            this.dropPosition = dropPosition

            this.hasUnsavedOrder = true
            cfg.onOrderChanged(node, moveDetails)
            this.$el.dispatchEvent(
                new CustomEvent('tree-order-changed', {
                    bubbles: true,
                    detail: { node, ...moveDetails },
                }),
            )

            this.dragEnd()
        },

        // ---- helpers ----

        _findById(nodes, id) {
            for (const node of nodes) {
                if (String(node[cfg.idField]) === String(id)) return node
                if (node[cfg.childrenField]?.length) {
                    const found = this._findById(node[cfg.childrenField], id)
                    if (found) return found
                }
            }
            return null
        },

        _removeById(nodes, id) {
            for (let i = 0; i < nodes.length; i++) {
                if (String(nodes[i][cfg.idField]) === String(id))
                    return nodes.splice(i, 1)[0]
                if (nodes[i][cfg.childrenField]?.length) {
                    const found = this._removeById(
                        nodes[i][cfg.childrenField],
                        id,
                    )
                    if (found) return found
                }
            }
            return null
        },

        /**
         * Returns the idField value of the top-level (root) ancestor for a node
         * identified by `id`. Returns null if the node is not found.
         */
        _getRootAncestorId(nodes, id) {
            for (const rootNode of nodes) {
                if (String(rootNode[cfg.idField]) === String(id)) {
                    return String(rootNode[cfg.idField])
                }
                if (rootNode[cfg.childrenField]?.length) {
                    const found = this._findById(
                        rootNode[cfg.childrenField],
                        id,
                    )
                    if (found) return String(rootNode[cfg.idField])
                }
            }
            return null
        },
    }
}
