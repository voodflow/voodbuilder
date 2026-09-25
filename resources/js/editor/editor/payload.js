/**
 * Save/export payload assembly.
 */

import { detachTopDropSpacerForExport, restoreTopDropSpacerAfterExport } from '../canvas-block-drag.js';
import { detachInnerDropSlotsForExport } from '../inner-drop-slots.js';
import { syncBindingsForExport, syncRepeatBindingsForExport } from '../bindings-ui.js';
import { syncAnimatedCountersForExport } from '../editor-animated-blocks.js';
import {
    ensureComponentInstancesForExport,
    syncComponentInstancePaintForExport,
    syncComponentInstancesForExport,
} from '../components-ui.js';
import { syncConditionsForExport } from '../conditions-ui.js';
import { findPageContentSlotInEditor } from '../chrome-content-slot-utils.js';
import { purgeOrphanPageContentNodes } from '../page-content-orphans.js';
import { extractChromeLayoutHtml } from '../editor-chrome-layout.js';
import { ensureCtaButtonsForExport } from '../editor-button-link.js';
import { ensureIconsForExport } from '../editor-utility-blocks.js';
import { ensureLayoutContainersForExport } from '../layout-blocks.js';
import { restoreContentWidthFromAttributes } from '../content-width-toolbar.js';
import { syncLayerVisibilityForExport } from '../layer-visibility.js';
import { extractChromeShellPageHtml } from '../editor-chrome-shell.js';
import { applyVideoFacadesToExportedHtml, syncVideoComponentsForExport } from '../editor-video.js';
import {
    pruneEmptyDynamicBlocks,
    syncDynamicBlockAttributes,
} from '../plugins/voodbuilder-editor.js';
import { syncSiteHeaderConfig } from '../chrome/blocks/nav/config.js';
import { syncSiteFooterConfig } from '../chrome/blocks/footer/config.js';
import { isFooterBlock, isNavBlock } from '../chrome/ids.js';
import {
    bakeAuthorStylesToComposerForExport,
    bakeSvgPaintForExport,
    pruneRedundantSpacingZerosForExport,
    purgeDesyncedBackgroundCssRules,
    restoreSvgPaintInspectorStyle,
    restoreSvgPaintInspectorStyles,
    safeFindComponents,
    syncPaintStylesForExport,
    syncSpacingStylesForExport,
} from '../tailwind-visual-style.js';
import { shouldOmitAuthorStyleValue } from '../theme-tokens.js';
import { withoutUndo } from '../editor-undo.js';
import {
    getPageSurfaceWallpaperUrlCache,
    isPageSurfaceMode,
    pageSurfaceDarkRuleSelector,
    purgeBrokenPageSurfaceDarkCssRules,
    readPageSurfaceDarkWallpaperStyles,
    readPageSurfaceWallpaperStyles,
    withWallpaperLayoutDefaults,
} from '../page-surface-styles.js';
import { STYLE_BG_SRC_ATTR, STYLE_BG_SRC_DARK_ATTR } from '../style-background-image.js';

/**
 * Emit page-surface light + dark wallpaper rules for Save.
 * Chrome-shell HTML omits the wrapper, so durable recovery is CSS-only.
 *
 * @param {object} editor
 * @returns {string}
 */
