/**
 * Content-panel settings for Basic elements: Icon, Text link, Divider.
 */

import {
    createFormSection,
    createSelectField,
    createTextField,
} from './editor-form-ui.js';
import {
    applyReadingProgressCssVars,
    readingProgressColorOptions,
    READING_PROGRESS_THICKNESS,
    resolveReadingProgressCssColor,
    resolveReadingProgressThicknessPx,
} from './reading-progress-appearance.js';
import {
    DEFAULT_TABLER_ICON,
    DEFAULT_TABLER_ICON_STROKE,
    DEFAULT_TABLER_ICON_STYLE,
    ensureTablerCatalog,
    findCategoryForIcon,
    getTablerCatalogSync,
    listTablerIconCategories,
    queryTablerIcons,
    resolveTablerIconName,
    resolveTablerIconStroke,
    resolveTablerIconStyle,
    tablerIconSvg,
    TABLER_CATEGORY_LABELS,
} from './tabler-icons-catalog.js';

function runWithSettingsChangeGuard(editor, callback) {
    if (typeof callback !== 'function') {
        return;
    }

    if (! editor) {
        callback();

        return;
    }

    const depth = Number(editor.__voodbuilderSettingsChangeDepth ?? 0);
    editor.__voodbuilderSettingsChangeDepth = depth + 1;
    editor.__voodbuilderSettingsChange = true;

    try {
        callback();
    } finally {
        const nextDepth = Number(editor.__voodbuilderSettingsChangeDepth ?? 1) - 1;
        editor.__voodbuilderSettingsChangeDepth = nextDepth;

        if (nextDepth <= 0) {
            editor.__voodbuilderSettingsChange = false;
            delete editor.__voodbuilderSettingsChangeDepth;
        }
    }
}

function componentKey(component) {
    return String(component?.cid ?? component?.getId?.() ?? component?.get?.('id') ?? '');
}

export function isIconComponent(component) {
    if (! component?.get) {
        return false;
    }

    if (component.get('type') === 'voodbuilder-icon') {
        return true;
    }

    return component.getAttributes?.()?.['data-voodbuilder-icon'] != null;
}

/**
 * Walk up from an SVG/path child to the Icon host wrapper.
 *
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
export function findIconHost(component) {
    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        if (isIconComponent(current)) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

export function isTextLinkComponent(component) {
    if (! component?.get) {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};

    if (attrs['data-voodbuilder-cta'] === 'true'
        || attrs['data-voodbuilder-skip-cta'] != null
        || attrs['data-voodbuilder-icon'] != null) {
        return false;
    }

    if (component.get('type') === 'voodbuilder-text-link') {
        return true;
    }

    if ((component.getClasses?.() ?? []).includes('vb-text-link')) {
        return true;
    }

    // Catalog / remote sections often ship plain <a> (Grapes type "link").
    if (String(component.get('tagName') ?? '').toLowerCase() !== 'a'
        && component.get('type') !== 'link') {
        return false;
    }

    return ! isInsideChromeShell(component);
}

/**
 * Walk up to the nearest content text link (so selecting text/SVG inside <a> still opens link settings).
 *
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
export function findTextLinkHost(component) {
    let current = component;

    while (current?.get) {
        if (isTextLinkComponent(current)) {
            return current;
        }

        current = current.parent?.();
    }

    return null;
}

function isInsideChromeShell(component) {
    let current = component;

    while (current?.get) {
        const attrs = current.getAttributes?.() ?? {};

        if (attrs['data-voodbuilder-editor-site-header'] != null
            || attrs['data-voodbuilder-editor-site-footer'] != null
            || attrs['data-voodbuilder-chrome-shell'] != null
            || attrs['data-voodbuilder-chrome-shell-part'] != null) {
            return true;
        }

        const tag = String(current.get('tagName') ?? '').toLowerCase();

        if (tag === 'nav') {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

export function isDividerComponent(component) {
    if (! component?.get) {
        return false;
    }

    const attrs = component.getAttributes?.() ?? {};

    return attrs['data-voodbuilder-divider'] != null
        || (component.getClasses?.() ?? []).includes('vb-divider')
        || String(component.get('tagName') ?? '').toLowerCase() === 'hr';
}

export function isReadingProgressComponent(component) {
    if (! component?.get) {
        return false;
    }

    const type = String(component.get('type') ?? '');
    const attrs = component.getAttributes?.() ?? {};

    return type === 'voodbuilder-reading-progress'
        || (
            attrs['data-voodbuilder-progress'] != null
            && (component.getClasses?.() ?? []).includes('vb-reading-progress')
        );
}

const READING_PROGRESS_COLORS = readingProgressColorOptions();

/**
 * @param {string} raw
 * @returns {string}
 */
export { resolveReadingProgressCssColor };

/**
 * @param {object} component
 */
export function applyReadingProgressAppearance(component) {
    if (! isReadingProgressComponent(component)) {
        return;
    }

    const attrs = component.getAttributes?.() ?? {};
    const color = String(attrs['data-vb-progress-color'] || 'brand');
    const thickness = String(attrs['data-vb-progress-thickness'] || '4');
    const cssColor = resolveReadingProgressCssColor(color);
    const height = `${resolveReadingProgressThicknessPx(thickness)}px`;

    const style = String(attrs.style ?? '')
        .split(';')
        .map((part) => part.trim())
        .filter((part) => (
            part !== ''
            && ! /^--vb-progress-(color|height)\s*:/i.test(part)
            && ! /^(position|top|left|right|z-index|margin|height|min-height)\s*:/i.test(part)
        ));

    style.push(`--vb-progress-color: ${cssColor}`);
    style.push(`--vb-progress-height: ${height}`);

    component.addAttributes({
        'data-voodbuilder-progress': '',
        'data-vb-progress-color': color,
        'data-vb-progress-thickness': thickness,
        style: style.join('; '),
    });

    component.removeClass?.('relative');

    const el = component.getEl?.();

    if (el instanceof HTMLElement) {
        applyReadingProgressCssVars(el);
    }
}

const ICON_SIZES = [
    { value: 'size-6', label: 'S (24px)' },
    { value: 'size-8', label: 'M (32px)' },
    { value: 'size-10', label: 'L (40px)' },
    { value: 'size-12', label: 'XL (48px)' },
    { value: 'size-16', label: '2XL (64px)' },
];

/** Stable px sizes — do not rely on Tailwind JIT for icon box dimensions. */
const ICON_SIZE_PX = {
    'size-6': 24,
    'size-8': 32,
    'size-10': 40,
    'size-12': 48,
    'size-16': 64,
};

const ICON_STROKES = [
    { value: '1', label: '1' },
    { value: '1.5', label: '1.5' },
    { value: '1.75', label: '1.75' },
    { value: '2', label: '2' },
    { value: '2.5', label: '2.5' },
];

