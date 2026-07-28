/**
 * Canvas toolbar action: cycle element content width on full-width pages/layouts.
 * Modes: full (edge-to-edge) → normal (80rem) → custom (layout content_max_width, if set).
 */

import { tablerIcon } from './editor-icons.js';

export const CONTENT_WIDTH_ATTR = 'data-voodbuilder-content-width';
export const CMD_CYCLE_CONTENT_WIDTH = 'voodbuilder-cycle-content-width';

export const CONTENT_WIDTH_FULL = 'full';
export const CONTENT_WIDTH_NORMAL = 'normal';
export const CONTENT_WIDTH_CUSTOM = 'custom';

export const STANDARD_CONTENT_MAX = '80rem';

const TOOLBAR_FLAG = 'data-voodbuilder-toolbar';

/**
 * @param {unknown} raw
 * @returns {{ mode: string, maxWidth: string|null, customMaxWidth: string|null }}
 */
export function normalizeEditorPageContentWidth(raw) {
    if (raw && typeof raw === 'object' && typeof raw.mode === 'string') {
        const mode = ['full', 'standard', 'custom', 'contained'].includes(raw.mode)
            ? (raw.mode === 'contained' ? 'standard' : raw.mode)
            : 'full';
        const maxWidth = typeof raw.maxWidth === 'string' && raw.maxWidth.trim() !== ''
            ? raw.maxWidth.trim()
            : (mode === 'full' ? null : STANDARD_CONTENT_MAX);
        const customMaxWidth = typeof raw.customMaxWidth === 'string' && raw.customMaxWidth.trim() !== ''
            ? raw.customMaxWidth.trim()
            : (mode === 'custom' && maxWidth ? maxWidth : null);

        return { mode, maxWidth, customMaxWidth };
    }

    return {
        mode: 'full',
        maxWidth: null,
        customMaxWidth: null,
    };
}

/**
 * @param {object} editor
 * @returns {{ mode: string, maxWidth: string|null, customMaxWidth: string|null }}
 */
export function getEditorPageContentWidth(editor) {
    return normalizeEditorPageContentWidth(editor?.__voodbuilderPageContentWidth);
}

/**
 * @param {object} editor
 * @returns {boolean}
 */
export function isFullWidthPageContext(editor) {
    const resolved = getEditorPageContentWidth(editor);

    if (resolved.mode === 'full') {
        return true;
    }

    return editor?.__voodbuilderFullWidthPage === true;
}

/**
 * Custom cycle step only when layout defines a distinct custom max width.
 *
 * @param {object} editor
 * @returns {string|null}
 */
export function resolveCustomContentMax(editor) {
    const { mode, maxWidth, customMaxWidth } = getEditorPageContentWidth(editor);
    const candidate = customMaxWidth
        || (mode === 'custom' && maxWidth ? maxWidth : null);

    if (! candidate || candidate === STANDARD_CONTENT_MAX) {
        return null;
    }

    return candidate;
}

/**
 * @param {object} editor
 * @returns {string[]}
 */
export function listContentWidthModes(editor) {
    const modes = [CONTENT_WIDTH_FULL, CONTENT_WIDTH_NORMAL];

    if (resolveCustomContentMax(editor)) {
        modes.push(CONTENT_WIDTH_CUSTOM);
    }

    return modes;
}

/**
 * @param {object} component
 * @param {object} editor
 * @returns {string}
 */
export function readComponentContentWidthMode(component, editor) {
    const raw = String(component?.getAttributes?.()?.[CONTENT_WIDTH_ATTR] ?? '').trim();

    if (raw === CONTENT_WIDTH_FULL || raw === CONTENT_WIDTH_NORMAL || raw === CONTENT_WIDTH_CUSTOM) {
        if (raw === CONTENT_WIDTH_CUSTOM && ! resolveCustomContentMax(editor)) {
            return CONTENT_WIDTH_NORMAL;
        }

        return raw;
    }

    const style = component?.getStyle?.() ?? {};
    const maxWidth = String(style.maxWidth ?? style['max-width'] ?? '').trim();

    if (maxWidth === STANDARD_CONTENT_MAX || maxWidth === '80rem') {
        return CONTENT_WIDTH_NORMAL;
    }

    const custom = resolveCustomContentMax(editor);

    if (custom && maxWidth === custom) {
        return CONTENT_WIDTH_CUSTOM;
    }

    const classes = component?.getClasses?.() ?? [];

    if (classes.includes('max-w-[80rem]') || classes.includes('max-w-[var(--width-vp-layout)]')) {
        // On a full page, --width-vp-layout is 100% — treat explicit 80rem class only.
        if (classes.includes('max-w-[80rem]')) {
            return CONTENT_WIDTH_NORMAL;
        }
    }

    return CONTENT_WIDTH_FULL;
}

