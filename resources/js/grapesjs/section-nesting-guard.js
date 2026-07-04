/**
 * Prevent catalog section blocks (Gallery, Articles, …) from nesting inside each other.
 * Nested sections export correctly in the editor but break layout on the live page.
 */

export function isCatalogSection(component) {
    if (! component?.get) {
        return false;
    }

    const tag = String(component.get('tagName') ?? '').toLowerCase();

    if (tag !== 'section') {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};

    return Boolean(attrs['data-voodbuilder-section-block'])
        || component.get('type') === 'voodbuilder-section';
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

function draggedComponentContainsCatalogSection(component) {
    if (! component?.get) {
        return false;
    }

    if (isCatalogSection(component)) {
        return true;
    }

    return (component.find?.('section[data-voodbuilder-section-block]') ?? []).length > 0
        || (component.find?.('section') ?? []).some((section) => isCatalogSection(section));
}

export function configureSectionDropTarget(section) {
    if (! isCatalogSection(section) || section.get('_voodbuilderSectionDropBound')) {
        return;
    }

    section.set('_voodbuilderSectionDropBound', true);
    section.set('droppable', (srcComponent) => ! draggedComponentContainsCatalogSection(srcComponent));
}

function bindAllSectionDropTargets(editor) {
    editor.getWrapper?.()?.find?.('section')?.forEach?.((section) => {
        configureSectionDropTarget(section);
    });
}

function guardNestedSection(editor, component) {
    if (! component) {
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

        editor.getWrapper?.()?.find?.('section[data-voodbuilder-section-block]')?.forEach?.((section) => {
            promoteNestedCatalogSection(editor, section);
        });
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
    if (isCatalogSection(component)) {
        return component;
    }

    const nested = component.find?.('section[data-voodbuilder-section-block]')?.[0]
        ?? component.find?.('section')?.find?.((section) => isCatalogSection(section));

    return nested ?? null;
}
