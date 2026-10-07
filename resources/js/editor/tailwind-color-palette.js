/**
 * Tailwind default color families for Style panel selects (editor UI).
 * Hex values power select swatches only — published CSS comes from page JIT / theme.
 */

export const TW_COLOR_FAMILIES = [
    "slate",
    "gray",
    "zinc",
    "neutral",
    "stone",
    "red",
    "orange",
    "amber",
    "yellow",
    "lime",
    "green",
    "emerald",
    "teal",
    "cyan",
    "sky",
    "blue",
    "indigo",
    "violet",
    "purple",
    "fuchsia",
    "pink",
    "rose"
];

export const TW_COLOR_SHADES = [
    "50",
    "100",
    "200",
    "300",
    "400",
    "500",
    "600",
    "700",
    "800",
    "900",
    "950"
];

/** Approximate Tailwind v4 default palette for editor swatches (not a CSS source of truth). */
export const TW_COLOR_HEX = {
    slate: {50:'#f8fafc',100:'#f1f5f9',200:'#e2e8f0',300:'#cbd5e1',400:'#94a3b8',500:'#64748b',600:'#475569',700:'#334155',800:'#1e293b',900:'#0f172a',950:'#020617'},
    gray: {50:'#f9fafb',100:'#f3f4f6',200:'#e5e7eb',300:'#d1d5db',400:'#9ca3af',500:'#6b7280',600:'#4b5563',700:'#374151',800:'#1f2937',900:'#111827',950:'#030712'},
    zinc: {50:'#fafafa',100:'#f4f4f5',200:'#e4e4e7',300:'#d4d4d8',400:'#a1a1aa',500:'#71717a',600:'#52525b',700:'#3f3f46',800:'#27272a',900:'#18181b',950:'#09090b'},
    neutral: {50:'#fafafa',100:'#f5f5f5',200:'#e5e5e5',300:'#d4d4d4',400:'#a3a3a3',500:'#737373',600:'#525252',700:'#404040',800:'#262626',900:'#171717',950:'#0a0a0a'},
    stone: {50:'#fafaf9',100:'#f5f5f4',200:'#e7e5e4',300:'#d6d3d1',400:'#a8a29e',500:'#78716c',600:'#57534e',700:'#44403c',800:'#292524',900:'#1c1917',950:'#0c0a09'},
    red: {50:'#fef2f2',100:'#fee2e2',200:'#fecaca',300:'#fca5a5',400:'#f87171',500:'#ef4444',600:'#dc2626',700:'#b91c1c',800:'#991b1b',900:'#7f1d1d',950:'#450a0a'},
    orange: {50:'#fff7ed',100:'#ffedd5',200:'#fed7aa',300:'#fdba74',400:'#fb923c',500:'#f97316',600:'#ea580c',700:'#c2410c',800:'#9a3412',900:'#7c2d12',950:'#431407'},
    amber: {50:'#fffbeb',100:'#fef3c7',200:'#fde68a',300:'#fcd34d',400:'#fbbf24',500:'#f59e0b',600:'#d97706',700:'#b45309',800:'#92400e',900:'#78350f',950:'#451a03'},
    yellow: {50:'#fefce8',100:'#fef9c3',200:'#fef08a',300:'#fde047',400:'#facc15',500:'#eab308',600:'#ca8a04',700:'#a16207',800:'#854d0e',900:'#713f12',950:'#422006'},
    lime: {50:'#f7fee7',100:'#ecfccb',200:'#d9f99d',300:'#bef264',400:'#a3e635',500:'#84cc16',600:'#65a30d',700:'#4d7c0f',800:'#3f6212',900:'#365314',950:'#1a2e05'},
    green: {50:'#f0fdf4',100:'#dcfce7',200:'#bbf7d0',300:'#86efac',400:'#4ade80',500:'#22c55e',600:'#16a34a',700:'#15803d',800:'#166534',900:'#14532d',950:'#052e16'},
    emerald: {50:'#ecfdf5',100:'#d1fae5',200:'#a7f3d0',300:'#6ee7b7',400:'#34d399',500:'#10b981',600:'#059669',700:'#047857',800:'#065f46',900:'#064e3b',950:'#022c22'},
    teal: {50:'#f0fdfa',100:'#ccfbf1',200:'#99f6e4',300:'#5eead4',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',700:'#0f766e',800:'#115e59',900:'#134e4a',950:'#042f2e'},
    cyan: {50:'#ecfeff',100:'#cffafe',200:'#a5f3fc',300:'#67e8f9',400:'#22d3ee',500:'#06b6d4',600:'#0891b2',700:'#0e7490',800:'#155e75',900:'#164e63',950:'#083344'},
    sky: {50:'#f0f9ff',100:'#e0f2fe',200:'#bae6fd',300:'#7dd3fc',400:'#38bdf8',500:'#0ea5e9',600:'#0284c7',700:'#0369a1',800:'#075985',900:'#0c4a6e',950:'#082f49'},
    blue: {50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a',950:'#172554'},
    indigo: {50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81',950:'#1e1b4b'},
    violet: {50:'#f5f3ff',100:'#ede9fe',200:'#ddd6fe',300:'#c4b5fd',400:'#a78bfa',500:'#8b5cf6',600:'#7c3aed',700:'#6d28d9',800:'#5b21b6',900:'#4c1d95',950:'#2e1065'},
    purple: {50:'#faf5ff',100:'#f3e8ff',200:'#e9d5ff',300:'#d8b4fe',400:'#c084fc',500:'#a855f7',600:'#9333ea',700:'#7e22ce',800:'#6b21a8',900:'#581c87',950:'#3b0764'},
    fuchsia: {50:'#fdf4ff',100:'#fae8ff',200:'#f5d0fe',300:'#f0abfc',400:'#e879f9',500:'#d946ef',600:'#c026d3',700:'#a21caf',800:'#86198f',900:'#701a75',950:'#4a044e'},
    pink: {50:'#fdf2f8',100:'#fce7f3',200:'#fbcfe8',300:'#f9a8d4',400:'#f472b6',500:'#ec4899',600:'#db2777',700:'#be185d',800:'#9d174d',900:'#831843',950:'#500724'},
    rose: {50:'#fff1f2',100:'#ffe4e6',200:'#fecdd3',300:'#fda4af',400:'#fb7185',500:'#f43f5e',600:'#e11d48',700:'#be123c',800:'#9f1239',900:'#881337',950:'#4c0519'},
};

/**
 * @param {string} prefix e.g. bg | text | border | from | via | to
 * @returns {Array<{value: string, label: string, hex?: string}>}
 */
export function tailwindColorOptions(prefix) {
    const options = [];

    for (const family of TW_COLOR_FAMILIES) {
        for (const shade of TW_COLOR_SHADES) {
            options.push({
                value: `${prefix}-${family}-${shade}`,
                label: `${family}-${shade}`,
                hex: TW_COLOR_HEX[family]?.[shade] ?? undefined,
            });
        }
    }

    return options;
}

const COLOR_UTILITY_PREFIX = '(?:bg|text|border|from|via|to|ring|outline|fill|stroke|divide|accent|caret|decoration|shadow)';

/**
 * @param {string} utility e.g. bg-red-500
 * @returns {string|null}
 */
export function hexForUtility(utility) {
    const match = String(utility ?? '').match(new RegExp(`^${COLOR_UTILITY_PREFIX}-([a-z]+)-(\\d+)$`));

    if (! match) {
        return null;
    }

    return TW_COLOR_HEX[match[1]]?.[match[2]] ?? null;
}

/**
 * Resolve a `vp-*` theme token to a concrete #hex from the canvas theme / palette CSS.
 * Editor chrome defaults are indigo — never use those for Style select swatches.
 *
 * @param {object|null|undefined} editor
 * @param {string} token e.g. vp-brand-1 or bg-vp-brand-1
 * @returns {string|null}
 */
export function resolveThemeSwatchHex(editor, token) {
    let name = String(token ?? '').trim();

    if (name === '') {
        return null;
    }

    if (name.includes(':')) {
        const parts = name.split(':');
        name = parts[parts.length - 1] ?? name;
    }

    name = name.replace(/\/\d{1,3}$/, '');
    const prefixed = name.match(/^(?:bg|text|border|from|via|to|shadow|outline|ring|fill|stroke)-(vp-[\w-]+)$/);

    if (prefixed) {
        name = prefixed[1];
    }

    if (! name.startsWith('vp-')) {
        return null;
    }

    const varName = `--color-${name}`;
    const paletteCss = String(editor?.__voodbuilderThemePaletteCss ?? '').trim();

    if (paletteCss !== '') {
        // Values may be color-mix(...)!important — capture until ; or } , then strip flags.
        const re = new RegExp(`${varName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\s*:\\s*([^;}+]+)`);
        const match = paletteCss.match(re);
        const raw = String(match?.[1] ?? '').trim();
        const fromPalette = cssColorToHex(raw);

        if (fromPalette) {
            return fromPalette;
        }
    }

    const docs = [];

    try {
        const frameDoc = editor?.Canvas?.getDocument?.();

        if (frameDoc) {
            docs.push(frameDoc);
        }
    } catch {
        // Frame may be unavailable during boot.
    }

    docs.push(document);

    for (const doc of docs) {
        if (! doc) {
            continue;
        }

        const roots = [doc.documentElement, doc.body].filter(Boolean);
        const view = doc.defaultView || window;

        for (const root of roots) {
            let raw = '';

            try {
                raw = String(view.getComputedStyle(root).getPropertyValue(varName) ?? '').trim();
            } catch {
                raw = '';
            }

            const hex = cssColorToHex(raw);

            if (hex) {
                return hex;
            }
        }
    }

    return cssColorToHex(`var(${varName})`);
}

/**
 * @param {string} hex
 * @returns {[number, number, number]|null}
 */
function expandHexRgb(hex) {
    const match = String(hex ?? '').trim().match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);

    if (! match) {
        return null;
    }

    let h = match[1];

    if (h.length === 3) {
        h = h.split('').map((c) => c + c).join('');
    }

    return [
        Number.parseInt(h.slice(0, 2), 16),
        Number.parseInt(h.slice(2, 4), 16),
        Number.parseInt(h.slice(4, 6), 16),
    ];
}

/**
 * Approximate `color-mix(in srgb, #a p%, #b)` without a DOM (Vitest / SSR).
 *
 * @param {string} raw
 * @returns {string|null}
 */
function hexFromSimpleColorMix(raw) {
    const match = String(raw ?? '').match(
        /^color-mix\(\s*in\s+srgb\s*,\s*(#[0-9a-f]{3,8})\s+([\d.]+)%\s*,\s*(#[0-9a-f]{3,8})\s*\)$/i,
    );

    if (! match) {
        return null;
    }

    const a = expandHexRgb(match[1]);
    const b = expandHexRgb(match[3]);
    const pct = Math.min(100, Math.max(0, Number.parseFloat(match[2]) || 0)) / 100;

    if (! a || ! b) {
        return null;
    }

    const mix = (x, y) => Math.round((x * pct) + (y * (1 - pct)));
    const part = (n) => {
        const h = Math.max(0, Math.min(255, n)).toString(16);

        return h.length === 1 ? `0${h}` : h;
    };

    return `#${part(mix(a[0], b[0]))}${part(mix(a[1], b[1]))}${part(mix(a[2], b[2]))}`;
}

/**
 * @param {string} cssColor
 * @returns {string|null}
 */
function cssColorToHex(cssColor) {
    // Theme Studio often emits `color-mix(...)!important` — flags break style.cssText.
    const raw = String(cssColor ?? '')
        .replace(/\s*!important\s*$/i, '')
        .trim();

    if (raw === '') {
        return null;
    }

    const fromHex = expandHexRgb(raw);

    if (fromHex) {
        const part = (n) => {
            const h = n.toString(16);

            return h.length === 1 ? `0${h}` : h;
        };

        return `#${part(fromHex[0])}${part(fromHex[1])}${part(fromHex[2])}`;
    }

    const fromMix = hexFromSimpleColorMix(raw);

    if (fromMix) {
        return fromMix;
    }

    if (typeof document === 'undefined' || typeof window === 'undefined') {
        return null;
    }

    const probe = document.createElement('div');
    // background-color resolves color-mix() more reliably than `color:` for swatches.
    probe.style.cssText = `position:absolute;left:-99999px;top:0;background-color:${raw}`;
    document.documentElement.appendChild(probe);
    let computed = '';

    try {
        computed = String(window.getComputedStyle(probe).backgroundColor ?? '');
    } catch {
        computed = '';
    }

    probe.remove();

    const rgb = computed.match(/rgba?\(\s*([\d.]+)\s*,\s*([\d.]+)\s*,\s*([\d.]+)/i);

    if (! rgb) {
        return null;
    }

    const part = (n) => {
        const h = Math.max(0, Math.min(255, Math.round(Number(n) || 0))).toString(16);

        return h.length === 1 ? `0${h}` : h;
    };

    return `#${part(rgb[1])}${part(rgb[2])}${part(rgb[3])}`;
}

/**
 * Concrete swatch color for a Style select option (`data-hex`). Prefers baked
 * theme hex for `vp-*` so the inspector host shows the canvas palette, not chrome defaults.
 *
 * @param {object|null|undefined} editor
 * @param {string} className
 * @returns {string|null}
 */
export function swatchHexForUtility(editor, className) {
    const themeHex = resolveThemeSwatchHex(editor, className);

    if (themeHex) {
        return themeHex;
    }

    const css = swatchCssForClassName(className);

    if (! css || css === 'transparent' || css.startsWith('var(') || css.startsWith('color-mix')) {
        return css === 'transparent' ? null : (cssColorToHex(css) || null);
    }

    if (css.startsWith('#')) {
        return cssColorToHex(css);
    }

    return cssColorToHex(css);
}

/**
 * CSS color for a class-manager suggestion swatch (hex, theme var, or color-mix).
 * Handles variants (`hover:`), opacity (`/10`), theme tokens (`bg-vp-brand-1`).
 *
 * @param {string} className
 * @returns {string|null}
 */
export function swatchCssForClassName(className) {
    let token = String(className ?? '').trim();

    if (token === '') {
        return null;
    }

    // Drop responsive / state / dark variants — keep the utility leaf.
    if (token.includes(':')) {
        const parts = token.split(':');
        token = parts[parts.length - 1] ?? token;
    }

    let opacityPercent = null;
    const opacityMatch = token.match(/\/(\d{1,3})$/);

    if (opacityMatch) {
        opacityPercent = Math.min(100, Math.max(0, Number.parseInt(opacityMatch[1], 10) || 0));
        token = token.slice(0, -opacityMatch[0].length);
    }

    let css = '';

    if (new RegExp(`^${COLOR_UTILITY_PREFIX}-black$`).test(token) || token === 'black') {
        css = '#000000';
    } else if (new RegExp(`^${COLOR_UTILITY_PREFIX}-white$`).test(token) || token === 'white') {
        css = '#ffffff';
    } else if (
        new RegExp(`^${COLOR_UTILITY_PREFIX}-transparent$`).test(token)
        || token === 'transparent'
    ) {
        css = 'transparent';
    } else {
        const hex = hexForUtility(token);

        if (hex) {
            css = hex;
        } else {
            const theme = token.match(new RegExp(`^${COLOR_UTILITY_PREFIX}-(vp-[\\w-]+)$`));

            if (theme) {
                css = `var(--color-${theme[1]})`;
            } else {
                const arbitrary = token.match(new RegExp(`^${COLOR_UTILITY_PREFIX}-\\[(.+)\\]$`));

                if (arbitrary) {
                    css = arbitrary[1].replace(/^['"]|['"]$/g, '');
                }
            }
        }
    }

    if (! css || css === 'transparent') {
        return css || null;
    }

    if (opacityPercent !== null && opacityPercent < 100) {
        return `color-mix(in srgb, ${css} ${opacityPercent}%, transparent)`;
    }

    return css;
}