/**
 * @param {string} mode
 * @param {object} editor
 * @returns {string}
 */
export function nextContentWidthMode(mode, editor) {
    const modes = listContentWidthModes(editor);
    const index = modes.indexOf(mode);
    const current = index >= 0 ? index : 0;

    return modes[(current + 1) % modes.length];
}

/**
 * Clear previous content-width inline measure without wiping unrelated styles.
 *
 * @param {object} component
 */
function clearContentWidthInlineStyles(component) {
    const style = { ...(component.getStyle?.() ?? {}) };
    delete style.maxWidth;
    delete style['max-width'];
    delete style.marginLeft;
    delete style['margin-left'];
    delete style.marginRight;
    delete style['margin-right'];
    delete style.marginInline;
    delete style['margin-inline'];
    delete style.width;

    // GrapesJS setStyle replaces; keep remaining keys.
    component.setStyle(style);

    // Ensure removed keys do not linger as empty strings in the model.
    component.removeStyle?.('max-width');
    component.removeStyle?.('maxWidth');
    component.removeStyle?.('margin-left');
    component.removeStyle?.('margin-right');
    component.removeStyle?.('margin-inline');
    component.removeStyle?.('width');
}

/**
 * Strip author utilities that would fight the content-width attr CSS.
 *
 * @param {object} component
 */
function stripConflictingWidthUtilities(component) {
    const classes = [...(component.getClasses?.() ?? [])].filter((name) => {
        const token = String(name);

        if (token === 'mx-auto' || token === 'max-w-none' || token === 'max-w-full') {
            return false;
        }

        if (/^(sm:|md:|lg:|xl:|2xl:)?max-w-/.test(token)) {
            return false;
        }

        return true;
    });

    component.setClass(classes);
}

/**
 * @param {object} component
 * @param {string} mode
 * @param {object} editor
 */
export function applyComponentContentWidth(component, mode, editor) {
    if (! component?.addAttributes) {
        return;
    }

    const next = mode === CONTENT_WIDTH_CUSTOM && ! resolveCustomContentMax(editor)
        ? CONTENT_WIDTH_NORMAL
        : mode;

    stripConflictingWidthUtilities(component);
    clearContentWidthInlineStyles(component);
    component.addAttributes({ [CONTENT_WIDTH_ATTR]: next });

    // Persist measure as inline style so published HTML works without relying only on CSS.
    if (next === CONTENT_WIDTH_NORMAL) {
        component.addStyle({
            width: '100%',
            'max-width': STANDARD_CONTENT_MAX,
            'margin-left': 'auto',
            'margin-right': 'auto',
        });
    } else if (next === CONTENT_WIDTH_CUSTOM) {
        const custom = resolveCustomContentMax(editor);
        if (custom) {
            component.addStyle({
                width: '100%',
                'max-width': custom,
                'margin-left': 'auto',
                'margin-right': 'auto',
            });
        }
    } else {
        component.addStyle({
            width: '100%',
            'max-width': 'none',
            'margin-left': '0',
            'margin-right': '0',
        });
    }
}

/**
 * @param {object} editor
 * @param {Record<string, string>} [labels]
 */
export function cycleSelectedContentWidth(editor, labels = {}) {
    const component = editor?.getSelected?.();

    if (! component || ! isFullWidthPageContext(editor)) {
        return;
    }

    const current = readComponentContentWidthMode(component, editor);
    const next = nextContentWidthMode(current, editor);

    applyComponentContentWidth(component, next, editor);
    ensureCanvasContentWidthToolbarState(editor, component, labels);

    const toast = contentWidthModeLabel(next, labels, editor);
    if (toast) {
        showContentWidthToast(toast);
    }
}

