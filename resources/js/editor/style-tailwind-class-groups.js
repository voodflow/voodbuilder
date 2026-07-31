/**
 * Tailwind utility option catalogs for the Style panel.
 * Classes are the source of truth — no Grapes Style Manager inline inventing.
 */

const NONE = { value: '', label: '—' };

function scale(prefix, values, labels = {}) {
    return values.map((value) => ({
        value: value === '' ? '' : `${prefix}-${value}`,
        label: labels[value] ?? (value === '' ? '—' : String(value)),
    }));
}

function withNone(options) {
    return [NONE, ...options.filter((opt) => opt.value !== '')];
}

const SPACING_SCALE = [
    '0', 'px', '0.5', '1', '1.5', '2', '2.5', '3', '3.5', '4', '5', '6', '7', '8',
    '9', '10', '11', '12', '14', '16', '20', '24', '28', '32', '36', '40', '44',
    '48', '52', '56', '60', '64', '72', '80', '96',
];

const FRACTION_WIDTHS = [
    '1/2', '1/3', '2/3', '1/4', '3/4', '1/5', '2/5', '3/5', '4/5',
    '1/6', '5/6', 'full',
];

export const WIDTH_OPTIONS = withNone([
    { value: 'w-auto', label: 'auto' },
    { value: 'w-full', label: 'full' },
    { value: 'w-screen', label: 'screen' },
    { value: 'w-fit', label: 'fit' },
    { value: 'w-min', label: 'min' },
    { value: 'w-max', label: 'max' },
    ...FRACTION_WIDTHS.map((value) => ({ value: `w-${value}`, label: value })),
    ...scale('w', SPACING_SCALE.filter((v) => ! ['px', '0.5', '1.5', '2.5', '3.5'].includes(v))).slice(1),
]);

export const HEIGHT_OPTIONS = withNone([
    { value: 'h-auto', label: 'auto' },
    { value: 'h-full', label: 'full' },
    { value: 'h-screen', label: 'screen' },
    { value: 'h-fit', label: 'fit' },
    { value: 'h-min', label: 'min' },
    { value: 'h-max', label: 'max' },
    ...FRACTION_WIDTHS.filter((v) => ['1/2', '1/3', '2/3', '1/4', '3/4', 'full'].includes(v))
        .map((value) => ({ value: `h-${value}`, label: value })),
    ...scale('h', ['0', '4', '8', '12', '16', '20', '24', '32', '40', '48', '56', '64', '72', '80', '96']).slice(1),
]);

export const MAX_WIDTH_OPTIONS = withNone([
    { value: 'max-w-none', label: 'none' },
    { value: 'max-w-xs', label: 'xs' },
    { value: 'max-w-sm', label: 'sm' },
    { value: 'max-w-md', label: 'md' },
    { value: 'max-w-lg', label: 'lg' },
    { value: 'max-w-xl', label: 'xl' },
    { value: 'max-w-2xl', label: '2xl' },
    { value: 'max-w-3xl', label: '3xl' },
    { value: 'max-w-4xl', label: '4xl' },
    { value: 'max-w-5xl', label: '5xl' },
    { value: 'max-w-6xl', label: '6xl' },
    { value: 'max-w-7xl', label: '7xl' },
    { value: 'max-w-full', label: 'full' },
    { value: 'max-w-prose', label: 'prose' },
    { value: 'max-w-screen-sm', label: 'screen-sm' },
    { value: 'max-w-screen-md', label: 'screen-md' },
    { value: 'max-w-screen-lg', label: 'screen-lg' },
    { value: 'max-w-screen-xl', label: 'screen-xl' },
]);

function boxSpacingOptions(prefix) {
    return withNone([
        { value: `${prefix}-auto`, label: 'auto' },
        ...SPACING_SCALE.map((value) => ({
            value: `${prefix}-${value}`,
            label: value,
        })),
    ]);
}

export const MARGIN_OPTIONS = boxSpacingOptions('m');
export const MARGIN_X_OPTIONS = boxSpacingOptions('mx');
export const MARGIN_Y_OPTIONS = boxSpacingOptions('my');
export const MARGIN_T_OPTIONS = boxSpacingOptions('mt');
export const MARGIN_R_OPTIONS = boxSpacingOptions('mr');
export const MARGIN_B_OPTIONS = boxSpacingOptions('mb');
export const MARGIN_L_OPTIONS = boxSpacingOptions('ml');

