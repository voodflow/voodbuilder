import { debugSwallowed } from './debug-swallowed.js';
import { isStyleEditingDark } from './style-tailwind-breakpoints.js';
import { STYLE_BG_SRC_ATTR, STYLE_BG_SRC_DARK_ATTR } from './style-background-image.js';

/**
 * Page-level surface styling: reuse Style panel background controls on the
 * GrapesJS wrapper. Publish remaps wrapper #id CSS to `body` so the background
 * covers the full page (including chrome).
 *
 * UX: when nothing is selected, Style targets the page automatically. A compact
 * “Page” switch appears only while editing another element (or locked chrome).
 */

export const PAGE_SURFACE_CLASS = 'voodbuilder-page-surface';
export const PAGE_SURFACE_ACTION_ATTR = 'data-voodbuilder-page-surface-action';
export const PAGE_SURFACE_FOCUS_EVENT = 'voodbuilder:page-surface-focus';
/** Canvas-only style tag: fixed ::before wallpaper + transparent shells (public parity). */
export const PAGE_SURFACE_CANVAS_WALLPAPER_STYLE_ID = 'voodbuilder-page-surface-canvas-wallpaper';

/**
 * In-memory light/dark page wallpaper URLs for the open editor session.
 * Chrome-shell Save omits wrapper attrs from HTML; this cache keeps both
 * themes available for collectPageSurfaceWallpaperCssForPersist even when
 * CssComposer compound rules misbehave.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {{ light: string, dark: string }}
 */
export function getPageSurfaceWallpaperUrlCache(editor) {
    if (! editor) {
        return { light: '', dark: '' };
    }

    if (! editor.__voodbuilderPageSurfaceWallpaperUrls
        || typeof editor.__voodbuilderPageSurfaceWallpaperUrls !== 'object') {
        editor.__voodbuilderPageSurfaceWallpaperUrls = { light: '', dark: '' };
    }

    return editor.__voodbuilderPageSurfaceWallpaperUrls;
}

/**
 * In-memory dark wallpaper *styles* (CssComposer cannot store `html.dark #id`:
 * Grapes `selectorsAdd` means comma-additional selectors, so `#id` + `html.dark`
 * becomes `#id, html.dark` and overwrites the light rule on the same element).
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {Record<string, string>}
 */
export function getPageSurfaceDarkWallpaperStylesCache(editor) {
    if (! editor) {
        return {};
    }

    if (! editor.__voodbuilderPageSurfaceDarkWallpaperStyles
        || typeof editor.__voodbuilderPageSurfaceDarkWallpaperStyles !== 'object') {
        editor.__voodbuilderPageSurfaceDarkWallpaperStyles = {};
    }

    return editor.__voodbuilderPageSurfaceDarkWallpaperStyles;
}

/**
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {'light'|'dark'} theme
 * @param {string} url
 */
export function setPageSurfaceWallpaperUrlCache(editor, theme, url) {
    if (! editor || (theme !== 'light' && theme !== 'dark')) {
        return;
    }

    const cache = getPageSurfaceWallpaperUrlCache(editor);
    cache[theme] = String(url ?? '').trim();
}

/**
 * CssComposer selector for dark-theme page wallpaper on the wrapper.
 *
 * @param {string} id
 * @returns {string}
 */
export function pageSurfaceDarkRuleSelector(id) {
    const safe = String(id ?? '').trim();

    return safe === '' ? '' : `html.dark #${safe}`;
}

/**
 * True when a CssComposer rule is the broken Grapes form `#id, html.dark`
 * (selectorsAdd = html.dark) — must never be persisted or it overwrites light.
 *
 * @param {object|null|undefined} rule
 * @param {string} [id]
 * @returns {boolean}
 */
export function isBrokenPageSurfaceDarkCssRule(rule, id = '') {
    if (! rule) {
        return false;
    }

    const add = String(rule.get?.('selectorsAdd') ?? '').replace(/\s+/g, ' ').trim().toLowerCase();

    if (add !== 'html.dark') {
        return false;
    }

    const safe = String(id ?? '').trim();

    if (safe === '') {
        return true;
    }

    let sel = '';

    try {
        sel = String(rule.selectorsToString?.({ skipState: true, skipAdd: true }) ?? '').trim();
    } catch {
        try {
            sel = String(rule.selectorsToString?.({ skipState: true }) ?? '').trim();
        } catch {
            sel = '';
        }
    }

    // selectorsToString may already include ", html.dark"
    const bare = sel.replace(/,\s*html\.dark\b/gi, '').trim();

    return bare === `#${safe}` || bare === safe || sel.includes(`#${safe}`);
}

/**
 * Drop broken `#id, html.dark` CssComposer rules so they cannot overwrite light `#id`.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} id
 * @returns {number}
 */
export function purgeBrokenPageSurfaceDarkCssRules(editor, id) {
    const safe = String(id ?? '').trim();

    if (! editor?.Css?.getAll || safe === '') {
        return 0;
    }

    let removed = 0;

    try {
        for (const rule of [...(editor.Css.getAll() ?? [])]) {
            if (! isBrokenPageSurfaceDarkCssRule(rule, safe)) {
                continue;
            }

            try {
                editor.Css.remove?.(rule);
                removed += 1;
            } catch {
                try {
                    rule.collection?.remove?.(rule);
                    removed += 1;
                } catch (error) {
                    // Optional.
                    debugSwallowed(error);
                }
            }
        }
    } catch (error) {
        // CssComposer may be unavailable.
        debugSwallowed(error);
    }

    return removed;
}

/**
 * Persist dark page wallpaper without touching CssComposer.
 *
 * Grapes `selectorsAdd: 'html.dark'` means *additional comma selectors*, so
 * `#id` + html.dark becomes `#id, html.dark {…}` and paints the dark photo onto
 * `#id` itself — destroying light. Dark paint lives in memory + Save string only.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} id
 * @param {Record<string, string>} styles
 * @returns {boolean}
 */