const ICON_PICKER_PAGE = 80;

/**
 * Icon color may be a CSS value (#hex/rgb/…) or a Tailwind text-* utility.
 *
 * @param {unknown} raw
 * @returns {{ mode: 'none'|'css'|'class', value: string, className: string }}
 */
function parseIconColor(raw) {
    const value = String(raw ?? '').trim();

    if (value === '' || value === 'currentColor' || value === 'inherit') {
        return { mode: 'none', value: '', className: '' };
    }

    if (/^(#|rgba?\(|hsla?\()/i.test(value)) {
        return { mode: 'css', value, className: '' };
    }

    // Named CSS colors kept as inline style.
    if (/^(transparent|black|white|red|blue|green|gray|grey|orange|purple|pink|yellow|currentColor)$/i.test(value)
        && ! value.includes('-')) {
        return { mode: 'css', value, className: '' };
    }

    const className = value.startsWith('text-') ? value : `text-${value}`;

    if (/^text-[\w./:[\]%-]+$/.test(className)) {
        return { mode: 'class', value: className, className };
    }

    // Fallback: treat as CSS (e.g. "rebeccapurple", CSS variables).
    return { mode: 'css', value, className: '' };
}

function isManagedIconTextClass(token) {
    const name = String(token ?? '');

    return name === 'text-vp-text-2'
        || /^text-(?:vp-[\w-]+|slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-\d{2,3}$/.test(name)
        || /^text-(?:black|white|transparent|current)$/.test(name)
        || /^text-\[.+\]$/.test(name);
}

/**
 * Layout / measure utilities that must never stick on an Icon host
 * (they make the SVG `w-full` explode to the column width).
 *
 * @param {string} token
 * @returns {boolean}
 */
function isIconLayoutNoiseClass(token) {
    const name = String(token ?? '');

    if (name.startsWith('size-') || isManagedIconTextClass(name)) {
        return true;
    }

    if (/^(sm:|md:|lg:|xl:|2xl:)?(w-|h-|max-w-|max-h-|min-w-|min-h-|basis-|grow|shrink)/.test(name)) {
        return true;
    }

    return [
        'w-full',
        'h-full',
        'ms-auto',
        'me-auto',
        'mx-auto',
        'flex-1',
        'flex-auto',
        'block',
        'vb-layout-row',
        'voodbuilder-editor-container',
    ].includes(name);
}

/**
 * @param {object} component
 * @returns {object|null}
 */
function findIconSvgChild(component) {
    return [...(component.components?.() ?? [])]
        .find((child) => String(child.get?.('tagName') ?? '').toLowerCase() === 'svg')
        ?? null;
}

/**
 * Glyph actually painted on the canvas (may differ from data-vb-icon before catalog load).
 *
 * @param {object} component
 * @returns {string}
 */
function readPaintedIconGlyph(component) {
    return String(findIconSvgChild(component)?.getAttributes?.()?.['data-vb-icon-glyph'] ?? '').trim();
}

/**
 * Pin host box size with inline styles so canvas JIT / compile thrash cannot enlarge the icon.
 *
 * @param {object} component
 * @param {string} sizeClass
 */
function applyIconHostBoxSize(component, sizeClass) {
    const px = ICON_SIZE_PX[sizeClass] ?? ICON_SIZE_PX['size-10'];
    // Prefer inline styles so we do not drop a previously painted `color`.
    const style = {
        ...(component.getStyle?.({ inline: true }) ?? component.getStyle?.() ?? {}),
    };

    const attrColor = String(
        parseStyleAttributeColor(component.getAttributes?.()?.style)
        ?? '',
    ).trim();

    if (attrColor && ! String(style.color ?? '').trim()) {
        style.color = attrColor;
    }

    style.width = `${px}px`;
    style.height = `${px}px`;
    style.maxWidth = `${px}px`;
    style.maxHeight = `${px}px`;
    style.minWidth = `${px}px`;
    style.minHeight = `${px}px`;
    style.flexShrink = '0';
    style.display = 'inline-flex';
    style.alignItems = 'center';
    style.justifyContent = 'center';
    style.lineHeight = '0';

    component.setStyle(style);
}

/**
 * @param {unknown} styleAttr
 * @returns {string}
 */
function parseStyleAttributeColor(styleAttr) {
    const raw = String(styleAttr ?? '');
    const match = /(?:^|;)\s*color\s*:\s*([^;]+)/i.exec(raw);

    return match?.[1]?.trim() ?? '';
}

/**
 * Replace icon SVG children. Grapes `components(html)` alone can leave a stale glyph.
 *
 * @param {object} component
 * @param {string} svgHtml
 */
function replaceIconGlyph(component, svgHtml) {
    const collection = component.components?.();

    if (collection) {
        try {
            if (typeof collection.reset === 'function') {
                collection.reset();
            } else if (typeof collection.remove === 'function') {
                [...collection].forEach((child) => {
                    try {
                        collection.remove(child);
                    } catch {
                        // ignore
                    }
                });
            } else if (typeof component.empty === 'function') {
                component.empty();
            } else {
                component.components([]);
            }
        } catch {
            try {
                component.components([]);
            } catch {
                // Last resort: overwrite below.
            }
        }
    }

    component.components(svgHtml);
}

/**
 * Resolve persisted icon color from attribute, Tailwind class, or inline style.
 *
 * @param {object} component
 * @returns {string}
 */
export function readIconColor(component) {
    const attrs = component?.getAttributes?.() ?? {};
    const fromAttr = String(attrs['data-vb-icon-color'] ?? '').trim();

    if (fromAttr) {
        return fromAttr;
    }

    const colorClass = [...(component?.getClasses?.() ?? [])]
        .find((token) => isManagedIconTextClass(token) && token !== 'text-vp-text-2');

    if (colorClass) {
        return colorClass;
    }

    const inline = component?.getStyle?.({ inline: true }) ?? {};
    const fromInline = String(inline.color ?? '').trim();

    if (fromInline) {
        return fromInline;
    }

    return String((component?.getStyle?.() ?? {}).color ?? '').trim();
}

/**
 * Persist CSS color on the Editor model + live canvas element.
 *
 * @param {object} component
 * @param {{ mode: string, value: string, className: string }} color
 */
function applyHostColorStyle(component, color) {
    const cssColor = color.mode === 'css' && color.value ? color.value : '';

    if (cssColor) {
        if (typeof component.addStyle === 'function') {
            component.addStyle({ color: cssColor }, { inline: true });
        } else {
            const styleBag = { ...(component.getStyle?.() ?? {}), color: cssColor };
            component.setStyle?.(styleBag);
        }
    } else {
        const styleBag = {
            ...(component.getStyle?.({ inline: true }) ?? component.getStyle?.() ?? {}),
        };
        delete styleBag.color;
        component.setStyle?.(styleBag);
    }

    const attrs = { ...(component.getAttributes?.() ?? {}) };
    const styleParts = String(attrs.style ?? '')
        .split(';')
        .map((part) => part.trim())
        .filter(Boolean)
        .filter((part) => ! /^color\s*:/i.test(part));

    if (cssColor) {
        styleParts.push(`color: ${cssColor}`);
    }

    component.addAttributes({
        style: styleParts.length > 0 ? styleParts.join('; ') : null,
    });

    const el = component.getEl?.()
        ?? component.getView?.()?.el
        ?? component.view?.el;

    if (el?.style) {
        if (cssColor) {
            el.style.color = cssColor;
        } else {
            el.style.removeProperty('color');
        }
    }
}

/**
 * Apply color without rebuilding the SVG tree (avoids canvas thrash / empty gaps).
 *
 * @param {object} component
 * @param {{ mode: string, value: string, className: string }} color
 * @param {string} stroke
 * @param {'outline'|'filled'} style
 */
function patchIconAppearance(component, color, stroke, style) {
    const sizeClass = String(
        component.getAttributes?.()?.['data-vb-icon-size']
        ?? readIconSize(component)
        ?? 'size-10',
    );
    const classes = [...(component.getClasses?.() ?? [])]
        .filter((token) => ! isIconLayoutNoiseClass(token));

    if (! classes.includes('inline-flex')) {
        classes.push('inline-flex', 'items-center', 'justify-center', 'vb-icon-link');
    } else if (! classes.includes('vb-icon-link')) {
        classes.push('vb-icon-link');
    }

    if (color.mode === 'class' && color.className) {
        classes.push(color.className);
    } else if (color.mode === 'none' && ! classes.some((token) => String(token).startsWith('text-'))) {
        classes.push('text-vp-text-2');
    }

    if (! classes.includes(sizeClass)) {
        classes.push(sizeClass);
    }

    component.setClass(classes);
    applyIconHostBoxSize(component, sizeClass);
    applyHostColorStyle(component, color);

    const svg = findIconSvgChild(component);

    if (! svg) {
        return;
    }

    if (style === 'outline') {
        svg.addAttributes({
            'stroke-width': stroke,
            stroke: 'currentColor',
            fill: 'none',
            width: '100%',
            height: '100%',
        });
    } else {
        svg.addAttributes({
            fill: 'currentColor',
            stroke: 'none',
            width: '100%',
            height: '100%',
        });
    }
}

const DIVIDER_COLORS = [
    { value: 'border-vp-divider', label: 'Default' },
    { value: 'border-vp-text-3', label: 'Muted' },
    { value: 'border-vp-brand-1', label: 'Brand' },
    { value: 'border-slate-300', label: 'Slate' },
    { value: 'border-blue-300', label: 'Blue' },
    { value: 'border-emerald-300', label: 'Green' },
    { value: 'border-rose-300', label: 'Rose' },
    { value: 'border-amber-300', label: 'Amber' },
];

function readIconSize(component) {
    const classes = component.getClasses?.() ?? [];
    const found = ICON_SIZES.find((item) => classes.includes(item.value));

    return found?.value ?? 'size-10';
}

function lockIconGlyphChildren(component) {
    const walk = (node) => {
        node.components?.().forEach((child) => {
            child.set({
                selectable: false,
                hoverable: false,
                highlightable: false,
                layerable: false,
                draggable: false,
                droppable: false,
                editable: false,
                copyable: false,
                removable: false,
            }, { silent: true });
            walk(child);
        });
    };

    walk(component);
}

function keepIconSelection(editor, component) {
    if (! editor || ! component) {
        return;
    }

    const selectHost = () => {
        if (editor.getSelected?.() === component) {
            return;
        }

        editor.select?.(component, { scroll: false });
    };

    selectHost();
    window.requestAnimationFrame(selectHost);
}

/**
 * Apply icon props. Accepts `(component, editor, options)` or legacy `(component, options)`.
 *
 * @param {object} component
 * @param {object|Record<string, unknown>} editorOrOptions
 * @param {Record<string, unknown>} [maybeOptions]
 */
/**
 * Schedule a glyph rebuild once the Tabler catalog finishes loading.
 * Prevents sticky circle fallback when attrs already say e.g. "pulse".
 *
 * @param {object} component
 * @param {object|null} editor
 * @param {Record<string, unknown>} options
 */
function scheduleIconGlyphRefresh(component, editor, options) {
    if (component.__vbIconCatalogRefreshPending || getTablerCatalogSync()) {
        return;
    }

    component.__vbIconCatalogRefreshPending = true;

    ensureTablerCatalog()
        .then(() => {
            component.__vbIconCatalogRefreshPending = false;
            component.__vbIconPainted = false;
            applyIconToComponent(component, editor, {
                ...options,
                forceGlyph: true,
            });
        })
        .catch(() => {
            component.__vbIconCatalogRefreshPending = false;
        });
}

export function applyIconToComponent(component, editorOrOptions, maybeOptions) {
    let editor = null;
    let options = maybeOptions ?? {};

    if (
        editorOrOptions
        && typeof editorOrOptions === 'object'
        && ! editorOrOptions.getSelected
        && (
            editorOrOptions.name !== undefined
            || editorOrOptions.sizeClass !== undefined
            || editorOrOptions.linkType !== undefined
            || editorOrOptions.style !== undefined
            || editorOrOptions.stroke !== undefined
            || editorOrOptions.color !== undefined
            || editorOrOptions.forceGlyph !== undefined
        )
    ) {
        options = editorOrOptions;
        editor = component?.em?.get?.('Editor') ?? null;
    } else {
        editor = editorOrOptions ?? null;
    }

    const { name, sizeClass, href, linkType, linkRef, target, style, stroke, color, forceGlyph } = options;
    const iconName = resolveTablerIconName(name);
    const iconStyle = resolveTablerIconStyle(style);
    const iconStroke = resolveTablerIconStroke(stroke);
    const colorRaw = String(color ?? '').trim();
    const parsedColor = parseIconColor(colorRaw);
    const size = ICON_SIZES.some((item) => item.value === sizeClass) ? sizeClass : 'size-10';
    const type = linkType || 'none';
    const resolvedHref = type === 'none'
        ? null
        : resolveHref(editor, type, linkRef, href);
    const attrs = component.getAttributes?.() ?? {};
    const prevColor = String(attrs['data-vb-icon-color'] ?? '');
    const paintedGlyph = readPaintedIconGlyph(component);
    const paintedStyle = String(
        findIconSvgChild(component)?.getAttributes?.()?.['data-vb-icon-style']
        ?? attrs['data-vb-icon-style']
        ?? DEFAULT_TABLER_ICON_STYLE,
    );
    const hasSvg = Boolean(findIconSvgChild(component));
    // Compare the SVG actually on canvas — attrs alone lie when catalog fell back to circle.
    const sameGlyph = ! forceGlyph
        && hasSvg
        && paintedGlyph === iconName
        && paintedStyle === iconStyle;

    const linkMatches = String(attrs['data-vb-link-type'] ?? 'none') === String(type)
        && String(attrs['data-vb-link'] ?? '') === String(type === 'url' || type === 'none' ? '' : (linkRef || ''))
        && String(attrs.target ?? '') === String(target || '');

    if (
        sameGlyph
        && attrs['data-vb-icon'] === iconName
        && attrs['data-vb-icon-size'] === size
        && String(attrs['data-vb-icon-stroke'] ?? DEFAULT_TABLER_ICON_STROKE) === iconStroke
        && prevColor === colorRaw
        && linkMatches
    ) {
        // Always re-paint color/size — canvas CSS rebuilds and cold loads often
        // leave data-vb-icon-color set while the live color falls back to gray.
        const elReady = Boolean(
            component.getEl?.()
            ?? component.getView?.()?.el
            ?? component.view?.el,
        );

        runWithSettingsChangeGuard(editor, () => {
            patchIconAppearance(component, parsedColor, iconStroke, iconStyle);
            lockIconGlyphChildren(component);
            component.__vbIconSynced = true;
            component.__vbIconPainted = elReady;
        });

        return;
    }

    const appearanceOnly = sameGlyph
        && attrs['data-vb-icon-size'] === size
        && linkMatches;

    runWithSettingsChangeGuard(editor, () => {
        if (appearanceOnly) {
            component.addAttributes({
                'data-vb-icon': iconName,
                'data-vb-icon-style': iconStyle,
                'data-vb-icon-stroke': iconStroke,
                'data-vb-icon-color': colorRaw || null,
            });
            patchIconAppearance(component, parsedColor, iconStroke, iconStyle);
            component.__vbIconSynced = true;
            component.__vbIconPainted = true;

            return;
        }

        const classes = [...(component.getClasses?.() ?? [])]
            .filter((token) => ! isIconLayoutNoiseClass(token));

        if (! classes.includes('inline-flex')) {
            classes.push('inline-flex', 'items-center', 'justify-center', 'vb-icon-link');
        } else if (! classes.includes('vb-icon-link')) {
            classes.push('vb-icon-link');
        }

        if (parsedColor.mode === 'class' && parsedColor.className) {
            classes.push(parsedColor.className);
        } else if (parsedColor.mode === 'none' && ! classes.some((token) => String(token).startsWith('text-'))) {
            classes.push('text-vp-text-2');
        }

        classes.push(size);

        const needsGlyphRebuild = ! sameGlyph
            || String(attrs['data-vb-icon-stroke'] ?? DEFAULT_TABLER_ICON_STROKE) !== iconStroke
            || Boolean(forceGlyph);

        component.setClass(classes);
        applyIconHostBoxSize(component, size);
        applyHostColorStyle(component, parsedColor);
        component.set({
            tagName: type === 'none' ? 'span' : 'a',
            linkType: type,
            linkRef: type === 'url' || type === 'none' ? '' : (linkRef || ''),
            href: resolvedHref || '#',
            target: target || '',
        });
        component.addAttributes({
            'data-voodbuilder-icon': '',
            'data-vb-icon': iconName,
            'data-vb-icon-size': size,
            'data-vb-icon-style': iconStyle,
            'data-vb-icon-stroke': iconStroke,
            'data-vb-icon-color': colorRaw || null,
            'data-vb-link-type': type,
            'data-vb-link': type === 'url' || type === 'none' ? null : (linkRef || null),
            href: type === 'none' ? null : (resolvedHref || '#'),
            target: type === 'none' || ! target ? null : target,
            rel: type !== 'none' && target === '_blank' ? 'noopener noreferrer' : null,
        });

        if (needsGlyphRebuild) {
            replaceIconGlyph(component, tablerIconSvg(iconName, {
                style: iconStyle,
                stroke: iconStroke,
                sizeClass: 'w-full h-full',
                color: null,
            }));
            lockIconGlyphChildren(component);
            // Re-apply color/size after glyph swap (setClass/style can be wiped by re-render).
            patchIconAppearance(component, parsedColor, iconStroke, iconStyle);

            const paintedAfter = readPaintedIconGlyph(component);

            if (paintedAfter !== iconName) {
                scheduleIconGlyphRefresh(component, editor, {
                    name: iconName,
                    sizeClass: size,
                    style: iconStyle,
                    stroke: iconStroke,
                    color: colorRaw,
                    href,
                    linkType: type,
                    linkRef,
                    target,
                });
            }
        } else {
            patchIconAppearance(component, parsedColor, iconStroke, iconStyle);
        }

        component.__vbIconSynced = true;
        component.__vbIconPainted = true;
        keepIconSelection(editor, component);
    });
}

function resolveHref(editor, linkType, linkRef, href) {
    const targets = editor?.__voodbuilderLinkTargets ?? { pages: [], menuItems: [] };

    if (linkType === 'page') {
        return (targets.pages ?? []).find((item) => String(item.id) === String(linkRef))?.url || '#';
    }

    if (linkType === 'menu') {
        return (targets.menuItems ?? []).find((item) => String(item.id) === String(linkRef))?.url || '#';
    }

    return String(href ?? '#').trim() || '#';
}

function createSegmentedControl({ label, name, value, options, onChange }) {
    const field = document.createElement('div');
    field.className = 'voodbuilder-editor-form-field';

    const labelEl = document.createElement('div');
    labelEl.className = 'voodbuilder-editor-form-label';
    labelEl.textContent = label;

    const row = document.createElement('div');
    row.className = 'voodbuilder-editor-segmented';
    row.setAttribute('role', 'radiogroup');
    row.setAttribute('aria-label', label);

    const buttons = [];

    for (const option of options) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'voodbuilder-editor-segmented__btn';
        btn.textContent = option.label;
        btn.dataset.value = option.value;
        btn.setAttribute('role', 'radio');
        btn.setAttribute('aria-checked', option.value === value ? 'true' : 'false');

        if (option.value === value) {
            btn.classList.add('is-active');
        }

        btn.addEventListener('click', () => {
            buttons.forEach((node) => {
                const active = node.dataset.value === option.value;
                node.classList.toggle('is-active', active);
                node.setAttribute('aria-checked', active ? 'true' : 'false');
            });
            onChange?.(option.value);
        });
        buttons.push(btn);
        row.appendChild(btn);
    }

    field.append(labelEl, row);

    return {
        field,
        setValue: (next) => {
            buttons.forEach((node) => {
                const active = node.dataset.value === next;
                node.classList.toggle('is-active', active);
                node.setAttribute('aria-checked', active ? 'true' : 'false');
            });
        },
    };
}

function createIconPicker({
    value,
    categoryId = 'all',
    style = DEFAULT_TABLER_ICON_STYLE,
    stroke = DEFAULT_TABLER_ICON_STROKE,
    color = '',
    labels = {},
    onChange,
}) {
    const wrap = document.createElement('div');
    wrap.className = 'voodbuilder-editor-field voodbuilder-editor-icon-picker';
    wrap.setAttribute('data-voodbuilder-icon-picker', '');

    const label = document.createElement('div');
    label.className = 'voodbuilder-editor-form-label';
    label.textContent = labels.iconName ?? 'Icon';

    let activeCategory = categoryId || 'all';
    let browseStyle = style === 'filled' ? 'filled' : 'all';
    let query = '';
    let current = resolveTablerIconName(value);
    let iconStyle = resolveTablerIconStyle(style);
    let iconStroke = resolveTablerIconStroke(stroke);
    let iconColor = String(color ?? '').trim();
    let offset = 0;
    let total = 0;
    let catalogReady = false;

    const status = document.createElement('p');
    status.className = 'voodbuilder-editor-icon-picker__status';
    status.textContent = labels.iconCatalogLoading ?? 'Loading Tabler icons…';

    const styleControl = createSegmentedControl({
        label: labels.iconStyle ?? 'Style',
        name: 'iconStyle',
        value: iconStyle,
        options: [
            { value: 'outline', label: labels.iconStyleOutline ?? 'Outline' },
            { value: 'filled', label: labels.iconStyleFilled ?? 'Filled' },
        ],
        onChange: (next) => {
            iconStyle = resolveTablerIconStyle(next);
            browseStyle = iconStyle === 'filled' ? 'filled' : 'all';
            offset = 0;
            emitChange();
            renderGrid();
        },
    });

    const strokeField = createSelectField({
        label: labels.iconStroke ?? 'Stroke',
        name: 'iconStroke',
        value: iconStroke,
        options: ICON_STROKES,
        onChange: (next) => {
            iconStroke = resolveTablerIconStroke(next);
            emitChange();
            renderGrid();
        },
    });

    const { field: colorField, input: colorText } = createTextField({
        label: labels.iconColor ?? 'Color',
        name: 'iconColor',
        value: iconColor,
        placeholder: labels.iconColorPlaceholder ?? '#hex · rgb() · text-red-500 · text-vp-brand-1',
    });

    const colorSwatch = document.createElement('input');
    colorSwatch.type = 'color';
    colorSwatch.className = 'voodbuilder-editor-icon-picker__swatch';
    colorSwatch.value = /^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(iconColor) ? iconColor : '#64748b';
    // The text field next to it owns the visible label, so the swatch needs its own name.
    colorSwatch.title = labels.iconColor ?? 'Color';
    colorSwatch.setAttribute('aria-label', labels.iconColorPicker ?? labels.iconColor ?? 'Color');
    colorText.insertAdjacentElement('afterend', colorSwatch);
    colorField.classList.add('voodbuilder-editor-icon-picker__color');

    const categoryField = createSelectField({
        label: labels.iconCategory ?? 'Category',
        name: 'iconCategory',
        value: activeCategory,
        options: [
            { value: 'all', label: labels.iconCategoryAll ?? 'All' },
            ...TABLER_CATEGORY_LABELS.map((category) => ({
                value: category,
                label: category,
            })),
        ],
        onChange: (next) => {
            activeCategory = next;
            offset = 0;
            renderGrid();
        },
    });

    const { field: searchField, input: searchInput } = createTextField({
        label: labels.iconSearch ?? 'Search',
        name: 'iconSearch',
        value: '',
        placeholder: labels.iconSearchPlaceholder ?? 'Type to search…',
    });

    const grid = document.createElement('div');
    grid.className = 'voodbuilder-editor-icon-picker__grid';
    grid.setAttribute('role', 'listbox');
    grid.setAttribute('aria-label', labels.iconName ?? 'Icon');

    const moreBtn = document.createElement('button');
    moreBtn.type = 'button';
    moreBtn.className = 'voodbuilder-editor-form-action voodbuilder-editor-icon-picker__more';
    moreBtn.hidden = true;
    moreBtn.addEventListener('click', () => {
        offset += ICON_PICKER_PAGE;
        renderGrid({ append: true });
    });

    const emitChange = () => {
        onChange?.({
            name: current,
            style: iconStyle,
            stroke: iconStroke,
            color: iconColor,
            category: activeCategory,
        });
    };

    const syncCategoryOptions = () => {
        const select = categoryField.querySelector('select');

        if (! select) {
            return;
        }

        const cats = listTablerIconCategories();
        const previous = select.value || activeCategory;
        select.replaceChildren();

        for (const cat of cats) {
            const option = document.createElement('option');
            option.value = cat.id;
            option.textContent = cat.id === 'all'
                ? (labels.iconCategoryAll ?? cat.label)
                : cat.label;
            option.selected = cat.id === previous;
            select.appendChild(option);
        }

        activeCategory = previous;

        const wrap = select.closest('.voodbuilder-editor-select-wrap');
        const list = wrap?.querySelector('.voodbuilder-editor-select-list');

        // Never mark the select as enhanced here — that races enhanceInspectorSelects
        // and leaves a hidden native select with no custom trigger (only "All" visible).
        if (list) {
            list.replaceChildren();

            for (const option of select.options) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'voodbuilder-editor-select-option';
                item.setAttribute('role', 'option');
                item.dataset.value = option.value;
                item.textContent = option.textContent?.trim() || option.value || '-';

                if (option.value === select.value) {
                    item.classList.add('is-selected');
                    item.setAttribute('aria-selected', 'true');
                }

                item.addEventListener('click', () => {
                    select.value = option.value;
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    wrap?.classList.remove('is-open');
                    list.hidden = true;
                });
                list.appendChild(item);
            }

            const triggerLabel = wrap.querySelector('.voodbuilder-editor-select-trigger-label');

            if (triggerLabel) {
                triggerLabel.textContent = select.options[select.selectedIndex]?.textContent?.trim()
                    || (labels.iconCategoryAll ?? 'All');
            }
        }
    };

    const renderGrid = ({ append = false } = {}) => {
        if (! catalogReady) {
            return;
        }

        const { names, total: matchTotal } = queryTablerIcons({
            category: activeCategory,
            query,
            style: browseStyle === 'filled' ? 'filled' : 'all',
            limit: ICON_PICKER_PAGE,
            offset: append ? offset : 0,
        });

        if (! append) {
            offset = 0;
            grid.replaceChildren();
        }

        total = matchTotal;
        status.textContent = labels.iconCatalogCount
            ? String(labels.iconCatalogCount).replace(':count', String(total))
            : `${total} icons`;

        for (const name of names) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'voodbuilder-editor-icon-picker__item';
            btn.setAttribute('role', 'option');
            btn.setAttribute('aria-selected', name === current ? 'true' : 'false');
            btn.title = name;
            btn.dataset.icon = name;

            if (name === current) {
                btn.classList.add('is-active');
            }

            // Preview icons stay theme-colored — never recolor the whole grid on each color edit.
            btn.innerHTML = tablerIconSvg(name, {
                style: iconStyle,
                stroke: iconStroke,
                sizeClass: 'size-6',
                color: null,
            });
            btn.addEventListener('click', () => {
                current = name;
                activeCategory = findCategoryForIcon(current) || activeCategory;
                const select = categoryField.querySelector('select');

                if (select && activeCategory !== 'all') {
                    select.value = activeCategory;
                }

                grid.querySelectorAll('.voodbuilder-editor-icon-picker__item').forEach((el) => {
                    const active = el.dataset.icon === current;
                    el.classList.toggle('is-active', active);
                    el.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                emitChange();
            });
            grid.appendChild(btn);
        }

        if (! append && names.length === 0) {
            const empty = document.createElement('p');
            empty.className = 'voodbuilder-editor-icon-picker__empty';
            empty.textContent = labels.iconSearchEmpty ?? 'No icons match.';
            grid.appendChild(empty);
        }

        const shown = grid.querySelectorAll('.voodbuilder-editor-icon-picker__item').length;
        moreBtn.hidden = shown >= total;
        moreBtn.textContent = labels.iconLoadMore
            ? String(labels.iconLoadMore).replace(':remaining', String(Math.max(0, total - shown)))
            : `Load more (${Math.max(0, total - shown)})`;
    };

    let colorCommitTimer = 0;

    const commitColor = (next, { rebuildGrid = false } = {}) => {
        iconColor = String(next ?? '').trim();
        colorText.value = iconColor;

        if (/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(iconColor)) {
            colorSwatch.value = iconColor;
        }

        emitChange();

        if (rebuildGrid) {
            renderGrid();
        }
    };

    colorText.addEventListener('change', () => commitColor(colorText.value));
    colorText.addEventListener('blur', () => commitColor(colorText.value));
    colorText.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            commitColor(colorText.value);
        }
    });
    // Swatch: debounce — continuous input was rebuilding the canvas on every pointer move.
    colorSwatch.addEventListener('input', () => {
        colorText.value = colorSwatch.value;
        window.clearTimeout(colorCommitTimer);
        colorCommitTimer = window.setTimeout(() => {
            commitColor(colorSwatch.value);
        }, 120);
    });
    colorSwatch.addEventListener('change', () => {
        window.clearTimeout(colorCommitTimer);
        commitColor(colorSwatch.value);
    });

    searchInput.addEventListener('input', () => {
        query = String(searchInput.value || '');
        offset = 0;
        renderGrid();
    });

    wrap.append(
        label,
        styleControl.field,
        strokeField,
        colorField,
        categoryField,
        searchField,
        status,
        grid,
        moreBtn,
    );

    ensureTablerCatalog()
        .then(() => {
            catalogReady = true;
            activeCategory = findCategoryForIcon(current) || activeCategory;
            syncCategoryOptions();
            renderGrid();
        })
        .catch(() => {
            status.textContent = labels.iconCatalogError ?? 'Could not load Tabler icons.';
        });

    return {
        field: wrap,
        getState: () => ({
            name: current,
            style: iconStyle,
            stroke: iconStroke,
            color: iconColor,
            category: activeCategory,
        }),
        refreshCategories: syncCategoryOptions,
    };
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderIconSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component) {
        return false;
    }

    const host = findIconHost(component) ?? component;

    if (! isIconComponent(host)) {
        return false;
    }

    const key = componentKey(host);
    const existing = mount.querySelector('[data-voodbuilder-icon-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    const targets = editor.__voodbuilderLinkTargets ?? { pages: [], menuItems: [] };
    const attrs = host.getAttributes?.() ?? {};
    let iconName = resolveTablerIconName(attrs['data-vb-icon'] ?? DEFAULT_TABLER_ICON);
    let sizeClass = attrs['data-vb-icon-size'] || readIconSize(host);
    let iconStyle = resolveTablerIconStyle(attrs['data-vb-icon-style'] ?? DEFAULT_TABLER_ICON_STYLE);
    let iconStroke = resolveTablerIconStroke(attrs['data-vb-icon-stroke'] ?? DEFAULT_TABLER_ICON_STROKE);
    let iconColor = readIconColor(host);
    let linkType = String(host.get('linkType') ?? attrs['data-vb-link-type'] ?? 'none') || 'none';
    let linkRef = String(host.get('linkRef') ?? attrs['data-vb-link'] ?? '');
    let href = String(host.get('href') ?? attrs.href ?? '');
    let target = String(host.get('target') ?? attrs.target ?? '');

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const { section, fields } = createFormSection(labels.iconSettingsTitle ?? 'Icon settings');
    section.setAttribute('data-voodbuilder-icon-settings', '');
    section.setAttribute('data-component-key', key);

    const picker = createIconPicker({
        value: iconName,
        categoryId: 'all',
        style: iconStyle,
        stroke: iconStroke,
        color: iconColor,
        labels,
        onChange: (state) => {
            iconName = state.name;
            iconStyle = state.style;
            iconStroke = state.stroke;
            iconColor = state.color;
            commit();
        },
    });

    const sizeField = createSelectField({
        label: labels.iconSize ?? 'Size',
        name: 'iconSize',
        value: sizeClass,
        options: ICON_SIZES,
        onChange: (value) => {
            sizeClass = value;
            commit();
        },
    });

    const typeField = createSelectField({
        label: labels.iconLinkType ?? 'Link',
        name: 'iconLinkType',
        value: linkType,
        options: [
            { value: 'none', label: labels.iconLinkNone ?? 'No link' },
            { value: 'url', label: labels.buttonLinkTypeUrl ?? 'URL' },
            { value: 'page', label: labels.buttonLinkTypePage ?? 'Site page' },
            { value: 'menu', label: labels.buttonLinkTypeMenu ?? 'Menu item' },
        ],
        onChange: (value) => {
            linkType = value;
            syncVisibility();
            commit();
        },
    });

    const { field: urlField, input: urlInput } = createTextField({
        label: labels.buttonLinkUrl ?? 'Link URL',
        name: 'iconHref',
        value: href === '#' ? '' : href,
        placeholder: labels.buttonLinkUrlPlaceholder ?? 'https:// or /page',
    });

    const pageField = createSelectField({
        label: labels.buttonLinkPage ?? 'Page',
        name: 'iconLinkRefPage',
        value: linkRef,
        options: [
            { value: '', label: '—' },
            ...(targets.pages ?? []).map((item) => ({
                value: String(item.id),
                label: item.label,
            })),
        ],
        onChange: (value) => {
            linkRef = value;
            commit();
        },
    });

    const menuField = createSelectField({
        label: labels.buttonLinkMenu ?? 'Menu item',
        name: 'iconLinkRefMenu',
        value: linkRef,
        options: [
            { value: '', label: '—' },
            ...(targets.menuItems ?? []).map((item) => ({
                value: String(item.id),
                label: item.label,
            })),
        ],
        onChange: (value) => {
            linkRef = value;
            commit();
        },
    });

    const targetField = createSelectField({
        label: labels.buttonLinkTarget ?? 'Open in',
        name: 'iconLinkTarget',
        value: target,
        options: [
            { value: '', label: labels.buttonLinkSameTab ?? 'Same tab' },
            { value: '_blank', label: labels.buttonLinkNewTab ?? 'New tab' },
        ],
        onChange: (value) => {
            target = value;
            commit();
        },
    });

    const strokeField = picker.field.querySelector('[name="iconStroke"]')?.closest('.voodbuilder-editor-form-field');

    const syncVisibility = () => {
        const linked = linkType !== 'none';
        urlField.hidden = linkType !== 'url';
        pageField.hidden = linkType !== 'page';
        menuField.hidden = linkType !== 'menu';
        targetField.hidden = ! linked;

        if (strokeField) {
            strokeField.hidden = iconStyle === 'filled';
        }
    };

    const commit = () => {
        href = String(urlInput.value || '#').trim() || '#';
        linkRef = linkType === 'page'
            ? String(pageField.querySelector('select')?.value || '')
            : linkType === 'menu'
                ? String(menuField.querySelector('select')?.value || '')
                : '';
        target = linkType === 'none'
            ? ''
            : String(targetField.querySelector('select')?.value || '');

        const pickerState = picker.getState?.() ?? {};
        iconName = pickerState.name ?? iconName;
        iconStyle = pickerState.style ?? iconStyle;
        iconStroke = pickerState.stroke ?? iconStroke;
        iconColor = pickerState.color ?? iconColor;

        syncVisibility();

        // Do NOT clear __vbIconSynced here — that forced a full SVG rebuild on every
        // color/size tweak and caused canvas thrash + empty page gaps.
        applyIconToComponent(host, editor, {
            name: iconName,
            sizeClass,
            style: iconStyle,
            stroke: iconStroke,
            color: iconColor,
            href,
            linkType,
            linkRef,
            target,
        });
    };

    urlInput.addEventListener('change', commit);
    urlInput.addEventListener('blur', commit);

    fields.append(
        picker.field,
        sizeField,
        typeField,
        urlField,
        pageField,
        menuField,
        targetField,
    );
    mount.appendChild(section);
    syncVisibility();
    editor.__voodbuilderEnhanceInspectorSelects?.(mount);
    // Refresh category options after custom selects exist (avoid race with enhance).
    picker.refreshCategories?.();
    keepIconSelection(editor, host);

    // Re-paint immediately on open — cold loads leave data-vb-icon-color without live color.
    applyIconToComponent(host, editor, {
        name: iconName,
        sizeClass,
        style: iconStyle,
        stroke: iconStroke,
        color: iconColor,
        href,
        linkType,
        linkRef,
        target,
    });

    // Prefetch catalog so apply uses full paths after first open.
    ensureTablerCatalog().then(() => {
        picker.refreshCategories?.();

        if (editor.getSelected?.() === host || findIconHost(editor.getSelected?.()) === host) {
            applyIconToComponent(host, editor, {
                name: iconName,
                sizeClass,
                style: iconStyle,
                stroke: iconStroke,
                color: iconColor,
                forceGlyph: true,
                href,
                linkType,
                linkRef,
                target,
            });
        }
    }).catch(() => {});

    return true;
}