export const PADDING_OPTIONS = boxSpacingOptions('p');
export const PADDING_X_OPTIONS = boxSpacingOptions('px');
export const PADDING_Y_OPTIONS = boxSpacingOptions('py');
export const PADDING_T_OPTIONS = boxSpacingOptions('pt');
export const PADDING_R_OPTIONS = boxSpacingOptions('pr');
export const PADDING_B_OPTIONS = boxSpacingOptions('pb');
export const PADDING_L_OPTIONS = boxSpacingOptions('pl');

export const BACKGROUND_OPTIONS = withNone([
    { value: 'bg-transparent', label: 'transparent' },
    { value: 'bg-white', label: 'white' },
    { value: 'bg-black', label: 'black' },
    { value: 'bg-vp-bg', label: 'vp-bg' },
    { value: 'bg-vp-bg-alt', label: 'vp-bg-alt' },
    { value: 'bg-vp-bg-elv', label: 'vp-bg-elv' },
    { value: 'bg-vp-gray-soft', label: 'vp-gray-soft' },
    { value: 'bg-vp-brand-1', label: 'vp-brand-1' },
    { value: 'bg-vp-brand-2', label: 'vp-brand-2' },
    { value: 'bg-vp-brand-3', label: 'vp-brand-3' },
    ...['50', '100', '200', '300', '400', '500', '600', '700', '800', '900'].map((shade) => ({
        value: `bg-gray-${shade}`,
        label: `gray-${shade}`,
    })),
]);

export const BORDER_WIDTH_OPTIONS = withNone([
    { value: 'border-0', label: '0' },
    { value: 'border', label: '1' },
    { value: 'border-2', label: '2' },
    { value: 'border-4', label: '4' },
    { value: 'border-8', label: '8' },
]);

export const BORDER_STYLE_OPTIONS = withNone([
    { value: 'border-solid', label: 'solid' },
    { value: 'border-dashed', label: 'dashed' },
    { value: 'border-dotted', label: 'dotted' },
    { value: 'border-double', label: 'double' },
    { value: 'border-none', label: 'none' },
]);

export const BORDER_COLOR_OPTIONS = withNone([
    { value: 'border-transparent', label: 'transparent' },
    { value: 'border-white', label: 'white' },
    { value: 'border-black', label: 'black' },
    { value: 'border-vp-divider', label: 'vp-divider' },
    { value: 'border-vp-brand-1', label: 'vp-brand-1' },
    { value: 'border-vp-brand-2', label: 'vp-brand-2' },
    { value: 'border-vp-brand-3', label: 'vp-brand-3' },
    ...['200', '300', '400', '500', '600', '700'].map((shade) => ({
        value: `border-gray-${shade}`,
        label: `gray-${shade}`,
    })),
]);

export const ROUNDED_OPTIONS = withNone([
    { value: 'rounded-none', label: 'none' },
    { value: 'rounded-sm', label: 'sm' },
    { value: 'rounded', label: 'default' },
    { value: 'rounded-md', label: 'md' },
    { value: 'rounded-lg', label: 'lg' },
    { value: 'rounded-xl', label: 'xl' },
    { value: 'rounded-2xl', label: '2xl' },
    { value: 'rounded-3xl', label: '3xl' },
    { value: 'rounded-full', label: 'full' },
]);

export const SHADOW_OPTIONS = withNone([
    { value: 'shadow-none', label: 'none' },
    { value: 'shadow-sm', label: 'sm' },
    { value: 'shadow', label: 'default' },
    { value: 'shadow-md', label: 'md' },
    { value: 'shadow-lg', label: 'lg' },
    { value: 'shadow-xl', label: 'xl' },
    { value: 'shadow-2xl', label: '2xl' },
    { value: 'shadow-inner', label: 'inner' },
]);

