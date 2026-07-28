/**
 * Bricks-like Layout library: Section (+ Container), Container, Block, Div.
 * Container opens a floating column-layout picker (equal + asymmetric presets).
 */

import { previewSvg, thumbWrap } from './editor-block-preview-utils.js';
import { lucideIcon } from './editor-icons.js';

export const LAYOUT_BLOCK_CATEGORY = 'Layout';
export const LAYOUT_ATTR = 'data-voodbuilder-layout';
export const LAYOUT_PRESET_ATTR = 'data-vb-layout-preset';
export const CMD_LAYOUT_PICKER = 'voodbuilder-open-layout-picker';

/** Row layout tokens applied/removed when changing presets (CSS grid — side-by-side in canvas). */
const ROW_LAYOUT_CLASSES = ['flex', 'flex-wrap', 'grid', 'gap-4'];
const CELL_BASE = ['vb-layout-block', 'min-h-16', 'min-w-0'];
const WIDTH_CLASS_RE = /^(sm:|md:|lg:|xl:|2xl:)?(w-|basis-|flex-|max-w-|min-w-)/;
/** Hardcoded boxed utilities — width must follow the page content slot (full vs standard). */
const BOXED_CONTAINER_CLASSES = new Set([
    'container',
    'mx-auto',
    'max-w-7xl',
    'max-w-6xl',
    'max-w-5xl',
    'max-w-4xl',
    'max-w-3xl',
    'max-w-2xl',
    'max-w-xl',
    'max-w-lg',
    'max-w-screen-xl',
    'max-w-screen-2xl',
]);

/** Legacy auto-gutters we used to inject on Container — strip so Section padding is WYSIWYG. */
const LEGACY_AUTO_CONTAINER_PAD = new Set(['px-4', 'px-5', 'px-6']);

