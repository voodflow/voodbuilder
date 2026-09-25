/**
 * Layout-builder Reading tab: typography controls in the inspector,
 * companion page preview inside the chrome layout page-content slot.
 * Visible only when chromeLayoutMode && readingPreviews from companions.
 */

import { lucideIcon, tablerIcon } from './editor-icons.js';
import { findPageContentSlotInEditor } from './chrome-content-slot-utils.js';
import { ensureFontLoaded } from './fonts/font-loader.js';
import { initReadingLocalNav } from './site-chrome-runtime.js';

const ELEMENTS = ['h1', 'h2', 'h3', 'h4', 'h5', 'p'];

/** Companion extras with VitePress-like defaults (no site Settings equivalent). */
const COLUMN_ELEMENTS = ['h5'];

/** Same defaults as ChromeLayoutReadingTypography::columnDefaults(). */
const COLUMN_DEFAULTS = {
    h5: { size: 'xs', sizeMd: null, sizeLg: null, weight: '700', leading: '1.5' },
};

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

/**
 * Mobile-first viewports — same mapping as the Style panel strip.
 * `sizeProp` is the type-scale row key holding that viewport's override.
 */
const VIEWPORTS = [
    { device: 'mobilePortrait', sizeProp: 'size' },
    { device: 'tablet', sizeProp: 'sizeMd' },
    { device: 'desktop', sizeProp: 'sizeLg' },
];

const SCALE_PROPS = ['size', 'sizeMd', 'sizeLg', 'weight', 'leading'];

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

/** @returns {string|null} */
function optionalValue(raw, { size = false } = {}) {
    const value = String(raw ?? '').trim();

    if (value === '') {
        return null;
    }

    return size ? normalizeSizeToken(value) : value;
}

/**
 * Layout overrides: every prop null = inherit site Settings.
 */
function normalizeOverrideTypeScale(rawScale) {
    const source = rawScale && typeof rawScale === 'object' ? rawScale : {};
    const typeScale = {};

    for (const element of ELEMENTS) {
        const row = source[element] ?? {};
        typeScale[element] = {};

        for (const prop of SCALE_PROPS) {
            typeScale[element][prop] = optionalValue(row[prop], { size: prop.startsWith('size') });
        }
    }

    return typeScale;
}

function emptyOverrideTypeScale() {
    return normalizeOverrideTypeScale(null);
}

function normalizeState(raw = {}) {
    return {
        font: String(raw.font ?? ''),
        headingFont: String(raw.headingFont ?? ''),
        typeScale: normalizeOverrideTypeScale(raw.typeScale),
        previewChannel: String(raw.previewChannel ?? ''),
    };
}

/**
 * Site Settings typography the layout inherits from (labels + type scale).
 */
function normalizeInherited(raw) {
    const source = raw && typeof raw === 'object' ? raw : {};
    const sourceScale = source.typeScale && typeof source.typeScale === 'object' ? source.typeScale : {};
    const typeScale = {};

    for (const element of ELEMENTS) {
        const row = sourceScale[element] ?? COLUMN_DEFAULTS[element] ?? {};
        typeScale[element] = {
            size: optionalValue(row.size, { size: true }) ?? DEFAULT_BODY_SIZE,
            sizeMd: optionalValue(row.sizeMd, { size: true }),
            sizeLg: optionalValue(row.sizeLg, { size: true }),
            weight: optionalValue(row.weight) ?? '400',
            leading: optionalValue(row.leading) ?? '1.5',
        };
    }

    return {
        bodyLabel: String(source.bodyLabel ?? source.bodyFont ?? ''),
        headingLabel: String(source.headingLabel ?? source.headingFont ?? ''),
        typeScale,
    };
}

function formatLabel(template, replacements) {
    return Object.entries(replacements).reduce(
        (text, [key, value]) => text.replace(`{${key}}`, String(value ?? '')),
        String(template),
    );
}

function optionLabel(options, value) {
    return options.find((opt) => opt.value === value)?.label ?? String(value ?? '');
}

/**
 * First non-null size at or below the viewport (mobile-first cascade).
 *
 * @param {Record<string, string|null>|undefined} row
 * @param {number} viewportIndex
 * @returns {string|null}
 */