export const FONT_SIZE_OPTIONS = withNone([
    { value: 'text-xs', label: 'xs' },
    { value: 'text-sm', label: 'sm' },
    { value: 'text-base', label: 'base' },
    { value: 'text-lg', label: 'lg' },
    { value: 'text-xl', label: 'xl' },
    { value: 'text-2xl', label: '2xl' },
    { value: 'text-3xl', label: '3xl' },
    { value: 'text-4xl', label: '4xl' },
    { value: 'text-5xl', label: '5xl' },
    { value: 'text-6xl', label: '6xl' },
    { value: 'text-7xl', label: '7xl' },
    { value: 'text-8xl', label: '8xl' },
    { value: 'text-9xl', label: '9xl' },
]);

export const FONT_WEIGHT_OPTIONS = withNone([
    { value: 'font-thin', label: 'thin' },
    { value: 'font-extralight', label: 'extralight' },
    { value: 'font-light', label: 'light' },
    { value: 'font-normal', label: 'normal' },
    { value: 'font-medium', label: 'medium' },
    { value: 'font-semibold', label: 'semibold' },
    { value: 'font-bold', label: 'bold' },
    { value: 'font-extrabold', label: 'extrabold' },
    { value: 'font-black', label: 'black' },
]);

export const TEXT_ALIGN_OPTIONS = withNone([
    { value: 'text-left', label: 'left' },
    { value: 'text-center', label: 'center' },
    { value: 'text-right', label: 'right' },
    { value: 'text-justify', label: 'justify' },
    { value: 'text-start', label: 'start' },
    { value: 'text-end', label: 'end' },
]);

export const TEXT_COLOR_OPTIONS = withNone([
    { value: 'text-inherit', label: 'inherit' },
    { value: 'text-white', label: 'white' },
    { value: 'text-black', label: 'black' },
    { value: 'text-vp-text-1', label: 'vp-text-1' },
    { value: 'text-vp-text-2', label: 'vp-text-2' },
    { value: 'text-vp-text-3', label: 'vp-text-3' },
    { value: 'text-vp-brand-1', label: 'vp-brand-1' },
    { value: 'text-vp-brand-2', label: 'vp-brand-2' },
    { value: 'text-vp-brand-3', label: 'vp-brand-3' },
    ...['400', '500', '600', '700', '800', '900'].map((shade) => ({
        value: `text-gray-${shade}`,
        label: `gray-${shade}`,
    })),
]);

export const LEADING_OPTIONS = withNone([
    { value: 'leading-none', label: 'none' },
    { value: 'leading-tight', label: 'tight' },
    { value: 'leading-snug', label: 'snug' },
    { value: 'leading-normal', label: 'normal' },
    { value: 'leading-relaxed', label: 'relaxed' },
    { value: 'leading-loose', label: 'loose' },
    ...['3', '4', '5', '6', '7', '8', '9', '10'].map((value) => ({
        value: `leading-${value}`,
        label: value,
    })),
]);

/**
 * Exclusive class groups managed by the Style panel.
 * @type {Array<{ id: string, options: Array<{value: string, label: string}>, inlineProps?: string[] }>}
 */
