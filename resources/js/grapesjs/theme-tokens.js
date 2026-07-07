const VARIANT_PREFIXES = [
    'hover:',
    'focus:',
    'active:',
    'group-hover:',
    'focus-within:',
];

const BRAND_COLORS = ['indigo', 'yellow', 'red', 'purple', 'pink', 'blue', 'green'];

const BRAND_LIGHT_SURFACE_HEX = new Set([
    '#eef2ff', '#e0e7ff', '#c7d2fe', '#eff6ff', '#dbeafe', '#bfdbfe',
    '#fefce8', '#fef9c3', '#fef2f2', '#fee2e2', '#faf5ff', '#f3e8ff',
    '#fdf2f8', '#fce7f3', '#f0fdf4', '#dcfce7',
]);

const BRAND_MID_SURFACE_HEX = new Set([
    '#a5b4fc', '#818cf8', '#6366f1', '#93c5fd', '#60a5fa', '#3b82f6',
    '#eab308', '#facc15', '#ef4444', '#f87171', '#a855f7', '#c084fc',
    '#ec4899', '#f472b6', '#22c55e', '#4ade80',
]);

const BRAND_DARK_SURFACE_HEX = new Set([
    '#4f46e5', '#4338ca', '#3730a3', '#312e81', '#2563eb', '#1d4ed8',
    '#1e40af', '#ca8a04', '#a16207', '#dc2626', '#b91c1c', '#9333ea',
    '#7e22ce', '#db2777', '#be185d', '#16a34a', '#15803d',
]);