function isBoxedOrWidthUtility(token) {
    const name = String(token);

    if (BOXED_CONTAINER_CLASSES.has(name) || LEGACY_AUTO_CONTAINER_PAD.has(name)) {
        return true;
    }

    if (WIDTH_CLASS_RE.test(name)) {
        return true;
    }

    // Arbitrary max-width (except the theme layout token — kept only if we re-add it).
    if (/^(sm:|md:|lg:|xl:|2xl:)?max-w-\[/.test(name)) {
        return true;
    }

    return false;
}

/** Classes for a Layout Container that fills the page-content slot (full or standard).
 * No auto px-* — section / author utilities own horizontal padding (editor ↔ front parity).
 */
function layoutContainerBaseClasses() {
    return ['w-full', 'vb-layout-row'];
}

/**
 * Grid track presets (fr units). Avoid md:w-* + w-full — those stack below the md
 * breakpoint and often never apply in the editor iframe.
 *
 * @type {Array<{ id: string, label: string, tracks: string[] }>}
 */
export const LAYOUT_PRESETS = [
    { id: '1', label: '1', tracks: ['minmax(0,1fr)'] },
    { id: '2', label: '1/2', tracks: ['minmax(0,1fr)', 'minmax(0,1fr)'] },
    { id: '3', label: '1/3', tracks: ['minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)'] },
    { id: '4', label: '1/4', tracks: ['minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)'] },
    { id: '6', label: '1/6', tracks: ['minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)'] },
    { id: '1-2', label: '1/3 · 2/3', tracks: ['minmax(0,1fr)', 'minmax(0,2fr)'] },
    { id: '2-1', label: '2/3 · 1/3', tracks: ['minmax(0,2fr)', 'minmax(0,1fr)'] },
    { id: '1-3', label: '1/4 · 3/4', tracks: ['minmax(0,1fr)', 'minmax(0,3fr)'] },
    { id: '3-1', label: '3/4 · 1/4', tracks: ['minmax(0,3fr)', 'minmax(0,1fr)'] },
    { id: '1-1-2', label: '1/4 · 1/4 · 1/2', tracks: ['minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,2fr)'] },
    { id: '2-1-1', label: '1/2 · 1/4 · 1/4', tracks: ['minmax(0,2fr)', 'minmax(0,1fr)', 'minmax(0,1fr)'] },
    { id: '1-2-1', label: '1/4 · 1/2 · 1/4', tracks: ['minmax(0,1fr)', 'minmax(0,2fr)', 'minmax(0,1fr)'] },
];

/** @deprecated alias for picker icons — same length as tracks */
function presetColumnWeights(preset) {
    return (preset.tracks ?? []).map((track) => {
        const match = String(track).match(/(\d+)\s*fr/);

        return match ? Number(match[1]) : 1;
    });
}

function layoutKind(component) {
    return String(component?.getAttributes?.()?.[LAYOUT_ATTR] ?? '').trim();
}

export function isLayoutContainer(component) {
    if (! component) {
        return false;
    }

    if (layoutKind(component) === 'container') {
        return true;
    }

    return component.get?.('type') === 'voodbuilder-container'
        || (component.getClasses?.() ?? []).includes('container');
}

export function isLayoutSection(component) {
    return layoutKind(component) === 'section'
        || component?.get?.('type') === 'voodbuilder-section'
        || String(component?.get?.('tagName') ?? '').toLowerCase() === 'section';
}

export function findNestedLayoutContainer(component) {
    if (! component?.components) {
        return null;
    }

    const children = [...(component.components()?.models ?? component.components() ?? [])];

    return children.find((child) => isLayoutContainer(child)) ?? null;
}

/**
 * Container that owns column presets (self if container, else nested in section).
 *
 * @param {object} component
 * @returns {object|null}
 */
export function resolveLayoutPresetContainer(component) {
    if (isLayoutContainer(component)) {
        return component;
    }

    if (isLayoutSection(component)) {
        return findNestedLayoutContainer(component);
    }

    return null;
}

function blockModel(extraClasses = []) {
    return {
        type: 'voodbuilder-layout-block',
        tagName: 'div',
        classes: [...CELL_BASE, ...extraClasses],
        attributes: {
            [LAYOUT_ATTR]: 'block',
        },
        droppable: true,
        highlightable: true,
        name: 'Block',
        components: [],
    };
}

/**
 * Sync Container classes/attrs/CSS for a layout preset without touching children.
 * Tracks are inline so the canvas iframe sees them (frontend.css is shell-only).
 * Mobile stacking uses canvas device CSS + public @media !important overrides.
 *
 * @param {object} container
 * @param {string} presetId
 */
export function syncContainerLayoutStyles(container, presetId) {
    if (! container) {
        return;
    }

    const preset = LAYOUT_PRESETS.find((item) => item.id === presetId) ?? LAYOUT_PRESETS[0];
    const classes = [...(container.getClasses?.() ?? [])]
        .filter((name) => {
            const token = String(name);

            if (ROW_LAYOUT_CLASSES.includes(token)) {
                return false;
            }

            if (isBoxedOrWidthUtility(token)) {
                return false;
            }

            return ! token.startsWith('gjs-');
        });

    for (const token of [...layoutContainerBaseClasses(), 'grid', 'gap-4']) {
        if (! classes.includes(token)) {
            classes.push(token);
        }
    }

    container.setClass(classes);
    container.addAttributes({
        [LAYOUT_ATTR]: 'container',
        [LAYOUT_PRESET_ATTR]: preset.id,
    });

    const tracks = (preset.tracks ?? ['minmax(0,1fr)']).join(' ');
    const style = { ...(container.getStyle?.() ?? {}) };

    // Inline tracks are required in the editor canvas (layout CSS is not in canvas_styles).
    style.display = 'grid';
    style.gap = '1rem';
    style.width = '100%';
    style.maxWidth = 'none';
    style['grid-template-columns'] = tracks;
    style['--vb-layout-tracks'] = tracks;
    delete style['grid-template-rows'];

    container.setStyle(style);
    container.removeStyle?.('grid-template-rows');
}

/**
 * Apply a column preset to a Container (replaces direct children with Blocks).
 *
 * @param {object} container
 * @param {string} presetId
 */
export function applyContainerLayoutPreset(container, presetId) {
    if (! container?.components) {
        return;
    }

    const preset = LAYOUT_PRESETS.find((item) => item.id === presetId) ?? LAYOUT_PRESETS[0];

    syncContainerLayoutStyles(container, preset.id);
    container.components(preset.tracks.map(() => blockModel()));
}

/**
 * Strip hardcoded max-w-* / .container so the Container fills the page-content slot
 * (full-width chrome layout → edge-to-edge; standard → slot max-width).
 *
 * @param {object} container
 */
export function syncContainerContentWidth(container) {
    if (! container) {
        return;
    }

    const contentWidthMode = String(container.getAttributes?.()?.['data-voodbuilder-content-width'] ?? '').trim();

    // Author content-width toolbar owns measure on full-width pages — do not wipe it.
    if (contentWidthMode === 'normal' || contentWidthMode === 'custom') {
        return;
    }

    const presetId = String(container.getAttributes?.()?.[LAYOUT_PRESET_ATTR] ?? '').trim();

    if (presetId) {
        syncContainerLayoutStyles(container, presetId);

        return;
    }

    const classes = [...(container.getClasses?.() ?? [])]
        .filter((name) => {
            const token = String(name);

            if (isBoxedOrWidthUtility(token)) {
                return false;
            }

            return ! token.startsWith('gjs-');
        });

    for (const token of layoutContainerBaseClasses()) {
        if (! classes.includes(token)) {
            classes.push(token);
        }
    }

    container.setClass(classes);
    container.addAttributes({ [LAYOUT_ATTR]: 'container' });

    const style = { ...(container.getStyle?.() ?? {}) };
    style.width = '100%';
    style.maxWidth = 'none';
    container.setStyle(style);
}

/**
 * Re-apply responsive styles on existing layout containers (saved pages with
 * legacy inline grid-template-columns / boxed max-w-7xl).
 *
 * @param {object} editor
 */
export function normalizeLayoutContainersResponsive(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper) {
        return;
    }

    const visit = (component) => {
        if (isLayoutContainer(component) && layoutKind(component) === 'container') {
            syncContainerContentWidth(component);
        }

        component.components?.()?.forEach?.((child) => visit(child));
    };

    visit(wrapper);
}

