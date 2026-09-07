/**
 * Prevent catalog section blocks (Gallery, Articles, …) from nesting inside each other.
 * Nested sections export correctly in the editor but break layout on the live page.
 */

import { forEachGrapesComponent, safeFindComponents } from './tailwind-visual-style.js';

export function isCatalogSection(component) {
    if (! component?.get) {
        return false;
    }

    const tag = String(component.get('tagName') ?? '').toLowerCase();

    if (tag !== 'section') {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};

    // Bricks-like Layout Section is a nest host, not a catalog section block.
    if (attrs['data-voodbuilder-layout'] === 'section') {
        return false;
    }

    const type = String(component.get('type') ?? '');

    return Boolean(attrs['data-voodbuilder-section-block'])
        || type === 'voodbuilder-logo-grid'
        || type === 'voodbuilder-logo-split'
        || type === 'voodbuilder-animated-stats'
        || type === 'voodbuilder-animated-cta';
}

export function findHostingCatalogSection(component) {
    let current = component?.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        if (isCatalogSection(current)) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

export function promoteNestedCatalogSection(editor, component) {
    if (! isCatalogSection(component)) {
        return false;
    }

    const hostSection = findHostingCatalogSection(component);

    if (! hostSection) {
        return false;
    }

    const parent = hostSection.parent?.();

    if (! parent) {
        return false;
    }

    const insertAt = parent.components().indexOf(hostSection) + 1;

    if (parent.components().indexOf(component) === insertAt - 1) {
        return false;
    }

    component.move(parent, { at: insertAt });
    editor.select?.(component);

    return true;
}

function findCatalogSectionInTree(component) {
    if (isCatalogSection(component)) {
        return component;
    }

    let found = null;

    forEachGrapesComponent(component, (child) => {
        if (found || ! isCatalogSection(child)) {
            return;
        }

        found = child;
    });

    return found;
}

function draggedComponentContainsCatalogSection(component) {
    if (! component?.get) {
        return false;
    }

    return Boolean(findCatalogSectionInTree(component));
}

function draggedComponentIsSectionLike(srcComponent) {
    if (! srcComponent?.get) {
        return false;
    }

    if (isCatalogSection(srcComponent)) {
        return true;
    }

    const tag = String(srcComponent.get('tagName') ?? '').toLowerCase();

    if (tag === 'section') {
        return true;
    }

    const attrs = srcComponent.getAttributes?.() ?? {};

    if (attrs['data-voodbuilder-section-block']) {
        return true;
    }

    if (attrs['data-voodbuilder-layout'] === 'section') {
        return true;
    }

    return draggedComponentContainsCatalogSection(srcComponent);
}

export function configureSectionDropTarget(section) {
    if (! isCatalogSection(section) || section.get('_voodbuilderSectionDropBound')) {
        return;
    }

    section.set('_voodbuilderSectionDropBound', true);
    section.set('droppable', (srcComponent) => ! draggedComponentIsSectionLike(srcComponent));
}

function bindAllSectionDropTargets(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    for (const section of safeFindComponents(wrapper, 'section')) {
        configureSectionDropTarget(section);
    }
}

function guardNestedSection(editor, component) {
    if (! component) {
        return;
    }

    // Layers-panel reorder is author-driven — do not auto-promote mid/after tree DnD.
    if (
        editor.__voodbuilderLayerTreeSorting
        || Date.now() < Number(editor.__voodbuilderLayerTreeSortingUntil ?? 0)
    ) {
        return;
    }

    configureSectionDropTarget(component);

    if (promoteNestedCatalogSection(editor, component)) {
        window.requestAnimationFrame(() => {
            bindAllSectionDropTargets(editor);
        });
    }
}

export function registerSectionNestingGuard(editor) {
    if (! editor || editor.__voodbuilderSectionNestingGuardBound) {
        return;
    }

    editor.__voodbuilderSectionNestingGuardBound = true;

    editor.on('load', () => {
        bindAllSectionDropTargets(editor);

        for (const section of safeFindComponents(editor.getWrapper?.(), 'section[data-voodbuilder-section-block]')) {
            promoteNestedCatalogSection(editor, section);
        }
    });

    editor.on('component:add', (component) => {
        window.requestAnimationFrame(() => {
            guardNestedSection(editor, findAddedCatalogSection(component) ?? component);
        });
    });

    editor.on('block:drag:stop', (component) => {
        if (! component) {
            return;
        }

        window.requestAnimationFrame(() => {
            guardNestedSection(editor, findAddedCatalogSection(component) ?? component);
        });
    });

    editor.on('sorter:drag:end', ({ target }) => {
        if (! target) {
            return;
        }

        window.requestAnimationFrame(() => {
            guardNestedSection(editor, findAddedCatalogSection(target) ?? target);
        });
    });
}

function findAddedCatalogSection(component) {
    return findCatalogSectionInTree(component);
}
