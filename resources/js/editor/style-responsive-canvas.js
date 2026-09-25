/**
 * Editor canvas: apply Style-panel md:/lg: utilities from the Grapes device
 * attribute, not only from @media. Frame width and Tailwind breakpoints can
 * disagree; without this, changing md:mt-8 / md:text-9xl looks like a no-op.
 */

import { debugSwallowed } from './debug-swallowed.js';
import { SPACING_SCALE } from './style-tailwind-class-groups.js';

/** Default Tailwind v4 font-size scale (rem). */
export const STYLE_FONT_SIZE_SCALE = Object.freeze({
    'text-xs': '0.75rem',
    'text-sm': '0.875rem',
    'text-base': '1rem',
    'text-lg': '1.125rem',
    'text-xl': '1.25rem',
    'text-2xl': '1.5rem',
    'text-3xl': '1.875rem',
    'text-4xl': '2.25rem',
    'text-5xl': '3rem',
    'text-6xl': '3.75rem',
    'text-7xl': '4.5rem',
    'text-8xl': '6rem',
    'text-9xl': '8rem',
});

/** Default Tailwind spacing scale → CSS length. */
export const STYLE_SPACING_LENGTH = Object.freeze({
    0: '0px',
    px: '1px',
    0.5: '0.125rem',
    1: '0.25rem',
    1.5: '0.375rem',
    2: '0.5rem',
    2.5: '0.625rem',
    3: '0.75rem',
    3.5: '0.875rem',
    4: '1rem',
    5: '1.25rem',
    6: '1.5rem',
    7: '1.75rem',
    8: '2rem',
    9: '2.25rem',
    10: '2.5rem',
    11: '2.75rem',
    12: '3rem',
    14: '3.5rem',
    16: '4rem',
    20: '5rem',
    24: '6rem',
    28: '7rem',
    32: '8rem',
    36: '9rem',
    40: '10rem',
    44: '11rem',
    48: '12rem',
    52: '13rem',
    56: '14rem',
    60: '15rem',
    64: '16rem',
    72: '18rem',
    80: '20rem',
    96: '24rem',
    auto: 'auto',
});

/** @type {Array<{ prefix: string, props: string[] }>} */
const SPACING_UTILITIES = Object.freeze([
    { prefix: 'm', props: ['margin'] },
    { prefix: 'mx', props: ['margin-left', 'margin-right'] },
    { prefix: 'my', props: ['margin-top', 'margin-bottom'] },
    { prefix: 'mt', props: ['margin-top'] },
    { prefix: 'mr', props: ['margin-right'] },
    { prefix: 'mb', props: ['margin-bottom'] },
    { prefix: 'ml', props: ['margin-left'] },
    { prefix: 'p', props: ['padding'] },
    { prefix: 'px', props: ['padding-left', 'padding-right'] },
    { prefix: 'py', props: ['padding-top', 'padding-bottom'] },
    { prefix: 'pt', props: ['padding-top'] },
    { prefix: 'pr', props: ['padding-right'] },
    { prefix: 'pb', props: ['padding-bottom'] },
    { prefix: 'pl', props: ['padding-left'] },
]);

const STYLE_ID = 'voodbuilder-editor-bp-style-utils';

/**
 * @param {string} className e.g. text-9xl / mt-8
 * @returns {string} CSS selector fragment .md\:text-9xl
 */
function cssClassSelector(bpPrefix, className) {
    const full = `${bpPrefix}${className}`;

    return `.${full.replace(/:/g, '\\:')}`;
}

/**
 * Tablet+desktop activate md:; desktop also activates lg:.
 *
 * @param {string} className
 * @param {string} declarations CSS declarations without braces
 * @returns {string[]}
 */
function deviceScopedUtilityRules(className, declarations) {
    const mdSel = cssClassSelector('md:', className);
    const lgSel = cssClassSelector('lg:', className);

    return [
        `body[data-voodbuilder-editor-device='tablet'] ${mdSel},`,
        `body[data-voodbuilder-editor-device='desktop'] ${mdSel} { ${declarations} }`,
        `body[data-voodbuilder-editor-device='desktop'] ${lgSel} { ${declarations} }`,
    ];
}