function cascadedSize(row, viewportIndex) {
    for (let i = viewportIndex; i >= 0; i -= 1) {
        const value = row?.[VIEWPORTS[i].sizeProp];

        if (typeof value === 'string' && value !== '') {
            return value;
        }
    }

    return null;
}

/**
 * @param {string|null|undefined} deviceId
 * @returns {number}
 */
function viewportIndexForDevice(deviceId) {
    const id = String(deviceId ?? '').trim();

    if (id === 'mobilePortrait' || id === 'mobile') {
        return 0;
    }

    if (id === 'tablet') {
        return 1;
    }

    return 2;
}

function currentDeviceId(editor) {
    try {
        return String(editor?.Devices?.getSelected?.()?.get?.('id') ?? editor?.getDevice?.() ?? 'desktop');
    } catch {
        return 'desktop';
    }
}

/**
 * Canvas preview vars for one viewport. Inherited props point at the site
 * `--vp-app-*` vars so Settings stay the single source of truth.
 *
 * @param {ReturnType<typeof normalizeState>} state
 * @param {Array<{value: string, stack: string}>} fontOptions
 * @param {number} viewportIndex
 */
function buildCssVariables(state, fontOptions, viewportIndex) {
    const vars = {
        '--vp-font-family-doc': state.font === ''
            ? 'var(--vp-font-family-body, var(--font-sans))'
            : fontStackFor(state.font, fontOptions),
        '--vp-font-family-doc-heading': state.headingFont === ''
            ? 'var(--vp-font-family-heading, var(--font-heading))'
            : fontStackFor(state.headingFont, fontOptions),
        '--vp-font-family-sidebar': 'var(--vp-font-family-doc)',
        '--vp-font-size-doc': 'var(--vp-doc-p-size)',
    };

    for (const element of ELEMENTS) {
        const row = state.typeScale[element];
        const size = cascadedSize(row, viewportIndex);
        const values = {
            size: size === null ? inheritedCssValue(element, 'size') : cssSizeFor(size),
            weight: row.weight ?? inheritedCssValue(element, 'weight'),
            leading: row.leading ?? inheritedCssValue(element, 'leading'),
        };

        // Unset column vars fall back to each stylesheet rule's own default.
        for (const [prop, value] of Object.entries(values)) {
            vars[`--vp-doc-${element}-${prop}`] = value ?? '';
        }
    }

    return vars;
}

/** @returns {string|null} null = leave the var unset (side-column elements). */
function inheritedCssValue(element, prop) {
    if (COLUMN_ELEMENTS.includes(element)) {
        return null;
    }

    return `var(--vp-app-${element}-${prop})`;
}