export function collectPageSurfaceWallpaperCssForPersist(editor) {
    if (! editor || ! isPageSurfaceMode(editor)) {
        return '';
    }

    const wrapper = editor.getWrapper?.();
    const id = String(wrapper?.getId?.() ?? '').trim();

    if (id === '') {
        return '';
    }

    purgeBrokenPageSurfaceDarkCssRules(editor, id);

    const attrs = wrapper?.getAttributes?.() ?? {};
    const cache = getPageSurfaceWallpaperUrlCache(editor);
    const lightAttr = String(
        attrs[STYLE_BG_SRC_ATTR]
        ?? cache.light
        ?? '',
    ).trim();
    const darkAttr = String(
        attrs[STYLE_BG_SRC_DARK_ATTR]
        ?? cache.dark
        ?? '',
    ).trim();

    let light = withWallpaperLayoutDefaults(readPageSurfaceWallpaperStyles(editor));
    let dark = withWallpaperLayoutDefaults(readPageSurfaceDarkWallpaperStyles(editor));

    // Attrs / session cache are the Style-panel source of truth per theme — always
    // win over CssComposer (Grapes cannot store real html.dark #id companions).
    if (lightAttr !== '') {
        light = withWallpaperLayoutDefaults({
            ...light,
            'background-image': `url('${lightAttr.replace(/'/g, "\\'")}')`,
        });
    }

    if (darkAttr !== '') {
        dark = withWallpaperLayoutDefaults({
            ...dark,
            'background-image': `url('${darkAttr.replace(/'/g, "\\'")}')`,
        });
    }

    // If light still equals dark URL, light was never stored separately — omit light
    // rather than publishing dark as the light wallpaper.
    const lightUrl = extractWallpaperUrl(light['background-image']);
    const darkUrl = extractWallpaperUrl(dark['background-image']);

    if (lightUrl !== '' && darkUrl !== '' && lightUrl === darkUrl && lightAttr === '' && cache.light === '') {
        light = { ...light, 'background-image': '' };
    }

    const rules = [];
    const lightRule = wallpaperStylesToCssRule(`#${id}`, light);

    if (lightRule !== '') {
        rules.push(lightRule);
    }

    const darkSelector = pageSurfaceDarkRuleSelector(id);
    const darkRule = wallpaperStylesToCssRule(darkSelector, dark);

    if (darkRule !== '') {
        rules.push(darkRule);
    }

    return rules.join('\n');
}

/**
 * @param {string|null|undefined} image
 * @returns {string}
 */
function extractWallpaperUrl(image) {
    const match = String(image ?? '').match(/url\(\s*['"]?([^'")]+)['"]?\s*\)/i);

    return match?.[1]?.trim() ?? '';
}

/**
 * @param {string} selector
 * @param {Record<string, string>} styles
 * @returns {string}
 */
