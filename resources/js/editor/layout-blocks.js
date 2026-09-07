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
const ROW_LAYOUT_CLASSES = ['flex', 'flex-wrap', 'grid'];
const DEFAULT_LAYOUT_GAP_CLASS = 'gap-4';
const LAYOUT_GAP_CLASS_RE = /^(sm:|md:|lg:|xl:|2xl:)?gap-/;
/** Track utilities owned by layout sync — strip before re-applying the active preset. */
const GRID_COLS_CLASS_RE = /^(sm:|md:|lg:|xl:|2xl:)?grid-cols-/;
const CELL_BASE = ['vb-layout-block', 'min-h-16', 'min-w-0'];
const WIDTH_CLASS_RE = /^(sm:|md:|lg:|xl:|2xl:)?(w-|basis-|flex-|max-w-|min-w-)/;
/** Inline keys that duplicate Tailwind grid/width mechanics (legacy saves + old sync). */
const LAYOUT_MECHANIC_STYLE_RE = /^(display|grid-template-columns|grid-template-rows|--vb-layout-tracks|gap|column-gap|row-gap)\s*:/i;
const LAYOUT_MEASURE_STYLE_RE = /^(width|max-width|maxWidth|margin-left|margin-right|margin-inline)\s*:/i;
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
 * Keep an author-chosen gap-* utility; default to gap-4 when none is present.
 *
 * @param {string[]} classes
 * @returns {string}
 */
function resolveLayoutGapClass(classes) {
    const found = classes.find((token) => LAYOUT_GAP_CLASS_RE.test(String(token)));

    return found ?? DEFAULT_LAYOUT_GAP_CLASS;
}

/**
 * Grid track presets (fr units) + Tailwind colsClass.
 * Equal splits use grid-cols-N; asymmetric use arbitrary grid-cols-[…].
 * Tracks stay on data-vb-layout-tracks for editor metadata / legacy resolve —
 * layout mechanics live on classes, not inline style.
 *
 * @type {Array<{ id: string, label: string, tracks: string[], colsClass: string }>}
 */
export const LAYOUT_PRESETS = [
    { id: '1', label: '1', tracks: ['minmax(0,1fr)'], colsClass: 'grid-cols-1' },
    { id: '2', label: '1/2', tracks: ['minmax(0,1fr)', 'minmax(0,1fr)'], colsClass: 'grid-cols-2' },
    { id: '3', label: '1/3', tracks: ['minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)'], colsClass: 'grid-cols-3' },
    { id: '4', label: '1/4', tracks: ['minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)'], colsClass: 'grid-cols-4' },
    { id: '6', label: '1/6', tracks: ['minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,1fr)'], colsClass: 'grid-cols-6' },
    { id: '1-2', label: '1/3 · 2/3', tracks: ['minmax(0,1fr)', 'minmax(0,2fr)'], colsClass: 'grid-cols-[minmax(0,1fr)_minmax(0,2fr)]' },
    { id: '2-1', label: '2/3 · 1/3', tracks: ['minmax(0,2fr)', 'minmax(0,1fr)'], colsClass: 'grid-cols-[minmax(0,2fr)_minmax(0,1fr)]' },
    { id: '1-3', label: '1/4 · 3/4', tracks: ['minmax(0,1fr)', 'minmax(0,3fr)'], colsClass: 'grid-cols-[minmax(0,1fr)_minmax(0,3fr)]' },
    { id: '3-1', label: '3/4 · 1/4', tracks: ['minmax(0,3fr)', 'minmax(0,1fr)'], colsClass: 'grid-cols-[minmax(0,3fr)_minmax(0,1fr)]' },
    { id: '1-1-2', label: '1/4 · 1/4 · 1/2', tracks: ['minmax(0,1fr)', 'minmax(0,1fr)', 'minmax(0,2fr)'], colsClass: 'grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,2fr)]' },
    { id: '2-1-1', label: '1/2 · 1/4 · 1/4', tracks: ['minmax(0,2fr)', 'minmax(0,1fr)', 'minmax(0,1fr)'], colsClass: 'grid-cols-[minmax(0,2fr)_minmax(0,1fr)_minmax(0,1fr)]' },
    { id: '1-2-1', label: '1/4 · 1/2 · 1/4', tracks: ['minmax(0,1fr)', 'minmax(0,2fr)', 'minmax(0,1fr)'], colsClass: 'grid-cols-[minmax(0,1fr)_minmax(0,2fr)_minmax(0,1fr)]' },
];

