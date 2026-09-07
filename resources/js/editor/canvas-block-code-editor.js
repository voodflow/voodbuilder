/**
 * Canvas toolbar "Edit code" — live HTML/CSS modal for static page blocks.
 */

import { prepareComponentHtmlForSave } from './component-catalog-actions.js';
import { openCanvasBlockCodeEditorDialog } from './component-code-import.js';
import { COMPONENT_ATTR } from './component-instance-type.js';
import { bakeSvgPaintForComponent, syncPaintStylesForExport } from './tailwind-visual-style.js';
import { shouldSuppressChromeSlotInspector } from './chrome-content-slot-utils.js';

export const CMD_EDIT_BLOCK_CODE = 'voodbuilder:edit-block-code';

const BLOCKED_TYPES = new Set([
    'voodbuilder-dynamic',
    'voodbuilder-code-block',
    'voodbuilder-reading-time',
    'voodbuilder-bound',
    'voodbuilder-bound-image',
    'voodbuilder-nav-menu-button',
    'voodbuilder-chrome-button',
    'voodbuilder-chrome-content-slot',
]);

const BLOCKED_BLOCK_IDS = new Set([
    'site_header',
    'site_nav_simple',
    'site_footer_columns',
    'landing_navbar',
]);

function componentAttributes(component) {
    return component?.getAttributes?.() ?? {};
}

function isBlockedComponent(component, editor = null) {
    if (! component) {
        return true;
    }

    if (editor && shouldSuppressChromeSlotInspector(component, editor)) {
        return true;
    }

    const type = String(component.get?.('type') ?? '');

    if (BLOCKED_TYPES.has(type)) {
        return true;
    }

    const attrs = componentAttributes(component);
    const blockId = String(attrs['data-voodbuilder-block'] ?? '');

    if (blockId !== '' && BLOCKED_BLOCK_IDS.has(blockId)) {
        return true;
    }

    if (blockId !== '') {
        return true;
    }

    if (attrs['data-voodbuilder-form'] != null) {
        return true;
    }

    return false;
}

function isCodeEditableRoot(component, editor) {
    if (! component || component === editor.getWrapper?.()) {
        return false;
    }

    const tag = String(component.get?.('tagName') ?? '').toLowerCase();
    const attrs = componentAttributes(component);
    const parent = component.parent?.();
    const isTopLevel = parent === editor.getWrapper?.();

    if (attrs['data-voodbuilder-content-slot'] && ! attrs['data-voodbuilder-page-content']) {
        return false;
    }

    return tag === 'section'
        || attrs[COMPONENT_ATTR] != null
        || attrs['data-voodbuilder-section-block'] != null
        || String(attrs.class ?? '').includes('voodbuilder-pasted-component')
        || isTopLevel;
}

export function resolveCodeEditableRoot(component, editor) {
    if (! component || ! editor?.getWrapper) {
        return null;
    }

    let current = component;
    let candidate = null;
    const wrapper = editor.getWrapper();

    while (current && current !== wrapper) {
        if (! isBlockedComponent(current, editor) && isCodeEditableRoot(current, editor)) {
            candidate = current;
        }

        current = current.parent?.();
    }

    return candidate;
}

function hasProtectedDescendant(component) {
    if (! component?.find) {
        return false;
    }

    return component.find('[data-voodbuilder-block], [data-voodbuilder-form]').length > 0;
}

export function canEditBlockCode(component, editor) {
    const root = resolveCodeEditableRoot(component, editor);

    if (! root) {
        return false;
    }

    return ! hasProtectedDescendant(root);
}

export function extractBlockCodeHtml(editor, component) {
    syncPaintStylesForExport(editor);
    bakeSvgPaintForComponent(editor, component);

    return prepareComponentHtmlForSave(editor, component);
}

export function applyBlockCodeHtml(editor, component, html, css = '') {
    const trimmedHtml = String(html ?? '').trim();
    const trimmedCss = String(css ?? '').trim();

    if (trimmedHtml === '' && trimmedCss === '') {
        return;
    }

    const doc = typeof DOMParser !== 'undefined'
        ? new DOMParser().parseFromString(trimmedHtml || '<div></div>', 'text/html')
        : null;
    const roots = doc ? [...doc.body.children] : [];
    const componentTag = String(component.get('tagName') ?? '').toLowerCase();

    const wrapWithOptionalCss = (innerHtml) => {
        if (! trimmedCss) {
            return innerHtml;
        }

        return `<style data-voodbuilder-block-css>${trimmedCss}</style>${innerHtml}`;
    };

    if (roots.length === 1) {
        const root = roots[0];
        const rootTag = root.tagName.toLowerCase();

        if (componentTag === rootTag) {
            const attrs = {};

            for (const attr of root.attributes) {
                attrs[attr.name] = attr.value;
            }

            component.addAttributes(attrs);
            component.components(wrapWithOptionalCss(root.innerHTML));

            return;
        }
    }

    component.components(wrapWithOptionalCss(trimmedHtml));
}

export function registerCanvasBlockCodeEditor(editor, options = {}) {
    if (editor.__voodbuilderCanvasBlockCodeRegistered) {
        return;
    }

    editor.__voodbuilderCanvasBlockCodeRegistered = true;
    editor.__voodbuilderCanvasBlockCodeOptions = options;

    editor.Commands.add(CMD_EDIT_BLOCK_CODE, {
        run(ed) {
            const selected = ed.getSelected();
            const root = resolveCodeEditableRoot(selected, ed);

            if (! root || ! canEditBlockCode(selected, ed)) {
                return;
            }

            const opts = ed.__voodbuilderCanvasBlockCodeOptions ?? options;

            if (! opts.componentsUrl) {
                return;
            }

            void openCanvasBlockCodeEditorDialog({
                componentsUrl: opts.componentsUrl,
                csrf: opts.csrf,
                labels: opts.labels ?? {},
                canvasStyles: opts.canvasStyles ?? [],
                editor: ed,
                initialHtml: extractBlockCodeHtml(ed, root),
                onApply: ({ html, css }) => {
                    applyBlockCodeHtml(ed, root, html, css);
                    ed.select(root);
                    ed.trigger('update');
                    ed.__voodbuilderInvalidatePageCss?.();
                },
            });
        },
    });
}