export const STYLE_UTILITY_GROUPS = [
    { id: 'width', options: WIDTH_OPTIONS, inlineProps: ['width'] },
    { id: 'height', options: HEIGHT_OPTIONS, inlineProps: ['height'] },
    { id: 'max-width', options: MAX_WIDTH_OPTIONS, inlineProps: ['max-width'] },
    { id: 'margin', options: MARGIN_OPTIONS, inlineProps: ['margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left'] },
    { id: 'margin-x', options: MARGIN_X_OPTIONS, inlineProps: ['margin-left', 'margin-right'] },
    { id: 'margin-y', options: MARGIN_Y_OPTIONS, inlineProps: ['margin-top', 'margin-bottom'] },
    { id: 'margin-t', options: MARGIN_T_OPTIONS, inlineProps: ['margin-top'] },
    { id: 'margin-r', options: MARGIN_R_OPTIONS, inlineProps: ['margin-right'] },
    { id: 'margin-b', options: MARGIN_B_OPTIONS, inlineProps: ['margin-bottom'] },
    { id: 'margin-l', options: MARGIN_L_OPTIONS, inlineProps: ['margin-left'] },
    { id: 'padding', options: PADDING_OPTIONS, inlineProps: ['padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left'] },
    { id: 'padding-x', options: PADDING_X_OPTIONS, inlineProps: ['padding-left', 'padding-right'] },
    { id: 'padding-y', options: PADDING_Y_OPTIONS, inlineProps: ['padding-top', 'padding-bottom'] },
    { id: 'padding-t', options: PADDING_T_OPTIONS, inlineProps: ['padding-top'] },
    { id: 'padding-r', options: PADDING_R_OPTIONS, inlineProps: ['padding-right'] },
    { id: 'padding-b', options: PADDING_B_OPTIONS, inlineProps: ['padding-bottom'] },
    { id: 'padding-l', options: PADDING_L_OPTIONS, inlineProps: ['padding-left'] },
    {
        id: 'background',
        options: BACKGROUND_OPTIONS,
        inlineProps: [
            'background', 'background-color', 'background-image', 'background-size',
            'background-position', 'background-repeat', 'background-attachment',
            'background-origin', 'background-clip',
        ],
    },
    { id: 'border-width', options: BORDER_WIDTH_OPTIONS, inlineProps: ['border', 'border-width'] },
    { id: 'border-style', options: BORDER_STYLE_OPTIONS, inlineProps: ['border-style'] },
    { id: 'border-color', options: BORDER_COLOR_OPTIONS, inlineProps: ['border-color'] },
    {
        id: 'rounded',
        options: ROUNDED_OPTIONS,
        inlineProps: [
            'border-radius', 'border-top-left-radius', 'border-top-right-radius',
            'border-bottom-left-radius', 'border-bottom-right-radius',
        ],
    },
    { id: 'shadow', options: SHADOW_OPTIONS, inlineProps: ['box-shadow'] },
    { id: 'font-size', options: FONT_SIZE_OPTIONS, inlineProps: ['font-size'] },
    { id: 'font-weight', options: FONT_WEIGHT_OPTIONS, inlineProps: ['font-weight'] },
    { id: 'text-align', options: TEXT_ALIGN_OPTIONS, inlineProps: ['text-align'] },
    { id: 'text-color', options: TEXT_COLOR_OPTIONS, inlineProps: ['color'] },
    { id: 'leading', options: LEADING_OPTIONS, inlineProps: ['line-height'] },
];

/**
 * @param {Array<{value: string, label: string}>} options
 * @returns {Set<string>}
 */
export function classSetFromOptions(options) {
    return new Set(options.map((opt) => opt.value).filter(Boolean));
}

/**
 * @param {Iterable<string>|string[]} classes
 * @param {Array<{value: string, label: string}>} options
 * @returns {string}
 */
export function resolveGroupValue(classes, options) {
    const set = new Set([...(classes ?? [])].map((name) => String(name ?? '').trim()).filter(Boolean));

    for (const opt of options) {
        if (opt.value && set.has(opt.value)) {
            return opt.value;
        }
    }

    return '';
}

/**
 * @param {object|null|undefined} component
 * @returns {string[]}
 */
export function componentClassList(component) {
    return [...(component?.getClasses?.() ?? [])]
        .map((name) => String(name ?? '').trim())
        .filter((name) => name !== '');
}

/**
 * Replace one exclusive utility group atomically.
 *
 * @param {object|null|undefined} component
 * @param {Set<string>} groupSet
 * @param {string|null|undefined} nextClass
 */
export function replaceClassGroup(component, groupSet, nextClass) {
    if (! component) {
        return;
    }

    const kept = componentClassList(component).filter((name) => ! groupSet.has(name));

    if (nextClass) {
        kept.push(nextClass);
    }

    if (typeof component.setClass === 'function') {
        component.setClass(kept);

        return;
    }

    for (const name of componentClassList(component)) {
        if (groupSet.has(name)) {
            component.removeClass?.(name);
        }
    }

    if (nextClass) {
        component.addClass?.(nextClass);
    }
}

/**
 * True when the component has any utility managed by this Style panel.
 *
 * @param {Iterable<string>|string[]} classes
 * @returns {boolean}
 */
export function hasAuthoredStyleUtilities(classes) {
    const set = new Set([...(classes ?? [])].map((name) => String(name ?? '').trim()).filter(Boolean));

    for (const group of STYLE_UTILITY_GROUPS) {
        for (const opt of group.options) {
            if (opt.value && set.has(opt.value)) {
                return true;
            }
        }
    }

    return false;
}
