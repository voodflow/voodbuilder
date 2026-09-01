/**
 * Tailwind utility option catalogs for the Style panel.
 * Classes are the source of truth — no Grapes Style Manager inline inventing.
 */

import { tailwindColorOptions } from './tailwind-color-palette.js';

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

export { SPACING_SCALE };

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

const THEME_BG = [
    { value: 'bg-transparent', label: 'transparent' },
    { value: 'bg-white', label: 'white', hex: '#ffffff' },
    { value: 'bg-black', label: 'black', hex: '#000000' },
    { value: 'bg-vp-bg', label: 'vp-bg' },
    { value: 'bg-vp-bg-alt', label: 'vp-bg-alt' },
    { value: 'bg-vp-bg-elv', label: 'vp-bg-elv' },
    { value: 'bg-vp-gray-soft', label: 'vp-gray-soft' },
    { value: 'bg-vp-brand-1', label: 'vp-brand-1' },
    { value: 'bg-vp-brand-2', label: 'vp-brand-2' },
    { value: 'bg-vp-brand-3', label: 'vp-brand-3' },
];

export const BACKGROUND_OPTIONS = withNone([
    ...THEME_BG,
    ...tailwindColorOptions('bg'),
]);

export const GRADIENT_DIRECTION_OPTIONS = withNone([
    { value: 'bg-none', label: 'none' },
    { value: 'bg-gradient-to-t', label: 'to top' },
    { value: 'bg-gradient-to-tr', label: 'to top-right' },
    { value: 'bg-gradient-to-r', label: 'to right' },
    { value: 'bg-gradient-to-br', label: 'to bottom-right' },
    { value: 'bg-gradient-to-b', label: 'to bottom' },
    { value: 'bg-gradient-to-bl', label: 'to bottom-left' },
    { value: 'bg-gradient-to-l', label: 'to left' },
    { value: 'bg-gradient-to-tl', label: 'to top-left' },
]);

const GRADIENT_STOP_BASE = [
    { value: 'from-transparent', label: 'transparent' },
    { value: 'from-white', label: 'white', hex: '#ffffff' },
    { value: 'from-black', label: 'black', hex: '#000000' },
];

export const GRADIENT_FROM_OPTIONS = withNone([
    ...GRADIENT_STOP_BASE,
    ...tailwindColorOptions('from'),
]);

export const GRADIENT_VIA_OPTIONS = withNone([
    { value: 'via-transparent', label: 'transparent' },
    { value: 'via-white', label: 'white', hex: '#ffffff' },
    { value: 'via-black', label: 'black', hex: '#000000' },
    ...tailwindColorOptions('via'),
]);

export const GRADIENT_TO_OPTIONS = withNone([
    { value: 'to-transparent', label: 'transparent' },
    { value: 'to-white', label: 'white', hex: '#ffffff' },
    { value: 'to-black', label: 'black', hex: '#000000' },
    ...tailwindColorOptions('to'),
]);

function sideBorderWidth(prefix) {
    // Full string literals — Tailwind @source cannot see `${prefix}-2` templates.
    const table = {
        'border-t': [
            { value: 'border-t-0', label: '0' },
            { value: 'border-t', label: '1' },
            { value: 'border-t-2', label: '2' },
            { value: 'border-t-4', label: '4' },
            { value: 'border-t-8', label: '8' },
        ],
        'border-r': [
            { value: 'border-r-0', label: '0' },
            { value: 'border-r', label: '1' },
            { value: 'border-r-2', label: '2' },
            { value: 'border-r-4', label: '4' },
            { value: 'border-r-8', label: '8' },
        ],
        'border-b': [
            { value: 'border-b-0', label: '0' },
            { value: 'border-b', label: '1' },
            { value: 'border-b-2', label: '2' },
            { value: 'border-b-4', label: '4' },
            { value: 'border-b-8', label: '8' },
        ],
        'border-l': [
            { value: 'border-l-0', label: '0' },
            { value: 'border-l', label: '1' },
            { value: 'border-l-2', label: '2' },
            { value: 'border-l-4', label: '4' },
            { value: 'border-l-8', label: '8' },
        ],
    };

    return withNone(table[prefix] ?? []);
}

