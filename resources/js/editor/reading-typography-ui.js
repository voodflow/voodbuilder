/**
 * Layout-builder Reading tab: typography controls in the inspector,
 * companion page preview inside the chrome layout page-content slot.
 * Visible only when chromeLayoutMode && readingPreviews from companions.
 */

import { tablerIcon } from './editor-icons.js';
import { findPageContentSlotInEditor } from './chrome-content-slot-utils.js';
import { ensureFontLoaded } from './fonts/font-loader.js';

const ELEMENTS = ['h1', 'h2', 'h3', 'h4', 'p'];

/** Same labels as Style → Typography font-size (style-tailwind-class-groups). */
const FONT_SIZE_TOKEN_OPTIONS = [
    { value: 'xs', label: 'xs' },
    { value: 'sm', label: 'sm' },
    { value: 'base', label: 'base' },
    { value: 'lg', label: 'lg' },
    { value: 'xl', label: 'xl' },
    { value: '2xl', label: '2xl' },
    { value: '3xl', label: '3xl' },
    { value: '4xl', label: '4xl' },
    { value: '5xl', label: '5xl' },
    { value: '6xl', label: '6xl' },
    { value: '7xl', label: '7xl' },
    { value: '8xl', label: '8xl' },
    { value: '9xl', label: '9xl' },
];

const FONT_SIZE_TOKENS = FONT_SIZE_TOKEN_OPTIONS.map((opt) => opt.value);

const WEIGHT_OPTIONS = [
    { value: '400', label: 'normal' },
    { value: '500', label: 'medium' },
    { value: '600', label: 'semibold' },
    { value: '700', label: 'bold' },
];

const LEADING_OPTIONS = [
    { value: '1', label: 'none' },
    { value: '1.25', label: 'tight' },
    { value: '1.375', label: 'snug' },
    { value: '1.5', label: 'normal' },
    { value: '1.625', label: 'relaxed' },
    { value: '2', label: 'loose' },
];

const DEFAULT_TYPE_SCALE = {
    h1: { size: '3xl', weight: '600', leading: '1.25' },
    h2: { size: '2xl', weight: '600', leading: '1.375' },
    h3: { size: 'xl', weight: '600', leading: '1.375' },
    h4: { size: 'lg', weight: '600', leading: '1.375' },
    p: { size: 'base', weight: '400', leading: '1.625' },
};

const DEFAULT_SIDEBAR_TYPE_SCALE = {
    h1: { size: 'base', weight: '600', leading: '1.375' },
    h2: { size: 'sm', weight: '600', leading: '1.375' },
    h3: { size: 'xs', weight: '500', leading: '1.5' },
    h4: { size: 'xs', weight: '500', leading: '1.5' },
    p: { size: 'sm', weight: '400', leading: '1.5' },
};

const INTER_STACK = "'Inter Variable', 'Inter', ui-sans-serif, system-ui, sans-serif";
const DEFAULT_BODY_SIZE = 'base';
const PREVIEW_ATTR = 'data-voodbuilder-reading-preview';

const LEGACY_REM_TO_TOKEN = {
    '0.75rem': 'xs',
    '0.8125rem': 'xs',
    '0.875rem': 'sm',
    '0.9375rem': 'sm',
    '1em': 'base',
    '1rem': 'base',
    '1.0625rem': 'base',
    '1.125rem': 'lg',
    '1.25rem': 'xl',
    '1.5rem': '2xl',
    '1.75rem': '3xl',
    '1.875rem': '3xl',
    '2rem': '3xl',
    '2.25rem': '4xl',
    '2.5rem': '4xl',
    '3rem': '5xl',
    '3.75rem': '6xl',
    '4.5rem': '7xl',
    '6rem': '8xl',
    '8rem': '9xl',
};

const LEGACY_BODY_TOKENS = {
    sm: 'sm',
    md: 'base',
    lg: 'lg',
    xl: 'xl',
};

function normalizeSizeToken(raw) {
    let value = String(raw ?? '').trim().toLowerCase();

    if (value.startsWith('text-')) {
        value = value.slice(5);
    }

    if (FONT_SIZE_TOKENS.includes(value)) {
        return value;
    }

    if (Object.prototype.hasOwnProperty.call(LEGACY_BODY_TOKENS, value) && value === 'md') {
        return LEGACY_BODY_TOKENS[value];
    }

    if (Object.prototype.hasOwnProperty.call(LEGACY_REM_TO_TOKEN, value)) {
        return LEGACY_REM_TO_TOKEN[value];
    }

    return DEFAULT_BODY_SIZE;
}