/**
 * Build device-scoped rules so tablet/desktop show md:/lg: font sizes immediately.
 *
 * @returns {string}
 */
export function buildEditorBreakpointFontSizeCss() {
    const lines = [];

    for (const [className, size] of Object.entries(STYLE_FONT_SIZE_SCALE)) {
        lines.push(...deviceScopedUtilityRules(className, `font-size: ${size} !important;`));
    }

    return lines.join('\n');
}

/**
 * Build device-scoped margin/padding rules for Style spacing controls.
 *
 * @returns {string}
 */
export function buildEditorBreakpointSpacingCss() {
    const lines = [];
    const tokens = [...SPACING_SCALE, 'auto'];

    for (const { prefix, props } of SPACING_UTILITIES) {
        for (const token of tokens) {
            const length = STYLE_SPACING_LENGTH[token];

            if (length == null) {
                continue;
            }

            const className = `${prefix}-${token}`;
            const declarations = props
                .map((prop) => `${prop}: ${length} !important;`)
                .join(' ');

            lines.push(...deviceScopedUtilityRules(className, declarations));
        }
    }

    return lines.join('\n');
}

/**
 * Combined editor paint sheet (font-size + spacing).
 *
 * @returns {string}
 */
export function buildEditorBreakpointStyleCss() {
    return [
        buildEditorBreakpointFontSizeCss(),
        buildEditorBreakpointSpacingCss(),
    ].filter(Boolean).join('\n');
}

/**
 * Safelist string for section-utilities @source (production + canvas JIT).
 *
 * @returns {string}
 */
export function buildResponsiveFontSizeSafelist() {
    const tokens = [];

    for (const className of Object.keys(STYLE_FONT_SIZE_SCALE)) {
        tokens.push(className, `md:${className}`, `lg:${className}`);
    }

    return tokens.join(' ');
}

/**
 * Responsive spacing tokens so md:/lg: margin/padding ship in section-utilities.
 *
 * @returns {string}
 */
export function buildResponsiveSpacingSafelist() {
    const tokens = [];
    const scaleTokens = [...SPACING_SCALE, 'auto'];

    for (const { prefix } of SPACING_UTILITIES) {
        for (const token of scaleTokens) {
            const bare = `${prefix}-${token}`;
            tokens.push(bare, `md:${bare}`, `lg:${bare}`);
        }
    }

    return tokens.join(' ');
}

export const STYLE_RESPONSIVE_FONT_SIZE_SAFELIST = buildResponsiveFontSizeSafelist();
export const STYLE_RESPONSIVE_SPACING_SAFELIST = buildResponsiveSpacingSafelist();

/**
 * @param {object} editor
 */
export function injectEditorBreakpointFontSizeCss(editor) {
    injectEditorBreakpointStyleCss(editor);
}

/**
 * @param {object} editor
 */
export function injectEditorBreakpointStyleCss(editor) {
    const doc = editor?.Canvas?.getDocument?.();

    if (! doc?.head) {
        return;
    }

    let style = doc.getElementById(STYLE_ID);

    // Migrate legacy font-only style tag id.
    if (! style) {
        const legacy = doc.getElementById('voodbuilder-editor-bp-font-size');

        if (legacy) {
            legacy.id = STYLE_ID;
            legacy.setAttribute('data-voodbuilder-editor-bp-style-utils', '');
            style = legacy;
        }
    }

    if (! style) {
        style = doc.createElement('style');
        style.id = STYLE_ID;
        style.setAttribute('data-voodbuilder-editor-bp-style-utils', '');
        doc.head.appendChild(style);
    }

    style.textContent = buildEditorBreakpointStyleCss();
}

/**
 * @param {object} editor
 */
export function registerEditorBreakpointFontSizeCss(editor) {
    if (! editor || editor.__voodbuilderBpFontSizeCssRegistered) {
        return;
    }

    editor.__voodbuilderBpFontSizeCssRegistered = true;

    const inject = () => {
        try {
            injectEditorBreakpointStyleCss(editor);
        } catch (error) {
            // Frame may be mid-reload.
            debugSwallowed(error);
        }
    };

    inject();
    editor.on('load', inject);
    editor.on('canvas:frame:load', inject);
    editor.on('device:select', inject);
    editor.on('change:device', inject);
}