function readTextLinkLabel(component) {
    return String(component.get('content') ?? '')
        || [...(component.components?.() ?? [])].map((child) => String(child.get?.('content') ?? '')).join('')
        || 'Text link';
}

function applyTextLinkToComponent(component, editor, { label, linkType, linkRef, href, target }) {
    const resolvedHref = resolveHref(editor, linkType, linkRef, href);
    const nextLabel = String(label ?? '').trim() || 'Text link';

    runWithSettingsChangeGuard(editor, () => {
        component.set({
            href: resolvedHref,
            target: target || '',
            'data-vb-link-type': linkType,
            linkType,
            linkRef: linkType === 'url' ? '' : linkRef,
        });

        component.addAttributes({
            href: resolvedHref,
            target: target || null,
            rel: target === '_blank' ? 'noopener noreferrer' : null,
            'data-vb-link-type': linkType,
            'data-vb-link': linkType === 'url' ? null : (linkRef || null),
        });

        const children = [...(component.components?.() ?? [])];
        const textNode = children.find((child) => child?.get?.('type') === 'textnode');

        if (textNode) {
            textNode.set('content', nextLabel);
        } else {
            component.components(nextLabel);
        }
    });
}

/**
 * Text link Content settings — same link controls as Button (type / URL / page / menu / target).
 *
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderTextLinkSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component) {
        return false;
    }

    const key = componentKey(component);
    const existing = mount.querySelector('[data-voodbuilder-text-link-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    const targets = editor.__voodbuilderLinkTargets ?? { pages: [], menuItems: [] };
    const attrs = component.getAttributes?.() ?? {};
    let linkType = String(component.get('linkType') ?? attrs['data-vb-link-type'] ?? 'url') || 'url';
    let linkRef = String(component.get('linkRef') ?? attrs['data-vb-link'] ?? '');
    let href = String(component.get('href') ?? attrs.href ?? '#');
    let target = String(component.get('target') ?? attrs.target ?? '');
    let label = readTextLinkLabel(component);

    if (linkType === 'none') {
        linkType = 'url';
    }

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const { section, fields } = createFormSection(labels.textLinkSettingsTitle ?? 'Text link');
    section.setAttribute('data-voodbuilder-text-link-settings', '');
    section.setAttribute('data-component-key', key);

    const { field: labelField, input: labelInput } = createTextField({
        label: labels.textLinkLabel ?? 'Label',
        name: 'textLinkLabel',
        value: label,
    });

    const typeField = createSelectField({
        label: labels.buttonLinkType ?? 'Link type',
        name: 'textLinkType',
        value: linkType,
        options: [
            { value: 'url', label: labels.buttonLinkTypeUrl ?? 'URL' },
            { value: 'page', label: labels.buttonLinkTypePage ?? 'Site page' },
            { value: 'menu', label: labels.buttonLinkTypeMenu ?? 'Menu item' },
        ],
        onChange: (value) => {
            linkType = value;
            syncVisibility();
            commit();
        },
    });

    const { field: urlField, input: urlInput } = createTextField({
        label: labels.buttonLinkUrl ?? 'Link URL',
        name: 'textLinkHref',
        value: href === '#' ? '' : href,
        placeholder: labels.buttonLinkUrlPlaceholder ?? 'https:// or /page',
    });

    const pageField = createSelectField({
        label: labels.buttonLinkPage ?? 'Page',
        name: 'textLinkRefPage',
        value: linkRef,
        options: [
            { value: '', label: '—' },
            ...(targets.pages ?? []).map((item) => ({
                value: String(item.id),
                label: item.label,
            })),
        ],
        onChange: (value) => {
            linkRef = value;
            commit();
        },
    });

    const menuField = createSelectField({
        label: labels.buttonLinkMenu ?? 'Menu item',
        name: 'textLinkRefMenu',
        value: linkRef,
        options: [
            { value: '', label: '—' },
            ...(targets.menuItems ?? []).map((item) => ({
                value: String(item.id),
                label: item.label,
            })),
        ],
        onChange: (value) => {
            linkRef = value;
            commit();
        },
    });

    const targetField = createSelectField({
        label: labels.buttonLinkTarget ?? 'Open in',
        name: 'textLinkTarget',
        value: target,
        options: [
            { value: '', label: labels.buttonLinkSameTab ?? 'Same tab' },
            { value: '_blank', label: labels.buttonLinkNewTab ?? 'New tab' },
        ],
        onChange: (value) => {
            target = value;
            commit();
        },
    });

    fields.append(labelField, typeField, urlField, pageField, menuField, targetField);
    mount.appendChild(section);

    const syncVisibility = () => {
        urlField.hidden = linkType !== 'url';
        pageField.hidden = linkType !== 'page';
        menuField.hidden = linkType !== 'menu';
    };

    const commit = () => {
        label = String(labelInput.value || 'Text link').trim() || 'Text link';
        href = String(urlInput.value || '#').trim() || '#';
        linkRef = linkType === 'page'
            ? String(pageField.querySelector('select')?.value || '')
            : linkType === 'menu'
                ? String(menuField.querySelector('select')?.value || '')
                : '';
        target = String(targetField.querySelector('select')?.value || '');

        applyTextLinkToComponent(component, editor, {
            label,
            linkType,
            linkRef,
            href,
            target,
        });
    };

    labelInput.addEventListener('change', commit);
    labelInput.addEventListener('blur', commit);
    urlInput.addEventListener('change', commit);
    urlInput.addEventListener('blur', commit);

    syncVisibility();
    editor.__voodbuilderEnhanceInspectorSelects?.(mount);

    return true;
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderDividerSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component) {
        return false;
    }

    const key = componentKey(component);
    const existing = mount.querySelector('[data-voodbuilder-divider-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    const classes = component.getClasses?.() ?? [];
    let color = DIVIDER_COLORS.find((item) => classes.includes(item.value))?.value ?? 'border-vp-divider';

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const { section, fields } = createFormSection(labels.dividerSettingsTitle ?? 'Divider');
    section.setAttribute('data-voodbuilder-divider-settings', '');
    section.setAttribute('data-component-key', key);

    const hint = document.createElement('p');
    hint.className = 'voodbuilder-editor-form-hint';
    hint.textContent = labels.dividerColorHint
        ?? 'Use border-* classes (not divide-*). Divide utilities style gaps between children, not the line itself.';

    const colorField = createSelectField({
        label: labels.dividerColor ?? 'Color',
        name: 'dividerColor',
        value: color,
        options: DIVIDER_COLORS,
        onChange: (value) => {
            color = value;
            runWithSettingsChangeGuard(editor, () => {
                const next = [...(component.getClasses?.() ?? [])]
                    .filter((token) => ! String(token).startsWith('border-') || token === 'border-0' || token === 'border-t');
                const base = ['vb-divider', 'my-6', 'w-full', 'border-0', 'border-t'];

                for (const token of base) {
                    if (! next.includes(token)) {
                        next.push(token);
                    }
                }

                next.push(color);
                component.setClass(next);
                component.addAttributes({ 'data-voodbuilder-divider': '', 'data-vb-divider-color': color });
            });
        },
    });

    fields.append(hint, colorField);
    mount.appendChild(section);
    editor.__voodbuilderEnhanceInspectorSelects?.(mount);

    return true;
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderReadingProgressSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component) {
        return false;
    }

    const key = componentKey(component);
    const existing = mount.querySelector('[data-voodbuilder-reading-progress-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    const attrs = component.getAttributes?.() ?? {};
    let color = String(attrs['data-vb-progress-color'] || 'brand');
    let thickness = String(attrs['data-vb-progress-thickness'] || '4');

    if (! READING_PROGRESS_COLORS.some((item) => item.value === color)) {
        color = 'brand';
    }

    if (! READING_PROGRESS_THICKNESS.some((item) => item.value === thickness)) {
        thickness = '4';
    }

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const { section, fields } = createFormSection(labels.readingProgressSettingsTitle ?? 'Reading progress');
    section.setAttribute('data-voodbuilder-reading-progress-settings', '');
    section.setAttribute('data-component-key', key);

    const hint = document.createElement('p');
    hint.className = 'voodbuilder-editor-form-hint';
    hint.textContent = labels.readingProgressSettingsHint
        ?? 'Fixed under the sticky nav. Color defaults to Brand; pick any Tailwind shade (e.g. red-500). Thickness is the bar height in pixels.';

    const colorField = createSelectField({
        label: labels.readingProgressColor ?? 'Color',
        name: 'progressColor',
        value: color,
        options: READING_PROGRESS_COLORS,
        onChange: (value) => {
            color = value;
            runWithSettingsChangeGuard(editor, () => {
                component.addAttributes({ 'data-vb-progress-color': color });
                applyReadingProgressAppearance(component);
            });
        },
    });

    const thicknessField = createSelectField({
        label: labels.readingProgressThickness ?? 'Thickness',
        name: 'progressThickness',
        value: thickness,
        options: READING_PROGRESS_THICKNESS,
        onChange: (value) => {
            thickness = value;
            runWithSettingsChangeGuard(editor, () => {
                component.addAttributes({ 'data-vb-progress-thickness': thickness });
                applyReadingProgressAppearance(component);
            });
        },
    });

    fields.append(hint, colorField, thicknessField);
    mount.appendChild(section);
    editor.__voodbuilderEnhanceInspectorSelects?.(mount);
    applyReadingProgressAppearance(component);

    return true;
}

export { ICON_SIZES };
