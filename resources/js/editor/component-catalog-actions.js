/**
 * Save canvas selections to the component catalog (shared by sidebar, canvas, and layers menus).
 */

import { alertDialog, componentMetaDialog } from './editor-dialog.js';
import { editorApiHeaders } from './editor-api.js';
import {
    COMPONENT_ATTR,
    COMPONENT_SCOPE_ATTR,
    PROPS_ATTR,
} from './component-instance-type.js';
import { bakeSvgPaintForComponent, detachPrivateStyleClassesOntoId, syncPaintStylesForExport } from './tailwind-visual-style.js';
import {
    isChromeDropZoneComponent,
    isChromeLayoutContentSlotComponent,
    isPageContentSlotComponent,
} from './chrome-content-slot-utils.js';
import { isFooterBlock, isNavBlock } from './chrome/ids.js';
import { readBlockId } from './core/block-tree.js';

/**
 * Navbar/footer chrome blocks (and their zones) are layout structure — saving them
 * as catalog components produces unstyled “Live menu…” snapshots that steal settings
 * from the real header when re-inserted.
 *
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isChromeStructureCatalogSaveBlocked(component) {
    if (! component) {
        return false;
    }

    if (
        isChromeDropZoneComponent(component)
        || isChromeLayoutContentSlotComponent(component)
        || isPageContentSlotComponent(component)
    ) {
        return true;
    }

    if (component.get?.('type') === 'wrapper') {
        return true;
    }

    const blockId = readBlockId(component);

    if (
        blockId === 'site_header'
        || blockId.startsWith('site_nav_')
        || blockId.startsWith('site_footer_')
        || isNavBlock(blockId)
        || isFooterBlock(blockId)
    ) {
        return true;
    }

    try {
        const nested = component.find?.('[data-voodbuilder-block]') ?? [];

        for (const child of nested) {
            const nestedId = readBlockId(child);

            if (
                nestedId === 'site_header'
                || nestedId.startsWith('site_nav_')
                || nestedId.startsWith('site_footer_')
                || isNavBlock(nestedId)
                || isFooterBlock(nestedId)
            ) {
                return true;
            }
        }
    } catch {
        // Ignore find() failures on incomplete models.
    }

    if (component.getAttributes?.()?.['data-voodbuilder-editor-site-header']) {
        return true;
    }

    let current = component.parent?.();

    while (current && current.get?.('type') !== 'wrapper') {
        const parentId = readBlockId(current);

        if (
            parentId === 'site_header'
            || parentId.startsWith('site_nav_')
            || parentId.startsWith('site_footer_')
            || isNavBlock(parentId)
            || isFooterBlock(parentId)
            || current.getAttributes?.()?.['data-voodbuilder-editor-site-header']
        ) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

export function inferComponentProperties(component) {
    const properties = [];
    const seen = new Set();

    component.find('[data-voodbuilder-prop]').forEach((child) => {
        const id = child.getAttributes()['data-voodbuilder-prop'];

        if (! id || seen.has(id)) {
            return;
        }

        seen.add(id);
        properties.push({
            id,
            label: id,
            type: 'text',
            default: child.get('content') ?? '',
        });
    });

    return properties;
}

export function prepareComponentHtmlForSave(editor, component) {
    syncPaintStylesForExport(editor);
    bakeSvgPaintForComponent(editor, component);

    return component.toHTML({ keepInlineStyle: true });
}

export function duplicateCanvasComponent(component, editor = null) {
    if (! component?.parent?.()) {
        return null;
    }

    const parent = component.parent();
    const index = parent.components().indexOf(component);

    if (index < 0) {
        return null;
    }

    const ed = editor
        ?? component.em?.get?.('Editor')
        ?? component.em
        ?? null;

    // Prefer HTML round-trip over model.clone(): some custom types flatten to a
    // text-only sibling on clone(), which is undeletable / missing from Layers.
    let clone = null;
    const html = typeof component.toHTML === 'function'
        ? String(component.toHTML({ keepInlineStyle: true }) ?? '').trim()
        : '';

    if (html !== '' && typeof parent.append === 'function') {
        try {
            const added = parent.append(html, { at: index + 1 });
            clone = Array.isArray(added) ? added[0] : (added?.models?.[0] ?? added);
        } catch {
            clone = null;
        }
    }

    if (! clone) {
        clone = component.clone();
        parent.components().add(clone, { at: index + 1 });
    }

    if (! clone?.get) {
        return null;
    }

    clone.emit?.('change:parent');

    const attrs = clone.getAttributes?.() ?? {};

    if (
        attrs['data-voodbuilder-top-drop-spacer']
        || attrs['data-voodbuilder-bottom-drop-spacer']
        || attrs['data-voodbuilder-inner-drop']
        || String(clone.get?.('type') ?? '') === 'voodbuilder-top-drop-spacer'
        || String(clone.get?.('type') ?? '') === 'voodbuilder-bottom-drop-spacer'
        || String(clone.get?.('type') ?? '') === 'voodbuilder-inner-drop-slot'
    ) {
        clone.remove?.();

        return null;
    }

    clone.set({
        locked: false,
        removable: true,
        copyable: true,
        draggable: true,
        selectable: true,
        hoverable: true,
        highlightable: true,
        layerable: true,
    }, { silent: true });

    // HTML duplication copies GrapesJS private style classes (.c1234). Detach them
    // onto unique #id rules so styling the clone does not restyle the original.
    if (ed) {
        detachPrivateStyleClassesOntoId(ed, clone);
    }

    return clone;
}

export async function saveComponentToCatalog(editor, component, options = {}) {
    const {
        componentsUrl,
        csrf,
        labels = {},
        categories = [],
        uncategorizedLabel = 'General',
        onSaved,
    } = options;

    if (! component || ! componentsUrl) {
        return null;
    }

    if (isChromeStructureCatalogSaveBlocked(component)) {
        await alertDialog({
            message: labels.componentsSaveChromeBlocked
                ?? 'Navbar and footer are layout structure — configure them from the Content tab. Save a content section instead.',
            labels,
        });

        return null;
    }

    const meta = await componentMetaDialog({
        title: labels.componentsSaveAs ?? labels.componentsSave ?? 'Save selection as component',
        labels,
        categories,
        defaultCategory: uncategorizedLabel,
        namePlaceholder: labels.componentsNamePrompt ?? 'Component name',
        categoryLabel: labels.componentsCodeImportCategory ?? 'Category',
        confirmLabel: labels.dialogConfirm ?? 'OK',
    });

    if (! meta?.name?.trim()) {
        return null;
    }

    const html = prepareComponentHtmlForSave(editor, component);
    const properties = inferComponentProperties(component);

    const response = await fetch(componentsUrl.replace(/\/$/, ''), {
        method: 'POST',
        credentials: 'same-origin',
        headers: editorApiHeaders(csrf, { json: true }),
        body: JSON.stringify({
            name: meta.name.trim(),
            category: meta.category || null,
            html,
            properties,
        }),
    });

    if (! response.ok) {
        await alertDialog({
            message: labels.componentsSaveError ?? 'Could not save component.',
            labels,
        });

        return null;
    }

    const payload = await response.json();
    const saved = payload.component ?? null;

    if (saved) {
        component.addAttributes({
            [COMPONENT_ATTR]: String(saved.id),
            [COMPONENT_SCOPE_ATTR]: String(saved.id),
            [PROPS_ATTR]: JSON.stringify({}),
        });

        if (typeof onSaved === 'function') {
            onSaved(saved);
        }
    }

    return saved;
}