function expandHex(hex) {
    if (/^#([0-9a-f]{3})$/i.test(hex)) {
        const [, short] = hex.match(/^#([0-9a-f]{3})$/i);

        return `#${short[0]}${short[0]}${short[1]}${short[1]}${short[2]}${short[2]}`.toLowerCase();
    }

    if (/^#[0-9a-f]{6}$/i.test(hex)) {
        return hex.toLowerCase();
    }

    return null;
}

function brandBackgroundVariableForHex(hex) {
    const expanded = expandHex(hex);

    if (! expanded) {
        return null;
    }

    if (BRAND_LIGHT_SURFACE_HEX.has(expanded)) {
        return 'var(--color-vp-gray-soft)';
    }

    if (BRAND_DARK_SURFACE_HEX.has(expanded)) {
        return 'var(--color-vp-brand-3)';
    }

    if (BRAND_MID_SURFACE_HEX.has(expanded)) {
        return 'var(--color-vp-brand-1)';
    }

    return null;
}

function migrateBrandToken(token) {
    // Keep explicit Tailwind palette utilities (bg-blue-200, text-indigo-500, …).
    // Users and the class picker expect those classes to round-trip unchanged.
    return null;
}

export function migrateToken(token) {
    for (const prefix of VARIANT_PREFIXES) {
        if (token.startsWith(prefix)) {
            return prefix + migrateToken(token.slice(prefix.length));
        }
    }

    switch (token) {
        case 'bg-white':
            return 'bg-vp-bg-elv';
        case 'bg-gray-50':
        case 'bg-gray-100':
            return 'bg-vp-bg-alt';
        case 'bg-gray-200':
        case 'bg-gray-300':
            return 'bg-vp-gray-soft';
        case 'text-gray-900':
        case 'text-gray-800':
        case 'text-gray-700':
            return 'text-vp-text-1';
        case 'text-gray-600':
        case 'text-gray-500':
            return 'text-vp-text-2';
        case 'text-gray-400':
        case 'text-gray-300':
        case 'text-gray-200':
            return 'text-vp-text-3';
        case 'border-gray-100':
        case 'border-gray-200':
        case 'border-gray-300':
            return 'border-vp-divider';
        case 'divide-gray-100':
        case 'divide-gray-200':
        case 'divide-gray-300':
            return 'divide-vp-divider';
        default:
            return migrateBrandToken(token) ?? token;
    }
}

export function migrateClassList(classList) {
    const tokens = classList.trim().split(/\s+/).filter(Boolean);

    if (tokens.length === 0) {
        return classList;
    }

    const migrated = tokens.map((token) => migrateToken(token));

    return normalizeBrandBackgroundClasses(migrateLegacyButtonClassesArray(migrated)).join(' ');
}

const BRAND_BG_VARIANT_PREFIXES = ['', ...VARIANT_PREFIXES];

const BRAND_BG_PRIORITIES = {
    '': ['bg-vp-brand-1', 'bg-vp-brand-2', 'bg-vp-brand-3'],
    'hover:': ['hover:bg-vp-brand-2', 'hover:bg-vp-brand-1', 'hover:bg-vp-brand-3'],
    'focus:': ['focus:bg-vp-brand-2', 'focus:bg-vp-brand-1', 'focus:bg-vp-brand-3'],
    'active:': ['active:bg-vp-brand-2', 'active:bg-vp-brand-1', 'active:bg-vp-brand-3'],
    'group-hover:': ['group-hover:bg-vp-brand-2', 'group-hover:bg-vp-brand-1', 'group-hover:bg-vp-brand-3'],
    'focus-within:': ['focus-within:bg-vp-brand-2', 'focus-within:bg-vp-brand-1', 'focus-within:bg-vp-brand-3'],
};

function brandBackgroundPattern(prefix) {
    const escaped = prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    return new RegExp(`^${escaped}bg-vp-brand-\\d+`);
}

function preferredBrandBackgroundClass(prefix, candidates) {
    const priorities = BRAND_BG_PRIORITIES[prefix] ?? BRAND_BG_PRIORITIES[''];

    for (const preferred of priorities) {
        if (candidates.includes(preferred)) {
            return preferred;
        }
    }

    return candidates[0];
}

function dedupeBrandBackgroundSlot(tokens, prefix) {
    const pattern = brandBackgroundPattern(prefix);
    const matches = tokens.filter((token) => pattern.test(token));

    if (matches.length <= 1) {
        return tokens;
    }

    const keep = preferredBrandBackgroundClass(prefix, matches);

    return tokens.filter((token) => ! pattern.test(token) || token === keep);
}

export function normalizeBrandBackgroundClasses(tokens) {
    let next = [...tokens];

    for (const prefix of BRAND_BG_VARIANT_PREFIXES) {
        next = dedupeBrandBackgroundSlot(next, prefix);
    }

    return next;
}

function migrateLegacyButtonClassesArray(tokens) {
    if (! tokens.includes('voodbuilder-gjs-btn-primary')) {
        return tokens;
    }

    const next = tokens.filter((token) => token !== 'voodbuilder-gjs-btn-primary');

    for (const className of ['bg-vp-brand-1', 'text-white', 'hover:bg-vp-brand-2']) {
        if (! next.includes(className)) {
            next.push(className);
        }
    }

    return next;
}

function migrateLegacyButtonClasses(classList) {
    const tokens = classList.trim().split(/\s+/).filter(Boolean);

    return migrateLegacyButtonClassesArray(tokens).join(' ');
}

const BACKGROUND_CLASS_PATTERN = /^(?:hover:|focus:|active:|group-hover:|focus-within:)?bg-/;
const BACKGROUND_OPACITY_CLASS_PATTERN = /^bg-opacity-/;

export function isBackgroundUtilityClass(className) {
    return BACKGROUND_CLASS_PATTERN.test(className)
        || BACKGROUND_OPACITY_CLASS_PATTERN.test(className);
}

export function extractBackgroundUtilityClasses(classes) {
    return classes.filter(isBackgroundUtilityClass);
}

function isComponentInstanceContainer(component) {
    const attrs = component.getAttributes?.() ?? {};

    if (attrs['data-voodbuilder-component']) {
        return true;
    }

    const classes = component.getClasses?.() ?? [];

    return classes.includes('voodbuilder-gjs-component-instance');
}

function isInsideComponentInstance(component) {
    let current = component;

    while (current) {
        if (isComponentInstanceContainer(current)) {
            return true;
        }

        const parent = current.parent?.();

        if (! parent || parent === current) {
            break;
        }

        current = parent;
    }

    return false;
}

export function isClearedBackground(value) {
    if (value == null || value === '') {
        return true;
    }

    const normalized = String(value).trim().toLowerCase();

    if (['none', 'transparent', 'unset', 'initial'].includes(normalized)) {
        return true;
    }

    const rgbaMatch = normalized.match(/^rgba\(\s*[\d.]+\s*,\s*[\d.]+\s*,\s*[\d.]+\s*,\s*([\d.]+)\s*\)$/);

    if (rgbaMatch && Number.parseFloat(rgbaMatch[1]) === 0) {
        return true;
    }

    return false;
}

const ORIGINAL_BACKGROUND_CLASSES_ATTR = 'data-vb-original-bg-classes';

export function stripBackgroundClasses(component) {
    const current = component.getClasses?.() ?? [];
    const stripped = current.filter((className) => {
        return BACKGROUND_CLASS_PATTERN.test(className)
            || BACKGROUND_OPACITY_CLASS_PATTERN.test(className)
            || className === 'voodbuilder-gjs-btn-primary';
    });

    if (stripped.length > 0) {
        const attrs = component.getAttributes?.() ?? {};
        const existing = String(attrs[ORIGINAL_BACKGROUND_CLASSES_ATTR] ?? '').trim();

        if (existing === '') {
            component.addAttributes({
                [ORIGINAL_BACKGROUND_CLASSES_ATTR]: stripped.join(' '),
            });
        }
    }

    const classes = current.filter((className) => {
        return ! BACKGROUND_CLASS_PATTERN.test(className)
            && ! BACKGROUND_OPACITY_CLASS_PATTERN.test(className)
            && className !== 'voodbuilder-gjs-btn-primary';
    });

    component.setClass(classes);
}

export function restoreBackgroundClasses(component) {
    const attrs = component.getAttributes?.() ?? {};
    const original = String(attrs[ORIGINAL_BACKGROUND_CLASSES_ATTR] ?? '').trim();

    if (original === '') {
        return;
    }

    const classes = new Set(component.getClasses?.() ?? []);

    for (const className of original.split(/\s+/).filter(Boolean)) {
        classes.add(className);
    }

    component.setClass([...classes]);
    component.removeAttributes(ORIGINAL_BACKGROUND_CLASSES_ATTR);
}

const ROUNDED_CLASS_PATTERN = /^rounded(?:-|$)/;

export function stripRoundedClasses(component) {
    const classes = (component.getClasses?.() ?? []).filter((className) => ! ROUNDED_CLASS_PATTERN.test(className));

    component.setClass(classes);
}

export function stripTextColorClasses(component) {
    const classes = (component.getClasses?.() ?? []).filter((className) => {
        if (! className.startsWith('text-')) {
            return true;
        }

        return TEXT_LAYOUT_UTILITIES.test(className);
    });

    component.setClass(classes);
}

const BORDER_WIDTH_CLASSES = new Set(['border', 'border-0', 'border-2', 'border-4', 'border-8']);

export function stripBorderColorClasses(component) {
    const classes = (component.getClasses?.() ?? []).filter((className) => {
        if (! className.startsWith('border')) {
            return true;
        }

        if (BORDER_WIDTH_CLASSES.has(className)) {
            return true;
        }

        if (/^border-(?:[trblxy](?:-[0248])?|[xy](?:-[0248])?)$/.test(className)) {
            return true;
        }

        return false;
    });

    component.setClass(classes);
}

const LIBRARY_TEXT_COLORS = new Set([
    '#ffffff',
    '#fff',
    'white',
    '#f3f4f6',
    '#e5e7eb',
    '#d1d5db',
    '#9ca3af',
    '#a5b4fc',
    '#818cf8',
    '#6366f1',
    '#c7d2fe',
    '#93c5fd',
]);

const TEXT_LAYOUT_UTILITIES = /^text-(?:xs|sm|base|lg|xl|2xl|3xl|4xl|5xl|6xl|7xl|8xl|9xl|left|center|right|justify|start|end)$/;

export function hasExplicitTextColorClass(classes) {
    return classes.some((className) => className.startsWith('text-') && ! TEXT_LAYOUT_UTILITIES.test(className));
}

function isDarkEditorTextColor(color) {
    if (['black', '#000', '#000000'].includes(color)) {
        return true;
    }

    if (color.startsWith('var(--color-vp-text')) {
        return true;
    }

    const rgbMatch = color.match(/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/);

    if (! rgbMatch) {
        return false;
    }

    const average = (Number.parseInt(rgbMatch[1], 10) + Number.parseInt(rgbMatch[2], 10) + Number.parseInt(rgbMatch[3], 10)) / 3;

    return average < 128;
}

function hasBackgroundClass(classes) {
    return classes.some((className) => /^bg-(?!opacity|blend|clip|origin|size|position|repeat|none|auto)/.test(className));
}

function ensureLandingSectionClasses(component) {
    if (component.get?.('tagName') !== 'section') {
        return;
    }

    const classes = component.getClasses?.() ?? [];

    if (! classes.includes('voodbuilder-gjs-section')) {
        component.addClass('voodbuilder-gjs-section');
    }
}

function migrateComponentInlineThemeStyles(component) {
    const classes = component.getClasses?.() ?? [];
    const style = component.getStyle?.() ?? {};
    const color = typeof style.color === 'string' ? style.color.trim().toLowerCase() : null;
    const hasTextColorClass = hasExplicitTextColorClass(classes);

    if (! color) {
        return;
    }

    const shouldStripLibraryColor = LIBRARY_TEXT_COLORS.has(color) && hasTextColorClass;
    const shouldStripDarkColor = hasTextColorClass && isDarkEditorTextColor(color);

    if (! shouldStripLibraryColor && ! shouldStripDarkColor) {
        return;
    }

    const nextStyle = { ...style };

    delete nextStyle.color;
    component.setStyle(nextStyle);
}

function migrateComponentTree(component) {
    if (isInsideComponentInstance(component)) {
        return;
    }

    ensureLandingSectionClasses(component);
    migrateComponentInlineThemeStyles(component);

    const classes = component.getClasses?.() ?? [];

    if (classes.length > 0) {
        const migrated = migrateClassList(classes.join(' '))
            .split(/\s+/)
            .filter(Boolean);

        component.setClass(migrated);
    }

    const style = component.getStyle?.() ?? {};
    const background = style['background-color'] ?? style.background;

    if (typeof background === 'string' && ! isClearedBackground(background.trim())) {
        const replacement = brandBackgroundVariableForHex(background.trim());

        if (replacement) {
            component.addStyle({ 'background-color': replacement });
        }
    }

    component.components?.().forEach(migrateComponentTree);
}

export function migrateEditorComponent(component) {
    migrateComponentTree(component);
}

export function purgeBroadSectionBackgroundRules(editor) {
    const cssComposer = editor.Css;

    if (! cssComposer?.getAll) {
        return;
    }

    const broadRules = cssComposer.getAll().filter((rule) => {
        const selectors = rule.get('selectors') ?? [];
        const style = rule.getStyle?.() ?? {};
        const hasBackgroundImage = Object.entries(style).some(([property, value]) => {
            if (! /background/i.test(property)) {
                return false;
            }

            return typeof value === 'string' && value.includes('url(');
        });

        if (! hasBackgroundImage) {
            return false;
        }

        return selectors.length > 0 && selectors.every((selector) => {
            const name = String(selector?.get?.('name') ?? selector?.name ?? selector ?? '');

            if (name.includes('#')) {
                return false;
            }

            return name.includes('voodbuilder-gjs-section') || name.includes('body-font');
        });
    });

    broadRules.forEach((rule) => cssComposer.remove(rule));
}

export function purgeLegacyEditorStyles(editor) {
    const cssComposer = editor.Css;

    if (! cssComposer?.getAll) {
        return;
    }

    const legacyRules = cssComposer.getAll().filter((rule) => {
        const selectors = rule.get('selectors') ?? [];

        return selectors.some((selector) => {
            const name = selector?.get?.('name') ?? selector?.name ?? selector;

            return String(name).includes('voodbuilder-gjs-btn-primary');
        });
    });

    legacyRules.forEach((rule) => cssComposer.remove(rule));
}

export function migrateEditorComponents(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    migrateComponentTree(wrapper);
}
