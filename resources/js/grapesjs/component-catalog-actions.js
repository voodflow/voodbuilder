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
import { bakeSvgPaintForComponent, syncPaintStylesForExport } from './tailwind-visual-style.js';

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

export function duplicateCanvasComponent(component) {
    if (! component?.parent?.()) {
        return null;
    }

    const parent = component.parent();
    const index = parent.components().indexOf(component);
    const clone = component.clone();
    parent.components().add(clone, { at: index + 1 });
    clone.emit?.('change:parent');

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

    const meta = await componentMetaDialog({
        title: labels.componentsSave ?? 'Save selection as component',
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