function presetIconSvg(weights) {
    const count = Math.max(1, weights.length);
    const gap = 1.5;
    const pad = 3;
    const inner = 42 - pad * 2;
    const totalGaps = gap * (count - 1);
    const sum = weights.reduce((a, b) => a + b, 0) || 1;
    let x = pad;
    const rects = weights.map((weight) => {
        const w = ((inner - totalGaps) * weight) / sum;
        const rect = `<rect x="${x.toFixed(1)}" y="${pad}" width="${w.toFixed(1)}" height="${(42 - pad * 2).toFixed(1)}" rx="1.2"/>`;
        x += w + gap;

        return rect;
    });

    return `<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true">${rects.join('')}</svg>`;
}

function dismissLayoutPicker() {
    document.querySelectorAll('[data-voodbuilder-layout-picker]').forEach((node) => node.remove());
}

function positionPickerNearComponent(editor, component, panel) {
    const frame = editor.Canvas?.getFrameEl?.();
    const canvasRect = editor.Canvas?.getElement?.()?.getBoundingClientRect?.()
        ?? document.querySelector('.voodbuilder-gjs-canvas-mount')?.getBoundingClientRect?.();
    const el = component.getEl?.();

    if (! frame || ! canvasRect || ! el) {
        panel.style.left = `${Math.max(16, canvasRect?.left ?? 16)}px`;
        panel.style.top = `${Math.max(16, (canvasRect?.top ?? 16) + 16)}px`;

        return;
    }

    const frameRect = frame.getBoundingClientRect();
    const elRect = el.getBoundingClientRect();
    const left = frameRect.left + elRect.left;
    const top = frameRect.top + elRect.top - 8;

    panel.style.left = `${Math.max(12, Math.min(left, window.innerWidth - 320))}px`;
    panel.style.top = `${Math.max(12, Math.min(top, window.innerHeight - 180))}px`;
}