function cssSizeFor(token) {
    return `var(--text-${normalizeSizeToken(token)})`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * @param {Array<{id?: string, family?: string, stack?: string}>|null|undefined} serverFonts
 * @returns {Array<{value: string, label: string, stack: string}>}
 */
function buildFontOptions(serverFonts) {
    const options = [
        { value: 'inter', label: 'Inter Variable (default)', stack: INTER_STACK },
    ];
    const seen = new Set(['inter']);

    for (const font of Array.isArray(serverFonts) ? serverFonts : []) {
        const id = String(font?.id ?? '').trim();

        if (id === '' || seen.has(id) || id.startsWith('inter')) {
            continue;
        }

        seen.add(id);
        options.push({
            value: id,
            label: String(font.family ?? id),
            stack: String(font.stack ?? INTER_STACK),
        });
    }

    return options;
}

function fontStackFor(fontId, fontOptions) {
    const match = fontOptions.find((opt) => opt.value === fontId);

    return match?.stack ?? INTER_STACK;
}

function previewUsesSecondaryFont(preview) {
    if (! preview || typeof preview !== 'object') {
        return false;
    }

    if (preview.secondaryFont === true || preview.hasSidebar === true) {
        return true;
    }

    if (preview.secondaryFont === false || preview.hasSidebar === false) {
        return false;
    }

    return Boolean(preview.eyebrow);
}

function normalizeTypeScale(rawScale, defaults) {
    const typeScale = { ...defaults };

    if (rawScale && typeof rawScale === 'object') {
        for (const element of ELEMENTS) {
            const row = rawScale[element] ?? {};
            typeScale[element] = {
                size: normalizeSizeToken(row.size ?? typeScale[element].size),
                weight: String(row.weight ?? typeScale[element].weight),
                leading: String(row.leading ?? typeScale[element].leading),
            };
        }
    } else {
        for (const element of ELEMENTS) {
            typeScale[element] = {
                ...typeScale[element],
                size: normalizeSizeToken(typeScale[element].size),
            };
        }
    }

    return typeScale;
}

function normalizeState(raw = {}) {
    return {
        font: String(raw.font ?? 'inter'),
        sidebarFont: String(raw.sidebarFont ?? raw.font ?? 'inter'),
        size: normalizeSizeToken(raw.size ?? DEFAULT_BODY_SIZE),
        typeScale: normalizeTypeScale(raw.typeScale, DEFAULT_TYPE_SCALE),
        sidebarTypeScale: normalizeTypeScale(raw.sidebarTypeScale, DEFAULT_SIDEBAR_TYPE_SCALE),
        previewChannel: String(raw.previewChannel ?? ''),
    };
}

function buildCssVariables(state, fontOptions) {
    const stack = fontStackFor(state.font, fontOptions);
    const sidebarStack = fontStackFor(state.sidebarFont, fontOptions);
    const vars = {
        '--vp-font-family-doc': stack,
        '--vp-font-family-sidebar': sidebarStack,
        '--vp-font-size-doc': cssSizeFor(state.size),
        '--vp-font-size-sidebar': cssSizeFor(state.sidebarTypeScale.p.size),
    };

    for (const element of ELEMENTS) {
        const row = state.typeScale[element];
        vars[`--vp-doc-${element}-size`] = cssSizeFor(row.size);
        vars[`--vp-doc-${element}-weight`] = row.weight;
        vars[`--vp-doc-${element}-leading`] = row.leading;
    }

    for (const element of ELEMENTS) {
        const row = state.sidebarTypeScale[element];
        vars[`--vp-sidebar-${element}-size`] = cssSizeFor(row.size);
        vars[`--vp-sidebar-${element}-weight`] = row.weight;
        vars[`--vp-sidebar-${element}-leading`] = row.leading;
    }

    return vars;
}

function styleAttrFromVars(vars) {
    return Object.entries(vars)
        .map(([key, value]) => `${key}: ${value}`)
        .join('; ');
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {Record<string, string>} vars
 */
function applyReadingVarsToCanvas(editor, vars) {
    const doc = editor?.Canvas?.getDocument?.();

    if (! doc) {
        return;
    }

    const targets = [doc.documentElement, doc.body].filter(Boolean);

    for (const el of targets) {
        for (const [name, value] of Object.entries(vars)) {
            el.style.setProperty(name, value);
        }
    }
}

function selectHtml(name, options, selected, attrs = '') {
    const opts = options.map((opt) => {
        const value = typeof opt === 'string' ? opt : opt.value;
        const label = typeof opt === 'string' ? opt : opt.label;

        return `<option value="${escapeHtml(value)}" ${value === selected ? 'selected' : ''}>${escapeHtml(label)}</option>`;
    }).join('');

    return `<select data-vb-reading="${escapeHtml(name)}" ${attrs} class="voodbuilder-editor-reading-select">${opts}</select>`;
}

function fieldRow(label, controlHtml) {
    return `
        <label class="voodbuilder-editor-reading-field">
            <span class="voodbuilder-editor-reading-field__label">${escapeHtml(label)}</span>
            ${controlHtml}
        </label>
    `;
}

function previewUsesAside(preview) {
    if (! preview || typeof preview !== 'object') {
        return false;
    }

    if (preview.hasAside === true || typeof preview.asideHtml === 'string') {
        return true;
    }

    return previewUsesSecondaryFont(preview);
}

function defaultSidebarHtml(preview, labels) {
    if (typeof preview.sidebarHtml === 'string' && preview.sidebarHtml.trim() !== '') {
        return preview.sidebarHtml;
    }

    return `
        <div class="voodbuilder-editor-reading__nav">
            <p class="voodbuilder-editor-reading__sidebar-title">${escapeHtml(preview.eyebrow || preview.label || 'Nav')}</p>
            <div class="voodbuilder-editor-reading__nav-group">
                <div class="voodbuilder-editor-reading__nav-group-title">${escapeHtml(labels.readingNavGroup ?? 'Getting Started')}</div>
                <a class="voodbuilder-editor-reading__nav-link is-active" href="#">${escapeHtml(labels.readingNavActive ?? 'Current page')}</a>
                <a class="voodbuilder-editor-reading__nav-link" href="#">${escapeHtml(labels.readingNavItem ?? 'Section')}</a>
            </div>
            <div class="voodbuilder-editor-reading__nav-group">
                <div class="voodbuilder-editor-reading__nav-group-title">${escapeHtml(labels.readingNavGroupAlt ?? 'More')}</div>
                <a class="voodbuilder-editor-reading__nav-link" href="#">${escapeHtml(labels.readingNavItem ?? 'Section')}</a>
            </div>
        </div>
    `;
}

function defaultAsideHtml(preview, labels) {
    if (typeof preview.asideHtml === 'string' && preview.asideHtml.trim() !== '') {
        return preview.asideHtml;
    }

    const article = String(preview.html ?? '');
    const tocItems = [];
    const headingRe = /<h([23])\b[^>]*(?:\sid=["']([^"']+)["'])?[^>]*>([\s\S]*?)<\/h\1>/gi;
    let match;

    while ((match = headingRe.exec(article)) !== null) {
        const level = Number(match[1]);
        const id = match[2] || `section-${tocItems.length + 1}`;
        const title = match[3].replace(/<[^>]+>/g, '').trim();

        if (title !== '') {
            tocItems.push({ level, id, title });
        }
    }

    if (tocItems.length === 0) {
        tocItems.push(
            { level: 2, id: 'overview', title: labels.readingTocOverview ?? 'Overview' },
            { level: 2, id: 'details', title: labels.readingTocDetails ?? 'Details' },
        );
    }

    const links = tocItems.map((item, index) => {
        const levelClass = item.level >= 3 ? ' vp-outline__link--level-3' : '';
        const active = index === 0 ? ' is-active' : '';

        return `<a href="#${escapeHtml(item.id)}" class="vp-outline__link${levelClass}${active}" data-toc-link>${escapeHtml(item.title)}</a>`;
    }).join('');

    return `
        <nav class="vp-outline" aria-label="${escapeHtml(labels.readingTocTitle ?? 'On this page')}">
            <div class="vp-outline__title">${escapeHtml(labels.readingTocTitle ?? 'On this page')}</div>
            <div class="vp-outline__rail">${links}</div>
        </nav>
    `;
}

function buildPreviewMarkup(preview, vars, labels, { showSecondary, showAside }) {
    const frameClass = [
        'voodbuilder-editor-reading__preview-frame',
        showSecondary ? 'has-secondary' : 'is-article-only',
        showAside ? 'has-aside' : '',
    ].filter(Boolean).join(' ');

    const sidebar = showSecondary
        ? `
            <aside class="voodbuilder-editor-reading__sidebar" data-voodbuilder-reading-sidebar>
                <div class="voodbuilder-editor-reading__sidebar-scroll" data-vb-reading-clear-pad>
                    <nav class="voodbuilder-editor-reading__sidebar-nav" aria-label="Documentation">
                        ${defaultSidebarHtml(preview, labels)}
                    </nav>
                </div>
            </aside>
        `
        : '';

    const aside = showAside
        ? `
            <aside class="voodbuilder-editor-reading__aside" data-voodbuilder-reading-sidebar>
                <div class="voodbuilder-editor-reading__aside-scroll">
                    ${defaultAsideHtml(preview, labels)}
                </div>
            </aside>
        `
        : '';

    return `
        <div class="${frameClass}" style="${escapeHtml(styleAttrFromVars(vars))}">
            ${sidebar}
            <div class="voodbuilder-editor-reading__main">
                <div class="voodbuilder-editor-reading__main-pad" data-vb-reading-clear-pad>
                    <div class="voodbuilder-editor-reading__main-row">
                        <article class="vp-doc voodbuilder-editor-reading__article">
                            ${preview.html ?? ''}
                        </article>
                        ${aside}
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Keep Reading preview content below sticky/fixed site nav.
 * Pads sidebar + main columns so the left rail bg stays continuous under the menu.
 *
 * @param {import('grapesjs').Editor} editor
 * @param {HTMLElement|null} previewHost
 */
function syncReadingPreviewClearance(editor, previewHost) {
    if (! (previewHost instanceof HTMLElement)) {
        return;
    }

    const doc = previewHost.ownerDocument;
    const win = doc?.defaultView;
    const frame = previewHost.querySelector('.voodbuilder-editor-reading__preview-frame');

    if (! doc || ! win || ! (frame instanceof HTMLElement)) {
        return;
    }

    const breathPx = 48;
    const header = doc.querySelector('header[role="banner"]');
    let clearancePx = breathPx;

    if (header instanceof HTMLElement) {
        const headerRect = header.getBoundingClientRect();
        const headerHeight = Math.max(Math.ceil(headerRect.height), 64);
        const hostTop = previewHost.getBoundingClientRect().top;
        const overlapPx = Math.max(0, Math.ceil(headerRect.bottom - hostTop));
        const position = win.getComputedStyle(header).position;
        const pinned = position === 'fixed'
            || position === 'sticky'
            || header.classList.contains('fixed')
            || header.classList.contains('sticky');

        if (pinned || overlapPx > 4) {
            clearancePx = Math.max(overlapPx, headerHeight) + breathPx;
        }
    }

    previewHost.style.setProperty('--vb-reading-clearance', `${clearancePx}px`);

    frame.querySelectorAll('[data-vb-reading-clear-pad]').forEach((node) => {
        if (node instanceof HTMLElement) {
            node.style.setProperty('padding-top', `${clearancePx}px`, 'important');
        }
    });

    const probe = frame.querySelector('article h1, article .vp-doc-title, article p, article') ?? frame;

    if (header instanceof HTMLElement && probe instanceof HTMLElement) {
        const headerBottom = header.getBoundingClientRect().bottom;
        const probeTop = probe.getBoundingClientRect().top;

        if (probeTop < headerBottom + breathPx - 2) {
            clearancePx += Math.ceil(headerBottom + breathPx - probeTop);
            previewHost.style.setProperty('--vb-reading-clearance', `${clearancePx}px`);
            frame.querySelectorAll('[data-vb-reading-clear-pad]').forEach((node) => {
                if (node instanceof HTMLElement) {
                    node.style.setProperty('padding-top', `${clearancePx}px`, 'important');
                }
            });
        }
    }
}

/**
 * @param {() => void} sync
 */
function scheduleReadingPreviewClearance(sync) {
    sync();
    window.requestAnimationFrame(() => {
        sync();
        window.setTimeout(sync, 50);
        window.setTimeout(sync, 150);
        window.setTimeout(sync, 320);
    });
}

/**
 * @param {import('grapesjs').Editor} editor
 * @returns {HTMLElement|null}
 */
function resolveContentSlotEl(editor) {
    const doc = editor?.Canvas?.getDocument?.();

    if (doc) {
        const fromDom = doc.querySelector('[data-voodbuilder-content-slot]:not([data-voodbuilder-page-content])')
            || doc.querySelector('[data-voodbuilder-content-slot]')
            || doc.querySelector('[data-voodbuilder-page-content]');

        if (fromDom instanceof HTMLElement) {
            return fromDom;
        }
    }

    const slot = findPageContentSlotInEditor(editor);
    const el = slot?.getEl?.()
        ?? slot?.getView?.()?.el
        ?? slot?.view?.el
        ?? null;

    return el instanceof HTMLElement ? el : null;
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {{
 *   mount: HTMLElement,
 *   readingTypography?: object,
 *   readingPreviews?: Record<string, {label: string, html: string, eyebrow?: string|null, secondaryFont?: boolean, hasSidebar?: boolean}>,
 *   fonts?: Array<{id?: string, family?: string, stack?: string}>,
 *   labels?: Record<string, string>,
 * }} options
 */
export function registerReadingTypographyUi(editor, options = {}) {
    const mount = options.mount;
    const previews = options.readingPreviews && typeof options.readingPreviews === 'object'
        ? options.readingPreviews
        : {};
    const previewIds = Object.keys(previews);

    if (! mount || previewIds.length === 0) {
        return null;
    }

    const labels = options.labels ?? {};
    const fontOptions = buildFontOptions(options.fonts);

    let state = normalizeState({
        ...(options.readingTypography ?? {}),
        previewChannel: previewIds[0],
    });

    if (! state.previewChannel || ! previews[state.previewChannel]) {
        state.previewChannel = previewIds[0];
    }

    let previewVisible = editor.__voodbuilderInspectorActiveTab === 'reading';
    /** @type {HTMLElement|null} */
    let previewHost = null;
    let paintTimer = 0;
    /** User-opened type-scale panels (`primary.h1`, `secondary.p`, …). Start all closed. */
    const openScalePanels = new Set();

    editor.__voodbuilderReadingTypography = {
        font: state.font,
        sidebarFont: state.sidebarFont,
        size: state.size,
        typeScale: state.typeScale,
        sidebarTypeScale: state.sidebarTypeScale,
    };

    const clearPreviewHosts = (root) => {
        const scope = root
            ?? editor?.Canvas?.getDocument?.()
            ?? null;

        if (! scope?.querySelectorAll) {
            previewHost = null;

            return;
        }

        scope.querySelectorAll(`[${PREVIEW_ATTR}]`).forEach((node) => node.remove());
        previewHost = null;
    };

    const purgePreviewComponents = () => {
        const slot = findPageContentSlotInEditor(editor);

        if (! slot?.components) {
            return;
        }

        [...(slot.components().models ?? [])].forEach((component) => {
            const attrs = component.getAttributes?.() ?? {};
            const className = String(attrs.class ?? '');

            if (
                attrs[PREVIEW_ATTR]
                || className.includes('voodbuilder-editor-reading__preview')
            ) {
                component.remove?.({ temporary: true });
            }
        });
    };

    const detachCanvasPreview = () => {
        const slotEl = resolveContentSlotEl(editor);

        slotEl?.classList.remove('is-reading-preview-active');
        purgePreviewComponents();
        clearPreviewHosts(slotEl?.ownerDocument ?? editor?.Canvas?.getDocument?.());
    };

    const applyPreviewVars = () => {
        const vars = buildCssVariables(state, fontOptions);
        const frame = previewHost?.querySelector?.('.voodbuilder-editor-reading__preview-frame');

        if (frame instanceof HTMLElement) {
            frame.setAttribute('style', styleAttrFromVars(vars));
        }

        applyReadingVarsToCanvas(editor, vars);
        scheduleReadingPreviewClearance(() => syncReadingPreviewClearance(editor, previewHost));
    };

    const paintCanvasPreview = () => {
        if (! previewVisible) {
            detachCanvasPreview();

            return;
        }

        const slotEl = resolveContentSlotEl(editor);

        if (! slotEl) {
            return;
        }

        // Grapes may promote injected DOM into components — drop those first.
        purgePreviewComponents();

        const doc = slotEl.ownerDocument;
        const preview = previews[state.previewChannel] ?? previews[previewIds[0]];
        const showSecondary = previewUsesSecondaryFont(preview);
        const showAside = previewUsesAside(preview);
        const vars = buildCssVariables(state, fontOptions);

        doc.querySelectorAll(`[${PREVIEW_ATTR}]`).forEach((node) => {
            if (node !== previewHost) {
                node.remove();
            }
        });

        const hostStillValid = previewHost instanceof HTMLElement
            && previewHost.isConnected
            && previewHost.parentElement === slotEl;

        if (! hostStillValid) {
            previewHost?.remove();
            previewHost = doc.createElement('div');
            previewHost.setAttribute(PREVIEW_ATTR, '1');
            previewHost.className = 'voodbuilder-editor-reading__preview voodbuilder-editor-reading__preview--canvas';
            slotEl.appendChild(previewHost);
        }

        [...slotEl.querySelectorAll(`[${PREVIEW_ATTR}]`)].forEach((node) => {
            if (node !== previewHost) {
                node.remove();
            }
        });

        slotEl.classList.add('is-reading-preview-active');
        previewHost.innerHTML = buildPreviewMarkup(preview, vars, labels, { showSecondary, showAside });
        applyReadingVarsToCanvas(editor, vars);
        scheduleReadingPreviewClearance(() => syncReadingPreviewClearance(editor, previewHost));

        // If Grapes synced the host into the model, strip it again without wiping our DOM host.
        window.requestAnimationFrame(() => {
            purgePreviewComponents();

            if (
                previewVisible
                && previewHost instanceof HTMLElement
                && ! previewHost.isConnected
                && slotEl.isConnected
            ) {
                slotEl.appendChild(previewHost);
            }

            scheduleReadingPreviewClearance(() => syncReadingPreviewClearance(editor, previewHost));
        });
    };

    const schedulePaint = () => {
        window.clearTimeout(paintTimer);
        paintCanvasPreview();
        paintTimer = window.setTimeout(paintCanvasPreview, 60);
    };

    const renderControls = () => {
        const preview = previews[state.previewChannel] ?? previews[previewIds[0]];
        const showSecondary = previewUsesSecondaryFont(preview);
        const channelOptions = previewIds.map((id) => ({
            value: id,
            label: previews[id].label ?? id,
        }));

        const scaleDetails = (scope, element, row) => {
            const panelKey = `${scope}.${element}`;
            const openAttr = openScalePanels.has(panelKey) ? ' open' : '';
            const prefix = scope === 'secondary' ? 'sidebarScale' : 'scale';

            return `
                <details class="voodbuilder-editor-reading-element" data-vb-reading-scale="${escapeHtml(panelKey)}"${openAttr}>
                    <summary>${escapeHtml(element.toUpperCase())}</summary>
                    <div class="voodbuilder-editor-reading-element__grid">
                        ${fieldRow(labels.readingSize ?? 'Size', selectHtml(`${prefix}.${element}.size`, FONT_SIZE_TOKEN_OPTIONS, row.size))}
                        ${fieldRow(labels.readingWeight ?? 'Weight', selectHtml(`${prefix}.${element}.weight`, WEIGHT_OPTIONS, row.weight))}
                        ${fieldRow(labels.readingLineHeight ?? 'Line height', selectHtml(`${prefix}.${element}.leading`, LEADING_OPTIONS, row.leading))}
                    </div>
                </details>
            `;
        };

        const elementBlocks = ELEMENTS.map((element) => scaleDetails('primary', element, state.typeScale[element])).join('');
        const secondaryScaleBlocks = ELEMENTS.map((element) => scaleDetails('secondary', element, state.sidebarTypeScale[element])).join('');

        const secondaryFields = showSecondary
            ? `
                ${fieldRow(
                    labels.readingSecondaryFont ?? 'Secondary font',
                    selectHtml('sidebarFont', fontOptions, state.sidebarFont),
                )}
                <div class="voodbuilder-editor-reading-scale">
                    <p class="voodbuilder-editor-reading-scale__title">${escapeHtml(labels.readingSecondaryTypeScale ?? 'Secondary type scale')}</p>
                    ${secondaryScaleBlocks}
                </div>
            `
            : '';

        mount.innerHTML = `
            <div class="voodbuilder-editor-reading">
                <div class="voodbuilder-editor-reading__intro">
                    <div class="voodbuilder-editor-reading__intro-row">
                        <strong>${escapeHtml(labels.readingTabTitle ?? 'Integration')}</strong>
                        <button
                            type="button"
                            class="voodbuilder-editor-reading-reset"
                            data-vb-reading-reset
                            title="${escapeHtml(labels.readingResetTitle ?? labels.readingReset ?? 'Reset to defaults')}"
                        >
                            ${tablerIcon('rotate-ccw', 14)}
                            <span>${escapeHtml(labels.readingReset ?? 'Reset')}</span>
                        </button>
                    </div>
                    <p>${escapeHtml(labels.readingTabHint ?? 'Typography for companion pages (docs, tutorials, …). Live preview appears in Page content.')}</p>
                </div>
                <div class="voodbuilder-editor-reading__controls">
                    ${fieldRow(labels.readingPreviewSample ?? 'Preview sample', selectHtml('previewChannel', channelOptions, state.previewChannel))}
                    ${fieldRow(labels.readingPrimaryFont ?? 'Primary font', selectHtml('font', fontOptions, state.font))}
                    ${fieldRow(labels.readingBaseSize ?? 'Body base size', selectHtml('size', FONT_SIZE_TOKEN_OPTIONS, state.size))}
                    <div class="voodbuilder-editor-reading-scale">
                        <p class="voodbuilder-editor-reading-scale__title">${escapeHtml(labels.readingTypeScale ?? 'Primary type scale')}</p>
                        ${elementBlocks}
                    </div>
                    ${secondaryFields}
                </div>
            </div>
        `;
    };

    const resetToDefaults = () => {
        const channel = state.previewChannel || previewIds[0] || '';
        Object.assign(state, normalizeState({
            font: 'inter',
            sidebarFont: 'inter',
            size: DEFAULT_BODY_SIZE,
            typeScale: DEFAULT_TYPE_SCALE,
            sidebarTypeScale: DEFAULT_SIDEBAR_TYPE_SCALE,
            previewChannel: channel,
        }));
        openScalePanels.clear();
        renderControls();
        commit({ rebuild: true });
    };

    const syncPreview = ({ rebuild = false } = {}) => {
        if (! previewVisible) {
            detachCanvasPreview();

            return;
        }

        if (rebuild || ! (previewHost instanceof HTMLElement) || ! previewHost.isConnected) {
            schedulePaint();

            return;
        }

        applyPreviewVars();
    };

    const render = () => {
        const preview = previews[state.previewChannel] ?? previews[previewIds[0]];

        if (! previewUsesSecondaryFont(preview)) {
            state.sidebarFont = state.font;
        }

        renderControls();
        syncPreview({ rebuild: true });
    };

    const ensureReadingFonts = () => {
        const ids = [state.font, state.sidebarFont].filter((id) => id && id !== 'inter');

        ids.forEach((id) => {
            void ensureFontLoaded(editor, id, { reassert: false });
        });
    };

    const commit = ({ rebuild = false } = {}) => {
        editor.__voodbuilderReadingTypography = {
            font: state.font,
            sidebarFont: state.sidebarFont,
            size: state.size,
            typeScale: state.typeScale,
            sidebarTypeScale: state.sidebarTypeScale,
        };
        ensureReadingFonts();
        editor.trigger?.('voodbuilder:reading-typography');
        renderControls();
        syncPreview({ rebuild });
    };

    const setPreviewVisible = (visible) => {
        previewVisible = Boolean(visible);
        schedulePaint();
    };

    mount.addEventListener('toggle', (event) => {
        const details = event.target;

        if (! (details instanceof HTMLDetailsElement)) {
            return;
        }

        const panelKey = details.getAttribute('data-vb-reading-scale');

        if (! panelKey) {
            return;
        }

        if (details.open) {
            openScalePanels.add(panelKey);
        } else {
            openScalePanels.delete(panelKey);
        }
    }, true);

    mount.addEventListener('click', (event) => {
        const target = event.target;

        if (! (target instanceof Element)) {
            return;
        }

        if (target.closest('[data-vb-reading-reset]')) {
            event.preventDefault();
            resetToDefaults();
        }
    });

    mount.addEventListener('change', (event) => {
        const target = event.target;
        if (! (target instanceof HTMLSelectElement)) {
            return;
        }

        const key = target.getAttribute('data-vb-reading');
        if (! key) {
            return;
        }

        if (key === 'previewChannel') {
            state.previewChannel = target.value;
            renderControls();
            syncPreview({ rebuild: true });

            return;
        }

        if (key === 'font' || key === 'sidebarFont' || key === 'size') {
            state[key] = key === 'size' ? normalizeSizeToken(target.value) : target.value;

            if (key === 'font' && ! previewUsesSecondaryFont(previews[state.previewChannel])) {
                state.sidebarFont = state.font;
            }

            commit({ rebuild: key === 'font' || key === 'sidebarFont' });

            return;
        }

        if (key.startsWith('scale.')) {
            const [, element, prop] = key.split('.');
            if (ELEMENTS.includes(element) && prop) {
                openScalePanels.add(`primary.${element}`);
                state.typeScale[element] = {
                    ...state.typeScale[element],
                    [prop]: prop === 'size' ? normalizeSizeToken(target.value) : target.value,
                };
                commit({ rebuild: false });
            }

            return;
        }

        if (key.startsWith('sidebarScale.')) {
            const [, element, prop] = key.split('.');
            if (ELEMENTS.includes(element) && prop) {
                openScalePanels.add(`secondary.${element}`);
                state.sidebarTypeScale[element] = {
                    ...state.sidebarTypeScale[element],
                    [prop]: prop === 'size' ? normalizeSizeToken(target.value) : target.value,
                };
                commit({ rebuild: false });
            }
        }
    });

    const previousActivate = editor.__voodbuilderActivateInspectorTab;

    if (typeof previousActivate === 'function' && ! editor.__voodbuilderReadingTabWrapped) {
        editor.__voodbuilderReadingTabWrapped = true;
        editor.__voodbuilderActivateInspectorTab = (tabId, opts) => {
            previousActivate(tabId, opts);
            setPreviewVisible(tabId === 'reading');
        };
    }

    editor.on('voodbuilder:inspector-tab', (tabId) => {
        setPreviewVisible(tabId === 'reading');
    });

    editor.on('canvas:frame:load', () => {
        applyReadingVarsToCanvas(editor, buildCssVariables(state, fontOptions));
        schedulePaint();
    });

    editor.on('voodbuilder:reading-preview:detach', detachCanvasPreview);
    editor.on('voodbuilder:reading-preview:attach', () => {
        if (previewVisible) {
            schedulePaint();
        }
    });

    // Grapes re-renders the content slot view and would wipe injected DOM — re-paint.
    const hookSlotRender = () => {
        const slot = findPageContentSlotInEditor(editor);

        if (! slot || slot.__voodbuilderReadingPreviewHooked) {
            return;
        }

        const view = slot.getView?.() ?? slot.view;

        if (! view) {
            return;
        }

        slot.__voodbuilderReadingPreviewHooked = true;
        const previousOnRender = typeof view.onRender === 'function'
            ? view.onRender.bind(view)
            : null;

        view.onRender = (...args) => {
            previousOnRender?.(...args);

            if (previewVisible) {
                window.requestAnimationFrame(paintCanvasPreview);
            }
        };
    };

    hookSlotRender();
    editor.on('load', hookSlotRender);
    editor.on('canvas:frame:load', hookSlotRender);

    setPreviewVisible(editor.__voodbuilderInspectorActiveTab === 'reading');
    render();

    return {
        getState: () => ({ ...editor.__voodbuilderReadingTypography }),
        refresh: render,
        detachPreview: detachCanvasPreview,
    };
}

/**
 * Inject Reading inspector tab/panel when companions registered previews.
 */
export function ensureReadingInspectorTab(shellMounts, labels = {}) {
    const tablist = shellMounts?.tablist;
    const panels = shellMounts?.panels;

    if (! tablist || ! panels || tablist.querySelector('[data-voodbuilder-tab="reading"]')) {
        return shellMounts;
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'voodbuilder-editor-inspector-tab';
    button.dataset.voodbuilderTab = 'reading';
    button.setAttribute('role', 'tab');
    button.setAttribute('aria-selected', 'false');
    button.setAttribute('aria-label', labels.tabReading ?? 'Integration');
    button.title = labels.tabReading ?? 'Integration';
    // Puzzle / components — companion integration, not typography (avoids the "T").
    button.innerHTML = tablerIcon('components', 17);
    tablist.appendChild(button);

    const panel = document.createElement('div');
    panel.className = 'voodbuilder-editor-inspector-panel';
    panel.dataset.voodbuilderInspector = 'reading';
    panel.hidden = true;
    panel.setAttribute('aria-hidden', 'true');
    panel.innerHTML = '<div class="voodbuilder-editor-reading-mount"></div>';
    panels.appendChild(panel);

    return {
        ...shellMounts,
        reading: panel.querySelector('.voodbuilder-editor-reading-mount'),
    };
}
