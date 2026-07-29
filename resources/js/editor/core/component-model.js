/**
 * Safe helpers for Editor component model operations (move / index).
 */

export function isValidGrapesComponent(component) {
    return Boolean(
        component
        && typeof component.get === 'function'
        && ! component.isRemoved?.(),
    );
}

export function canMoveGrapesComponent(component) {
    return isValidGrapesComponent(component) && typeof component.move === 'function';
}

export function canIndexGrapesComponent(component) {
    return isValidGrapesComponent(component) && typeof component.index === 'function';
}

export function isValidMoveTarget(parent) {
    return isValidGrapesComponent(parent) && typeof parent.components === 'function';
}

export function safeComponentIndex(component, fallback = -1) {
    if (! canIndexGrapesComponent(component)) {
        return fallback;
    }

    try {
        const index = component.index();

        return Number.isFinite(index) ? index : fallback;
    } catch {
        return fallback;
    }
}

export function safeComponentMove(component, parent, options = {}) {
    if (! canMoveGrapesComponent(component) || ! isValidMoveTarget(parent)) {
        return false;
    }

    try {
        component.move(parent, options);

        return true;
    } catch {
        return false;
    }
}

export function safeMoveToEnd(component, parent) {
    if (! isValidMoveTarget(parent)) {
        return false;
    }

    return safeComponentMove(component, parent, { at: parent.components().length });
}

export function safeReorderComponent(component, parent, at) {
    if (! Number.isFinite(at) || at < 0) {
        return false;
    }

    if (safeComponentIndex(component) === at) {
        return true;
    }

    return safeComponentMove(component, parent, { at });
}

function detachInvalidChild(collection, child, index) {
    if (! collection) {
        return;
    }

    if (child && typeof collection.remove === 'function') {
        try {
            collection.remove(child);

            return;
        } catch {
            // Fall back to direct model compaction below.
        }
    }

    const models = collection.models;

    if (! Array.isArray(models) || index < 0 || index >= models.length) {
        return;
    }

    models.splice(index, 1);
}

function compactInvalidChildren(parent) {
    if (! isValidMoveTarget(parent)) {
        return;
    }

    const collection = parent.components();
    const models = collection?.models;

    if (! Array.isArray(models) || models.length === 0) {
        return;
    }

    for (let index = models.length - 1; index >= 0; index -= 1) {
        const child = models[index];

        if (isValidGrapesComponent(child)) {
            compactInvalidChildren(child);

            continue;
        }

        detachInvalidChild(collection, child, index);
    }
}

export function hasInvalidLayerChildren(parent) {
    if (! isValidMoveTarget(parent)) {
        return false;
    }

    const models = parent.components()?.models;

    if (! Array.isArray(models)) {
        return false;
    }

    if (models.some((child) => child == null || ! isValidGrapesComponent(child))) {
        return true;
    }

    for (const child of models) {
        if (hasInvalidLayerChildren(child)) {
            return true;
        }
    }

    return false;
}

export function sanitizeComponentTreeForLayers(root) {
    if (! isValidGrapesComponent(root)) {
        return;
    }

    compactInvalidChildren(root);
}

/**
 * @param {object|null|undefined} editor
 */
export function sanitizeEditorLayerTree(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! isValidGrapesComponent(wrapper)) {
        return;
    }

    sanitizeComponentTreeForLayers(wrapper);
}

/**
 * Wrap Layers.render so invalid collection entries are purged before Editor builds views.
 *
 * @param {object} editor
 */
/**
 * Editor Layers.render() reuses component.viewLayer without remove(), so the
 * shared sorter keeps a detached container and native layer DnD silently dies.
 */
function clearStaleLayerViews(component) {
    if (! component) {
        return;
    }

    if (component.viewLayer) {
        try {
            component.viewLayer.__clearItems?.();
        } catch {
            // ignore
        }

        delete component.viewLayer;
    }

    const children = component.components?.();

    if (! children) {
        return;
    }

    const list = typeof children.forEach === 'function'
        ? children
        : (children.models ?? children);

    if (typeof list.forEach === 'function') {
        list.forEach((child) => clearStaleLayerViews(child));
    }
}

export function guardEditorLayersRender(editor) {
    if (editor.__voodbuilderLayersRenderGuarded || ! editor.Layers?.render) {
        return;
    }

    editor.__voodbuilderLayersRenderGuarded = true;

    const originalRender = editor.Layers.render.bind(editor.Layers);

    editor.Layers.render = (...args) => {
        sanitizeEditorLayerTree(editor);

        const wrapper = editor.getWrapper?.();

        if (hasInvalidLayerChildren(wrapper)) {
            sanitizeComponentTreeForLayers(wrapper);
        }

        if (hasInvalidLayerChildren(wrapper)) {
            return false;
        }

        clearStaleLayerViews(wrapper);

        try {
            return originalRender(...args);
        } catch {
            return false;
        }
    };
}