/**
 * Floating Bricks-style layout preset picker for a Container.
 *
 * @param {object} editor
 * @param {object} container
 * @param {Record<string, string>} [labels]
 */
export function openContainerLayoutPicker(editor, container, labels = {}) {
    if (! container || ! isLayoutContainer(container)) {
        return;
    }

    dismissLayoutPicker();

    const panel = document.createElement('div');
    panel.className = 'voodbuilder-gjs-layout-picker';
    panel.setAttribute('data-voodbuilder-layout-picker', '');
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', labels.layoutPickerTitle ?? 'Layout');

    const grid = document.createElement('div');
    grid.className = 'voodbuilder-gjs-layout-picker__grid';

    const current = String(container.getAttributes?.()?.[LAYOUT_PRESET_ATTR] ?? '');

    for (const preset of LAYOUT_PRESETS) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'voodbuilder-gjs-layout-picker__option';
        button.title = preset.label;
        button.setAttribute('aria-label', preset.label);
        button.innerHTML = presetIconSvg(presetColumnWeights(preset));

        if (preset.id === current) {
            button.classList.add('is-active');
        }

        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            editor.__voodbuilderLayoutPickerSkipAdd = true;

            try {
                applyContainerLayoutPreset(container, preset.id);
            } finally {
                editor.__voodbuilderLayoutPickerSkipAdd = false;
            }

            editor.select(container);
            dismissLayoutPicker();
            editor.__voodbuilderSchedulePageCssRebuild?.(0);
        });

        grid.appendChild(button);
    }

    const caption = document.createElement('div');
    caption.className = 'voodbuilder-gjs-layout-picker__caption';
    caption.textContent = labels.layoutPickerCaption ?? 'Layout: Container';

    panel.append(grid, caption);
    document.body.appendChild(panel);
    positionPickerNearComponent(editor, container, panel);

    const onPointer = (event) => {
        if (panel.contains(event.target)) {
            return;
        }

        dismissLayoutPicker();
        window.removeEventListener('pointerdown', onPointer, true);
    };

    window.setTimeout(() => {
        window.addEventListener('pointerdown', onPointer, true);
    }, 0);
}

function sectionContentHtml() {
    return `
<section class="body-font w-full py-12" ${LAYOUT_ATTR}="section" data-gjs-type="voodbuilder-section" data-gjs-name="Section">
  <div class="w-full" ${LAYOUT_ATTR}="container" data-gjs-type="voodbuilder-container" data-gjs-name="Container" data-gjs-droppable="true"></div>
</section>`.trim();
}

function containerContentHtml() {
    return `
<div class="w-full" ${LAYOUT_ATTR}="container" data-gjs-type="voodbuilder-container" data-gjs-name="Container" data-gjs-droppable="true"></div>`.trim();
}

function blockContentHtml() {
    return `
<div class="vb-layout-block min-w-0 min-h-16" ${LAYOUT_ATTR}="block" data-gjs-type="voodbuilder-layout-block" data-gjs-name="Block" data-gjs-droppable="true"></div>`.trim();
}

function divContentHtml() {
    return `
<div class="vb-layout-div w-full min-h-8" ${LAYOUT_ATTR}="div" data-gjs-type="voodbuilder-layout-div" data-gjs-name="Div" data-gjs-droppable="true"></div>`.trim();
}