/**
 * Map track list → Tailwind grid-cols utility (equal N → grid-cols-N, else arbitrary).
 *
 * @param {string|string[]} tracks
 * @returns {string}
 */
export function tracksToGridColsClass(tracks) {
    const parts = (Array.isArray(tracks) ? tracks : String(tracks).trim().split(/\s+/))
        .map((part) => String(part).trim().replace(/\s+/g, ''))
        .filter(Boolean);

    if (parts.length === 0) {
        return 'grid-cols-1';
    }

    const equalOneFr = parts.every((part) => part === 'minmax(0,1fr)' || part === '1fr');

    if (equalOneFr && parts.length >= 1 && parts.length <= 12) {
        return `grid-cols-${parts.length}`;
    }

    return `grid-cols-[${parts.join('_')}]`;
}

/**
 * @param {string} presetId
 * @param {string} [tracksFallback]
 * @returns {string}
 */
function resolvePresetColsClass(presetId, tracksFallback = '') {
    const preset = LAYOUT_PRESETS.find((item) => item.id === presetId);

    if (preset?.colsClass) {
        return preset.colsClass;
    }

    return tracksToGridColsClass(tracksFallback || (preset?.tracks ?? ['minmax(0,1fr)']));
}

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

/**
 * Intentional Layout → Container only (data-voodbuilder-layout="container").
 * Do NOT treat every content-width shell (.voodbuilder-editor-container /
 * data-voodbuilder-role=content) as a layout grid — companion/dynamic blocks
 * use that class for measure, not for Columns / layout picker.
 */
export function isLayoutContainer(component) {
    if (! component) {
        return false;
    }

    return layoutKind(component) === 'container';
}

/**
 * Intentional Layout → Section only (data-voodbuilder-layout="section").
 * Companion section shells use .voodbuilder-editor-section for content-width
 * without becoming layout structure targets.
 */
