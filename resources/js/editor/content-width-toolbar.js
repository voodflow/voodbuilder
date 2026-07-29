/**
 * Canvas toolbar action: cycle element content width on full-width pages/layouts.
 * Modes: full (edge-to-edge) → normal (80rem) → custom (layout content_max_width, if set).
 *
 * Product formula: page full → section always full → first content child full|normal|custom.
 * Toolbar shows on section (1st level) and its content wrapper (2nd level).
 */

import { isFooterBlock, isNavBlock, isHeaderBlock } from './chrome/ids.js';
import { tablerIcon } from './editor-icons.js';
import { isLayoutContainer, isLayoutSection, LAYOUT_ATTR } from './layout-blocks.js';

export const CONTENT_WIDTH_ATTR = 'data-voodbuilder-content-width';
export const CMD_CYCLE_CONTENT_WIDTH = 'voodbuilder-cycle-content-width';

export const CONTENT_WIDTH_FULL = 'full';
export const CONTENT_WIDTH_NORMAL = 'normal';
export const CONTENT_WIDTH_CUSTOM = 'custom';

export const STANDARD_CONTENT_MAX = '80rem';

const TOOLBAR_FLAG = 'data-voodbuilder-toolbar';
const HERO_MEDIA_CLASS = 'voodbuilder-hero-media';
const CONTAINER_CLASS = 'voodbuilder-editor-container';
const SECTION_CLASS = 'voodbuilder-editor-section';
const LEAF_TAGS = new Set(['p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'span', 'a', 'button', 'img', 'svg', 'path', 'ul', 'ol', 'li', 'input', 'textarea', 'label', 'br', 'hr', 'i', 'em', 'strong', 'small', 'code', 'pre']);

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
 * Show element content-width controls when the page can host full-bleed
 * sections with boxed children (landing / full chrome / full content).
 *
 * Also reads canvas document attrs as fallback when editor props are stale
 * (common on ?edit=1 until the iframe finishes revealCanvasDocument).
 *
 * @param {object} editor
 * @returns {boolean}
 */
export function isFullWidthPageContext(editor) {
    if (editor?.__voodbuilderPopupMode === true) {
        return false;
    }

    if (editor?.__voodbuilderFullWidthPage === true) {
        return true;
    }

    const resolved = getEditorPageContentWidth(editor);

    if (resolved.mode === 'full') {
        return true;
    }

    if ((editor?.__voodbuilderChromeWidth ?? null) === 'full') {
        return true;
    }

    try {
        const doc = editor?.Canvas?.getDocument?.();
        const root = doc?.documentElement;
        const body = doc?.body;
        const pageWidth = root?.dataset?.voodbuilderPageWidth
            || root?.dataset?.voodbuilderCanvasContentWidth
            || body?.dataset?.voodbuilderCanvasContentWidth
            || body?.dataset?.voodbuilderPageWidth
            || '';
        const chromeWidth = root?.dataset?.voodbuilderChromeWidth
            || body?.dataset?.voodbuilderChromeWidth
            || '';

        if (pageWidth === 'full' || chromeWidth === 'full') {
            return true;
        }
    } catch {
        // Canvas not ready yet.
    }

    // Page/layout editors default to allowing the cycle (Full ↔ Normal).
    // Contained-only hosts should set chromeWidth=content + non-full page width.
    return (editor?.__voodbuilderChromeWidth ?? 'full') !== 'content'
        || resolved.mode !== 'standard';
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

    // Editor setStyle replaces; keep remaining keys.
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
 * @returns {Record<string, string>}
 */
function componentAttrs(component) {
    return component?.getAttributes?.() ?? {};
}

/**
 * @param {object} component
 * @returns {string[]}
 */
function componentClasses(component) {
    return component?.getClasses?.() ?? [];
}

/**
 * @param {object} component
 * @returns {object[]}
 */
function componentChildren(component) {
    if (! component?.components) {
        return [];
    }

    return [...(component.components()?.models ?? component.components() ?? [])];
}

/**
 * @param {object} component
 * @returns {boolean}
 */
function isHeroMediaLayer(component) {
    const classes = componentClasses(component);
    const role = String(componentAttrs(component)['data-voodbuilder-role'] ?? '').trim();

    return classes.includes(HERO_MEDIA_CLASS)
        || role === 'media'
        || role === 'shade';
}

/**
 * @param {object} component
 * @returns {boolean}
 */
function isDecorativeSectionChild(component) {
    if (isHeroMediaLayer(component)) {
        return true;
    }

    const attrs = componentAttrs(component);
    const classes = componentClasses(component);
    const tag = String(component?.get?.('tagName') ?? '').toLowerCase();

    if (attrs['aria-hidden'] === 'true' && (classes.includes('absolute') || classes.includes('pointer-events-none'))) {
        return true;
    }

    if (tag === 'iframe' || tag === 'video' || tag === 'picture') {
        return classes.includes('absolute') || Boolean(attrs['aria-hidden']);
    }

    return false;
}

/**
 * @param {object} component
 * @returns {boolean}
 */
export function isContentWidthSection(component) {
    return isLayoutSection(component)
        || componentClasses(component).includes(SECTION_CLASS);
}

/**
 * @param {object} component
 * @returns {boolean}
 */
export function isContentWidthContainer(component) {
    return isLayoutContainer(component)
        || componentClasses(component).includes(CONTAINER_CLASS)
        || String(componentAttrs(component)['data-voodbuilder-role'] ?? '') === 'content';
}

/**
 * @param {object} component
 * @returns {boolean}
 */
function isChromeNavOrFooterTree(component) {
    let current = component;

    while (current) {
        const attrs = componentAttrs(current);
        const classes = componentClasses(current);
        const tag = String(current.get?.('tagName') ?? '').toLowerCase();
        const blockId = String(attrs['data-voodbuilder-block'] ?? '');
        const dropZone = String(attrs['data-voodbuilder-chrome-drop-zone'] ?? '');
        const chromePart = String(attrs['data-voodbuilder-chrome-shell-part'] ?? '');

        // Page body lives under chrome-shell — only exclude nav/footer chrome trees.
        if (dropZone === 'nav' || dropZone === 'footer'
            || chromePart === 'nav' || chromePart === 'header' || chromePart === 'footer'
            || classes.includes('voodbuilder-editor-footer')
            || tag === 'footer'
            || tag === 'nav'
            || (tag === 'header' && (classes.includes('voodbuilder-editor-dynamic') || blockId.startsWith('site_')))
            || (blockId && (isFooterBlock(blockId) || isNavBlock(blockId) || isHeaderBlock(blockId)))
            || (classes.includes('voodbuilder-editor-dynamic') && (tag === 'footer' || tag === 'header' || tag === 'nav'))) {
            return true;
        }

        current = current.parent?.() ?? null;
    }

    return false;
}

/**
 * First boxed content child under a section (skips hero-media / decorative layers).
 *
 * @param {object} section
 * @returns {object|null}
 */
export function findSectionContentWrapper(section) {
    const children = componentChildren(section);

    const explicit = children.find((child) => {
        if (isDecorativeSectionChild(child)) {
            return false;
        }

        return isContentWidthContainer(child);
    });

    if (explicit) {
        return explicit;
    }

    return children.find((child) => {
        if (isDecorativeSectionChild(child)) {
            return false;
        }

        const tag = String(child.get?.('tagName') ?? '').toLowerCase();

        return tag === 'div' || tag === 'article' || tag === 'main';
    }) ?? null;
}

/**
 * Ensure a section has a content wrapper to apply width to.
 *
 * @param {object} section
 * @returns {object|null}
 */
export function ensureSectionContentWrapper(section) {
    const existing = findSectionContentWrapper(section);

    if (existing) {
        if (! isContentWidthContainer(existing)) {
            const classes = [...componentClasses(existing)];

            if (! classes.includes(CONTAINER_CLASS) && ! classes.includes('container')) {
                classes.unshift(CONTAINER_CLASS);
                existing.setClass?.(classes);
            }
        }

        return existing;
    }

    if (! section?.components?.()?.add) {
        return null;
    }

    const created = section.components().add({
        tagName: 'div',
        classes: [CONTAINER_CLASS, 'w-full'],
        attributes: {
            [LAYOUT_ATTR]: 'container',
            'data-voodbuilder-role': 'content',
        },
        droppable: true,
    }, { at: componentChildren(section).length });

    return Array.isArray(created) ? (created[0] ?? null) : (created ?? null);
}

/**
 * Resolve the element that owns content-width (never the section itself).
 *
 * @param {object} component
 * @returns {object|null}
 */
export function resolveContentWidthTarget(component) {
    if (! component) {
        return null;
    }

    if (isContentWidthSection(component)) {
        return ensureSectionContentWrapper(component);
    }

    if (isSectionContentLevel(component) || isBarePageContentContainer(component)) {
        return component;
    }

    return null;
}

/**
 * Second level: direct content child of a section.
 *
 * @param {object} component
 * @returns {boolean}
 */
export function isSectionContentLevel(component) {
    const parent = component?.parent?.();

    if (! parent || ! isContentWidthSection(parent)) {
        return false;
    }

    if (isDecorativeSectionChild(component)) {
        return false;
    }

    const wrapper = findSectionContentWrapper(parent);

    return wrapper === component || isContentWidthContainer(component);
}

/**
 * Optional: root page-content container without a section parent (not footer chrome).
 *
 * @param {object} component
 * @returns {boolean}
 */
export function isBarePageContentContainer(component) {
    if (! isContentWidthContainer(component) || isChromeNavOrFooterTree(component)) {
        return false;
    }

    const parent = component.parent?.();

    if (! parent) {
        return false;
    }

    if (isContentWidthSection(parent)) {
        return false;
    }

    const parentAttrs = componentAttrs(parent);
    const parentTag = String(parent.get?.('tagName') ?? '').toLowerCase();
    const isPageRoot = parentAttrs['data-voodbuilder-page-content']
        || parentAttrs['data-voodbuilder-content-slot']
        || parent.get?.('type') === 'wrapper'
        || parentTag === 'body'
        || parentTag === 'main';

    if (! isPageRoot) {
        return false;
    }

    return true;
}

/**
 * @param {object} component
 * @returns {boolean}
 */
function isLeafLikeComponent(component) {
    const tag = String(component?.get?.('tagName') ?? '').toLowerCase();
    const type = String(component?.get?.('type') ?? '');

    if (LEAF_TAGS.has(tag) || type === 'text' || type === 'image' || type === 'link') {
        return true;
    }

    return false;
}

/**
 * Show content-width on section (1st) and its content wrapper (2nd) only.
 *
 * @param {object} component
 * @param {object} editor
 * @returns {boolean}
 */
export function shouldShowContentWidthToolbar(component, editor) {
    if (! component || ! isFullWidthPageContext(editor)) {
        return false;
    }

    if (isChromeNavOrFooterTree(component)) {
        return false;
    }

    if (isLeafLikeComponent(component) || isDecorativeSectionChild(component)) {
        return false;
    }

    if (isContentWidthSection(component)) {
        return true;
    }

    if (isSectionContentLevel(component)) {
        return true;
    }

    return isBarePageContentContainer(component);
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

    // Durable Tailwind utilities survive save/reload better than private CssComposer
    // classes alone (chrome-shell export keeps #id rules, not .cNNNN).
    const classes = [...(component.getClasses?.() ?? [])].filter((name) => {
        const token = String(name);

        return token !== 'max-w-[80rem]' && token !== 'mx-auto';
    });

    if (! classes.includes('w-full')) {
        classes.push('w-full');
    }

    // Persist measure as inline style so published HTML works without relying only on CSS.
    if (next === CONTENT_WIDTH_NORMAL) {
        if (! classes.includes('mx-auto')) {
            classes.push('mx-auto');
        }

        if (! classes.includes('max-w-[80rem]')) {
            classes.push('max-w-[80rem]');
        }

        component.setClass(classes);
        component.addStyle({
            width: '100%',
            'max-width': STANDARD_CONTENT_MAX,
            'margin-left': 'auto',
            'margin-right': 'auto',
        });
    } else if (next === CONTENT_WIDTH_CUSTOM) {
        const custom = resolveCustomContentMax(editor);

        if (custom) {
            if (! classes.includes('mx-auto')) {
                classes.push('mx-auto');
            }

            component.setClass(classes);
            component.addStyle({
                width: '100%',
                'max-width': custom,
                'margin-left': 'auto',
                'margin-right': 'auto',
            });
        } else {
            component.setClass(classes);
        }
    } else {
        component.setClass(classes);
        component.addStyle({
            width: '100%',
            'max-width': 'none',
            'margin-left': '0',
            'margin-right': '0',
        });
    }

    // Force canvas view to pick up attr + styles immediately (one-click WYSIWYG).
    try {
        component.view?.render?.();
    } catch {
        // View may be unavailable during bulk setComponents.
    }
}

/**
 * Re-apply content-width styles from persisted data-voodbuilder-content-width.
 * Editor load / setComponents may drop inline styles or CssComposer private rules.
 *
 * @param {object} editor
 */
export function restoreContentWidthFromAttributes(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const walk = (component) => {
        const attrs = component?.getAttributes?.() ?? {};
        const mode = String(attrs[CONTENT_WIDTH_ATTR] ?? '').trim();

        if (mode === CONTENT_WIDTH_FULL || mode === CONTENT_WIDTH_NORMAL || mode === CONTENT_WIDTH_CUSTOM) {
            applyComponentContentWidth(component, mode, editor);
        }

        for (const child of component?.components?.()?.models ?? []) {
            walk(child);
        }
    };

    walk(wrapper);
}

/**
 * @param {object} editor
 * @param {Record<string, string>} [labels]
 */
export function cycleSelectedContentWidth(editor, labels = {}) {
    const selected = editor?.getSelected?.();

    if (! selected || ! shouldShowContentWidthToolbar(selected, editor)) {
        return;
    }

    const component = resolveContentWidthTarget(selected);

    if (! component) {
        return;
    }

    const current = readComponentContentWidthMode(component, editor);
    const next = nextContentWidthMode(current, editor);

    applyComponentContentWidth(component, next, editor);
    ensureCanvasContentWidthToolbarState(editor, selected, labels);

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
    const toast = document.getElementById('voodbuilder-editor-classes-toast')
        ?? Object.assign(document.createElement('div'), {
            id: 'voodbuilder-editor-classes-toast',
            className: 'voodbuilder-editor-classes-toast',
        });

    if (! toast.parentElement) {
        document.body.appendChild(toast);
    }

    toast.innerHTML = `<div class="voodbuilder-editor-classes-toast__title">${message}</div>`;
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
    if (! shouldShowContentWidthToolbar(component, editor)) {
        return null;
    }

    const target = resolveContentWidthTarget(component) ?? component;
    const mode = readComponentContentWidthMode(target, editor);

    return {
        attributes: {
            class: `voodbuilder-editor-toolbar-item--content-width is-${mode}`,
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

    const target = resolveContentWidthTarget(component) ?? component;
    const mode = readComponentContentWidthMode(target, editor);
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
