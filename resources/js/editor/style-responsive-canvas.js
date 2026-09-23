/**
 * Editor canvas: apply Style-panel md:/lg: font-size utilities from the Grapes
 * device attribute, not only from @media. Frame width and Tailwind breakpoints
 * can disagree; without this, changing md:text-9xl looks like a no-op until Save.
 */

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

const STYLE_ID = 'voodbuilder-editor-bp-font-size';

/**
 * @param {string} className e.g. text-9xl
 * @returns {string} CSS selector fragment .md\:text-9xl
 */
function cssClassSelector(prefix, className) {
    const full = `${prefix}${className}`;

    return `.${full.replace(/:/g, '\\:')}`;
}

/**
 * Build device-scoped rules so tablet/desktop show md:/lg: font sizes immediately.
 *
 * @returns {string}
 */
export function buildEditorBreakpointFontSizeCss() {
    const lines = [];

    for (const [className, size] of Object.entries(STYLE_FONT_SIZE_SCALE)) {
        const mdSel = cssClassSelector('md:', className);
        const lgSel = cssClassSelector('lg:', className);

        lines.push(
            `body[data-voodbuilder-editor-device='tablet'] ${mdSel},`,
            `body[data-voodbuilder-editor-device='desktop'] ${mdSel} { font-size: ${size} !important; }`,
            `body[data-voodbuilder-editor-device='desktop'] ${lgSel} { font-size: ${size} !important; }`,
        );
    }

    return lines.join('\n');
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

export const STYLE_RESPONSIVE_FONT_SIZE_SAFELIST = buildResponsiveFontSizeSafelist();

/**
 * @param {object} editor
 */
export function injectEditorBreakpointFontSizeCss(editor) {
    const doc = editor?.Canvas?.getDocument?.();

    if (! doc?.head) {
        return;
    }

    let style = doc.getElementById(STYLE_ID);

    if (! style) {
        style = doc.createElement('style');
        style.id = STYLE_ID;
        style.setAttribute('data-voodbuilder-editor-bp-font-size', '');
        doc.head.appendChild(style);
    }

    style.textContent = buildEditorBreakpointFontSizeCss();
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
            injectEditorBreakpointFontSizeCss(editor);
        } catch {
            // Frame may be mid-reload.
        }
    };

    inject();
    editor.on('load', inject);
    editor.on('canvas:frame:load', inject);
    editor.on('device:select', inject);
    editor.on('change:device', inject);
}