export function isLayoutSection(component) {
    if (! component) {
        return false;
    }

    return layoutKind(component) === 'section'
        || component.get?.('type') === 'voodbuilder-section';
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
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
export function isLayoutBlock(component) {
    if (! component) {
        return false;
    }

    return layoutKind(component) === 'block'
        || component.get?.('type') === 'voodbuilder-layout-block';
}

/**
 * @param {object} container
 * @returns {object[]}
 */
function listDirectChildren(container) {
    return [...(container.components?.()?.models ?? container.components?.() ?? [])];
}

/**
 * @param {object} container
 * @returns {object[]}
 */
function listLayoutBlocks(container) {
    return listDirectChildren(container).filter((child) => isLayoutBlock(child));
}

/**
 * Ensure Container children are Layout Blocks so columns can grow/shrink
 * without wiping author content.
 *
 * @param {object} container
 * @returns {object[]}
 */
function ensureColumnBlocks(container) {
    const children = listDirectChildren(container);
    const blocks = children.filter((child) => isLayoutBlock(child));
    const loose = children.filter((child) => ! isLayoutBlock(child));

    if (blocks.length === 0) {
        const added = container.append(blockModel(), { at: 0 });
        const block = Array.isArray(added) ? added[0] : added;

        for (const child of loose) {
            if (child?.move && block && ! child.isRemoved?.()) {
                child.move(block);
            }
        }

        return listLayoutBlocks(container);
    }

    if (loose.length > 0) {
        const first = blocks[0];

        for (const child of loose) {
            if (child?.move && first && ! child.isRemoved?.()) {
                child.move(first);
            }
        }
    }

    return listLayoutBlocks(container);
}

/**
 * @param {object} source
 * @param {object} target
 */
function moveAllChildren(source, target) {
    if (! source?.components || ! target) {
        return;
    }

    const kids = [...(source.components()?.models ?? source.components() ?? [])];

    for (const child of kids) {
        if (child?.move && ! child.isRemoved?.()) {
            child.move(target);
        }
    }
}

/**
 * Sync Layout Block chrome classes after a preset change.
 *
 * @param {object} block
 */
function syncLayoutBlockChrome(block) {
    if (! block) {
        return;
    }

    const classes = [...(block.getClasses?.() ?? [])]
        .filter((token) => {
            const name = String(token);

            if (CELL_BASE.includes(name)) {
                return false;
            }

            return ! WIDTH_CLASS_RE.test(name);
        });

    for (const token of CELL_BASE) {
        if (! classes.includes(token)) {
            classes.push(token);
        }
    }

    block.setClass(classes);
    block.addAttributes({ [LAYOUT_ATTR]: 'block' });
    block.set?.({
        type: 'voodbuilder-layout-block',
        droppable: true,
        highlightable: true,
        name: block.get?.('name') || 'Block',
    });
}

/**
 * Sync Container classes/attrs for a layout preset without touching children.
 * Tracks are Tailwind grid-cols-* (plus data-vb-layout-tracks for metadata).
 * Mobile stacking uses canvas device CSS + public @media !important overrides.
 *
 * @param {object} container
 * @param {string} presetId
 * @param {{ resetMeasure?: boolean }} [options]
 */
export function syncContainerLayoutStyles(container, presetId, options = {}) {
    if (! container) {
        return;
    }

    const preset = LAYOUT_PRESETS.find((item) => item.id === presetId) ?? LAYOUT_PRESETS[0];
    const contentWidthMode = String(container.getAttributes?.()?.['data-voodbuilder-content-width'] ?? '').trim();
    const hasContentWidth = contentWidthMode === 'normal'
        || contentWidthMode === 'custom'
        || contentWidthMode === 'full';
    const resetMeasure = options.resetMeasure ?? ! hasContentWidth;
    const classes = [...(container.getClasses?.() ?? [])]
        .filter((name) => {
            const token = String(name);

            if (ROW_LAYOUT_CLASSES.includes(token) || GRID_COLS_CLASS_RE.test(token)) {
                return false;
            }

            if (token.startsWith('gjs-')) {
                return false;
            }

            // Durable Content width utilities must survive layout track sync.
            if (hasContentWidth && (token === 'max-w-[80rem]' || token === 'mx-auto' || token === 'w-full')) {
                return true;
            }

            if (isBoxedOrWidthUtility(token)) {
                return false;
            }

            return true;
        });

    const gapClass = resolveLayoutGapClass(classes);
    const tracks = (preset.tracks ?? ['minmax(0,1fr)']).join(' ');
    const colsClass = preset.colsClass ?? tracksToGridColsClass(preset.tracks ?? ['minmax(0,1fr)']);

    for (const token of [...layoutContainerBaseClasses(), 'grid', colsClass, gapClass]) {
        if (! classes.includes(token)) {
            classes.push(token);
        }
    }

    container.setClass(classes);

    container.addAttributes({
        [LAYOUT_ATTR]: 'container',
        [LAYOUT_PRESET_ATTR]: preset.id,
        'data-vb-layout-tracks': tracks,
    });

    stripLayoutMechanicInlineStyles(container, { resetMeasure });
}

/**
 * Apply a column preset to a Container while preserving existing column content.
 *
 * Growing (1→2): keep current content in the first column(s), append empty Blocks.
 * Shrinking (3→1): merge leftover columns into the last remaining Block.
 *
 * @param {object} container
 * @param {string} presetId
 */
export function applyContainerLayoutPreset(container, presetId) {
    if (! container?.components) {
        return;
    }

    const preset = LAYOUT_PRESETS.find((item) => item.id === presetId) ?? LAYOUT_PRESETS[0];
    const targetCount = Math.max(1, (preset.tracks ?? ['minmax(0,1fr)']).length);

    let blocks = ensureColumnBlocks(container);

    while (blocks.length < targetCount) {
        container.append(blockModel());
        blocks = listLayoutBlocks(container);
    }

    if (blocks.length > targetCount) {
        const keep = blocks[targetCount - 1];

        for (let index = targetCount; index < blocks.length; index += 1) {
            moveAllChildren(blocks[index], keep);
        }

        for (let index = blocks.length - 1; index >= targetCount; index -= 1) {
            blocks[index]?.remove?.();
        }

        blocks = listLayoutBlocks(container);
    }

    for (const block of blocks) {
        syncLayoutBlockChrome(block);
    }

    syncContainerLayoutStyles(container, preset.id);
}

/** Avoid component:styleUpdate — full setStyle would re-emit margin-* and strip author p-*. */
const SILENT_LAYOUT_STYLE = { noEvent: true };

/**
 * Remove legacy layout mechanic inline styles (display/grid/width) so Tailwind classes win.
 *
 * @param {object} container
 * @param {{ resetMeasure?: boolean }} [options]
 */
function stripLayoutMechanicInlineStyles(container, options = {}) {
    const resetMeasure = options.resetMeasure === true;
    const style = {
        ...(container.getStyle?.() ?? {}),
        ...(container.getStyle?.({ inline: true }) ?? {}),
    };

    delete style.display;
    delete style['grid-template-columns'];
    delete style['grid-template-rows'];
    delete style['--vb-layout-tracks'];
    delete style.gap;
    delete style['column-gap'];
    delete style['row-gap'];

    if (resetMeasure) {
        delete style.width;
        delete style.maxWidth;
        delete style['max-width'];
        delete style['margin-left'];
        delete style['margin-right'];
        delete style['margin-inline'];
    }

    container.setStyle(style, SILENT_LAYOUT_STYLE);
    container.removeStyle?.('display');
    container.removeStyle?.('grid-template-columns');
    container.removeStyle?.('grid-template-rows');
    container.removeStyle?.('--vb-layout-tracks');
    container.removeStyle?.('gap');
    container.removeStyle?.('column-gap');
    container.removeStyle?.('row-gap');

    if (resetMeasure) {
        container.removeStyle?.('width');
        container.removeStyle?.('max-width');
        container.removeStyle?.('maxWidth');
        container.removeStyle?.('margin-left');
        container.removeStyle?.('margin-right');
        container.removeStyle?.('margin-inline');
    }

    const attrs = { ...(container.getAttributes?.() ?? {}) };
    const styleParts = String(attrs.style ?? '')
        .split(';')
        .map((part) => part.trim())
        .filter(Boolean)
        .filter((part) => {
            if (LAYOUT_MECHANIC_STYLE_RE.test(part)) {
                return false;
            }

            if (resetMeasure && LAYOUT_MEASURE_STYLE_RE.test(part)) {
                return false;
            }

            return true;
        });

    if (styleParts.length > 0) {
        container.addAttributes({
            style: styleParts.join('; '),
        });
    } else if (Object.prototype.hasOwnProperty.call(attrs, 'style')) {
        container.addAttributes({ style: '' });
        container.removeAttributes?.('style');
    }

    const el = container.getEl?.()
        ?? container.getView?.()?.el
        ?? container.view?.el;

    if (el?.style) {
        el.style.removeProperty('display');
        el.style.removeProperty('grid-template-columns');
        el.style.removeProperty('grid-template-rows');
        el.style.removeProperty('--vb-layout-tracks');
        el.style.removeProperty('gap');
        el.style.removeProperty('column-gap');
        el.style.removeProperty('row-gap');

        if (resetMeasure) {
            el.style.removeProperty('width');
            el.style.removeProperty('max-width');
            el.style.removeProperty('margin-left');
            el.style.removeProperty('margin-right');
            el.style.removeProperty('margin-inline');
        }
    }
}

/**
 * Apply column tracks via Tailwind grid-cols-* (no layout inline styles).
 *
 * @param {object} container
 * @param {string} tracks
 * @param {{ resetMeasure?: boolean, presetId?: string }} [options]
 */
function applyLayoutTracksToContainer(container, tracks, options = {}) {
    const resetMeasure = options.resetMeasure === true;
    const presetId = String(options.presetId ?? container.getAttributes?.()?.[LAYOUT_PRESET_ATTR] ?? '').trim();
    const colsClass = resolvePresetColsClass(presetId, tracks);
    const contentWidthMode = String(container.getAttributes?.()?.['data-voodbuilder-content-width'] ?? '').trim();
    const hasContentWidth = contentWidthMode === 'normal'
        || contentWidthMode === 'custom'
        || contentWidthMode === 'full';

    const classes = [...(container.getClasses?.() ?? [])]
        .filter((name) => {
            const token = String(name);

            if (ROW_LAYOUT_CLASSES.includes(token) || GRID_COLS_CLASS_RE.test(token)) {
                return false;
            }

            if (token.startsWith('gjs-')) {
                return false;
            }

            if (hasContentWidth && (token === 'max-w-[80rem]' || token === 'mx-auto' || token === 'w-full')) {
                return true;
            }

            if (resetMeasure && isBoxedOrWidthUtility(token)) {
                return false;
            }

            return true;
        });

    const gapClass = resolveLayoutGapClass(classes);

    for (const token of [...layoutContainerBaseClasses(), 'grid', colsClass, gapClass]) {
        if (! classes.includes(token)) {
            classes.push(token);
        }
    }

    container.setClass(classes);
    container.addAttributes({
        'data-vb-layout-tracks': tracks,
    });

    stripLayoutMechanicInlineStyles(container, { resetMeasure });
}

/**
 * Resolve saved tracks from preset id or data-vb-layout-tracks fallback.
 *
 * @param {object} container
 * @returns {string}
 */
function resolveSavedLayoutTracks(container) {
    const attrs = container.getAttributes?.() ?? {};
    const presetId = String(attrs[LAYOUT_PRESET_ATTR] ?? '').trim();
    const fromAttr = String(attrs['data-vb-layout-tracks'] ?? '').trim();

    if (presetId) {
        const preset = LAYOUT_PRESETS.find((item) => item.id === presetId);

        if (preset?.tracks?.length) {
            return preset.tracks.join(' ');
        }
    }

    if (fromAttr) {
        return fromAttr;
    }

    const fromStyle = String(
        (container.getStyle?.({ inline: true }) ?? {})['grid-template-columns']
        ?? (container.getStyle?.({ inline: true }) ?? {})['--vb-layout-tracks']
        ?? '',
    ).trim();

    return fromStyle;
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

    const attrs = container.getAttributes?.() ?? {};
    const presetId = String(attrs[LAYOUT_PRESET_ATTR] ?? '').trim();
    const savedTracks = resolveSavedLayoutTracks(container);
    const contentWidthMode = String(attrs['data-voodbuilder-content-width'] ?? '').trim();
    const hasContentWidth = contentWidthMode === 'normal'
        || contentWidthMode === 'custom'
        || contentWidthMode === 'full';

    // Restore column tracks without wiping Content width measure/utilities.
    if (presetId || savedTracks) {
        if (presetId) {
            syncContainerLayoutStyles(container, presetId, { resetMeasure: ! hasContentWidth });
        } else {
            applyLayoutTracksToContainer(container, savedTracks, { resetMeasure: ! hasContentWidth });
            container.addAttributes({ [LAYOUT_ATTR]: 'container' });
        }

        return;
    }

    // Author content-width toolbar owns measure — do not wipe it.
    if (hasContentWidth) {
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
    stripLayoutMechanicInlineStyles(container, { resetMeasure: true });
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

    const prevSilent = editor.__voodbuilderLayoutStyleSilent;
    editor.__voodbuilderLayoutStyleSilent = true;

    try {
        const visit = (component) => {
            const attrs = component?.getAttributes?.() ?? {};
            const hasPreset = String(attrs[LAYOUT_PRESET_ATTR] ?? '').trim() !== '';
            const hasTracks = String(attrs['data-vb-layout-tracks'] ?? '').trim() !== '';
            // Only touch Layout package containers — never every .voodbuilder-editor-container.
            const isPkgContainer = layoutKind(component) === 'container'
                || component.get?.('type') === 'voodbuilder-container';

            if (isPkgContainer || hasPreset || hasTracks) {
                syncContainerContentWidth(component);
            }

            component.components?.()?.forEach?.((child) => visit(child));
        };

        visit(wrapper);
    } finally {
        editor.__voodbuilderLayoutStyleSilent = prevSilent;
    }
}

/**
 * Re-apply column tracks before getHtml so a cold 1-col canvas cannot be saved.
 *
 * @param {object} editor
 */
export function ensureLayoutContainersForExport(editor) {
    normalizeLayoutContainersResponsive(editor);
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
        ?? document.querySelector('.voodbuilder-editor-canvas-mount')?.getBoundingClientRect?.();
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
    panel.className = 'voodbuilder-editor-layout-picker';
    panel.setAttribute('data-voodbuilder-layout-picker', '');
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-label', labels.layoutPickerTitle ?? 'Layout');

    const grid = document.createElement('div');
    grid.className = 'voodbuilder-editor-layout-picker__grid';

    const current = String(container.getAttributes?.()?.[LAYOUT_PRESET_ATTR] ?? '');

    for (const preset of LAYOUT_PRESETS) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'voodbuilder-editor-layout-picker__option';
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
    caption.className = 'voodbuilder-editor-layout-picker__caption';
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
<section class="voodbuilder-editor-section body-font w-full py-12" ${LAYOUT_ATTR}="section" data-gjs-type="voodbuilder-section" data-gjs-name="Section">
  <div class="voodbuilder-editor-container w-full" ${LAYOUT_ATTR}="container" data-gjs-type="voodbuilder-container" data-gjs-name="Container" data-gjs-droppable="true"></div>
</section>`.trim();
}

function containerContentHtml() {
    return `
<div class="voodbuilder-editor-container w-full" ${LAYOUT_ATTR}="container" data-gjs-type="voodbuilder-container" data-gjs-name="Container" data-gjs-droppable="true"></div>`.trim();
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
            if (el?.getAttribute?.('data-voodbuilder-block')) {
                return false;
            }

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

    editor.on('canvas:frame:load', () => {
        window.requestAnimationFrame(() => normalizeLayoutContainersResponsive(editor));
        window.setTimeout(() => normalizeLayoutContainersResponsive(editor), 50);
        window.setTimeout(() => normalizeLayoutContainersResponsive(editor), 250);
    });

    // Device switch (desktop → mobile) should stack columns; heal any leftover inline grid.
    editor.on('change:device', () => {
        normalizeLayoutContainersResponsive(editor);
    });

    // Style-panel gap-* must win over legacy inline gap:1rem from older saves.
    editor.on('component:update:classes', (component) => {
        if (! isLayoutContainer(component) || layoutKind(component) !== 'container') {
            return;
        }

        const classes = component.getClasses?.() ?? [];
        const hasGapUtility = [...classes].some((token) => LAYOUT_GAP_CLASS_RE.test(String(token)));

        if (! hasGapUtility) {
            return;
        }

        const style = {
            ...(component.getStyle?.() ?? {}),
            ...(component.getStyle?.({ inline: true }) ?? {}),
        };

        if (style.gap === undefined && style['column-gap'] === undefined && style['row-gap'] === undefined) {
            return;
        }

        delete style.gap;
        delete style['column-gap'];
        delete style['row-gap'];
        // Silent: avoid re-emitting margin-* from content-width into spacing strip.
        component.setStyle(style, SILENT_LAYOUT_STYLE);
        component.removeStyle?.('gap');
        component.removeStyle?.('column-gap');
        component.removeStyle?.('row-gap');
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
            class: 'voodbuilder-editor-toolbar-item--layout',
            'data-voodbuilder-toolbar': 'layout',
            title: labels.layoutPickerTitle ?? 'Layout',
            'aria-label': labels.layoutPickerTitle ?? 'Layout',
        },
        label: lucideIcon('layout-grid', 16),
        command: CMD_LAYOUT_PICKER,
    };
}