export const BORDER_WIDTH_OPTIONS = withNone([
    { value: 'border-0', label: '0' },
    { value: 'border', label: '1' },
    { value: 'border-2', label: '2' },
    { value: 'border-4', label: '4' },
    { value: 'border-8', label: '8' },
]);

export const BORDER_T_WIDTH_OPTIONS = sideBorderWidth('border-t');
export const BORDER_R_WIDTH_OPTIONS = sideBorderWidth('border-r');
export const BORDER_B_WIDTH_OPTIONS = sideBorderWidth('border-b');
export const BORDER_L_WIDTH_OPTIONS = sideBorderWidth('border-l');

export const BORDER_STYLE_OPTIONS = withNone([
    { value: 'border-solid', label: 'solid' },
    { value: 'border-dashed', label: 'dashed' },
    { value: 'border-dotted', label: 'dotted' },
    { value: 'border-double', label: 'double' },
    { value: 'border-hidden', label: 'hidden' },
    { value: 'border-none', label: 'none' },
]);

/** Compact set for Decorations segmented control. */
export const BORDER_STYLE_SEGMENTS = [
    { value: 'border-solid', label: 'Solid', icon: 'border-solid' },
    { value: 'border-dashed', label: 'Dashed', icon: 'border-dashed' },
    { value: 'border-dotted', label: 'Dotted', icon: 'border-dotted' },
    { value: 'border-none', label: 'None', icon: 'border-none' },
];