function styleAttrFromVars(vars) {
    return Object.entries(vars)
        .filter(([, value]) => value !== '')
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
                <h5 class="voodbuilder-editor-reading__nav-group-title">${escapeHtml(labels.readingNavGroup ?? 'Getting Started')}</h5>
                <a class="voodbuilder-editor-reading__nav-link is-active" href="#">${escapeHtml(labels.readingNavActive ?? 'Current page')}</a>
                <a class="voodbuilder-editor-reading__nav-link" href="#">${escapeHtml(labels.readingNavItem ?? 'Section')}</a>
            </div>
            <div class="voodbuilder-editor-reading__nav-group">
                <h5 class="voodbuilder-editor-reading__nav-group-title">${escapeHtml(labels.readingNavGroupAlt ?? 'More')}</h5>
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
            <h5 class="vp-outline__title">${escapeHtml(labels.readingTocTitle ?? 'On this page')}</h5>
            <div class="vp-outline__rail">${links}</div>
        </nav>
    `;
}

/** Mobile / tablet local nav — same hooks as the public `<x-voodbuilder::reading-local-nav>`. */
function localNavPreviewHtml(labels, { showSecondary, showAside, outlineHtml = '' }) {
    if (! showSecondary && ! showAside) {
        return '';
    }

    const menu = showSecondary
        ? `<button type="button" class="vp-local-nav__menu" data-vp-reading-drawer-toggle aria-controls="vp-editor-reading-drawer" aria-expanded="false">
                <svg class="vp-local-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h16" /></svg>
                <span>${escapeHtml(labels.readingLocalNavMenu ?? 'Menu')}</span>
            </button>`
        : '';
    const outline = showAside
        ? `<details class="vp-local-nav__outline" data-vp-local-outline>
                <summary>
                    <span>${escapeHtml(labels.readingOnThisPage ?? labels.readingTocTitle ?? 'On this page')}</span>
                    <svg class="vp-local-nav__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6" /></svg>
                </summary>
                <div class="vp-local-nav__outline-panel">
                    <a href="#" class="vp-local-nav__top" data-vp-local-outline-top>${escapeHtml(labels.readingReturnToTop ?? 'Return to top')}</a>
                    ${outlineHtml}
                </div>
            </details>`
        : '';

    return `
        <div class="vp-local-nav voodbuilder-editor-reading__local-nav${showSecondary ? ' has-sidebar' : ''}${showAside ? ' has-outline' : ''}" data-vp-local-nav>
            ${menu}
            ${outline}
        </div>
    `;
}

function pagerPreviewHtml(labels) {
    return `
        <nav class="voodbuilder-editor-reading__pager" aria-label="Pager">
            <a class="voodbuilder-editor-reading__pager-link" href="#">
                <span class="voodbuilder-editor-reading__pager-desc">${escapeHtml(labels.readingPagerPrev ?? 'Previous page')}</span>
                <span class="voodbuilder-editor-reading__pager-title">${escapeHtml(labels.readingNavGroup ?? 'Getting Started')}</span>
            </a>
            <a class="voodbuilder-editor-reading__pager-link is-next" href="#">
                <span class="voodbuilder-editor-reading__pager-desc">${escapeHtml(labels.readingPagerNext ?? 'Next page')}</span>
                <span class="voodbuilder-editor-reading__pager-title">${escapeHtml(labels.readingNavItem ?? 'Section')}</span>
            </a>
        </nav>
    `;
}

function buildPreviewMarkup(preview, vars, labels, { showSecondary, showAside }) {
    const frameClass = [
        'voodbuilder-editor-reading__preview-frame',
        showSecondary ? 'has-secondary' : 'is-article-only',
        showAside ? 'has-aside' : '',
    ].filter(Boolean).join(' ');

    const outlineHtml = showAside ? defaultAsideHtml(preview, labels) : '';

    const sidebar = showSecondary
        ? `
            <aside
                id="vp-editor-reading-drawer"
                class="voodbuilder-editor-reading__sidebar vp-reading-drawer"
                data-voodbuilder-reading-sidebar
                data-vp-reading-drawer
            >
                <div class="voodbuilder-editor-reading__sidebar-scroll" data-vb-reading-clear-pad>
                    <nav class="voodbuilder-editor-reading__sidebar-nav" aria-label="Documentation">
                        ${defaultSidebarHtml(preview, labels)}
                    </nav>
                </div>
            </aside>
            <div class="vp-reading-backdrop voodbuilder-editor-reading__backdrop" data-vp-reading-drawer-close aria-hidden="true"></div>
        `
        : '';

    const aside = showAside
        ? `
            <aside class="voodbuilder-editor-reading__aside" data-voodbuilder-reading-sidebar>
                <div class="voodbuilder-editor-reading__aside-scroll">
                    ${outlineHtml}
                </div>
            </aside>
        `
        : '';

    return `
        <div class="${frameClass}" style="${escapeHtml(styleAttrFromVars(vars))}">
            ${sidebar}
            <div class="voodbuilder-editor-reading__main">
                <div class="voodbuilder-editor-reading__main-pad" data-vb-reading-clear-pad>
                    ${localNavPreviewHtml(labels, { showSecondary, showAside, outlineHtml })}
                    <div class="voodbuilder-editor-reading__main-row">
                        <article class="vp-doc voodbuilder-editor-reading__article">
                            ${preview.html ?? ''}
                            ${pagerPreviewHtml(labels)}
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
    const inherited = normalizeInherited(options.readingTypography?.inherited, fontOptions);

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
    /** User-opened type-scale panels (`primary.h1`, …). Start all closed. */
    const openScalePanels = new Set();

    const snapshot = () => ({
        font: state.font,
        headingFont: state.headingFont,
        typeScale: state.typeScale,
    });
    const activeViewportIndex = () => viewportIndexForDevice(currentDeviceId(editor));
    const cssVariables = () => buildCssVariables(state, fontOptions, activeViewportIndex());

    editor.__voodbuilderReadingTypography = snapshot();

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
        const vars = cssVariables();
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
        const vars = cssVariables();

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
        initReadingLocalNav(doc);
        doc.documentElement?.classList?.remove('vp-reading-drawer-open');

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

            initReadingLocalNav(doc);
            scheduleReadingPreviewClearance(() => syncReadingPreviewClearance(editor, previewHost));
        });
    };

    const schedulePaint = () => {
        window.clearTimeout(paintTimer);
        paintCanvasPreview();
        paintTimer = window.setTimeout(paintCanvasPreview, 60);
    };

    const renderControls = () => {
        const channelOptions = previewIds.map((id) => ({
            value: id,
            label: previews[id].label ?? id,
        }));

        const viewportIndex = activeViewportIndex();
        const sizeProp = VIEWPORTS[viewportIndex].sizeProp;
        const inheritLabel = (value) => formatLabel(labels.readingInheritValue ?? 'Inherit ({value})', { value });
        const siteFontLabel = (font) => formatLabel(labels.readingInheritSiteFont ?? 'From site settings ({font})', { font });

        const elementLabel = (element) => {
            if (element === 'h5') {
                return labels.readingElementH5 ?? 'H5';
            }

            return element.toUpperCase();
        };

        const primaryDetails = (element) => {
            const panelKey = `primary.${element}`;
            const openAttr = openScalePanels.has(panelKey) ? ' open' : '';
            const row = state.typeScale[element];
            const siteRow = inherited.typeScale[element];
            const inheritedSize = (viewportIndex > 0 ? cascadedSize(row, viewportIndex - 1) : null)
                ?? cascadedSize(siteRow, viewportIndex);
            const effectiveSize = cascadedSize(row, viewportIndex) ?? cascadedSize(siteRow, viewportIndex);
            const effectiveWeight = row.weight ?? siteRow.weight;
            const overridden = SCALE_PROPS.some((prop) => row[prop] !== null);

            return `
                <details class="voodbuilder-editor-reading-element${overridden ? ' is-overridden' : ''}" data-vb-reading-scale="${escapeHtml(panelKey)}"${openAttr}>
                    <summary>
                        <span>${escapeHtml(elementLabel(element))}</span>
                        <span class="voodbuilder-editor-reading-element__meta">${escapeHtml(`${effectiveSize ?? ''} · ${optionLabel(WEIGHT_OPTIONS, effectiveWeight)}`)}</span>
                    </summary>
                    <div class="voodbuilder-editor-reading-element__grid">
                        ${fieldRow(labels.readingSize ?? 'Size', selectHtml(
                            `scale.${element}.${sizeProp}`,
                            [{ value: '', label: inheritLabel(inheritedSize ?? '') }, ...FONT_SIZE_TOKEN_OPTIONS],
                            row[sizeProp] ?? '',
                        ))}
                        ${fieldRow(labels.readingWeight ?? 'Weight', selectHtml(
                            `scale.${element}.weight`,
                            [{ value: '', label: inheritLabel(optionLabel(WEIGHT_OPTIONS, siteRow.weight)) }, ...WEIGHT_OPTIONS],
                            row.weight ?? '',
                        ))}
                        ${fieldRow(labels.readingLineHeight ?? 'Line height', selectHtml(
                            `scale.${element}.leading`,
                            [{ value: '', label: inheritLabel(optionLabel(LEADING_OPTIONS, siteRow.leading)) }, ...LEADING_OPTIONS],
                            row.leading ?? '',
                        ))}
                    </div>
                </details>
            `;
        };

        const viewportButtons = VIEWPORTS.map(({ device }, index) => {
            const label = device === 'tablet'
                ? (labels.deviceTablet ?? 'Tablet')
                : device === 'desktop'
                    ? (labels.deviceDesktop ?? 'Desktop')
                    : (labels.deviceMobile ?? 'Mobile');
            const active = index === viewportIndex;

            return `<button type="button" class="voodbuilder-editor-style-viewport__btn${active ? ' is-active' : ''}" data-vb-reading-viewport="${device}" aria-pressed="${active ? 'true' : 'false'}">${escapeHtml(label)}</button>`;
        }).join('');

        const elementBlocks = ELEMENTS.map(primaryDetails).join('');
        const headingFontOptions = [{ value: '', label: siteFontLabel(inherited.headingLabel) }, ...fontOptions];
        const bodyFontOptions = [{ value: '', label: siteFontLabel(inherited.bodyLabel) }, ...fontOptions];

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
                    ${fieldRow(labels.readingHeadingFont ?? 'Heading font', selectHtml('headingFont', headingFontOptions, state.headingFont))}
                    ${fieldRow(labels.readingBodyFont ?? 'Body font', selectHtml('font', bodyFontOptions, state.font))}
                    <div class="voodbuilder-editor-reading-scale">
                        <p class="voodbuilder-editor-reading-scale__title">${escapeHtml(labels.readingTypeScale ?? 'Type scale')}</p>
                        <div class="voodbuilder-editor-style-viewport voodbuilder-editor-reading-viewport">
                            <div class="voodbuilder-editor-style-viewport__group" role="group" aria-label="${escapeHtml(labels.classStyleViewportAria ?? 'Viewport')}">
                                ${viewportButtons}
                            </div>
                            <p class="voodbuilder-editor-reading-viewport__hint">${escapeHtml(labels.readingTypeScaleHint ?? 'Size per viewport: Mobile is the base, Tablet and Desktop override it only when set.')}</p>
                        </div>
                        ${elementBlocks}
                    </div>
                </div>
            </div>
        `;
    };

    const resetToDefaults = () => {
        const channel = state.previewChannel || previewIds[0] || '';
        Object.assign(state, normalizeState({
            font: '',
            headingFont: '',
            typeScale: emptyOverrideTypeScale(),
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
        renderControls();
        syncPreview({ rebuild: true });
    };

    const ensureReadingFonts = () => {
        const ids = [state.font, state.headingFont].filter((id) => id && id !== 'inter');

        ids.forEach((id) => {
            void ensureFontLoaded(editor, id, { reassert: false });
        });
    };

    const commit = ({ rebuild = false } = {}) => {
        editor.__voodbuilderReadingTypography = snapshot();
        ensureReadingFonts();
        // Integration edits never touch Grapes `update`, so mark dirty explicitly
        // or Save's html/css/js hash short-circuits and never hits the server.
        editor.__voodbuilderMarkPageUnsaved?.();
        editor.trigger?.('voodbuilder:reading-typography');
        renderControls();
        syncPreview({ rebuild });
    };

    editor.__voodbuilderApplyReadingTypography = (next = {}) => {
        const channel = state.previewChannel || previewIds[0] || '';

        Object.assign(state, normalizeState({
            ...next,
            previewChannel: channel,
        }));
        editor.__voodbuilderReadingTypography = snapshot();
        ensureReadingFonts();
        renderControls();
        syncPreview({ rebuild: true });
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

            return;
        }

        const viewportButton = target.closest('[data-vb-reading-viewport]');

        if (viewportButton) {
            event.preventDefault();
            editor.setDevice?.(viewportButton.getAttribute('data-vb-reading-viewport') || 'desktop');
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

        if (key === 'font' || key === 'headingFont') {
            state[key] = target.value;
            commit({ rebuild: true });

            return;
        }

        if (key.startsWith('scale.')) {
            const [, element, prop] = key.split('.');
            if (ELEMENTS.includes(element) && SCALE_PROPS.includes(prop)) {
                openScalePanels.add(`primary.${element}`);
                state.typeScale[element] = {
                    ...state.typeScale[element],
                    [prop]: optionalValue(target.value, { size: prop.startsWith('size') }),
                };
                commit({ rebuild: false });
            }

            return;
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
        applyReadingVarsToCanvas(editor, cssVariables());
        schedulePaint();
    });

    // Sizes are per viewport: re-point the size selects and canvas vars at the new device.
    const onDeviceChange = () => {
        renderControls();
        const doc = editor.Canvas?.getDocument?.();
        doc?.documentElement?.classList?.remove('vp-reading-drawer-open');
        applyReadingVarsToCanvas(editor, cssVariables());

        if (previewVisible) {
            applyPreviewVars();
        }
    };

    editor.on('device:select', onDeviceChange);
    editor.on('change:device', onDeviceChange);

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
    // Lucide boxes — same family as other inspector tabs (tabler `components` rendered blank in some builds).
    button.innerHTML = lucideIcon('boxes', 17);
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