export function setPageSurfaceDarkWallpaperRule(editor, id, styles) {
    const safe = String(id ?? '').trim();

    if (! editor || safe === '' || ! styles || typeof styles !== 'object') {
        return false;
    }

    purgeBrokenPageSurfaceDarkCssRules(editor, safe);

    const nextStyle = withWallpaperLayoutDefaults({ ...styles });
    const cache = getPageSurfaceDarkWallpaperStylesCache(editor);
    cache[safe] = { ...nextStyle };

    const urlMatch = String(nextStyle['background-image'] ?? '')
        .match(/url\(\s*['"]?([^'")]+)['"]?\s*\)/i);
    const url = urlMatch?.[1]?.trim() ?? '';

    if (url !== '') {
        setPageSurfaceWallpaperUrlCache(editor, 'dark', url);
    }

    return true;
}

/**
 * Read dark page wallpaper styles (memory cache — not CssComposer).
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} id
 * @returns {Record<string, string>}
 */
export function getPageSurfaceDarkWallpaperRuleStyles(editor, id) {
    const safe = String(id ?? '').trim();

    if (! editor || safe === '') {
        return {};
    }

    const cached = getPageSurfaceDarkWallpaperStylesCache(editor)[safe];

    if (cached && typeof cached === 'object' && Object.keys(cached).length > 0) {
        return { ...cached };
    }

    const url = String(getPageSurfaceWallpaperUrlCache(editor).dark ?? '').trim();

    if (url !== '') {
        return withWallpaperLayoutDefaults({
            'background-image': `url("${url.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}")`,
        });
    }

    return {};
}

/**
 * Remove dark page wallpaper (memory + purge broken CssComposer `#id, html.dark`).
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} id
 * @param {string} [property] When set, only drop that property.
 * @returns {boolean}
 */