export const BORDER_COLOR_OPTIONS = withNone([
    { value: 'border-transparent', label: 'transparent' },
    { value: 'border-white', label: 'white', hex: '#ffffff' },
    { value: 'border-black', label: 'black', hex: '#000000' },
    { value: 'border-current', label: 'current' },
    { value: 'border-vp-divider', label: 'vp-divider' },
    { value: 'border-vp-brand-1', label: 'vp-brand-1' },
    { value: 'border-vp-brand-2', label: 'vp-brand-2' },
    { value: 'border-vp-brand-3', label: 'vp-brand-3' },
    ...tailwindColorOptions('border'),
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

/**
 * Corner/side rounded catalogs — every class name must be a full string literal
 * so Tailwind @source (section-utilities) can emit the CSS. Template strings
 * like `${prefix}-3xl` are invisible to the scanner (linked `rounded-3xl` worked;
 * per-corner `rounded-tl-3xl` did not).
 *
 * @param {'rounded-t'|'rounded-r'|'rounded-b'|'rounded-l'|'rounded-tl'|'rounded-tr'|'rounded-br'|'rounded-bl'} prefix
 */
function sideRounded(prefix) {
    const table = {
        'rounded-t': [
            { value: 'rounded-t-none', label: 'none' },
            { value: 'rounded-t-sm', label: 'sm' },
            { value: 'rounded-t', label: 'default' },
            { value: 'rounded-t-md', label: 'md' },
            { value: 'rounded-t-lg', label: 'lg' },
            { value: 'rounded-t-xl', label: 'xl' },
            { value: 'rounded-t-2xl', label: '2xl' },
            { value: 'rounded-t-3xl', label: '3xl' },
            { value: 'rounded-t-full', label: 'full' },
        ],
        'rounded-r': [
            { value: 'rounded-r-none', label: 'none' },
            { value: 'rounded-r-sm', label: 'sm' },
            { value: 'rounded-r', label: 'default' },
            { value: 'rounded-r-md', label: 'md' },
            { value: 'rounded-r-lg', label: 'lg' },
            { value: 'rounded-r-xl', label: 'xl' },
            { value: 'rounded-r-2xl', label: '2xl' },
            { value: 'rounded-r-3xl', label: '3xl' },
            { value: 'rounded-r-full', label: 'full' },
        ],
        'rounded-b': [
            { value: 'rounded-b-none', label: 'none' },
            { value: 'rounded-b-sm', label: 'sm' },
            { value: 'rounded-b', label: 'default' },
            { value: 'rounded-b-md', label: 'md' },
            { value: 'rounded-b-lg', label: 'lg' },
            { value: 'rounded-b-xl', label: 'xl' },
            { value: 'rounded-b-2xl', label: '2xl' },
            { value: 'rounded-b-3xl', label: '3xl' },
            { value: 'rounded-b-full', label: 'full' },
        ],
        'rounded-l': [
            { value: 'rounded-l-none', label: 'none' },
            { value: 'rounded-l-sm', label: 'sm' },
            { value: 'rounded-l', label: 'default' },
            { value: 'rounded-l-md', label: 'md' },
            { value: 'rounded-l-lg', label: 'lg' },
            { value: 'rounded-l-xl', label: 'xl' },
            { value: 'rounded-l-2xl', label: '2xl' },
            { value: 'rounded-l-3xl', label: '3xl' },
            { value: 'rounded-l-full', label: 'full' },
        ],
        'rounded-tl': [
            { value: 'rounded-tl-none', label: 'none' },
            { value: 'rounded-tl-sm', label: 'sm' },
            { value: 'rounded-tl', label: 'default' },
            { value: 'rounded-tl-md', label: 'md' },
            { value: 'rounded-tl-lg', label: 'lg' },
            { value: 'rounded-tl-xl', label: 'xl' },
            { value: 'rounded-tl-2xl', label: '2xl' },
            { value: 'rounded-tl-3xl', label: '3xl' },
            { value: 'rounded-tl-full', label: 'full' },
        ],
        'rounded-tr': [
            { value: 'rounded-tr-none', label: 'none' },
            { value: 'rounded-tr-sm', label: 'sm' },
            { value: 'rounded-tr', label: 'default' },
            { value: 'rounded-tr-md', label: 'md' },
            { value: 'rounded-tr-lg', label: 'lg' },
            { value: 'rounded-tr-xl', label: 'xl' },
            { value: 'rounded-tr-2xl', label: '2xl' },
            { value: 'rounded-tr-3xl', label: '3xl' },
            { value: 'rounded-tr-full', label: 'full' },
        ],
        'rounded-br': [
            { value: 'rounded-br-none', label: 'none' },
            { value: 'rounded-br-sm', label: 'sm' },
            { value: 'rounded-br', label: 'default' },
            { value: 'rounded-br-md', label: 'md' },
            { value: 'rounded-br-lg', label: 'lg' },
            { value: 'rounded-br-xl', label: 'xl' },
            { value: 'rounded-br-2xl', label: '2xl' },
            { value: 'rounded-br-3xl', label: '3xl' },
            { value: 'rounded-br-full', label: 'full' },
        ],
        'rounded-bl': [
            { value: 'rounded-bl-none', label: 'none' },
            { value: 'rounded-bl-sm', label: 'sm' },
            { value: 'rounded-bl', label: 'default' },
            { value: 'rounded-bl-md', label: 'md' },
            { value: 'rounded-bl-lg', label: 'lg' },
            { value: 'rounded-bl-xl', label: 'xl' },
            { value: 'rounded-bl-2xl', label: '2xl' },
            { value: 'rounded-bl-3xl', label: '3xl' },
            { value: 'rounded-bl-full', label: 'full' },
        ],
    };

    return withNone(table[prefix] ?? []);
}

export const ROUNDED_T_OPTIONS = sideRounded('rounded-t');
export const ROUNDED_R_OPTIONS = sideRounded('rounded-r');
export const ROUNDED_B_OPTIONS = sideRounded('rounded-b');
export const ROUNDED_L_OPTIONS = sideRounded('rounded-l');
export const ROUNDED_TL_OPTIONS = sideRounded('rounded-tl');
export const ROUNDED_TR_OPTIONS = sideRounded('rounded-tr');
export const ROUNDED_BR_OPTIONS = sideRounded('rounded-br');
export const ROUNDED_BL_OPTIONS = sideRounded('rounded-bl');

export const SHADOW_OPTIONS = withNone([
    { value: 'shadow-2xs', label: '2xs' },
    { value: 'shadow-xs', label: 'xs' },
    { value: 'shadow-sm', label: 'sm' },
    { value: 'shadow', label: 'default' },
    { value: 'shadow-md', label: 'md' },
    { value: 'shadow-lg', label: 'lg' },
    { value: 'shadow-xl', label: 'xl' },
    { value: 'shadow-2xl', label: '2xl' },
    { value: 'shadow-inner', label: 'inner' },
    { value: 'shadow-none', label: 'none' },
]);

export const SHADOW_COLOR_OPTIONS = withNone([
    { value: 'shadow-transparent', label: 'transparent' },
    { value: 'shadow-current', label: 'current' },
    { value: 'shadow-inherit', label: 'inherit' },
    { value: 'shadow-white', label: 'white', hex: '#ffffff' },
    { value: 'shadow-black', label: 'black', hex: '#000000' },
    ...tailwindColorOptions('shadow'),
]);

export const DROP_SHADOW_OPTIONS = withNone([
    { value: 'drop-shadow-xs', label: 'xs' },
    { value: 'drop-shadow-sm', label: 'sm' },
    { value: 'drop-shadow', label: 'default' },
    { value: 'drop-shadow-md', label: 'md' },
    { value: 'drop-shadow-lg', label: 'lg' },
    { value: 'drop-shadow-xl', label: 'xl' },
    { value: 'drop-shadow-2xl', label: '2xl' },
    { value: 'drop-shadow-none', label: 'none' },
]);

/** CSS background-image size utilities (cover / contain / auto). */
export const BG_SIZE_OPTIONS = withNone([
    { value: 'bg-auto', label: 'auto' },
    { value: 'bg-cover', label: 'cover' },
    { value: 'bg-contain', label: 'contain' },
]);

/** CSS background-position utilities (hero-compatible + corners). */
export const BG_POSITION_OPTIONS = withNone([
    { value: 'bg-center', label: 'center' },
    { value: 'bg-top', label: 'top' },
    { value: 'bg-bottom', label: 'bottom' },
    { value: 'bg-left', label: 'left' },
    { value: 'bg-right', label: 'right' },
    { value: 'bg-left-top', label: 'left top' },
    { value: 'bg-left-bottom', label: 'left bottom' },
    { value: 'bg-right-top', label: 'right top' },
    { value: 'bg-right-bottom', label: 'right bottom' },
]);

export const BG_REPEAT_OPTIONS = withNone([
    { value: 'bg-no-repeat', label: 'no-repeat' },
    { value: 'bg-repeat', label: 'repeat' },
    { value: 'bg-repeat-x', label: 'repeat-x' },
    { value: 'bg-repeat-y', label: 'repeat-y' },
    { value: 'bg-repeat-round', label: 'round' },
    { value: 'bg-repeat-space', label: 'space' },
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

/** Compact set for Typography segmented control (excludes start/end). */
export const TEXT_ALIGN_SEGMENTS = [
    { value: 'text-left', label: 'Left', icon: 'align-left' },
    { value: 'text-center', label: 'Center', icon: 'align-center' },
    { value: 'text-right', label: 'Right', icon: 'align-right' },
    { value: 'text-justify', label: 'Justify', icon: 'align-justify' },
];

export const TEXT_TRANSFORM_OPTIONS = withNone([
    { value: 'normal-case', label: 'none' },
    { value: 'uppercase', label: 'uppercase' },
    { value: 'lowercase', label: 'lowercase' },
    { value: 'capitalize', label: 'capitalize' },
]);

export const TEXT_TRANSFORM_SEGMENTS = [
    { value: 'capitalize', label: 'Capitalize', icon: 'Aa' },
    { value: 'uppercase', label: 'Uppercase', icon: 'AA' },
    { value: 'lowercase', label: 'Lowercase', icon: 'aa' },
];

export const TEXT_DECORATION_OPTIONS = withNone([
    { value: 'no-underline', label: 'none' },
    { value: 'underline', label: 'underline' },
    { value: 'overline', label: 'overline' },
    { value: 'line-through', label: 'line-through' },
]);

export const TEXT_DECORATION_SEGMENTS = [
    { value: 'underline', label: 'Underline', icon: 'underline' },
    { value: 'line-through', label: 'Line through', icon: 'line-through' },
    { value: 'overline', label: 'Overline', icon: 'overline' },
];

/** Sentinel for Typography → Text color (not a Tailwind class). */
export const TEXT_COLOR_GRADIENT_VALUE = '__vb_text_gradient__';

/** Required utilities for gradient text (background clipped to glyphs). */
export const TEXT_GRADIENT_BASE_CLASSES = ['bg-clip-text', 'text-transparent'];

export const TEXT_COLOR_OPTIONS = withNone([
    { value: TEXT_COLOR_GRADIENT_VALUE, label: 'Gradient' },
    { value: 'text-inherit', label: 'inherit' },
    { value: 'text-current', label: 'current' },
    { value: 'text-transparent', label: 'transparent' },
    { value: 'text-white', label: 'white', hex: '#ffffff' },
    { value: 'text-black', label: 'black', hex: '#000000' },
    { value: 'text-vp-text-1', label: 'vp-text-1' },
    { value: 'text-vp-text-2', label: 'vp-text-2' },
    { value: 'text-vp-text-3', label: 'vp-text-3' },
    { value: 'text-vp-brand-1', label: 'vp-brand-1' },
    { value: 'text-vp-brand-2', label: 'vp-brand-2' },
    { value: 'text-vp-brand-3', label: 'vp-brand-3' },
    ...tailwindColorOptions('text'),
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

export const TRACKING_OPTIONS = withNone([
    { value: 'tracking-tighter', label: 'tighter' },
    { value: 'tracking-tight', label: 'tight' },
    { value: 'tracking-normal', label: 'normal' },
    { value: 'tracking-wide', label: 'wide' },
    { value: 'tracking-wider', label: 'wider' },
    { value: 'tracking-widest', label: 'widest' },
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
        // Color only — do not wipe background-image / size (gradient & future image).
        inlineProps: ['background-color'],
    },
    {
        id: 'gradient-direction',
        options: GRADIENT_DIRECTION_OPTIONS,
        inlineProps: ['background-image'],
    },
    { id: 'gradient-from', options: GRADIENT_FROM_OPTIONS, inlineProps: [] },
    { id: 'gradient-via', options: GRADIENT_VIA_OPTIONS, inlineProps: [] },
    { id: 'gradient-to', options: GRADIENT_TO_OPTIONS, inlineProps: [] },
    {
        id: 'text-gradient-direction',
        options: GRADIENT_DIRECTION_OPTIONS,
        inlineProps: [],
    },
    { id: 'text-gradient-from', options: GRADIENT_FROM_OPTIONS, inlineProps: [] },
    { id: 'text-gradient-via', options: GRADIENT_VIA_OPTIONS, inlineProps: [] },
    { id: 'text-gradient-to', options: GRADIENT_TO_OPTIONS, inlineProps: [] },
    { id: 'border-width', options: BORDER_WIDTH_OPTIONS, inlineProps: ['border', 'border-width'] },
    { id: 'border-t-width', options: BORDER_T_WIDTH_OPTIONS, inlineProps: ['border-top-width'] },
    { id: 'border-r-width', options: BORDER_R_WIDTH_OPTIONS, inlineProps: ['border-right-width'] },
    { id: 'border-b-width', options: BORDER_B_WIDTH_OPTIONS, inlineProps: ['border-bottom-width'] },
    { id: 'border-l-width', options: BORDER_L_WIDTH_OPTIONS, inlineProps: ['border-left-width'] },
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
    { id: 'rounded-t', options: ROUNDED_T_OPTIONS, inlineProps: ['border-top-left-radius', 'border-top-right-radius'] },
    { id: 'rounded-r', options: ROUNDED_R_OPTIONS, inlineProps: ['border-top-right-radius', 'border-bottom-right-radius'] },
    { id: 'rounded-b', options: ROUNDED_B_OPTIONS, inlineProps: ['border-bottom-left-radius', 'border-bottom-right-radius'] },
    { id: 'rounded-l', options: ROUNDED_L_OPTIONS, inlineProps: ['border-top-left-radius', 'border-bottom-left-radius'] },
    { id: 'rounded-tl', options: ROUNDED_TL_OPTIONS, inlineProps: ['border-top-left-radius'] },
    { id: 'rounded-tr', options: ROUNDED_TR_OPTIONS, inlineProps: ['border-top-right-radius'] },
    { id: 'rounded-br', options: ROUNDED_BR_OPTIONS, inlineProps: ['border-bottom-right-radius'] },
    { id: 'rounded-bl', options: ROUNDED_BL_OPTIONS, inlineProps: ['border-bottom-left-radius'] },
    { id: 'shadow', options: SHADOW_OPTIONS, inlineProps: ['box-shadow'] },
    { id: 'shadow-color', options: SHADOW_COLOR_OPTIONS, inlineProps: [] },
    { id: 'drop-shadow', options: DROP_SHADOW_OPTIONS, inlineProps: ['filter'] },
    { id: 'bg-size', options: BG_SIZE_OPTIONS, inlineProps: [] },
    { id: 'bg-position', options: BG_POSITION_OPTIONS, inlineProps: [] },
    { id: 'bg-repeat', options: BG_REPEAT_OPTIONS, inlineProps: [] },
    { id: 'font-size', options: FONT_SIZE_OPTIONS, inlineProps: ['font-size'] },
    { id: 'font-weight', options: FONT_WEIGHT_OPTIONS, inlineProps: ['font-weight'] },
    { id: 'text-align', options: TEXT_ALIGN_OPTIONS, inlineProps: ['text-align'] },
    { id: 'text-color', options: TEXT_COLOR_OPTIONS, inlineProps: ['color'] },
    { id: 'leading', options: LEADING_OPTIONS, inlineProps: ['line-height'] },
    { id: 'tracking', options: TRACKING_OPTIONS, inlineProps: ['letter-spacing'] },
    { id: 'text-transform', options: TEXT_TRANSFORM_OPTIONS, inlineProps: ['text-transform'] },
    { id: 'text-decoration', options: TEXT_DECORATION_OPTIONS, inlineProps: ['text-decoration', 'text-decoration-line'] },
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
 * True when the component uses Tailwind gradient text (clip + transparent + gradient stops).
 *
 * @param {Iterable<string>|string[]} classes
 */
export function hasTextGradientClasses(classes) {
    const set = new Set([...(classes ?? [])].map((name) => String(name ?? '').trim()).filter(Boolean));

    if (TEXT_GRADIENT_BASE_CLASSES.every((name) => set.has(name))) {
        return true;
    }

    if (! set.has('text-transparent')) {
        return false;
    }

    const direction = resolveGroupValue(set, GRADIENT_DIRECTION_OPTIONS);

    return Boolean(
        (direction && direction !== 'bg-none')
        || resolveGroupValue(set, GRADIENT_FROM_OPTIONS)
        || resolveGroupValue(set, GRADIENT_VIA_OPTIONS)
        || resolveGroupValue(set, GRADIENT_TO_OPTIONS),
    );
}

/**
 * Solid text color utility, ignoring gradient-text mode.
 *
 * @param {Iterable<string>|string[]} classes
 */
export function resolveSolidTextColor(classes) {
    if (hasTextGradientClasses(classes)) {
        return '';
    }

    return resolveGroupValue(classes, TEXT_COLOR_OPTIONS.filter(
        (opt) => opt.value !== TEXT_COLOR_GRADIENT_VALUE,
    ));
}

/**
 * @param {object|null|undefined} component
 * @returns {string[]}
 */
export function componentClassList(component) {
    const raw = component?.getClasses?.() ?? [];
    const names = [];

    for (const item of raw) {
        let name = '';

        if (typeof item === 'string') {
            name = item;
        } else if (item && typeof item === 'object') {
            name = String(
                item.getLabel?.()
                ?? item.get?.('name')
                ?? item.id
                ?? '',
            );
        }

        name = name.trim().replace(/^\./, '');

        if (name !== '' && ! names.includes(name)) {
            names.push(name);
        }
    }

    return names;
}

/**
 * When setting a side/axis/shorthand utility, drop siblings that would fight it.
 *
 * @type {Record<string, string[]>}
 */
const UTILITY_CONFLICT_GROUPS = {
    margin: ['margin-x', 'margin-y', 'margin-t', 'margin-r', 'margin-b', 'margin-l'],
    'margin-x': ['margin', 'margin-l', 'margin-r'],
    'margin-y': ['margin', 'margin-t', 'margin-b'],
    'margin-t': ['margin', 'margin-y'],
    'margin-r': ['margin', 'margin-x'],
    'margin-b': ['margin', 'margin-y'],
    'margin-l': ['margin', 'margin-x'],
    padding: ['padding-x', 'padding-y', 'padding-t', 'padding-r', 'padding-b', 'padding-l'],
    'padding-x': ['padding', 'padding-l', 'padding-r'],
    'padding-y': ['padding', 'padding-t', 'padding-b'],
    'padding-t': ['padding', 'padding-y'],
    'padding-r': ['padding', 'padding-x'],
    'padding-b': ['padding', 'padding-y'],
    'padding-l': ['padding', 'padding-x'],
    'border-width': ['border-t-width', 'border-r-width', 'border-b-width', 'border-l-width'],
    'border-t-width': ['border-width'],
    'border-r-width': ['border-width'],
    'border-b-width': ['border-width'],
    'border-l-width': ['border-width'],
    rounded: [
        'rounded-t', 'rounded-r', 'rounded-b', 'rounded-l',
        'rounded-tl', 'rounded-tr', 'rounded-br', 'rounded-bl',
    ],
    'rounded-t': ['rounded', 'rounded-tl', 'rounded-tr'],
    'rounded-r': ['rounded', 'rounded-tr', 'rounded-br'],
    'rounded-b': ['rounded', 'rounded-bl', 'rounded-br'],
    'rounded-l': ['rounded', 'rounded-tl', 'rounded-bl'],
    'rounded-tl': ['rounded', 'rounded-t', 'rounded-l'],
    'rounded-tr': ['rounded', 'rounded-t', 'rounded-r'],
    'rounded-br': ['rounded', 'rounded-b', 'rounded-r'],
    'rounded-bl': ['rounded', 'rounded-b', 'rounded-l'],
};

/**
 * Replace one exclusive utility group atomically (deduped, no append).
 *
 * Prefer removeClass + addClass (same path as CLASSES "+"). Avoid setClass —
 * it races Grapes SelectorManager / attrUpdated and can drop utilities so the
 * Style Spacing panel appears to "do nothing" while manual class entry works.
 *
 * @param {object|null|undefined} component
 * @param {Set<string>} groupSet
 * @param {string|null|undefined} nextClass
 * @param {{ alsoClear?: Iterable<Set<string>> }} [options]
 */
export function replaceClassGroup(component, groupSet, nextClass, options = {}) {
    if (! component || ! groupSet) {
        return;
    }

    const clearSets = [groupSet, ...(options.alsoClear ?? [])];
    const drop = new Set();

    for (const set of clearSets) {
        for (const name of set ?? []) {
            if (name) {
                drop.add(name);
            }
        }
    }

    const next = String(nextClass ?? '').trim();
    const before = componentClassList(component);

    for (const name of before) {
        if (drop.has(name) && name !== next) {
            component.removeClass?.(name);
        }
    }

    if (next !== '' && ! componentClassList(component).includes(next)) {
        component.addClass?.(next);
    }

    // Second pass: Grapes sometimes keeps a Selector without reflecting it in
    // getClasses until addClass runs again (same as CLASSES "+" persistence).
    const present = new Set(componentClassList(component));

    if (next !== '' && ! present.has(next)) {
        component.addClass?.(next);
        present.add(next);
    }

    for (const name of before) {
        if (drop.has(name) && name !== next && present.has(name)) {
            component.removeClass?.(name);
        }
    }
}

/**
 * @param {string} groupId
 * @returns {string[]}
 */
export function utilityConflictGroupIds(groupId) {
    return UTILITY_CONFLICT_GROUPS[groupId] ?? [];
}

/**
 * @deprecated Use utilityConflictGroupIds
 * @param {string} groupId
 * @returns {string[]}
 */
export function spacingConflictGroupIds(groupId) {
    return utilityConflictGroupIds(groupId);
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