/** Library thumbs: official Tabler outline SVGs (MIT) — viewBox 24×24. */
function layoutWireframes() {
    return {
        'voodbuilder-layout-section': thumbWrap(previewSvg(
            '<path d="M4 4l16 0"/><path d="M4 20l16 0"/><path d="M6 9m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v2a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"/>',
            '0 0 24 24',
        )),
        'voodbuilder-layout-container': thumbWrap(previewSvg(
            '<path d="M4 4l0 16"/><path d="M20 4l0 16"/><path d="M9 6m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"/>',
            '0 0 24 24',
        )),
        'voodbuilder-layout-block': thumbWrap(previewSvg(
            '<path d="M5 3m0 1a1 1 0 0 1 1 -1h12a1 1 0 0 1 1 1v16a1 1 0 0 1 -1 1h-12a1 1 0 0 1 -1 -1z"/>',
            '0 0 24 24',
        )),
        'voodbuilder-layout-div': thumbWrap(previewSvg(
            '<path d="M20 4v.01"/><path d="M20 20v.01"/><path d="M20 16v.01"/><path d="M20 12v.01"/><path d="M20 8v.01"/><path d="M8 4m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v14a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z"/><path d="M4 4v.01"/><path d="M4 20v.01"/><path d="M4 16v.01"/><path d="M4 12v.01"/><path d="M4 8v.01"/>',
            '0 0 24 24',
        )),
    };
}

/**
 * @param {object} editor
 * @param {Record<string, string>} [labels]
 */
export function registerLayoutBlocks(editor, labels = {}) {
    const bm = editor.BlockManager;

    if (! bm || editor.__voodbuilderLayoutBlocksRegistered) {
        return;
    }

    editor.__voodbuilderLayoutBlocksRegistered = true;

    const wireframes = layoutWireframes();
    const category = {
        id: LAYOUT_BLOCK_CATEGORY,
        label: labels.layoutCategory ?? LAYOUT_BLOCK_CATEGORY,
        open: true,
        order: -190,
    };

    for (const id of ['column1', 'column2', 'column3', 'column3-7']) {
        if (bm.get(id)) {
            bm.remove(id);
        }
    }

    bm.add('voodbuilder-layout-section', {
        label: labels.layoutSection ?? 'Section',
        category,
        media: wireframes['voodbuilder-layout-section'],
        content: sectionContentHtml(),
        select: true,
    });

    bm.add('voodbuilder-layout-container', {
        label: labels.layoutContainer ?? 'Container',
        category,
        media: wireframes['voodbuilder-layout-container'],
        content: containerContentHtml(),
        select: true,
    });

    bm.add('voodbuilder-layout-block', {
        label: labels.layoutBlock ?? 'Block',
        category,
        media: wireframes['voodbuilder-layout-block'],
        content: blockContentHtml(),
        select: true,
    });

    bm.add('voodbuilder-layout-div', {
        label: labels.layoutDiv ?? 'Div',
        category,
        media: wireframes['voodbuilder-layout-div'],
        content: divContentHtml(),
        select: true,
    });
}

/**
 * @param {object} editor
 */
export function registerLayoutComponentTypes(editor) {
    if (editor.__voodbuilderLayoutTypesRegistered) {
        return;
    }

    editor.__voodbuilderLayoutTypesRegistered = true;

    editor.DomComponents.addType('voodbuilder-layout-block', {
        isComponent: (el) => el?.getAttribute?.(LAYOUT_ATTR) === 'block',
        model: {
            defaults: {
                tagName: 'div',
                name: 'Block',
                droppable: true,
                attributes: { [LAYOUT_ATTR]: 'block' },
                classes: ['vb-layout-block', 'min-w-0', 'min-h-16'],
            },
        },
    });

    editor.DomComponents.addType('voodbuilder-layout-div', {
        isComponent: (el) => el?.getAttribute?.(LAYOUT_ATTR) === 'div',
        model: {
            defaults: {
                tagName: 'div',
                name: 'Div',
                droppable: true,
                attributes: { [LAYOUT_ATTR]: 'div' },
                classes: ['vb-layout-div', 'w-full', 'min-h-8'],
            },
        },
    });

    const sectionType = editor.DomComponents.getType('voodbuilder-section');
    const previousSectionIs = sectionType?.isComponent;

    editor.DomComponents.addType('voodbuilder-section', {
        isComponent: (el) => {
            if (el?.getAttribute?.(LAYOUT_ATTR) === 'section') {
                return { type: 'voodbuilder-section' };
            }

            return typeof previousSectionIs === 'function' ? previousSectionIs(el) : false;
        },
    });

    const containerType = editor.DomComponents.getType('voodbuilder-container');
    const previousContainerIs = containerType?.isComponent;

    editor.DomComponents.addType('voodbuilder-container', {
        isComponent: (el) => {
            if (el?.getAttribute?.(LAYOUT_ATTR) === 'container') {
                return { type: 'voodbuilder-container' };
            }

            return typeof previousContainerIs === 'function' ? previousContainerIs(el) : false;
        },
        model: {
            defaults: {
                name: 'Container',
                droppable: true,
            },
        },
    });
}