export function clearPageSurfaceDarkWallpaperRule(editor, id, property = '') {
    const safe = String(id ?? '').trim();

    if (! editor || safe === '') {
        return false;
    }

    const prop = String(property ?? '').trim();
    const cache = getPageSurfaceDarkWallpaperStylesCache(editor);
    const existing = cache[safe] && typeof cache[safe] === 'object' ? { ...cache[safe] } : {};

    purgeBrokenPageSurfaceDarkCssRules(editor, safe);

    if (prop === '') {
        delete cache[safe];
        setPageSurfaceWallpaperUrlCache(editor, 'dark', '');

        return true;
    }

    delete existing[prop];
    delete existing.background;

    if (! /url\s*\(/i.test(String(existing['background-image'] ?? ''))) {
        delete cache[safe];
        setPageSurfaceWallpaperUrlCache(editor, 'dark', '');

        return true;
    }

    cache[safe] = existing;

    return true;
}
/**
 * Page-level surface styling is available on normal site pages (including chrome
 * shell). Disabled only while editing a chrome layout or a popup.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {boolean}
 */
export function isPageSurfaceMode(editor) {
    return ! editor?.__voodbuilderChromeLayoutMode
        && ! editor?.__voodbuilderPopupMode;
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {boolean}
 */
export function isPageSurfaceComponent(component, editor = null) {
    if (! component || component.isRemoved?.()) {
        return false;
    }

    if (component.get?.('type') === 'wrapper') {
        return true;
    }

    const wrapper = editor?.getWrapper?.();

    return Boolean(wrapper && component === wrapper);
}

/**
 * Style is editing the page surface (wrapper selected, nothing selected, or force).
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {boolean}
 */
export function isTargetingPageSurface(editor) {
    if (! isPageSurfaceMode(editor)) {
        return false;
    }

    const selected = editor?.getSelected?.() ?? null;

    if (selected && ! selected.isRemoved?.() && ! isPageSurfaceComponent(selected, editor)) {
        if (editor.__voodbuilderForcePageSurfaceStyle) {
            editor.__voodbuilderForcePageSurfaceStyle = false;
        }

        return false;
    }

    if (editor?.__voodbuilderForcePageSurfaceStyle) {
        return true;
    }

    if (! selected || selected.isRemoved?.()) {
        return true;
    }

    return isPageSurfaceComponent(selected, editor);
}

/**
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {import('grapesjs').Component | null}
 */
export function resolveStyleTarget(editor) {
    if (isTargetingPageSurface(editor)) {
        return editor?.getWrapper?.() ?? null;
    }

    const selected = editor?.getSelected?.() ?? null;

    if (selected && ! selected.isRemoved?.()) {
        return selected;
    }

    return null;
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function ensurePageSurfaceWrapper(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper || ! isPageSurfaceMode(editor)) {
        return wrapper ?? null;
    }

    // Host layouts already expose `.voodbuilder-page-surface` on <body>. Avoid
    // repeatedly mutating the Grapes wrapper classes — that storms chrome-shell
    // refresh / Tailwind rebuild and can freeze Save.
    if (editor.__voodbuilderPageSurfaceClassApplied) {
        return wrapper;
    }

    const classes = wrapper.getClasses?.() ?? [];
    const classList = Array.isArray(classes)
        ? classes.map((c) => (typeof c === 'string' ? c : String(c?.get?.('name') ?? c?.id ?? '')))
        : [...(classes.models ?? [])].map((c) => (
            typeof c === 'string' ? c : String(c?.get?.('name') ?? c?.id ?? '')
        ));

    if (! classList.includes(PAGE_SURFACE_CLASS) && ! editor.__voodbuilderChromeShellMode) {
        wrapper.addClass?.(PAGE_SURFACE_CLASS);
    }

    editor.__voodbuilderPageSurfaceClassApplied = true;

    // Chrome shell keeps the wrapper non-selectable (nav/footer chrome). Page
    // styles still target the wrapper via resolveStyleTarget / force flag.
    if (! editor.__voodbuilderChromeShellMode) {
        wrapper.set?.({
            droppable: true,
            selectable: true,
            highlightable: true,
            hoverable: false,
            locked: false,
        });
    }

    return wrapper;
}

/**
 * Focus Style on the page surface. Prefers selecting the wrapper; if Grapes
 * rejects that (or chrome shell locks the wrapper), falls back to a forced
 * page-style target with no selection.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {import('grapesjs').Component | null}
 */
export function selectPageSurface(editor) {
    if (! editor || ! isPageSurfaceMode(editor)) {
        return null;
    }

    const wrapper = ensurePageSurfaceWrapper(editor);

    if (! wrapper) {
        return null;
    }

    // Shell pages: wrapper is intentionally not selectable — clear selection and
    // force Style onto the page surface instead.
    if (editor.__voodbuilderChromeShellMode) {
        try {
            editor.select?.();
        } catch (error) {
            // Ignore clear-selection failures.
            debugSwallowed(error);
        }
        editor.__voodbuilderForcePageSurfaceStyle = true;
        try {
            editor.trigger?.(PAGE_SURFACE_FOCUS_EVENT);
        } catch (error) {
            // Optional sync hook for the Style panel.
            debugSwallowed(error);
        }

        return wrapper;
    }

    try {
        editor.select?.(wrapper, { scroll: false });
    } catch (error) {
        // Grapes may reject selection mid-destroy.
        debugSwallowed(error);
    }

    if (isPageSurfaceComponent(editor.getSelected?.(), editor)) {
        editor.__voodbuilderForcePageSurfaceStyle = false;
    } else {
        try {
            editor.select?.();
        } catch (error) {
            // Clear selection so resolveStyleTarget falls back to wrapper.
            debugSwallowed(error);
        }
        editor.__voodbuilderForcePageSurfaceStyle = true;
    }

    try {
        editor.trigger?.(PAGE_SURFACE_FOCUS_EVENT);
    } catch (error) {
        // Optional sync hook for the Style panel.
        debugSwallowed(error);
    }

    return wrapper;
}

/**
 * Remap wrapper #id author rules to body/html so published pages keep full-bleed
 * fixed wallpapers (not a scroll-away strip behind the nav).
 *
 * Prefer PHP {@see PageSurfaceCssPublish} at publish time so Save keeps #id rules
 * for editor Size/Position/Repeat hydration. This helper remains for tests and
 * any client-side preview that needs the public selectors.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} css
 * @returns {string}
 */
export function remapPageSurfaceCssForPublish(editor, css) {
    const raw = String(css ?? '').trim();

    if (raw === '' || ! isPageSurfaceMode(editor)) {
        return raw;
    }

    const wrapper = editor?.getWrapper?.();
    const id = String(wrapper?.getId?.() ?? '').trim();

    if (id === '') {
        return raw;
    }

    const escaped = id.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    // html + body: fixed backgrounds are more reliable on html in WebKit.
    const bodyTarget = `html, body, body.${PAGE_SURFACE_CLASS}, .${PAGE_SURFACE_CLASS}`;

    const remapped = raw
        .replace(new RegExp(`#${escaped}(?=[\\s,{.:#[])`, 'g'), bodyTarget)
        .replace(new RegExp(`\\[data-gjs-type=["']wrapper["']\\]`, 'g'), bodyTarget);

    return ensurePageSurfaceWallpaperLayout(remapped);
}

/**
 * Parse wallpaper layout props from body/html/page-surface rules in a stylesheet.
 * Used to restore wrapper #id CssComposer state after legacy saves that remapped
 * page wallpaper to body (image visible, Size/Position selects empty).
 *
 * @param {string} css
 * @returns {Record<string, string>}
 */
export function extractPageSurfaceWallpaperStylesFromCss(css) {
    const source = String(css ?? '');
    const styles = {};

    if (source === '' || ! /background-image\s*:/i.test(source)) {
        return styles;
    }

    const ruleRe = /([^{}@]+)\{([^{}]*)\}/g;
    let match;

    while ((match = ruleRe.exec(source)) !== null) {
        const selectors = String(match[1] ?? '').trim().toLowerCase();
        const body = String(match[2] ?? '');

        if (! /background-image\s*:/i.test(body) || ! /url\s*\(/i.test(body)) {
            continue;
        }

        const targetsPageSurface = /(^|[,\s])(html|body)([,\s.#:]|$)/.test(selectors)
            || selectors.includes(PAGE_SURFACE_CLASS);

        // Dark-theme companions are read separately.
        if (! targetsPageSurface || /(^|[\s,])html\.dark\b|(^|[\s,])\.dark\b/.test(selectors)) {
            continue;
        }

        const declRe = /([a-z-]+)\s*:\s*([^;]+)/gi;
        let decl;

        while ((decl = declRe.exec(body)) !== null) {
            const prop = String(decl[1] ?? '').trim().toLowerCase();
            const value = String(decl[2] ?? '').replace(/\s*!important\s*$/i, '').trim();

            if (! prop.startsWith('background') || value === '') {
                continue;
            }

            if (! styles[prop]) {
                styles[prop] = value;
            }
        }
    }

    return styles;
}

/**
 * Collect component ids currently present in the editor tree.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {Set<string>}
 */
export function collectEditorComponentIds(editor) {
    const ids = new Set();
    const wrapper = editor?.getWrapper?.();

    if (! wrapper?.onAll) {
        const id = String(wrapper?.getId?.() ?? '').trim();

        if (id !== '') {
            ids.add(id);
        }

        return ids;
    }

    wrapper.onAll((component) => {
        const id = String(component?.getId?.() ?? '').trim();

        if (id !== '') {
            ids.add(id);
        }
    });

    return ids;
}

/**
 * Best page-wallpaper candidate from any `#id` rule (including known ids).
 * Used when the wrapper is empty but a prior Save left the paint on a content
 * ghost or a regenerated-id orphan that is still "known" in the tree.
 *
 * @param {string} css
 * @param {{ dark?: boolean, preferId?: string }} [options]
 * @returns {Record<string, string>}
 */
export function extractBestPageWallpaperFromCss(css, options = {}) {
    const source = String(css ?? '');
    const dark = Boolean(options.dark);
    const preferId = String(options.preferId ?? '').trim();
    let best = null;
    let bestScore = -1;

    if (source === '' || ! /background-image\s*:/i.test(source)) {
        return {};
    }

    const ruleRe = dark
        ? /html\.dark\s+#([A-Za-z][\w-]*)\s*\{([^{}]*)\}/gi
        : /#([A-Za-z][\w-]*)\s*\{([^{}]*)\}/gi;
    let match;

    while ((match = ruleRe.exec(source)) !== null) {
        const id = String(match[1] ?? '').trim();
        const body = String(match[2] ?? '');

        if (! dark) {
            const start = match.index ?? 0;
            const before = source.slice(Math.max(0, start - 16), start).toLowerCase();

            if (before.includes('html.dark')) {
                continue;
            }
        }

        if (id === '' || ! /background-image\s*:/i.test(body) || ! /url\s*\(/i.test(body)) {
            continue;
        }

        const styles = {};
        const declRe = /([a-z-]+)\s*:\s*([^;]+)/gi;
        let decl;

        while ((decl = declRe.exec(body)) !== null) {
            const prop = String(decl[1] ?? '').trim().toLowerCase();
            const value = String(decl[2] ?? '').replace(/\s*!important\s*$/i, '').trim();

            if (! prop.startsWith('background') || value === '') {
                continue;
            }

            styles[prop] = value;
        }

        if (! styles['background-image']) {
            continue;
        }

        let score = 1;

        if (/background-(?:size|position|repeat)\s*:/i.test(body)) {
            score += 3;
        }

        if (/background-attachment\s*:\s*fixed/i.test(body)) {
            score += 2;
        }

        if (preferId !== '' && id === preferId) {
            score += 10;
        }

        if (score > bestScore) {
            bestScore = score;
            best = styles;
        }
    }

    return best ? withWallpaperLayoutDefaults(best) : {};
}

/**
 * Wallpaper #id rules whose id is not in the live component tree (typical after
 * Grapes regenerates the wrapper id on reload). Public PHP remaps these to body;
 * the editor must reclaim them onto the current wrapper.
 *
 * @param {string} css
 * @param {Iterable<string>|Set<string>} knownIds
 * @returns {Record<string, string>}
 */
export function extractOrphanWallpaperStylesFromCss(css, knownIds = []) {
    const source = String(css ?? '');
    const styles = {};
    const known = knownIds instanceof Set ? knownIds : new Set(
        [...(knownIds ?? [])].map((id) => String(id ?? '').trim()).filter(Boolean),
    );

    if (source === '' || ! /background-image\s*:/i.test(source)) {
        return styles;
    }

    const ruleRe = /#([A-Za-z][\w-]*)\s*\{([^{}]*)\}/g;
    let match;

    while ((match = ruleRe.exec(source)) !== null) {
        const id = String(match[1] ?? '').trim();
        const body = String(match[2] ?? '');
        const start = match.index ?? 0;
        const before = source.slice(Math.max(0, start - 16), start).toLowerCase();

        // Skip dark-theme rules — handled by extractDarkOrphanWallpaperStylesFromCss.
        if (before.includes('html.dark')) {
            continue;
        }

        if (id === '' || known.has(id)) {
            continue;
        }

        if (! /background-image\s*:/i.test(body) || ! /url\s*\(/i.test(body)) {
            continue;
        }

        // Prefer wallpaper-ish rules (layout props or attachment) over random photo paints.
        const looksLikePageWallpaper = /background-(?:size|position|repeat|attachment)\s*:/i.test(body)
            || /background-attachment\s*:\s*fixed/i.test(body);

        if (! looksLikePageWallpaper && Object.keys(styles).length > 0) {
            continue;
        }

        const declRe = /([a-z-]+)\s*:\s*([^;]+)/gi;
        let decl;

        while ((decl = declRe.exec(body)) !== null) {
            const prop = String(decl[1] ?? '').trim().toLowerCase();
            const value = String(decl[2] ?? '').replace(/\s*!important\s*$/i, '').trim();

            if (! prop.startsWith('background') || value === '') {
                continue;
            }

            styles[prop] = value;
        }
    }

    return styles;
}

/**
 * Orphan `html.dark #id` wallpaper rules (wrapper id regenerated on reload).
 *
 * @param {string} css
 * @param {Iterable<string>|Set<string>} knownIds
 * @returns {Record<string, string>}
 */
export function extractDarkOrphanWallpaperStylesFromCss(css, knownIds = []) {
    const source = String(css ?? '');
    const styles = {};
    const known = knownIds instanceof Set ? knownIds : new Set(
        [...(knownIds ?? [])].map((id) => String(id ?? '').trim()).filter(Boolean),
    );

    if (source === '' || ! /background-image\s*:/i.test(source)) {
        return styles;
    }

    const ruleRe = /html\.dark\s+#([A-Za-z][\w-]*)\s*\{([^{}]*)\}/gi;
    let match;

    while ((match = ruleRe.exec(source)) !== null) {
        const id = String(match[1] ?? '').trim();
        const body = String(match[2] ?? '');

        if (id === '' || known.has(id)) {
            continue;
        }

        if (! /background-image\s*:/i.test(body) || ! /url\s*\(/i.test(body)) {
            continue;
        }

        const declRe = /([a-z-]+)\s*:\s*([^;]+)/gi;
        let decl;

        while ((decl = declRe.exec(body)) !== null) {
            const prop = String(decl[1] ?? '').trim().toLowerCase();
            const value = String(decl[2] ?? '').replace(/\s*!important\s*$/i, '').trim();

            if (! prop.startsWith('background') || value === '') {
                continue;
            }

            styles[prop] = value;
        }
    }

    return styles;
}

/**
 * Read wallpaper styles for the page surface: wrapper #id, then body/html rules,
 * then orphan #id wallpaper (id regenerated on reload).
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} [css]
 * @returns {Record<string, string>}
 */
export function readPageSurfaceWallpaperStyles(editor, css = '') {
    if (! editor || ! isPageSurfaceMode(editor)) {
        return {};
    }

    const wrapper = editor.getWrapper?.();
    const id = String(wrapper?.getId?.() ?? '').trim();
    const fromId = id && editor.Css?.getIdRule
        ? { ...(editor.Css.getIdRule(id)?.getStyle?.() ?? {}) }
        : {};
    const fromAttr = String(wrapper?.getAttributes?.()?.[STYLE_BG_SRC_ATTR] ?? '').trim();
    const fromCache = String(getPageSurfaceWallpaperUrlCache(editor).light ?? '').trim();
    const lightUrl = fromAttr || fromCache;

    // Attrs/cache win over CssComposer — bare `#id` may hold the last dark paint
    // when Grapes could not store an html.dark companion.
    if (lightUrl !== '') {
        return withWallpaperLayoutDefaults({
            ...fromId,
            'background-image': `url("${lightUrl.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}")`,
        });
    }

    if (/url\s*\(/i.test(String(fromId['background-image'] ?? ''))) {
        return fromId;
    }

    const sheet = String(
        css
        || editor.__voodbuilderAuthorPageCss
        || editor.__voodbuilderPageLiveCss
        || editor.getCss?.()
        || '',
    ).replace(/#[\w-]+\s*,\s*html\.dark\s*\{[^{}]*\}/gi, ' ');
    const fromBody = extractPageSurfaceWallpaperStylesFromCss(sheet);

    if (fromBody['background-image']) {
        return fromBody;
    }

    const known = collectEditorComponentIds(editor);

    // Current wrapper id is "known" but may have empty paint — still allow reclaiming
    // a previous wrapper id that is no longer in the tree.
    const orphan = extractOrphanWallpaperStylesFromCss(sheet, known);

    if (orphan['background-image']) {
        return orphan;
    }

    return extractBestPageWallpaperFromCss(sheet, { preferId: id });
}

/**
 * Read dark-theme page wallpaper (`html.dark #id` + durable attr).
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} [css]
 * @returns {Record<string, string>}
 */
export function readPageSurfaceDarkWallpaperStyles(editor, css = '') {
    if (! editor || ! isPageSurfaceMode(editor)) {
        return {};
    }

    const wrapper = editor.getWrapper?.();
    const id = String(wrapper?.getId?.() ?? '').trim();
    const fromRule = getPageSurfaceDarkWallpaperRuleStyles(editor, id);

    if (/url\s*\(/i.test(String(fromRule['background-image'] ?? ''))) {
        return fromRule;
    }

    const fromAttr = String(wrapper?.getAttributes?.()?.[STYLE_BG_SRC_DARK_ATTR] ?? '').trim();
    const fromCache = String(getPageSurfaceWallpaperUrlCache(editor).dark ?? '').trim();
    const darkUrl = fromAttr || fromCache;

    if (darkUrl !== '') {
        const light = readPageSurfaceWallpaperStyles(editor, css);

        return {
            'background-image': `url("${darkUrl.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}")`,
            'background-size': String(light['background-size'] ?? fromRule['background-size'] ?? '').trim() || 'cover',
            'background-position': String(light['background-position'] ?? fromRule['background-position'] ?? '').trim() || 'center',
            'background-repeat': String(light['background-repeat'] ?? fromRule['background-repeat'] ?? '').trim() || 'no-repeat',
        };
    }

    const sheet = String(
        css
        || editor.__voodbuilderAuthorPageCss
        || editor.__voodbuilderPageLiveCss
        || editor.getCss?.()
        || '',
    );
    const known = collectEditorComponentIds(editor);
    const orphan = extractDarkOrphanWallpaperStylesFromCss(sheet, known);

    if (orphan['background-image']) {
        return orphan;
    }

    return extractBestPageWallpaperFromCss(sheet, { dark: true, preferId: id });
}

/**
 * Fill missing wallpaper layout props (cover / center / no-repeat).
 * Used by hydrate + canvas preview so image-only #id rules do not tile.
 *
 * @param {Record<string, string>} styles
 * @returns {Record<string, string>}
 */
export function withWallpaperLayoutDefaults(styles) {
    const next = styles && typeof styles === 'object' ? { ...styles } : {};
    const image = String(next['background-image'] ?? '').trim();

    if (image === '' || image === 'none' || ! /url\s*\(/i.test(image)) {
        return next;
    }

    if (! String(next['background-size'] ?? '').trim()) {
        next['background-size'] = 'cover';
    }

    if (! String(next['background-position'] ?? '').trim()) {
        next['background-position'] = 'center';
    }

    if (! String(next['background-repeat'] ?? '').trim()) {
        next['background-repeat'] = 'no-repeat';
    }

    return next;
}

/**
 * @param {Record<string, string>|null|undefined} styles
 * @param {string} [selector]
 * @returns {string}
 */
function buildFixedWallpaperLayerCss(styles, selector = 'body::before') {
    const prepared = withWallpaperLayoutDefaults(styles);
    const image = String(prepared['background-image'] ?? '').trim();

    if (image === '' || image === 'none' || ! /url\s*\(/i.test(image)) {
        return '';
    }

    const size = String(prepared['background-size'] ?? '').trim() || 'cover';
    const position = String(prepared['background-position'] ?? '').trim() || 'center';
    const repeat = String(prepared['background-repeat'] ?? '').trim() || 'no-repeat';

    return [
        `${selector} {`,
        '  content: "";',
        '  position: fixed;',
        '  inset: 0;',
        '  z-index: -1;',
        '  pointer-events: none;',
        `  background-image: ${image};`,
        `  background-size: ${size};`,
        `  background-position: ${position};`,
        `  background-repeat: ${repeat};`,
        '}',
    ].join('\n');
}

/**
 * Build canvas-only CSS that mirrors public PageSurfaceCssPublish: fixed ::before
 * layer + transparent content shells so the wallpaper is visible in the editor.
 * Supports dual light + dark wallpapers.
 *
 * Host `background-image` from CssComposer / inline paint is cleared so an
 * image-only #id rule cannot tile over the fixed ::before layer.
 *
 * @param {Record<string, string>} lightStyles
 * @param {Record<string, string>|null} [darkStyles]
 * @param {{ hostId?: string }} [options]
 * @returns {string}
 */
export function buildPageSurfaceCanvasWallpaperCss(lightStyles, darkStyles = null, options = {}) {
    const light = lightStyles && typeof lightStyles === 'object' ? lightStyles : {};
    const dark = darkStyles && typeof darkStyles === 'object' ? darkStyles : null;
    const lightLayer = buildFixedWallpaperLayerCss(light, 'body::before');
    const darkLayer = dark ? buildFixedWallpaperLayerCss(dark, 'html.dark body::before') : '';
    // When only light exists, hide it under html.dark — otherwise dark preview still
    // shows the light photo (body::before keeps matching).
    const darkHideLight = lightLayer !== '' && darkLayer === ''
        ? [
            'html.dark body::before {',
            '  background-image: none !important;',
            '}',
        ].join('\n')
        : '';
    const layers = [lightLayer, darkLayer, darkHideLight].filter(Boolean);

    if (layers.length === 0) {
        return '';
    }

    const hostId = String(options?.hostId ?? '').trim().replace(/[^\w-]/g, '');
    const hostClear = [
        `html, body, body.${PAGE_SURFACE_CLASS}, [data-gjs-type="wrapper"] {`,
        '  background-image: none !important;',
        // Opaque body/html theme fills hide body::before (z-index:-1) in the iframe.
        '  background-color: transparent !important;',
        '}',
        hostId
            ? [
                `#${hostId} {`,
                '  background-image: none !important;',
                '  background-attachment: scroll !important;',
                '  background-color: transparent !important;',
                '}',
            ].join('\n')
            : '',
    ].filter(Boolean);

    return [
        ...layers,
        ...hostClear,
        'body .voodbuilder-site-shell,',
        'body .voodbuilder-site-content,',
        'body .voodbuilder-home-shell,',
        'body .voodbuilder-landing-shell,',
        'body .voodbuilder-events-shell {',
        '  background-color: transparent !important;',
        '}',
    ].join('\n');
}

/**
 * Inject/remove the canvas wallpaper preview style tag in the Grapes iframe.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {Record<string, string>|null} [styles] When set, merges into the active
 *   theme paint (light or dark from top-bar). When null, reads both from CssComposer.
 * @returns {boolean}
 */
export function syncPageSurfaceCanvasWallpaperPreview(editor, styles = null) {
    if (! editor || ! isPageSurfaceMode(editor)) {
        return false;
    }

    let doc = null;

    try {
        doc = editor.Canvas?.getDocument?.() ?? null;
    } catch {
        doc = null;
    }

    if (! doc?.head) {
        return false;
    }

    let light = withWallpaperLayoutDefaults(readPageSurfaceWallpaperStyles(editor));
    let dark = withWallpaperLayoutDefaults(readPageSurfaceDarkWallpaperStyles(editor));

    if (styles && typeof styles === 'object') {
        const hasImage = /url\s*\(/i.test(String(styles['background-image'] ?? ''));

        if (isStyleEditingDark(editor)) {
            dark = withWallpaperLayoutDefaults({ ...dark, ...styles });

            if (! hasImage && styles['background-image'] !== undefined) {
                dark = { ...dark, 'background-image': '' };
            }
        } else {
            light = withWallpaperLayoutDefaults({ ...light, ...styles });

            if (! hasImage && styles['background-image'] !== undefined) {
                light = { ...light, 'background-image': '' };
            }
        }
    }

    const hostId = String(editor.getWrapper?.()?.getId?.() ?? '').trim();
    const css = buildPageSurfaceCanvasWallpaperCss(light, dark, { hostId });
    const existing = doc.getElementById(PAGE_SURFACE_CANVAS_WALLPAPER_STYLE_ID);

    // Public pages paint only on ::before — strip host inline wallpaper so a
    // leftover image-only style="" cannot tile over the fixed layer.
    try {
        const hostEl = editor.getWrapper?.()?.getEl?.() ?? editor.getWrapper?.()?.view?.el;

        if (hostEl?.style) {
            hostEl.style.removeProperty?.('background-image');
            hostEl.style.removeProperty?.('background-size');
            hostEl.style.removeProperty?.('background-position');
            hostEl.style.removeProperty?.('background-repeat');
            hostEl.style.removeProperty?.('background-attachment');
        }
    } catch (error) {
        // Frame may be unavailable.
        debugSwallowed(error);
    }

    if (css === '') {
        existing?.remove();

        return false;
    }

    if (existing) {
        existing.textContent = css;

        // Palette / live Tailwind sheets are re-appended later; stay last so the
        // transparent host overrides win.
        if (doc.head.lastElementChild !== existing) {
            doc.head.appendChild(existing);
        }

        return true;
    }

    const style = doc.createElement('style');
    style.id = PAGE_SURFACE_CANVAS_WALLPAPER_STYLE_ID;
    style.textContent = css;
    doc.head.appendChild(style);

    return true;
}

/**
 * Copy legacy body/html wallpaper CSS (or orphan #id wallpaper) onto the Grapes
 * wrapper #id rule so Style Size/Position/Repeat can hydrate after reload.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} [css]
 * @returns {boolean}
 */
export function hydratePageSurfaceWallpaperFromCss(editor, css = '') {
    if (! editor || ! isPageSurfaceMode(editor)) {
        return false;
    }

    const wrapper = editor.getWrapper?.();
    const id = String(wrapper?.getId?.() ?? '').trim();

    if (id === '' || ! editor.Css?.setIdRule) {
        return false;
    }

    purgeBrokenPageSurfaceDarkCssRules(editor, id);

    const sheet = String(
        css
        || editor.__voodbuilderAuthorPageCss
        || editor.__voodbuilderPageLiveCss
        || editor.getCss?.()
        || '',
    )
        // Drop Grapes broken `#id, html.dark` so light hydrate cannot adopt the dark photo.
        .replace(/#[\w-]+\s*,\s*html\.dark\s*\{[^{}]*\}/gi, ' ');
    const known = collectEditorComponentIds(editor);
    let fromSheet = extractPageSurfaceWallpaperStylesFromCss(sheet);

    if (! fromSheet['background-image']) {
        fromSheet = extractOrphanWallpaperStylesFromCss(sheet, known);
    }

    // Known-id ghost (chrome-shell empty instance that kept the old wrapper id)
    // or image-only #id — still reclaim onto the current wrapper.
    if (! fromSheet['background-image']) {
        fromSheet = extractBestPageWallpaperFromCss(sheet, { preferId: id });
    }

    let hydrated = false;

    if (fromSheet['background-image']) {
        try {
            const existing = { ...(editor.Css.getIdRule?.(id)?.getStyle?.() ?? {}) };
            const next = withWallpaperLayoutDefaults({ ...fromSheet, ...existing });

            for (const prop of [
                'background-image',
                'background-size',
                'background-position',
                'background-repeat',
                'background-attachment',
            ]) {
                if (! String(existing[prop] ?? '').trim() && fromSheet[prop]) {
                    next[prop] = fromSheet[prop];
                }
            }

            editor.Css.setIdRule(id, withWallpaperLayoutDefaults(next));

            const urlMatch = String(next['background-image'] ?? '')
                .match(/url\(\s*['"]?([^'")]+)['"]?\s*\)/i);
            const lightUrl = urlMatch?.[1]?.trim() ?? '';

            if (lightUrl !== '' && wrapper?.addAttributes) {
                const current = String(wrapper.getAttributes?.()?.[STYLE_BG_SRC_ATTR] ?? '').trim();

                if (current !== lightUrl) {
                    wrapper.addAttributes({ [STYLE_BG_SRC_ATTR]: lightUrl });
                }

                setPageSurfaceWallpaperUrlCache(editor, 'light', lightUrl);
            }

            hydrated = true;
        } catch (error) {
            // CssComposer may be unavailable.
            debugSwallowed(error);
        }
    }

    let darkFromSheet = extractDarkOrphanWallpaperStylesFromCss(sheet, known);

    if (! darkFromSheet['background-image']) {
        darkFromSheet = extractBestPageWallpaperFromCss(sheet, { dark: true, preferId: id });
    }

    if (! darkFromSheet['background-image']) {
        darkFromSheet = getPageSurfaceDarkWallpaperRuleStyles(editor, id);
    }

    if (darkFromSheet['background-image']) {
        try {
            const existingDark = getPageSurfaceDarkWallpaperRuleStyles(editor, id);
            const nextDark = withWallpaperLayoutDefaults({ ...darkFromSheet, ...existingDark });

            for (const prop of [
                'background-image',
                'background-size',
                'background-position',
                'background-repeat',
            ]) {
                if (! String(existingDark[prop] ?? '').trim() && darkFromSheet[prop]) {
                    nextDark[prop] = darkFromSheet[prop];
                }
            }

            setPageSurfaceDarkWallpaperRule(editor, id, withWallpaperLayoutDefaults(nextDark));

            const urlMatch = String(nextDark['background-image'] ?? '')
                .match(/url\(\s*['"]?([^'")]+)['"]?\s*\)/i);
            const darkUrl = urlMatch?.[1]?.trim() ?? '';

            if (darkUrl !== '' && wrapper?.addAttributes) {
                const current = String(wrapper.getAttributes?.()?.[STYLE_BG_SRC_DARK_ATTR] ?? '').trim();

                if (current !== darkUrl) {
                    wrapper.addAttributes({ [STYLE_BG_SRC_DARK_ATTR]: darkUrl });
                }

                setPageSurfaceWallpaperUrlCache(editor, 'dark', darkUrl);
            }

            hydrated = true;
        } catch (error) {
            // Optional dark rule.
            debugSwallowed(error);
        }
    }

    // Image-only #id from a prior Save (no size/repeat) — pad defaults so canvas
    // ::before and Style selects match cover / center / no-repeat.
    try {
        const current = { ...(editor.Css.getIdRule?.(id)?.getStyle?.() ?? {}) };

        if (/url\s*\(/i.test(String(current['background-image'] ?? ''))) {
            const padded = withWallpaperLayoutDefaults(current);
            const changed = ['background-size', 'background-position', 'background-repeat']
                .some((prop) => String(current[prop] ?? '').trim() !== String(padded[prop] ?? '').trim());

            if (changed) {
                editor.Css.setIdRule(id, padded);
                hydrated = true;
            }
        }
    } catch (error) {
        // Optional.
        debugSwallowed(error);
    }

    syncPageSurfaceCanvasWallpaperPreview(editor);

    return hydrated;
}

/**
 * Fill missing wallpaper layout props on remapped page-surface rules that paint
 * a background-image (cover/center/no-repeat/fixed).
 *
 * @param {string} css
 * @returns {string}
 */
export function ensurePageSurfaceWallpaperLayout(css) {
    const source = String(css ?? '');

    if (source === '' || ! /background-image\s*:/i.test(source)) {
        return source;
    }

    return source.replace(
        /(html\s*,\s*body[^,{]*(?:,[^,{]*)*|body[^,{]*(?:,[^,{]*voodbuilder-page-surface[^,{]*)*)\{([^{}]*)\}/gi,
        (match, selectors, body) => {
            if (! /background-image\s*:/i.test(body) || ! /url\s*\(/i.test(body)) {
                return match;
            }

            let next = body;

            if (! /background-size\s*:/i.test(next)) {
                next += '; background-size: cover';
            }

            if (! /background-position\s*:/i.test(next)) {
                next += '; background-position: center';
            }

            if (! /background-repeat\s*:/i.test(next)) {
                next += '; background-repeat: no-repeat';
            }

            if (! /background-attachment\s*:/i.test(next)) {
                next += '; background-attachment: fixed';
            }

            next = next.replace(/;;+/g, ';').replace(/^;\s*/, '').trim();

            return `${selectors} {${next}}`;
        },
    );
}

/**
 * Quiet Style affordance for page-level styles (infrequent but explicit).
 *
 * Lives at the *bottom* of the Style inspector — never in the topbar or as a
 * branded chip at the top of the panel (those broke visual harmony).
 *
 * - Editing an element → muted text link “Sfondo pagina…”
 * - Targeting page → hidden (the Background-only Style chrome is the signal)
 *
 * @param {import('grapesjs').Editor} editor
 * @param {HTMLElement|null|undefined} stylePanel
 * @param {{ pageSurfaceLabel?: string, pageSurfaceHint?: string, pageSurfaceSwitch?: string }} [labels]
 */
export function ensurePageSurfaceAction(editor, stylePanel, labels = {}) {
    if (! editor || ! stylePanel || ! isPageSurfaceMode(editor)) {
        stylePanel?.querySelector(`[${PAGE_SURFACE_ACTION_ATTR}]`)?.remove();

        return null;
    }

    const targetingPage = isTargetingPageSurface(editor);
    const switchLabel = labels.pageSurfaceSwitch
        ?? labels.pageSurfaceLabel
        ?? 'Page background…';
    const hint = labels.pageSurfaceHint
        ?? 'Background for the whole page (not a single block).';

    let bar = stylePanel.querySelector(`[${PAGE_SURFACE_ACTION_ATTR}]`);

    if (! bar) {
        bar = document.createElement('div');
        bar.setAttribute(PAGE_SURFACE_ACTION_ATTR, '1');
        bar.className = 'voodbuilder-editor-page-surface-action voodbuilder-editor-page-surface-action--footer';
        bar.innerHTML = `
            <button type="button" class="voodbuilder-editor-page-surface-action__link" data-voodbuilder-page-surface-select>
            </button>
        `;
        stylePanel.appendChild(bar);

        bar.querySelector('[data-voodbuilder-page-surface-select]')?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (isTargetingPageSurface(editor)) {
                return;
            }

            selectPageSurface(editor);
            editor.__voodbuilderActivateInspectorTab?.('style', { userInitiated: true });
        });
    } else if (! bar.classList.contains('voodbuilder-editor-page-surface-action--footer')) {
        // Migrate older top-of-panel chips to the footer slot.
        bar.classList.add('voodbuilder-editor-page-surface-action--footer');
        stylePanel.appendChild(bar);
    }

    const btn = bar.querySelector('[data-voodbuilder-page-surface-select]');

    if (btn) {
        btn.textContent = switchLabel;
        btn.classList.remove('voodbuilder-editor-page-surface-action__btn', 'is-active', 'is-status');
        btn.classList.add('voodbuilder-editor-page-surface-action__link');
        btn.disabled = false;
        btn.removeAttribute('aria-pressed');
        btn.title = hint;
    }

    // Only show when an element is selected — the escape hatch to page background.
    // While editing the page itself, omit the control (Background-only Style is enough).
    bar.hidden = targetingPage;
    bar.dataset.voodbuilderPageSurfaceMode = targetingPage ? 'page' : 'switch';

    return bar;
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {{ pageSurfaceLabel?: string, pageSurfaceHint?: string, pageSurfaceSwitch?: string }} [labels]
 */
export function registerPageSurfaceStyles(editor, labels = {}) {
    if (! editor || editor.__voodbuilderPageSurfaceRegistered) {
        return;
    }

    editor.__voodbuilderPageSurfaceRegistered = true;

    const syncUi = () => {
        ensurePageSurfaceWrapper(editor);

        const stylePanel = document.querySelector('[data-voodbuilder-inspector="style"]');
        ensurePageSurfaceAction(editor, stylePanel, labels);

        // Drop legacy top-of-panel “Page” badge (replaced by quieter footer link).
        document.querySelectorAll('[data-voodbuilder-page-surface-badge]').forEach((badge) => {
            badge.remove();
        });
    };

    const boot = () => {
        ensurePageSurfaceWrapper(editor);
        syncUi();
    };

    editor.on('load', boot);
    editor.on('component:selected', () => {
        if (editor.__voodbuilderForcePageSurfaceStyle) {
            const selected = editor.getSelected?.();

            if (selected && ! isPageSurfaceComponent(selected, editor)) {
                editor.__voodbuilderForcePageSurfaceStyle = false;
            }
        }

        syncUi();
    });
    editor.on('component:deselected', () => {
        window.requestAnimationFrame(syncUi);
    });
    editor.on(PAGE_SURFACE_FOCUS_EVENT, syncUi);
    editor.on('canvas:frame:load', () => {
        window.requestAnimationFrame(boot);
    });

    boot();
}
