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
    const match = token.match(/^(bg|text|border|ring|from|to|via)-(indigo|yellow|red|purple|pink|blue|green)-(\d+)$/);

    if (! match) {
        return null;
    }

    const utility = match[1];
    const shade = Number.parseInt(match[3], 10);

    if (utility === 'bg') {
        if (shade <= 100) {
            return 'bg-vp-gray-soft';
        }

        if (shade >= 600) {
            return 'bg-vp-brand-3';
        }

        return 'bg-vp-brand-1';
    }

    if (utility === 'text') {
        if (shade >= 600) {
            return 'text-vp-brand-2';
        }

        return 'text-vp-brand-1';
    }

    if (utility === 'border') {
        return 'border-vp-brand-1';
    }

    if (utility === 'ring') {
        return 'ring-vp-brand-1/20';
    }

    if (['from', 'to', 'via'].includes(utility)) {
        return `${utility}-vp-brand-1`;
    }

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

    return tokens.map((token) => migrateToken(token)).join(' ');
}

function migrateComponentTree(component) {
    const classes = component.getClasses?.() ?? [];

    if (classes.length > 0) {
        component.setClass(
            migrateClassList(classes.join(' '))
                .split(/\s+/)
                .filter(Boolean),
        );
    }

    const style = component.getStyle?.() ?? {};
    const background = style['background-color'] ?? style.background;

    if (typeof background === 'string') {
        const replacement = brandBackgroundVariableForHex(background.trim());

        if (replacement) {
            component.addStyle({ 'background-color': replacement });
        }
    }

    component.components?.().forEach(migrateComponentTree);
}

export function migrateEditorComponents(editor) {
    const wrapper = editor.getWrapper?.();

    if (! wrapper) {
        return;
    }

    migrateComponentTree(wrapper);
}