/**
 * @param {object} editor
 * @param {Record<string, string>} [labels]
 */
export function configureLayoutBlocksCanvas(editor, labels = {}) {
    if (editor.__voodbuilderLayoutCanvasConfigured) {
        return;
    }

    editor.__voodbuilderLayoutCanvasConfigured = true;

    registerLayoutComponentTypes(editor);
    registerLayoutBlocks(editor, labels);

    editor.Commands.add(CMD_LAYOUT_PICKER, {
        run(ed) {
            const selected = ed.getSelected();
            const target = isLayoutContainer(selected)
                ? selected
                : findNestedLayoutContainer(selected);

            if (target) {
                openContainerLayoutPicker(ed, target, labels);
            }
        },
    });

    const maybeOpenPicker = (component) => {
        if (! component || editor.__voodbuilderBulkStructureUpdate || editor.__voodbuilderLayoutPickerSkipAdd) {
            return;
        }

        if (isLayoutContainer(component) && layoutKind(component) === 'container') {
            syncContainerContentWidth(component);
            window.requestAnimationFrame(() => {
                openContainerLayoutPicker(editor, component, labels);
            });

            return;
        }

        if (isLayoutSection(component) && layoutKind(component) === 'section') {
            const nested = findNestedLayoutContainer(component);

            if (nested) {
                syncContainerContentWidth(nested);
                window.requestAnimationFrame(() => {
                    openContainerLayoutPicker(editor, nested, labels);
                });
            }
        }
    };

    editor.on('block:drag:stop', (component) => {
        if (! component) {
            return;
        }

        // Heal width immediately so full-width chrome layouts are edge-to-edge on drop.
        if (isLayoutContainer(component) && layoutKind(component) === 'container') {
            syncContainerContentWidth(component);
        } else if (isLayoutSection(component)) {
            const nested = findNestedLayoutContainer(component);

            if (nested) {
                syncContainerContentWidth(nested);
            }
        }

        maybeOpenPicker(component);
    });

    editor.on('component:add', (component) => {
        if (
            ! component
            || editor.__voodbuilderBulkStructureUpdate
            || editor.__voodbuilderActiveBlockDrag
            || editor.__voodbuilderCssRebuildDragLock
        ) {
            return;
        }

        if (isLayoutContainer(component) && layoutKind(component) === 'container') {
            syncContainerContentWidth(component);
        }
    });

    editor.on('component:deselected', dismissLayoutPicker);
    editor.on('load', () => {
        dismissLayoutPicker();
        normalizeLayoutContainersResponsive(editor);
    });

    // Device switch (desktop → mobile) should stack columns; heal any leftover inline grid.
    editor.on('change:device', () => {
        normalizeLayoutContainersResponsive(editor);
    });
}

/**
 * Toolbar entry for Container layout presets.
 *
 * @param {object} component
 * @param {Record<string, string>} [labels]
 * @returns {object|null}
 */
export function buildLayoutPickerToolbarButton(component, labels = {}) {
    if (! isLayoutContainer(component) && ! (isLayoutSection(component) && findNestedLayoutContainer(component))) {
        return null;
    }

    return {
        attributes: {
            class: 'voodbuilder-gjs-toolbar-item--layout',
            'data-voodbuilder-toolbar': 'layout',
            title: labels.layoutPickerTitle ?? 'Layout',
            'aria-label': labels.layoutPickerTitle ?? 'Layout',
        },
        label: lucideIcon('layout-grid', 16),
        command: CMD_LAYOUT_PICKER,
    };
}