/**
 * @param {string} mode
 * @param {Record<string, string>} labels
 * @param {object} editor
 * @returns {string}
 */
export function contentWidthModeLabel(mode, labels = {}, editor = null) {
    if (mode === CONTENT_WIDTH_NORMAL) {
        return labels.contentWidthNormal ?? 'Normal (80rem)';
    }

    if (mode === CONTENT_WIDTH_CUSTOM) {
        const custom = resolveCustomContentMax(editor);
        const base = labels.contentWidthCustom ?? 'Custom';

        return custom ? `${base} (${custom})` : base;
    }

    return labels.contentWidthFull ?? 'Full width';
}

/**
 * @param {string} mode
 * @param {Record<string, string>} labels
 * @param {object} editor
 * @returns {string}
 */
export function contentWidthToolbarTitle(mode, labels = {}, editor = null) {
    const current = contentWidthModeLabel(mode, labels, editor);
    const next = contentWidthModeLabel(nextContentWidthMode(mode, editor), labels, editor);
    const prefix = labels.contentWidthTitle ?? 'Content width';

    return `${prefix}: ${current} → ${next}`;
}

function showContentWidthToast(message) {
    const toast = document.getElementById('voodbuilder-gjs-classes-toast')
        ?? Object.assign(document.createElement('div'), {
            id: 'voodbuilder-gjs-classes-toast',
            className: 'voodbuilder-gjs-classes-toast',
        });

    if (! toast.parentElement) {
        document.body.appendChild(toast);
    }

    toast.innerHTML = `<div class="voodbuilder-gjs-classes-toast__title">${message}</div>`;
    toast.hidden = false;
    window.clearTimeout(toast._hideTimer);
    toast._hideTimer = window.setTimeout(() => {
        toast.hidden = true;
    }, 1600);
}

/**
 * @param {object} component
 * @param {object} editor
 * @param {Record<string, string>} [labels]
 * @returns {object|null}
 */
export function buildContentWidthToolbarButton(component, editor, labels = {}) {
    if (! component || ! isFullWidthPageContext(editor)) {
        return null;
    }

    const mode = readComponentContentWidthMode(component, editor);

    return {
        attributes: {
            class: `voodbuilder-gjs-toolbar-item--content-width is-${mode}`,
            [TOOLBAR_FLAG]: 'content-width',
            title: contentWidthToolbarTitle(mode, labels, editor),
            'aria-label': contentWidthToolbarTitle(mode, labels, editor),
            'data-voodbuilder-content-width-mode': mode,
        },
        label: tablerIcon('arrow-autofit-width', 16),
        command: CMD_CYCLE_CONTENT_WIDTH,
    };
}

/**
 * @param {object} editor
 * @param {object} component
 * @param {Record<string, string>} [labels]
 */
export function ensureCanvasContentWidthToolbarState(editor, component, labels = {}) {
    const toolbarEl = editor?.Canvas?.getToolbarEl?.();

    if (! toolbarEl || ! component) {
        return;
    }

    const button = toolbarEl.querySelector(`[${TOOLBAR_FLAG}="content-width"]`);

    if (! button) {
        return;
    }

    const mode = readComponentContentWidthMode(component, editor);
    button.classList.remove('is-full', 'is-normal', 'is-custom');
    button.classList.add(`is-${mode}`);
    button.setAttribute('data-voodbuilder-content-width-mode', mode);
    const title = contentWidthToolbarTitle(mode, labels, editor);
    button.setAttribute('title', title);
    button.setAttribute('aria-label', title);
}

/**
 * @param {object} editor
 * @param {Record<string, string>} [labels]
 */
export function ensureContentWidthCommand(editor, labels = {}) {
    if (! editor?.Commands) {
        return;
    }

    const commands = editor.Commands;
    const hasCommand = (id) => (typeof commands.has === 'function'
        ? commands.has(id)
        : Boolean(commands.getAll?.()?.[id]));

    if (! hasCommand(CMD_CYCLE_CONTENT_WIDTH)) {
        commands.add(CMD_CYCLE_CONTENT_WIDTH, {
            run: (ed) => {
                cycleSelectedContentWidth(ed, ed.__voodbuilderLabels ?? labels);
            },
        });
    }
}