function wallpaperStylesToCssRule(selector, styles) {
    const sel = String(selector ?? '').trim();
    const image = String(styles?.['background-image'] ?? '').trim();

    if (sel === '' || image === '' || image === 'none' || ! /url\s*\(/i.test(image)) {
        return '';
    }

    const prepared = withWallpaperLayoutDefaults(styles);
    const decls = [];

    for (const prop of [
        'background-image',
        'background-size',
        'background-position',
        'background-repeat',
        'background-attachment',
        'background-color',
    ]) {
        const value = String(prepared[prop] ?? '').trim();

        if (value === '' || shouldOmitAuthorStyleValue(prop, value)) {
            continue;
        }

        decls.push(`${prop}:${value}`);
    }

    return decls.length === 0 ? '' : `${sel} {${decls.join(';')}}`;
}

/**
 * GrapesJS private style classes (c1234) must not be persisted — clones share
 * those names and reloading makes sibling fonts/colors converge.
 *
 * @param {string} className
 * @returns {boolean}
 */
function isGrapesPrivateClassName(className) {
    return /^c\d+[a-z0-9]*$/i.test(String(className ?? '').trim());
}

/**
 * Mirror EditorPastedComponentNormalizer::isTailwindUtilityClassName — utilities
 * are regenerated by page JIT; only custom / #id author rules need persistence.
 *
 * @param {string} className
 * @returns {boolean}
 */
function isTailwindUtilityClassName(className) {
    const name = String(className ?? '').trim().replace(/^!/, '');

    if (name === '') {
        return false;
    }

    if (isGrapesPrivateClassName(name)) {
        return true;
    }

    // Bare utilities (no hyphen) that Tailwind ships as single tokens.
    if (/^(isolate|border|rounded|shadow|truncate|uppercase|lowercase|capitalize|italic|underline|antialiased|contents|grow|shrink)$/i.test(name)) {
        return true;
    }

    return /^(?:[a-z][a-z0-9_-]*:)*-?(?:flex|grid|inline-flex|inline|block|hidden|contents|table|flow-root|list-item|absolute|relative|fixed|sticky|static|container|mx-|my-|mt-|mb-|ml-|mr-|w-|h-|min-w-|max-w-|min-h-|max-h-|size-|gap-|p-|px-|py-|pt-|pb-|pl-|pr-|m-|text-|bg-|rounded|shadow|aspect-|col-|row-|items-|justify-|self-|order-|space-|divide-|border-opacity|border-|ring-|outline-|opacity-|z-|top-|bottom-|left-|right-|inset-|object-|overflow-|truncate|whitespace-|leading-|font-|tracking-|underline|decoration-|backdrop-|transition|duration-|ease-|scale-|rotate-|translate-|skew-|origin-|fill-|stroke-|sr-only|not-sr-only|pointer-events-|select-|cursor-|align-|place-|content-|grow|shrink|basis-|from-|to-|via-|bg-vp-|text-vp-|antialiased|subpixel-antialiased|italic|not-italic|visible|invisible|collapse|isolate|box-|break-|hyphens-|list-|columns-|float-|clear-|overscroll-|scroll-|snap-|touch-|will-change-|accent-|caret-|field-sizing-|animate-)/i
        .test(name);
}

/**
 * True when the selector list includes a real Style Manager `#id` token.
 * Tailwind arbitrary colors use escaped `\#` inside class names (`.bg-\[\#fff\]`)
 * — those must NOT count as author id paints or Save ships the whole JIT sheet.
 *
 * @param {string} selectors
 * @returns {boolean}
 */
function hasUnescapedIdSelector(selectors) {
    const value = String(selectors ?? '');

    // `(^|[^\\])#` — hash not preceded by a backslash (CSS-escaped arbitrary value).
    return /(?:^|[^\\])#[A-Za-z_]/.test(value);
}

/**
 * Keep Style Manager author paints (#id) and custom class rules from Library
 * / pasted embeds (e.g. .vb-hero-plasma__orb). Drop Tailwind utilities and
 * Grapes private classes — regenerated or harmful on reload.
 *
 * @param {string} selectors
 * @returns {boolean}
 */
function isAuthorStyleSelector(selectors) {
    const value = String(selectors ?? '').trim();

    if (value === '') {
        return false;
    }

    if (hasUnescapedIdSelector(value)) {
        return true;
    }

    if (! value.includes('.')) {
        return false;
    }

    const classMatches = value.matchAll(/\.((?:\\.|[^\s.#:[>+~,])+)/g);

    for (const match of classMatches) {
        const className = String(match[1] ?? '').replace(/\\/g, '').replace(/^!/, '');

        if (className !== '' && ! isTailwindUtilityClassName(className)) {
            return true;
        }
    }

    return false;
}

/**
 * Extract @keyframes blocks (nested braces) so Library/embed animations survive save.
 * Theme / Style Manager presets (fade-up, flip-up, ping, …) are NOT persisted — they
 * live in theme.css via Tailwind CSS Animated and must stay class-driven.
 *
 * @param {string} css
 * @returns {{ keyframes: string[], remainder: string }}
 */
function extractKeyframesBlocks(css) {
    const source = String(css ?? '');
    const keyframes = [];
    let remainder = '';
    let i = 0;

    while (i < source.length) {
        const at = source.slice(i).search(/@keyframes\b/i);

        if (at < 0) {
            remainder += source.slice(i);
            break;
        }

        remainder += source.slice(i, i + at);
        i += at;

        const brace = source.indexOf('{', i);

        if (brace < 0) {
            remainder += source.slice(i);
            break;
        }

        let depth = 0;
        let end = brace;

        for (; end < source.length; end += 1) {
            const ch = source[end];

            if (ch === '{') {
                depth += 1;
            } else if (ch === '}') {
                depth -= 1;

                if (depth === 0) {
                    end += 1;
                    break;
                }
            }
        }

        const block = source.slice(i, end).trim();

        if (block !== '' && ! isThemeAnimationKeyframeBlock(block)) {
            keyframes.push(block);
        }

        i = end;
    }

    return { keyframes, remainder };
}

/**
 * @keyframes shipped by tailwindcss-animated / Style Manager presets.
 * Custom Library motion (e.g. vb-plasma-spin) must still be persisted.
 *
 * @param {string} block
 * @returns {boolean}
 */
export function isThemeAnimationKeyframeBlock(block) {
    const match = String(block ?? '').match(/@keyframes\s+([^\s*{]+)/i);

    if (! match) {
        return false;
    }

    const name = String(match[1] ?? '').trim().toLowerCase();

    return /^(?:fade|fade-up|fade-down|fade-left|fade-right|flip-up|flip-down|ping|bounce|spin|pulse|wiggle|wiggle-more|shake|jump|jump-in|jump-out|rotate-x|rotate-y)$/.test(name);
}

export function extractGrapesComposerCss(css) {
    const source = String(css ?? '').trim();

    if (source === '') {
        return '';
    }

    const { keyframes, remainder } = extractKeyframesBlocks(source);

    // Strip @media blocks (utilities / responsive bundles are regenerated server-side).
    // prefers-reduced-motion for catalog motion lives in theme.css.
    let withoutMedia = remainder;
    let guard = 0;

    while (guard < 50) {
        const next = withoutMedia.replace(/@media[^{]*\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/g, ' ');

        if (next === withoutMedia) {
            break;
        }

        withoutMedia = next;
        guard += 1;
    }

    const kept = [...keyframes];

    for (const match of withoutMedia.matchAll(/([^{}@]+)\{([^{}]*)\}/g)) {
        const selectors = match[1].trim();
        const body = match[2].trim();

        if (selectors === '' || body === '') {
            continue;
        }

        if (! isAuthorStyleSelector(selectors)) {
            continue;
        }

        const cleanedBody = sanitizeClearedPaintRuleBody(body);

        if (cleanedBody === '') {
            continue;
        }

        kept.push(`${selectors} {${cleanedBody}}`);
    }

    return kept.join('\n');
}

/**
 * Last-resort Save trim: keep only `#id` / `html.dark #id` Style Manager paints.
 * Drops custom BEM / Library class rules when the author CSS still exceeds max_css.
 *
 * @param {string} css
 * @returns {string}
 */
export function extractBareIdAuthorCss(css) {
    const source = String(css ?? '').trim();

    if (source === '') {
        return '';
    }

    const kept = [];

    for (const match of source.matchAll(/([^{}@]+)\{([^{}]*)\}/g)) {
        const selectors = match[1].trim();
        const body = match[2].trim();

        if (selectors === '' || body === '') {
            continue;
        }

        // Bare #id (optional pseudo) OR html.dark #id — page light + dark wallpaper.
        if (! /^(?:html\.dark\s+)?(?:#[A-Za-z_][\w-]*(?::+[A-Za-z_-][\w-]*)*)(?:\s*,\s*(?:html\.dark\s+)?#[A-Za-z_][\w-]*(?::+[A-Za-z_-][\w-]*)*)*$/i.test(selectors)) {
            continue;
        }

        if (! hasUnescapedIdSelector(selectors)) {
            continue;
        }

        const cleanedBody = sanitizeClearedPaintRuleBody(body);

        if (cleanedBody === '') {
            continue;
        }

        kept.push(`${selectors} {${cleanedBody}}`);
    }

    return kept.join('\n');
}

/**
 * Remove cleared SVG paint declarations (color/stroke/fill:none) from a CSS rule body.
 *
 * @param {string} body
 * @returns {string}
 */
export function sanitizeClearedPaintRuleBody(body) {
    return String(body ?? '')
        .split(';')
        .map((chunk) => chunk.trim())
        .filter((chunk) => {
            if (chunk === '') {
                return false;
            }

            const colon = chunk.indexOf(':');

            if (colon === -1) {
                return true;
            }

            const property = chunk.slice(0, colon).trim().toLowerCase();
            const value = chunk.slice(colon + 1).trim();

            if (! ['color', 'fill', 'stroke'].includes(property)) {
                return true;
            }

            return ! shouldOmitAuthorStyleValue(property, value);
        })
        .join('; ');
}

/**
 * Remove author `#id { … }` rules from a CSS blob (e.g. live JIT sheet).
 * Keeps utilities; avoids stale font-family #id rules overriding the canvas
 * after Save when the live sheet is injected last in the iframe head.
 *
 * @param {string} css
 * @returns {string}
 */
export function stripAuthorIdRules(css) {
    const source = String(css ?? '');

    if (source === '' || ! source.includes('#')) {
        return source.trim();
    }

    return source
        // Dark page wallpaper companions (`html.dark #id`) before bare #id.
        .replace(/html\.dark\s+#[A-Za-z][\w-]*\s*\{[^{}]*\}/gi, ' ')
        .replace(/#[A-Za-z][\w-]*(?:\s*,\s*#[A-Za-z][\w-]*)*\s*\{[^{}]*\}/g, ' ')
        .replace(/\n{3,}/g, '\n\n')
        .trim();
}

/**
 * Safety net: emit `#id { … }` rules from component inline + CssComposer #id
 * styles. Grapes `getCss()` can omit unused/private rules; without this, Style
 * Manager paints vanish from builder_payload.css on chrome-shell save.
 *
 * @param {object} editor
 * @param {{ root?: object }} [options]
 * @returns {string}
 */
export function collectAuthorIdCssFromComponents(editor, options = {}) {
    const wrapper = editor?.getWrapper?.();
    const root = options.root ?? wrapper;
    const cssApi = editor?.Css;

    if (! root?.onAll) {
        return '';
    }

    const rules = [];
    const seen = new Set();

    root.onAll((component) => {
        const id = String(component?.getId?.() ?? '').trim();

        if (id === '' || seen.has(id)) {
            return;
        }

        seen.add(id);

        const fromId = { ...(cssApi?.getIdRule?.(id)?.getStyle?.() ?? {}) };
        const inline = { ...(component.getStyle?.({ inline: true }) ?? {}) };
        // Combined getStyle() can include private-class paints still attached.
        const combined = { ...(component.getStyle?.() ?? {}) };
        const merged = { ...fromId, ...combined, ...inline };
        const decls = [];

        for (const [property, value] of Object.entries(merged)) {
            if (value == null || value === '') {
                continue;
            }

            const raw = String(value).trim();

            if (raw === '' || raw === 'undefined' || raw === 'null') {
                continue;
            }

            // Skip Grapes internal / empty clears / SM-invented defaults.
            if (/^(none|transparent|initial|inherit|unset)$/i.test(raw.replace(/\s*!important\s*$/i, '').trim())
                && (property === 'background' || property === 'background-color' || property === 'background-image')) {
                continue;
            }

            if (shouldOmitAuthorStyleValue(property, raw)) {
                continue;
            }

            decls.push(`${property}:${raw}`);
        }

        if (decls.length === 0) {
            return;
        }

        rules.push(`#${id} {${decls.join(';')}}`);
    });

    return rules.join('\n');
}

/**
 * Read Grapes CssComposer author CSS for Save.
 *
 * Prefer enumerating CssRule models over `getCss({ keepUnusedStyles: true })`:
 * on chrome-shell pages the latter can serialize the live utility bundle and
 * freeze Save for tens of seconds.
 *
 * Never fall back to `getCss({ keepUnusedStyles: true })` — when CssComposer only
 * holds utilities, that fallback used to block Save for ~10–20s.
 *
 * @param {object} editor
 * @returns {string}
 */
export function readComposerCssForPersist(editor) {
    const cssApi = editor?.Css;

    if (cssApi?.getAll) {
        try {
            const rules = [];
            let scanned = 0;

            for (const rule of cssApi.getAll()) {
                scanned += 1;

                // Soft cap: a polluted CssComposer (full JIT sheet) must not dominate Save.
                if (scanned > 4_000) {
                    break;
                }

                const sel = cssRuleSelectorText(rule);

                // Reject utilities BEFORE getStyle() — getStyle on thousands of JIT
                // rules is what made Save feel frozen even when nothing author-owned
                // needed serializing.
                if (sel === '' || ! isAuthorStyleSelector(sel)) {
                    continue;
                }

                // Grapes selectorsAdd "html.dark" means comma-additional → `#id, html.dark`.
                // That paints dark onto #id and must never be persisted (pageSurfaceCss emits
                // real `html.dark #id` instead).
                if (/html\.dark/i.test(sel) && ! /^html\.dark\s+#/i.test(sel.trim())) {
                    continue;
                }

                const style = rule?.getStyle?.() ?? {};
                const decls = [];

                for (const [property, value] of Object.entries(style)) {
                    if (value == null || String(value).trim() === '') {
                        continue;
                    }

                    decls.push(`${property}:${value}`);
                }

                if (decls.length === 0) {
                    continue;
                }

                rules.push(`${sel} {${decls.join(';')}}`);
            }

            return rules.join('\n');
        } catch (error) {
            console.warn('VoodBuilder readComposerCssForPersist: Css.getAll failed', error);
        }
    }

    // Do NOT call getCss({ keepUnusedStyles: true }) — it freezes Save on chrome-shell
    // pages by serializing the live utility bundle. Component #id walk covers paints.
    return '';
}

/**
 * @param {object} rule
 * @returns {string}
 */
function cssRuleSelectorText(rule) {
    if (! rule) {
        return '';
    }

    // Never persist Grapes selectorsAdd "html.dark" as `#id, html.dark` — that is
    // comma-additional, not the descendant `html.dark #id` we need for dual themes.
    const addRaw = String(rule.get?.('selectorsAdd') ?? '').replace(/\s+/g, ' ').trim();

    if (addRaw.toLowerCase() === 'html.dark') {
        return '';
    }

    let base = '';

    if (typeof rule.selectorsToString === 'function') {
        try {
            base = String(rule.selectorsToString({ skipAdd: true }) ?? '').trim();
        } catch {
            base = String(rule.selectorsToString() ?? '').trim();
        }
    }

    if (base === '') {
        const selectors = rule.get?.('selectors');

        if (selectors && typeof selectors.map === 'function') {
            base = selectors
                .map((item) => (typeof item === 'string' ? item : String(item?.get?.('name') ?? item?.id ?? '')))
                .filter(Boolean)
                .join(', ');
        } else if (Array.isArray(selectors)) {
            base = selectors
                .map((item) => (typeof item === 'string' ? item : String(item?.get?.('name') ?? '')))
                .filter(Boolean)
                .join(', ');
        }
    }

    // Strip a trailing ", html.dark" Grapes may bake into selectorsToString().
    base = base.replace(/,\s*html\.dark\b/gi, '').trim();

    if (/html\.dark/i.test(base) && ! /^html\.dark\s+#/i.test(base)) {
        return '';
    }

    const add = addRaw;

    if (add !== '' && base !== '') {
        if (base.toLowerCase().includes(add.toLowerCase())) {
            return base;
        }

        // Prefix compound (descendant), never comma-list.
        return `${add} ${base}`.replace(/\s+/g, ' ').trim();
    }

    if (base !== '') {
        return base;
    }

    return add;
}

/**
 * Merge CSS chunks, preferring the first declaration block per selector.
 * Light `#id` and dark `html.dark #id` are distinct (both must survive Save).
 *
 * @param {string[]} chunks
 * @returns {string}
 */
export function mergeAuthorCssChunks(chunks) {
    const seenSelectors = new Set();
    const out = [];

    for (const chunk of chunks) {
        const source = String(chunk ?? '').trim();

        if (source === '') {
            continue;
        }

        // Split into rules while keeping non-# utility blobs intact.
        if (! source.includes('#') && ! /html\.dark/i.test(source)) {
            if (! out.includes(source)) {
                out.push(source);
            }

            continue;
        }

        let cursor = 0;
        const re = /([^{}@]+)\{([^{}]*)\}/g;
        let match;

        while ((match = re.exec(source)) !== null) {
            const between = source.slice(cursor, match.index).trim();

            if (between !== '' && ! out.includes(between)) {
                out.push(between);
            }

            cursor = match.index + match[0].length;
            const selectors = match[1].trim();
            const body = match[2].trim();
            const rule = `${selectors} {${body}}`;
            const key = selectors.replace(/\s+/g, ' ').toLowerCase();

            if (key !== '' && seenSelectors.has(key)) {
                continue;
            }

            if (key !== '') {
                seenSelectors.add(key);
            }

            out.push(rule);
        }

        const tail = source.slice(cursor).trim();

        if (tail !== '' && ! out.includes(tail)) {
            out.push(tail);
        }
    }

    return out.join('\n');
}

function normalizeDynamicBlockComponents(editor) {
    safeFindComponents(editor.getWrapper?.(), '[data-voodbuilder-block]').forEach((component) => {
        syncDynamicBlockAttributes(component);

        const blockId = component.getAttributes()['data-voodbuilder-block'];

        if (isNavBlock(blockId)) {
            syncSiteHeaderConfig(component);
        }

        if (isFooterBlock(blockId)) {
            syncSiteFooterConfig(component);
        }
    });
}

/**
 * Editor getHtml({ withProps: true }) serializes object props as
 * data-gjs-resizable="{"ratioDefault":1}" which is invalid HTML and leaks
 * junk onto the published page. Prefer single-quoted JSON attributes.
 *
 * @param {string} html
 * @returns {string}
 */
export function encodeJsonDataGjsAttributes(html) {
    if (typeof html !== 'string' || html === '' || ! html.includes('data-gjs-')) {
        return html;
    }

    return html.replace(
        /\s+data-gjs-([a-zA-Z0-9_-]+)="(\{[\s\S]*?\})"/g,
        (match, name, json) => {
            if (! json.includes('"')) {
                return match;
            }

            return ` data-gjs-${name}='${json.replace(/'/g, '&#39;')}'`;
        },
    );
}

/**
 * @param {object} editor
 * @param {{ mutate?: boolean, light?: boolean }} [options]
 *   mutate=false → read-only snapshot for onUpdate (must not wipe CssComposer / styles).
 *   mutate=true (default) → save path: sync/bake/purge then serialize.
 *   light=true → Save-fast path: skip non-essential full-tree syncs (bindings/video/…).
 */
export function buildPayload(editor, options = {}) {
    const mutate = options.mutate !== false;
    const light = options.light === true;
    const chromeShell = Boolean(editor.__voodbuilderChromeShellMode);
    const exportRoot = chromeShell ? (findPageContentSlotInEditor(editor) ?? null) : null;
    const scoped = exportRoot ? { root: exportRoot } : {};

    const runExportStep = (label, step) => {
        try {
            step();
        } catch (error) {
            console.warn(`Voodbuilder buildPayload: ${label} failed`, error);
        }
    };

    const assemble = () => {
        if (mutate) {
            // Prevent live JIT / chrome refresh storms while we touch styles for export.
            editor.__voodbuilderSetCssRebuildSuspended?.(true);
            editor.__voodbuilderBulkStructureUpdate = true;

            try {
                if (! light) {
                    runExportStep('detachReadingPreview', () => editor.trigger?.('voodbuilder:reading-preview:detach'));
                    runExportStep('normalizeDynamicBlockComponents', () => normalizeDynamicBlockComponents(editor));
                    runExportStep('pruneEmptyDynamicBlocks', () => pruneEmptyDynamicBlocks(editor));
                    runExportStep('syncBindingsForExport', () => syncBindingsForExport(editor));
                    runExportStep('ensureComponentInstancesForExport', () => ensureComponentInstancesForExport(editor));
                    runExportStep('purgeDesyncedBackgroundCssRules', () => purgeDesyncedBackgroundCssRules(editor));
                    runExportStep('syncSpacingStylesForExport', () => syncSpacingStylesForExport(editor, scoped));
                    runExportStep('syncPaintStylesForExport', () => syncPaintStylesForExport(editor, scoped));
                    runExportStep('syncComponentInstancePaintForExport', () => syncComponentInstancePaintForExport(editor));
                    runExportStep('bakeSvgPaintForExport', () => bakeSvgPaintForExport(editor));
                    runExportStep('pruneRedundantSpacingZerosForExport', () => pruneRedundantSpacingZerosForExport(editor));
                    runExportStep('syncComponentInstancesForExport', () => syncComponentInstancesForExport(editor));
                    runExportStep('syncRepeatBindingsForExport', () => syncRepeatBindingsForExport(editor));
                    runExportStep('syncAnimatedCountersForExport', () => syncAnimatedCountersForExport(editor));
                    runExportStep('syncConditionsForExport', () => syncConditionsForExport(editor));
                    runExportStep('syncVideoComponentsForExport', () => syncVideoComponentsForExport(editor));
                    runExportStep('purgeOrphanPageContentNodes', () => {
                        if (exportRoot) {
                            purgeOrphanPageContentNodes(exportRoot);
                        }
                    });
                    runExportStep('ensureCtaButtonsForExport', () => ensureCtaButtonsForExport(editor));
                    runExportStep('ensureIconsForExport', () => ensureIconsForExport(editor));
                    runExportStep('ensureLayoutContainersForExport', () => ensureLayoutContainersForExport(editor));
                    runExportStep('restoreContentWidthFromAttributes', () => restoreContentWidthFromAttributes(editor));
                }

                runExportStep('detachTopDropSpacerForExport', () => detachTopDropSpacerForExport(editor));
                runExportStep('detachInnerDropSlotsForExport', () => detachInnerDropSlotsForExport(editor));
                // Chrome shell: bake only the page content slot (nav/footer are separate).
                runExportStep('bakeAuthorStylesToComposerForExport', () => {
                    bakeAuthorStylesToComposerForExport(editor, scoped);
                });
                runExportStep('syncLayerVisibilityForExport', () => syncLayerVisibilityForExport(editor));
            } finally {
                editor.__voodbuilderBulkStructureUpdate = false;
                // Do not flush a deferred JIT on resume — Save already ships author CSS;
                // the server compiles utilities. Flushing here re-queued 10s+ compiles.
                editor.__voodbuilderFlushCssRebuildOnResume = false;
                editor.__voodbuilderSetCssRebuildSuspended?.(false);
            }
        }

        let html = '';

        if (chromeShell) {
            // Never serialize the full chrome shell via getHtml() — nav/footer dwarfs
            // page content and dominated Save on chrome-shell pages.
            const slotHtml = extractChromeShellPageHtml(editor);

            if (slotHtml !== null) {
                const trimmed = slotHtml.trim();

                if (trimmed !== '') {
                    html = slotHtml;
                } else {
                    const childCount = exportRoot?.components?.()?.length ?? 0;

                    if (childCount === 0) {
                        html = '';
                    } else {
                        console.error(
                            'VoodBuilder: chrome shell HTML extract was empty while the content slot still has children — falling back to slot.toHTML.',
                            { childCount },
                        );
                        html = String(exportRoot?.toHTML?.({
                            keepInlineStyle: true,
                            withProps: false,
                        }) ?? '');
                    }
                }
            }
        } else if (editor.__voodbuilderChromeLayoutMode) {
            html = extractChromeLayoutHtml(editor);
        } else {
            try {
                html = editor.getHtml({
                    cleanId: false,
                    withProps: false,
                    keepInlineStyle: true,
                });
            } catch (error) {
                console.error('VoodBuilder getHtml failed; retrying without inline style opts', error);
                html = editor.getHtml();
            }
        }

        // Click-to-play cover must survive export (GrapesJS still serializes embed iframes).
        html = applyVideoFacadesToExportedHtml(editor, html);
        html = encodeJsonDataGjsAttributes(html);

        editor.__voodbuilderLastSavedPageHtml = String(html);

        // Author #id / BEM only — never ship live JIT. Avoid getCss(keepUnusedStyles).
        const composerCss = readComposerCssForPersist(editor);
        const styleManagerCss = extractGrapesComposerCss(composerCss);
        const componentAuthorCss = collectAuthorIdCssFromComponents(editor, scoped);
        // Chrome-shell Save skips the wrapper in HTML — emit light + dark page
        // wallpaper #id rules explicitly so refresh can reclaim both themes.
        const pageSurfaceCss = collectPageSurfaceWallpaperCssForPersist(editor);
        // Page-surface light+dark from attrs/cache must win over CssComposer #id —
        // Grapes often holds only the last painted theme on bare `#id`.
        let css = mergeAuthorCssChunks([
            pageSurfaceCss,
            styleManagerCss,
            componentAuthorCss,
        ]);

        // Final guard: never ship Grapes comma form `#id, html.dark`.
        css = css.replace(/#[\w-]+\s*,\s*html\.dark\s*\{[^{}]*\}/gi, '').trim();

        const maxCssBytes = Number(editor.__voodbuilderMaxCssBytes ?? 1_000_000);

        if (css.length > maxCssBytes) {
            css = mergeAuthorCssChunks([
                extractBareIdAuthorCss(pageSurfaceCss),
                extractBareIdAuthorCss(styleManagerCss),
                extractBareIdAuthorCss(componentAuthorCss),
            ]);
        }

        if (css.length > maxCssBytes) {
            css = '';
        }

        const payload = {
            html,
            css,
            js: editor.getJs(),
        };

        // Canvas already JIT-compiled utilities (author sees new classes live). Ship
        // them so the server can skip a second Node compile on Save.
        const liveCss = String(editor.__voodbuilderPageLiveCss ?? '').trim();

        if (liveCss !== '') {
            payload.live_css = stripAuthorIdRules(liveCss);
        }

        if (editor.__voodbuilderChromeLayoutMode && editor.__voodbuilderReadingTypography) {
            payload.readingTypography = {
                font: editor.__voodbuilderReadingTypography.font,
                headingFont: editor.__voodbuilderReadingTypography.headingFont,
                typeScale: editor.__voodbuilderReadingTypography.typeScale,
            };
        }

        restoreTopDropSpacerAfterExport(editor);

        if (mutate && ! light) {
            runExportStep('attachReadingPreview', () => editor.trigger?.('voodbuilder:reading-preview:attach'));
        }

        restoreSvgPaintInspectorStyles(editor);

        const selected = editor.getSelected?.();

        if (selected && String(selected.get?.('tagName') ?? '').toLowerCase() === 'svg') {
            window.requestAnimationFrame(() => {
                restoreSvgPaintInspectorStyle(selected);
                editor.StyleManager?.select?.(selected);
            });
        }

        return payload;
    };

    return mutate ? withoutUndo(editor, assemble) : assemble();
}
