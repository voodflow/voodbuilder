/**
 * Style Manager Tailwind sectors — Dimension / Decorations / Typography.
 * Same pattern as Animation: apply exclusive utility classes, never invent Grapes inline CSS.
 */

import {
    clearBackgroundCssRules,
    clearStyleProperty,
    hydrateAuthorStylesFromIdRules,
    isCorruptedStackStyleValue,
    resolveVisualStyleTarget,
} from './tailwind-visual-style.js';
import {
    isStyleManagerInventedValue,
    shouldOmitAuthorStyleValue,
} from './theme-tokens.js';
import { applyEditorFontFamily, previewEditorFontFamily } from './fonts/fonts-ui.js';
import { findFontByStack, styleManagerFontOptions, cssSafeFontStack } from './fonts/catalog.js';
import { enhanceInspectorSelects } from './inspector-select-ui.js';
import { createImageUrlField, createSelectField } from './editor-form-ui.js';
import { isEditorBooting } from './editor-lifecycle.js';
import {
    STYLE_BG_OPACITY_ATTR,
    STYLE_BG_OPACITY_OPTIONS,
    STYLE_BG_SRC_ATTR,
    composeDecorationBackgroundImageCss,
    composePhotoAwareGradientLayer,
    composeTailwindGradientLayer,
    cssColorFromBackgroundUtility,
    extractUrlFromBackgroundImage,
    inferBackgroundImageOpacityFromCss,
    normalizeBackgroundImageOpacity,
    toRgbaWithAlpha,
} from './style-background-image.js';
import {
    BACKGROUND_OPTIONS,
    BG_COLOR_OPACITY_ATTR,
    BG_COLOR_OPACITY_OPTIONS,
    BG_POSITION_OPTIONS,
    BG_REPEAT_OPTIONS,
    BG_SIZE_OPTIONS,
    BORDER_B_WIDTH_OPTIONS,
    BORDER_COLOR_OPTIONS,
    BORDER_L_WIDTH_OPTIONS,
    BORDER_R_WIDTH_OPTIONS,
    BORDER_STYLE_OPTIONS,
    BORDER_STYLE_SEGMENTS,
    BORDER_T_WIDTH_OPTIONS,
    BORDER_WIDTH_OPTIONS,
    DROP_SHADOW_OPTIONS,
    FONT_SIZE_OPTIONS,
    FONT_WEIGHT_OPTIONS,
    GRADIENT_DIRECTION_OPTIONS,
    GRADIENT_FROM_OPTIONS,
    GRADIENT_FROM_POS_OPTIONS,
    GRADIENT_POS_DEFAULTS,
    GRADIENT_TO_OPTIONS,
    GRADIENT_TO_POS_OPTIONS,
    GRADIENT_VIA_OPTIONS,
    GRADIENT_VIA_POS_OPTIONS,
    gradientStopPositionFromUtility,
    gradientStopPositionUtility,
    HEIGHT_OPTIONS,
    LEADING_OPTIONS,
    MARGIN_B_OPTIONS,
    MARGIN_L_OPTIONS,
    MARGIN_OPTIONS,
    MARGIN_R_OPTIONS,
    MARGIN_T_OPTIONS,
    MARGIN_X_OPTIONS,
    MARGIN_Y_OPTIONS,
    MAX_WIDTH_OPTIONS,
    PADDING_B_OPTIONS,
    PADDING_L_OPTIONS,
    PADDING_OPTIONS,
    PADDING_R_OPTIONS,
    PADDING_T_OPTIONS,
    PADDING_X_OPTIONS,
    PADDING_Y_OPTIONS,
    ROUNDED_B_OPTIONS,
    ROUNDED_BL_OPTIONS,
    ROUNDED_BR_OPTIONS,
    ROUNDED_L_OPTIONS,
    ROUNDED_OPTIONS,
    ROUNDED_R_OPTIONS,
    ROUNDED_T_OPTIONS,
    ROUNDED_TL_OPTIONS,
    ROUNDED_TR_OPTIONS,
    SHADOW_COLOR_OPTIONS,
    SHADOW_OPTIONS,
    SPACING_SCALE,
    STYLE_UTILITY_GROUPS,
    TEXT_ALIGN_OPTIONS,
    TEXT_ALIGN_SEGMENTS,
    TEXT_COLOR_GRADIENT_VALUE,
    TEXT_COLOR_OPTIONS,
    TEXT_GRADIENT_BASE_CLASSES,
    TEXT_DECORATION_OPTIONS,
    TEXT_DECORATION_SEGMENTS,
    TEXT_TRANSFORM_OPTIONS,
    TEXT_TRANSFORM_SEGMENTS,
    TRACKING_OPTIONS,
    WIDTH_OPTIONS,
    applyBackgroundColorWithOpacity,
    classSetFromOptions,
    clearBackgroundColorUtilities,
    componentClassList,
    hasTextGradientClasses,
    replaceClassGroup,
    resolveBackgroundColorAndOpacity,
    resolveGroupValue,
    resolveSolidTextColor,
    utilityConflictGroupIds,
} from './style-tailwind-class-groups.js';
import {
    STYLE_BREAKPOINT_PREFIXES,
    currentStyleBreakpointPrefix,
    deviceIdToBreakpointPrefix,
    prefixedUtility,
    replaceClassGroupAllBreakpoints,
    replaceClassGroupAtBreakpoint,
    resolveGroupValueAtBreakpoint,
    resolveGroupValueExact,
    stripResponsivePrefix,
} from './style-tailwind-breakpoints.js';
import { pageCssCoversClass } from './page-tailwind-autobuild.js';
import { PAGE_SURFACE_FOCUS_EVENT, resolveStyleTarget } from './page-surface-styles.js';
import {
    injectEditorBreakpointStyleCss,
    registerEditorBreakpointFontSizeCss,
} from './style-responsive-canvas.js';
import { hexForUtility } from './tailwind-color-palette.js';

/**
 * @param {Iterable<string>|string[]} classes
 * @param {Array<{value: string, label?: string}>} options
 * @param {object|null|undefined} editor
 * @returns {string}
 */
function resolveStyleGroup(classes, options, editor = null) {
    return resolveGroupValueAtBreakpoint(classes, options, currentStyleBreakpointPrefix(editor));
}

/**
 * @param {object|null|undefined} component
 * @param {Set<string>} groupSet
 * @param {string|null|undefined} nextClass
 * @param {object|null|undefined} editor
 * @param {{ alsoClear?: Iterable<Set<string>> }} [options]
 */
function replaceStyleGroup(component, groupSet, nextClass, editor = null, options = {}) {
    replaceClassGroupAtBreakpoint(
        component,
        groupSet,
        nextClass,
        currentStyleBreakpointPrefix(editor),
        options,
    );
}

/**
 * Gradients (bg + text) always author at base — md:/lg: from-/to- force JIT
 * rebuilds and drop --tw-gradient-stops until Save.
 *
 * @param {object|null|undefined} component
 * @param {Set<string>} groupSet
 * @param {string|null|undefined} nextClass
 * @param {{ alsoClear?: Iterable<Set<string>> }} [options]
 */
function replaceGradientStyleGroup(component, groupSet, nextClass, options = {}) {
    replaceClassGroupAllBreakpoints(component, groupSet, nextClass, options);
}
const GROUP_SETS = Object.fromEntries(
    STYLE_UTILITY_GROUPS.map((group) => [group.id, classSetFromOptions(group.options)]),
);

const GROUP_INLINE = Object.fromEntries(
    STYLE_UTILITY_GROUPS.map((group) => [group.id, group.inlineProps ?? []]),
);

const TEXT_GRADIENT_GROUP_IDS = [
    'text-gradient-direction',
    'text-gradient-from',
    'text-gradient-via',
    'text-gradient-to',
    'text-gradient-from-pos',
    'text-gradient-via-pos',
    'text-gradient-to-pos',
];

const SHARED_GRADIENT_GROUP_IDS = [
    'gradient-direction',
    'gradient-from',
    'gradient-via',
    'gradient-to',
    'gradient-from-pos',
    'gradient-via-pos',
    'gradient-to-pos',
];

function isGradientStyleGroupId(groupId) {
    return SHARED_GRADIENT_GROUP_IDS.includes(groupId)
        || TEXT_GRADIENT_GROUP_IDS.includes(groupId);
}
function clearTextGradientUtilities(component, { includeGradientStops = true } = {}) {
    if (! component) {
        return;
    }

    for (const cls of TEXT_GRADIENT_BASE_CLASSES) {
        component.removeClass?.(cls);
    }

    if (! includeGradientStops) {
        return;
    }

    for (const id of [...TEXT_GRADIENT_GROUP_IDS, ...SHARED_GRADIENT_GROUP_IDS]) {
        const groupSet = GROUP_SETS[id];

        if (groupSet) {
            replaceClassGroupAllBreakpoints(component, groupSet, null);
        }
    }
}

function ensureTextGradientBase(component) {
    if (! component) {
        return;
    }

    for (const cls of TEXT_GRADIENT_BASE_CLASSES) {
        if (! componentClassList(component).includes(cls)) {
            component.addClass?.(cls);
        }
    }
}

/**
 * Text gradient needs utility `background-image` + transparent fill.
 * Author #id/inline `color` / `background-image` / webkit fill often leave solid text.
 */
function ensureTextGradientPaint(editor, component) {
    if (! editor || ! component || ! hasTextGradientClasses(componentClassList(component))) {
        return;
    }

    ensureTextGradientBase(component);
    clearSolidTextColorUtilities(component);
    clearStyleProperty(editor, component, 'color');
    clearStyleProperty(editor, component, 'background-image');
    clearStyleProperty(editor, component, '-webkit-text-fill-color');

    // Reinforce clip (beats inherited text-* and Grapes RTE fills).
    component.addStyle?.(
        {
            color: 'transparent',
            '-webkit-text-fill-color': 'transparent',
        },
        { inline: true },
    );

    const id = String(component.getId?.() ?? '').trim();

    if (id && editor.Css?.setIdRule) {
        try {
            const existing = { ...(editor.Css.getIdRule?.(id)?.getStyle?.() ?? {}) };
            delete existing['background-image'];
            delete existing.background;
            editor.Css.setIdRule(id, {
                ...existing,
                color: 'transparent',
                '-webkit-text-fill-color': 'transparent',
            });
        } catch {
            // Optional.
        }
    }

    try {
        component.view?.updateClasses?.();
        component.view?.updateStyle?.();
    } catch {
        // View may be unavailable.
    }
}

function clearSolidTextColorUtilities(component) {
    if (! component) {
        return;
    }

    for (const opt of TEXT_COLOR_OPTIONS) {
        const value = opt.value;

        if (! value || value === TEXT_COLOR_GRADIENT_VALUE || value === 'text-transparent') {
            continue;
        }

        for (const bp of STYLE_BREAKPOINT_PREFIXES) {
            component.removeClass?.(`${bp}${value}`);
        }
    }
}

function defaultTextGradientDirection(component) {
    const classes = componentClassList(component);
    const direction = resolveGroupValue(classes, GRADIENT_DIRECTION_OPTIONS);

    if (direction && direction !== 'bg-none') {
        return;
    }

    replaceClassGroup(
        component,
        GROUP_SETS['text-gradient-direction'],
        'bg-gradient-to-r',
    );
}

const SANITIZE_PROPERTIES = [
    'box-shadow',
    'text-shadow',
    'border',
    'border-width',
    'border-style',
    'border-color',
    'border-top',
    'border-right',
    'border-bottom',
    'border-left',
    'transition',
    'transform',
];

function optionsHtml(options) {
    return options.map((opt) => {
        const hex = opt.hex ? ` data-hex="${escapeAttr(opt.hex)}"` : '';

        return `<option value="${escapeAttr(opt.value)}"${hex}>${escapeHtml(opt.label)}</option>`;
    }).join('');
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function escapeAttr(value) {
    return escapeHtml(value).replace(/'/g, '&#39;');
}

/**
 * @param {{ ariaLabel?: string }} args
 *   ariaLabel — name for a field whose visible label is deliberately blank because the
 *   surrounding block heading already reads as its label. Without it the control has no
 *   programmatic name at all.
 */
function fieldHtml({ label, selectAttr, addAttr, options, addLabel, searchable = false, searchPlaceholder = 'Search…', live = false, ariaLabel = null }) {
    const searchAttr = searchable
        ? ` data-vb-search="1" data-vb-search-placeholder="${escapeAttr(searchPlaceholder)}"`
        : '';

    const accessibleName = String(ariaLabel ?? label ?? '').trim();
    const nameAttr = accessibleName === '' ? '' : ` aria-label="${escapeAttr(accessibleName)}"`;

    const addButton = live
        ? ''
        : `<button type="button" class="voodbuilder-editor-anim-combobox__add" ${addAttr}>
                        ${escapeHtml(addLabel)}
                    </button>`;

    return `
        <div class="gjs-sm-property voodbuilder-editor-anim-property${live ? ' voodbuilder-editor-anim-property--live' : ''}">
            <div class="gjs-sm-label"><span class="gjs-sm-label-text">${escapeHtml(label)}</span></div>
            <div class="gjs-fields">
                <div class="voodbuilder-editor-anim-combobox${live ? ' voodbuilder-editor-anim-combobox--solo' : ''}">
                    <select class="voodbuilder-editor-input voodbuilder-editor-input--select"${nameAttr}${searchAttr} ${selectAttr}>
                        ${optionsHtml(options)}
                    </select>
                    ${addButton}
                </div>
            </div>
        </div>
    `;
}

function typographySegmentIcon(icon) {
    switch (icon) {
        case 'align-left':
            return '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M2 3h12v1.5H2V3zm0 4h8v1.5H2V7zm0 4h12v1.5H2V11zm0 4h8v1.5H2V15z"/></svg>';
        case 'align-center':
            return '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M2 3h12v1.5H2V3zm2 4h8v1.5H4V7zm-2 4h12v1.5H2V11zm2 4h8v1.5H4V15z"/></svg>';
        case 'align-right':
            return '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M2 3h12v1.5H2V3zm4 4h8v1.5H6V7zm-4 4h12v1.5H2V11zm4 4h8v1.5H6V15z"/></svg>';
        case 'align-justify':
            return '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M2 3h12v1.5H2V3zm0 4h12v1.5H2V7zm0 4h12v1.5H2V11zm0 4h12v1.5H2V15z"/></svg>';
        case 'underline':
            return '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M4 2h1.5v6.2a2.5 2.5 0 1 0 5 0V2H12v6.2a4 4 0 1 1-8 0V2zm0 12h8v1.5H4V14z"/></svg>';
        case 'line-through':
            return '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M3 7.25h10v1.5H3v-1.5zM5.2 3h5.6l-.7 3.5H5.9L5.2 3zm.9 10 .7-3.5h2.4l.7 3.5H6.1z"/></svg>';
        case 'overline':
            return '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M4 2h8v1.5H4V2zm1.5 3.5H12v6.2a4 4 0 1 1-8 0V5.5h1.5v6.2a2.5 2.5 0 1 0 5 0V5.5z"/></svg>';
        case 'border-solid':
            return '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M2 7.25h12v1.5H2z"/></svg>';
        case 'border-dashed':
            return '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M2 7.25h2.5v1.5H2zm3.5 0h2.5v1.5H5.5zm3.5 0H14v1.5H9z"/></svg>';
        case 'border-dotted':
            return '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><circle cx="2.5" cy="8" r="0.85" fill="currentColor"/><circle cx="5.5" cy="8" r="0.85" fill="currentColor"/><circle cx="8.5" cy="8" r="0.85" fill="currentColor"/><circle cx="11.5" cy="8" r="0.85" fill="currentColor"/><circle cx="14.5" cy="8" r="0.85" fill="currentColor"/></svg>';
        case 'border-none':
            return '<svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M3.2 2.1 13.9 12.8l-1.1 1.1L2.1 3.2 3.2 2.1zM2 7.25h3.2v1.5H2zm8.8 0H14v1.5h-3.2z"/></svg>';
        default:
            return escapeHtml(String(icon ?? ''));
    }
}

function segmentControlHtml({ label, groupId, segments, clearLabel = 'None', authoredHint = 'Value set' }) {
    const buttons = segments.map((seg) => `
        <button
            type="button"
            class="voodbuilder-editor-typo-seg__btn"
            data-voodbuilder-tw-segment="${escapeAttr(groupId)}"
            data-value="${escapeAttr(seg.value)}"
            title="${escapeAttr(seg.label)}"
            aria-label="${escapeAttr(seg.label)}"
            aria-pressed="false"
        >${typographySegmentIcon(seg.icon)}</button>
    `).join('');

    return `
        <div class="gjs-sm-property voodbuilder-editor-typo-seg" data-voodbuilder-typo-seg="${escapeAttr(groupId)}">
            <div class="voodbuilder-editor-typo-seg__head">
                <span class="voodbuilder-editor-typo-seg__label">${escapeHtml(label)}</span>
                <span class="voodbuilder-editor-typo-seg__dot" data-voodbuilder-typo-dot hidden title="${escapeAttr(authoredHint)}" aria-hidden="true"></span>
            </div>
            <div class="voodbuilder-editor-typo-seg__group" role="group" aria-label="${escapeAttr(label)}">
                ${buttons}
                <button
                    type="button"
                    class="voodbuilder-editor-typo-seg__btn voodbuilder-editor-typo-seg__btn--clear"
                    data-voodbuilder-tw-segment="${escapeAttr(groupId)}"
                    data-value=""
                    title="${escapeAttr(clearLabel)}"
                    aria-label="${escapeAttr(clearLabel)}"
                    aria-pressed="false"
                >×</button>
            </div>
        </div>
    `;
}

function spacingTokenFromClass(value) {
    if (! value) {
        return '';
    }

    return stripResponsivePrefix(value).replace(/^(m|p|mt|mr|mb|ml|pt|pr|pb|pl|mx|my|px|py)-/, '') || '';
}

function spacingSideCellHtml(kind, side, title, scaleLabel) {
    return `
        <div class="voodbuilder-editor-spacing-cross__cell voodbuilder-editor-spacing-cross__cell--${side}">
            <input
                type="text"
                class="voodbuilder-editor-spacing-cross__input"
                data-voodbuilder-spacing-kind="${kind}"
                data-voodbuilder-spacing-side="${side}"
                inputmode="decimal"
                autocomplete="off"
                spellcheck="false"
                placeholder="—"
                title="${escapeAttr(title)}"
                aria-label="${escapeAttr(title)}"
            />
            <button
                type="button"
                class="voodbuilder-editor-spacing-cross__scale"
                data-voodbuilder-spacing-scale="${kind}"
                data-voodbuilder-spacing-side="${side}"
                title="${escapeAttr(scaleLabel)}"
                aria-label="${escapeAttr(scaleLabel)}"
            >tw</button>
        </div>
    `;
}

function spacingBlockHtml(kind, label, labels) {
    const linkGroup = labels.classStyleSpacingLinkSides ?? 'Link sides';
    const linkIndependent = labels.classStyleSpacingLinkIndependent ?? 'Independent sides';
    const linkOpposites = labels.classStyleSpacingLinkOpposites ?? 'Opposites linked';
    const linkAll = labels.classStyleSpacingLinkAll ?? 'All sides linked';
    const scaleLabel = labels.classStyleSpacingScale ?? 'Tailwind scale';

    return `
        <div class="voodbuilder-editor-spacing-block" data-voodbuilder-spacing-box="${kind}" data-link="all">
            <div class="voodbuilder-editor-spacing-block__head">
                <span class="voodbuilder-editor-spacing-block__label">${escapeHtml(label)}</span>
                <span class="voodbuilder-editor-spacing-block__dot" data-voodbuilder-spacing-dot hidden title="${escapeAttr(labels.classStyleAuthoredHint ?? 'Value set')}" aria-hidden="true"></span>
                <div class="voodbuilder-editor-spacing-block__links" role="group" aria-label="${escapeAttr(linkGroup)}">
                    <button type="button" class="voodbuilder-editor-spacing-block__link" data-voodbuilder-spacing-link="independent" title="${escapeAttr(linkIndependent)}" aria-pressed="false">
                        <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M4.5 3.5a2 2 0 0 1 2.8 0l.7.7-.7.7-.7-.7a1 1 0 1 0-1.4 1.4l.7.7-.7.7-.7-.7a2 2 0 0 1 0-2.8zm7 7a2 2 0 0 1-2.8 0l-.7-.7.7-.7.7.7a1 1 0 1 0 1.4-1.4l-.7-.7.7-.7.7.7a2 2 0 0 1 0 2.8zM6.2 8.5l1.3-1.3.7.7-1.3 1.3-.7-.7zm2.6-2.6l1.3-1.3.7.7-1.3 1.3-.7-.7z"/></svg>
                    </button>
                    <button type="button" class="voodbuilder-editor-spacing-block__link" data-voodbuilder-spacing-link="opposites" title="${escapeAttr(linkOpposites)}" aria-pressed="false">
                        <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M4 2h8v2H4V2zm0 10h8v2H4v-2zM2.5 6.5h3v3h-3v-3zm8 0h3v3h-3v-3z"/></svg>
                    </button>
                    <button type="button" class="voodbuilder-editor-spacing-block__link is-active" data-voodbuilder-spacing-link="all" title="${escapeAttr(linkAll)}" aria-pressed="true">
                        <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M4.5 2.5a2.5 2.5 0 0 1 3.5 0l.7.7-.7.7a1.5 1.5 0 1 0 0 2.1l.7.7-.7.7a2.5 2.5 0 1 1-3.5-3.5l.7-.7-.7-.7zm7 7a2.5 2.5 0 0 1-3.5 0l-.7-.7.7-.7a1.5 1.5 0 1 0 0-2.1l-.7-.7.7-.7a2.5 2.5 0 1 1 3.5 3.5l-.7.7.7.7z"/></svg>
                    </button>
                </div>
            </div>
            <div class="voodbuilder-editor-spacing-cross" data-voodbuilder-spacing-cross="${kind}">
                ${spacingSideCellHtml(kind, 't', `${label} top`, scaleLabel)}
                ${spacingSideCellHtml(kind, 'l', `${label} left`, scaleLabel)}
                <div class="voodbuilder-editor-spacing-cross__core" aria-hidden="true"></div>
                ${spacingSideCellHtml(kind, 'r', `${label} right`, scaleLabel)}
                ${spacingSideCellHtml(kind, 'b', `${label} bottom`, scaleLabel)}
            </div>
        </div>
    `;
}

function spacingBoxHtml(labels) {
    return `
        <div class="voodbuilder-editor-spacing" data-voodbuilder-spacing>
            ${spacingBlockHtml('margin', labels.classStyleMargin ?? 'Margin', labels)}
            ${spacingBlockHtml('padding', labels.classStylePadding ?? 'Padding', labels)}
        </div>
    `;
}

/**
 * @param {object|null|undefined} editor
 * @param {string|null|undefined} value bare utility
 * @returns {string}
 */
function styleWrittenToken(editor, value) {
    const bare = String(value ?? '').trim();

    if (bare === '' || bare === TEXT_COLOR_GRADIENT_VALUE) {
        return '';
    }

    return prefixedUtility(bare, currentStyleBreakpointPrefix(editor));
}

function scheduleClassCompile(editor, writtenClass = '') {
    // Soft schedule only when the utility already ships in section-utilities.css.
    // Responsive (md:/lg:) and other uncovered tokens need a forced JIT rebuild —
    // soft schedule can no-op if Grapes has not yet reflected addClass in the page
    // class set, so the canvas stays stale until Save.
    const token = String(writtenClass ?? '').trim();
    const needsForce = token !== ''
        && typeof editor?.__voodbuilderForcePageCssRebuild === 'function'
        && (
            /^(?:sm|md|lg|xl|2xl):/.test(token)
            || ! pageCssCoversClass(editor, token)
        );

    if (needsForce) {
        editor.__voodbuilderForcePageCssRebuild(60);
        // Device-scoped Style CSS paints immediately (does not wait for JIT).
        try {
            injectEditorBreakpointStyleCss(editor);
        } catch {
            // Frame may be unavailable.
        }

        return;
    }

    editor?.__voodbuilderSchedulePageCssRebuild?.(0);
}

function ensureSectorsRoot(stylesMount) {
    let root = stylesMount.querySelector('.gjs-sm-sectors');

    if (root) {
        return root;
    }

    root = document.createElement('div');
    root.className = 'gjs-sm-sectors';
    stylesMount.appendChild(root);

    return root;
}

/**
 * Style viewport strip — Mobile / Tablet / Desktop (mobile-first prefixes).
 * Mirrors canvas device; does not gate Animation or other sectors.
 *
 * @param {Record<string, string>} labels
 * @returns {HTMLElement}
 */
function buildViewportStrip(labels) {
    const strip = document.createElement('div');
    strip.className = 'voodbuilder-editor-style-viewport';
    strip.dataset.voodbuilderStyleViewportStrip = '';

    const base = labels.classStyleViewportBaseTab ?? labels.deviceMobile ?? 'Mobile';
    const tablet = labels.deviceTablet ?? 'Tablet';
    const desktop = labels.deviceDesktop ?? 'Desktop';
    const aria = labels.classStyleViewportAria ?? 'Style viewport';
    const cascade = labels.classStyleViewportCascade
        ?? 'Edits apply to the selected viewport (Mobile = base for all; Tablet = md:; Desktop = lg:).';

    strip.innerHTML = `
        <div class="voodbuilder-editor-style-viewport__meta">
            <span class="voodbuilder-editor-style-viewport__hint" data-voodbuilder-style-viewport-hint></span>
            <code class="voodbuilder-editor-style-viewport__prefix" data-voodbuilder-style-viewport-prefix></code>
        </div>
        <div class="voodbuilder-editor-style-viewport__group" role="group" aria-label="${escapeAttr(aria)}">
            <button type="button" class="voodbuilder-editor-style-viewport__btn" data-voodbuilder-style-viewport="mobilePortrait" title="${escapeAttr(base)}">${escapeHtml(base)}</button>
            <button type="button" class="voodbuilder-editor-style-viewport__btn" data-voodbuilder-style-viewport="tablet" title="${escapeAttr(tablet)}">${escapeHtml(tablet)}</button>
            <button type="button" class="voodbuilder-editor-style-viewport__btn" data-voodbuilder-style-viewport="desktop" title="${escapeAttr(desktop)}">${escapeHtml(desktop)}</button>
        </div>
        <p class="voodbuilder-editor-style-viewport__cascade" data-voodbuilder-style-viewport-cascade>${escapeHtml(cascade)}</p>
    `;

    return strip;
}

/**
 * @param {HTMLElement|null|undefined} root
 * @param {object|null|undefined} editor
 * @param {Record<string, string>} [labels]
 */
function syncViewportStrip(root, editor, labels = {}) {
    if (! root) {
        return;
    }

    const strip = root.querySelector?.('[data-voodbuilder-style-viewport-strip]')
        ?? root.closest?.('.gjs-sm-sectors')?.querySelector?.('[data-voodbuilder-style-viewport-strip]');

    if (! strip) {
        return;
    }

    let deviceId = 'desktop';

    try {
        deviceId = String(
            editor?.Devices?.getSelected?.()?.get?.('id')
            ?? editor?.getDevice?.()
            ?? 'desktop',
        ).trim() || 'desktop';
    } catch {
        deviceId = 'desktop';
    }

    const prefix = deviceIdToBreakpointPrefix(deviceId);
    const prefixEl = strip.querySelector('[data-voodbuilder-style-viewport-prefix]');
    const hintEl = strip.querySelector('[data-voodbuilder-style-viewport-hint]');

    if (prefixEl) {
        prefixEl.textContent = prefix === ''
            ? (labels.classStyleViewportBase ?? 'base')
            : prefix.replace(/:$/, '');
    }

    if (hintEl) {
        const deviceLabel = deviceId === 'tablet'
            ? (labels.deviceTablet ?? 'Tablet')
            : (deviceId === 'mobilePortrait' || deviceId === 'mobile')
                ? (labels.classStyleViewportBaseTab ?? labels.deviceMobile ?? 'Mobile')
                : (labels.deviceDesktop ?? 'Desktop');
        const template = labels.classStyleViewportEditing
            ?? 'Editing: {device}';
        hintEl.textContent = String(template).replace('{device}', deviceLabel);
    }

    strip.querySelectorAll('button[data-voodbuilder-style-viewport]').forEach((button) => {
        const id = button.getAttribute('data-voodbuilder-style-viewport') || '';
        const active = id === deviceId
            || (deviceId === 'mobile' && id === 'mobilePortrait');
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
}

/**
 * @param {object} editor
 * @param {HTMLElement} strip
 */
function wireViewportStrip(editor, strip) {
    if (! editor || ! strip || strip.dataset.voodbuilderStyleViewportWired === '1') {
        return;
    }

    strip.dataset.voodbuilderStyleViewportWired = '1';

    strip.querySelectorAll('button[data-voodbuilder-style-viewport]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const id = button.getAttribute('data-voodbuilder-style-viewport') || '';

            if (! id) {
                return;
            }

            try {
                editor.setDevice?.(id);
            } catch {
                // Device manager may be unavailable during boot.
            }
        });
    });
}

/**
 * @param {HTMLElement} stylesMount
 * @param {Record<string, string>} labels
 * @param {object} editor
 * @returns {HTMLElement}
 */
function ensureViewportStrip(stylesMount, labels, editor) {
    const root = ensureSectorsRoot(stylesMount);
    let strip = root.querySelector('[data-voodbuilder-style-viewport-strip]');

    if (! strip) {
        strip = buildViewportStrip(labels);
        root.insertBefore(strip, root.firstChild);
    }

    wireViewportStrip(editor, strip);
    syncViewportStrip(root, editor, labels);

    return strip;
}

function hideNativeStyleManagerSectors(stylesMount) {
    stylesMount.classList.add('voodbuilder-editor-styles--tailwind-only');

    for (const sector of stylesMount.querySelectorAll('.gjs-sm-sector')) {
        if (
            sector.hasAttribute('data-voodbuilder-tw-sector')
            || sector.hasAttribute('data-voodbuilder-animation-sector')
        ) {
            continue;
        }

        sector.hidden = true;
        sector.setAttribute('data-voodbuilder-native-sm-hidden', '');
    }
}

function clearInlineProps(editor, component, properties) {
    if (! editor || ! component || ! Array.isArray(properties)) {
        return;
    }

    const target = resolveVisualStyleTarget(component) ?? component;
    const prevSilent = editor.__voodbuilderLayoutStyleSilent;

    // Clearing leftover inline padding must not re-enter spacing strip.
    editor.__voodbuilderLayoutStyleSilent = true;

    try {
        for (const property of properties) {
            // background-color alone must NOT wipe background-image (Style color + photo).
            // Family wipe is only for shorthand `background` or clearing the image itself.
            clearStyleProperty(editor, target, property, {
                family: property === 'background' || property === 'background-image',
            });
        }

        // Full background CSS rule wipe only when clearing paint-that-includes-image.
        // Changing Color utilities previously called clearBackgroundCssRules via
        // background-color and deleted the author's photo on every swatch click.
        const wipeRulesProps = new Set(['background', 'background-image']);

        if (properties.some((property) => wipeRulesProps.has(String(property)))) {
            clearBackgroundCssRules(editor, target);
        }
    } finally {
        editor.__voodbuilderLayoutStyleSilent = prevSilent;
    }
}

function sanitizeInventedStyles(editor, component) {
    if (! editor || ! component) {
        return;
    }

    const target = resolveVisualStyleTarget(component) ?? component;
    const inline = { ...(target.getStyle?.({ inline: true }) ?? {}) };
    const id = target.getId?.();
    const idStyle = id && editor.Css?.getIdRule
        ? { ...(editor.Css.getIdRule(id)?.getStyle?.() ?? {}) }
        : {};

    for (const property of SANITIZE_PROPERTIES) {
        const values = [inline[property], idStyle[property]];

        for (const value of values) {
            if (value == null || value === '') {
                continue;
            }

            if (
                isCorruptedStackStyleValue(value)
                || shouldOmitAuthorStyleValue(property, value)
                || isStyleManagerInventedValue(property, value)
            ) {
                clearStyleProperty(editor, target, property);
                break;
            }
        }
    }
}

function syncSelectsFromComponent(root, component, editor = null, options = {}) {
    if (! root) {
        return;
    }

    const classes = componentClassList(component);
    const bp = currentStyleBreakpointPrefix(editor);

    for (const group of STYLE_UTILITY_GROUPS) {
        const el = root.querySelector(`[data-voodbuilder-tw-group="${group.id}"]`);

        if (el) {
            if (group.id === 'text-color') {
                el.value = hasTextGradientClasses(classes)
                    ? TEXT_COLOR_GRADIENT_VALUE
                    : (
                        resolveGroupValueAtBreakpoint(
                            classes,
                            TEXT_COLOR_OPTIONS.filter((opt) => opt.value !== TEXT_COLOR_GRADIENT_VALUE),
                            bp,
                        )
                        || resolveSolidTextColor(classes)
                    );
            } else if (group.id === 'background' || group.id === 'background-opacity') {
                const bg = resolveBackgroundColorAndOpacity(classes, component);
                el.value = group.id === 'background' ? bg.color : bg.opacity;
            } else if (isGradientStyleGroupId(group.id)) {
                // Gradients always live at base — do not mark cascade as inherited.
                el.value = resolveGroupValueAtBreakpoint(classes, group.options, '');
                el.classList?.toggle?.('is-inherited', false);
                el.removeAttribute?.('title');
            } else {
                const exact = resolveGroupValueExact(classes, group.options, bp);
                const cascaded = resolveGroupValueAtBreakpoint(classes, group.options, bp);
                el.value = cascaded;
                const inherited = Boolean(cascaded) && exact !== cascaded;
                el.classList?.toggle?.('is-inherited', inherited);
                if (inherited && options.inheritedHint) {
                    el.title = options.inheritedHint;
                } else if (! inherited && el.removeAttribute) {
                    el.removeAttribute('title');
                }
            }

            el.dispatchEvent(new Event('vb:options-changed', { bubbles: true }));
        }
    }

    syncGradientPosSliders(root, component);
    syncTypographyTextGradient(root, component, editor);

    const fontSelect = root.querySelector('[data-voodbuilder-tw-font-family]');

    if (fontSelect) {
        const id = component?.getId?.();
        const fromAttr = String(component?.getAttributes?.()?.['data-vb-font'] ?? '').trim();
        const fromId = id && editor?.Css?.getIdRule
            ? String(editor.Css.getIdRule(id)?.getStyle?.()?.['font-family'] ?? '').trim()
            : '';
        const familyRaw = String(
            fromAttr
            || component?.getStyle?.({ inline: true })?.['font-family']
            || component?.getStyle?.()?.['font-family']
            || fromId
            || '',
        ).replace(/\s*!important\s*$/i, '').trim();

        const canonical = familyRaw ? cssSafeFontStack(familyRaw) : '';
        const matchedFont = canonical ? findFontByStack(canonical) : null;
        const matchedValue = matchedFont?.stack
            ?? [...fontSelect.options].find((opt) => {
                if (! opt.value || ! canonical) {
                    return false;
                }

                return cssSafeFontStack(opt.value) === canonical
                    || findFontByStack(opt.value)?.stack === matchedFont?.stack;
            })?.value
            ?? '';

        fontSelect.value = matchedValue;

        if (canonical && fontSelect.value === '') {
            const orphan = document.createElement('option');
            orphan.value = canonical;
            orphan.textContent = matchedFont?.family ?? canonical;
            fontSelect.appendChild(orphan);
            fontSelect.value = canonical;
        }

        fontSelect.dispatchEvent(new Event('vb:options-changed', { bubbles: true }));
    }

    syncSpacingBox(root, component, {
        ...options,
        inheritedHint: options.inheritedHint
            ?? 'Inherited from a smaller viewport — change to override here',
    }, editor);
    syncDecorationBlocks(root, component, options, editor);
    syncBackgroundImageField(root, component, editor);
    syncTypographySegments(root, component, editor);

    // Migrate legacy bg-opacity-* / slash classes (Grapes-unsafe) → attr + inline paint.
    try {
        const bg = resolveBackgroundColorAndOpacity(componentClassList(component), component);
        const needsPaint = Boolean(bg.color && bg.opacity);
        const needsMigrate = bg.legacyOpacity && bg.color;

        if ((needsMigrate || needsPaint) && ! editor?.__voodbuilderTwStyleApplying) {
            const wasApplying = Boolean(editor?.__voodbuilderTwStyleApplying);

            if (editor) {
                editor.__voodbuilderTwStyleApplying = true;
            }

            try {
                if (needsMigrate || needsPaint) {
                    applyBackgroundColorWithOpacity(component, bg.color, bg.opacity);
                    paintBackgroundColorOpacity(editor, component, bg.color, bg.opacity);
                    component.view?.updateClasses?.();
                    component.view?.updateStyle?.();
                    scheduleClassCompile(editor);
                }

                const colorEl = root.querySelector?.('[data-voodbuilder-tw-group="background"]');
                const opacityEl = root.querySelector?.('[data-voodbuilder-tw-group="background-opacity"]');

                if (colorEl) {
                    colorEl.value = bg.color;
                    colorEl.dispatchEvent(new Event('vb:options-changed', { bubbles: true }));
                }

                if (opacityEl) {
                    opacityEl.value = bg.opacity;
                    opacityEl.dispatchEvent(new Event('vb:options-changed', { bubbles: true }));
                }
            } finally {
                if (editor && ! wasApplying) {
                    editor.__voodbuilderTwStyleApplying = false;
                }
            }
        }
    } catch {
        // Optional migrate.
    }
}

/**
 * Grapes rarely emits `component:update:classes` for CLASSES chip add/remove/rename
 * (those mutate the selectors collection in place). Watch the collection directly.
 *
 * @param {object|null|undefined} component
 * @param {() => void} onChange
 * @returns {() => void} unsubscribe
 */
export function watchComponentClassList(component, onChange) {
    if (! component || typeof onChange !== 'function') {
        return () => {};
    }

    const classes = component.get?.('classes') ?? component.classes;

    if (! classes || typeof classes.on !== 'function' || typeof classes.off !== 'function') {
        return () => {};
    }

    const handler = () => {
        onChange();
    };

    classes.on('add remove reset change', handler);

    return () => {
        classes.off('add remove reset change', handler);
    };
}

function resolveSolidBackgroundColorCss(utility) {
    const token = String(utility ?? '').trim();

    if (token === 'bg-black') {
        return '#000000';
    }

    if (token === 'bg-white') {
        return '#ffffff';
    }

    if (token === '' || token === 'bg-transparent') {
        return '';
    }

    return hexForUtility(token) || cssColorFromBackgroundUtility(token) || '';
}

/**
 * Paint solid Color opacity via inline/#id (Grapes cannot store `bg-black/60`).
 * Plain `bg-*` stays for the Color field; alpha lives in data-vb-bg-color-opacity.
 */
function paintBackgroundColorOpacity(editor, component, color, opacityPercent) {
    if (! editor || ! component) {
        return;
    }

    const target = resolveVisualStyleTarget(component) ?? component;
    const pct = String(opacityPercent ?? '').trim();
    const base = String(color ?? '').trim();

    if (base === '' || pct === '' || pct === '100') {
        clearStyleProperty(editor, target, 'background-color', { family: false });

        return;
    }

    const hex = resolveSolidBackgroundColorCss(base);
    const alpha = Math.min(1, Math.max(0, Number.parseInt(pct, 10) / 100));
    const painted = hex
        ? (toRgbaWithAlpha(hex, alpha) ?? `color-mix(in oklab, ${hex} ${pct}%, transparent)`)
        : `color-mix(in oklab, currentColor ${pct}%, transparent)`;

    // Neutralize opaque utility paint — inline alpha must win on canvas + frontend.
    target.addStyle?.(
        { 'background-color': painted },
        { inline: true },
    );

    const id = String(target.getId?.() ?? component.getId?.() ?? '').trim();

    if (id && editor.Css?.setIdRule) {
        try {
            const existing = { ...(editor.Css.getIdRule?.(id)?.getStyle?.() ?? {}) };
            editor.Css.setIdRule(id, {
                ...existing,
                'background-color': painted,
            });
        } catch {
            // CssComposer may be unavailable.
        }
    }
}

function applyGroup(editor, component, groupId, value) {
    const groupSet = GROUP_SETS[groupId];

    if (! component) {
        return;
    }

    if (groupId === 'background' || groupId === 'background-opacity') {
        editor.__voodbuilderTwStyleApplying = true;

        try {
            const current = resolveBackgroundColorAndOpacity(componentClassList(component), component);
            const nextColor = groupId === 'background' ? (value || '') : current.color;
            const nextOpacity = groupId === 'background-opacity' ? (value || '') : current.opacity;

            if (nextColor === '') {
                clearBackgroundColorUtilities(component);
                paintBackgroundColorOpacity(editor, component, '', '');
            } else {
                applyBackgroundColorWithOpacity(component, nextColor, nextOpacity);
                paintBackgroundColorOpacity(editor, component, nextColor, nextOpacity);
            }

            // Color clears gradient (exclusive); keep decoration photo.
            if (groupId === 'background' && nextColor !== '') {
                const alsoClear = ['gradient-direction', 'gradient-from', 'gradient-via', 'gradient-to']
                    .map((id) => GROUP_SETS[id])
                    .filter(Boolean);

                for (const set of alsoClear) {
                    replaceClassGroupAllBreakpoints(component, set, null);
                }
            }

            const preservedBgUrl = readBackgroundImageUrl(component, editor);

            if (preservedBgUrl !== '') {
                reapplyDecorationBackgroundPaint(editor, component, preservedBgUrl);
            }

            try {
                component.view?.updateClasses?.();
                component.view?.updateStyle?.();
            } catch {
                // View may be unavailable during bulk updates.
            }

            scheduleClassCompile(editor);
            editor?.trigger?.('update');
            editor?.trigger?.('component:update', component);
        } finally {
            editor.__voodbuilderTwStyleApplying = false;
        }

        return;
    }

    if (groupId === 'text-color') {
        editor.__voodbuilderTwStyleApplying = true;

        try {
            if (value === TEXT_COLOR_GRADIENT_VALUE) {
                ensureTextGradientBase(component);
                clearSolidTextColorUtilities(component);
                clearStyleProperty(editor, component, 'color');
                defaultTextGradientDirection(component);
                ensureTextGradientPaint(editor, component);
            } else if (value) {
                clearTextGradientUtilities(component);
                replaceStyleGroup(component, groupSet, value, editor, { alsoClear: [] });
                clearInlineProps(editor, component, GROUP_INLINE[groupId] ?? []);
            } else if (hasTextGradientClasses(componentClassList(component))) {
                clearTextGradientUtilities(component);
                clearInlineProps(editor, component, GROUP_INLINE[groupId] ?? []);
            } else {
                replaceStyleGroup(component, groupSet, null, editor, { alsoClear: [] });
                clearInlineProps(editor, component, GROUP_INLINE[groupId] ?? []);
            }

            try {
                component.view?.updateClasses?.();
            } catch {
                // View may be unavailable during bulk updates.
            }

            scheduleClassCompile(editor, styleWrittenToken(editor, value));
            editor?.trigger?.('update');
            editor?.trigger?.('component:update', component);
        } finally {
            editor.__voodbuilderTwStyleApplying = false;
        }

        return;
    }

    if (TEXT_GRADIENT_GROUP_IDS.includes(groupId)) {
        if (! groupSet) {
            return;
        }

        editor.__voodbuilderTwStyleApplying = true;

        try {
            ensureTextGradientBase(component);
            clearSolidTextColorUtilities(component);
            clearStyleProperty(editor, component, 'color');

            if (groupId === 'text-gradient-direction' && value && value !== 'bg-none') {
                clearStyleProperty(editor, component, 'background-image');
            }

            if (
                (groupId === 'text-gradient-from' || groupId === 'text-gradient-via' || groupId === 'text-gradient-to')
                && value
            ) {
                clearStyleProperty(editor, component, 'background-image');
            }

            replaceGradientStyleGroup(component, groupSet, value || null);
            clearInlineProps(editor, component, GROUP_INLINE[groupId] ?? []);
            ensureTextGradientPaint(editor, component);

            try {
                component.view?.updateClasses?.();
            } catch {
                // View may be unavailable during bulk updates.
            }

            // Bare token — gradients stay at base (bundled in section-utilities).
            scheduleClassCompile(editor, String(value ?? '').trim());
            editor?.trigger?.('update');
            editor?.trigger?.('component:update', component);
        } finally {
            editor.__voodbuilderTwStyleApplying = false;
        }

        return;
    }

    if (! groupSet) {
        return;
    }

    // Settings → classes writes; class-list watch re-hydrates selects only (no write-back).
    editor.__voodbuilderTwStyleApplying = true;

    try {
        const alsoClearIds = [...utilityConflictGroupIds(groupId)];
        const classes = componentClassList(component);
        const textGradientActive = hasTextGradientClasses(classes);

        // Capture decoration photo before color/gradient writes.
        const isBgPaintGroup = groupId === 'background'
            || groupId === 'gradient-direction'
            || groupId === 'gradient-from'
            || groupId === 'gradient-via'
            || groupId === 'gradient-to'
            || groupId === 'gradient-from-pos'
            || groupId === 'gradient-via-pos'
            || groupId === 'gradient-to-pos';
        const preservedBgUrl = isBgPaintGroup
            ? readBackgroundImageUrl(component, editor)
            : '';
        const preservedBgOpacity = isBgPaintGroup
            ? readBackgroundImageOpacity(component, editor)
            : 1;

        // Solid color clears gradient stops (exclusive); keep image.
        if (groupId === 'background' && value && ! textGradientActive) {
            alsoClearIds.push(
                'gradient-direction',
                'gradient-from',
                'gradient-via',
                'gradient-to',
                'gradient-from-pos',
                'gradient-via-pos',
                'gradient-to-pos',
            );
        }

        // Gradient clears solid color (exclusive); keep image — do not wipe background-image.
        if (
            (groupId === 'gradient-direction' && value && value !== 'bg-none')
            || (
                (groupId === 'gradient-from' || groupId === 'gradient-via' || groupId === 'gradient-to'
                    || groupId === 'gradient-from-pos' || groupId === 'gradient-via-pos' || groupId === 'gradient-to-pos')
                && value
            )
        ) {
            if (! textGradientActive) {
                alsoClearIds.push('background');
            }
        }

        // Clearing Via color also drops its stop position.
        if ((groupId === 'gradient-via' || groupId === 'text-gradient-via') && ! value) {
            alsoClearIds.push(groupId === 'gradient-via' ? 'gradient-via-pos' : 'text-gradient-via-pos');
        }

        if (
            (groupId === 'gradient-direction' || groupId === 'gradient-from' || groupId === 'gradient-via' || groupId === 'gradient-to'
                || groupId === 'gradient-from-pos' || groupId === 'gradient-via-pos' || groupId === 'gradient-to-pos')
            && textGradientActive
        ) {
            ensureTextGradientBase(component);
            clearSolidTextColorUtilities(component);
            clearStyleProperty(editor, component, 'color');
        }

        const alsoClear = alsoClearIds
            .map((id) => GROUP_SETS[id])
            .filter(Boolean);

        if (isGradientStyleGroupId(groupId)) {
            replaceGradientStyleGroup(component, groupSet, value || null, { alsoClear });
        } else {
            replaceStyleGroup(component, groupSet, value || null, editor, { alsoClear });
        }

        // Transparent as From with a high Start % → thin hard band. Reset to 0%.
        if (
            (groupId === 'gradient-from' || groupId === 'text-gradient-from')
            && String(value ?? '').trim().endsWith('-transparent')
        ) {
            const posId = groupId === 'gradient-from' ? 'gradient-from-pos' : 'text-gradient-from-pos';
            const posSet = GROUP_SETS[posId];
            const currentPos = gradientStopPositionFromUtility(
                resolveGroupValueAtBreakpoint(
                    componentClassList(component),
                    GRADIENT_FROM_POS_OPTIONS,
                    'lg:',
                ),
            );

            if (posSet && (currentPos == null || currentPos > 20)) {
                replaceGradientStyleGroup(component, posSet, 'from-0%');
            }
        }
        clearInlineProps(editor, component, GROUP_INLINE[groupId] ?? []);
        // DOM first (realtime), compile only if the utility is missing from canvas CSS.
        try {
            component.view?.updateClasses?.();
        } catch {
            // View may be unavailable during bulk updates.
        }

        // Color / gradient changed: keep photo and recompose layers (gradient + fade + url).
        if (isBgPaintGroup && preservedBgUrl !== '') {
            component.addAttributes?.({
                [STYLE_BG_OPACITY_ATTR]: String(preservedBgOpacity),
                [STYLE_BG_SRC_ATTR]: preservedBgUrl,
            });
            // Ensure src is readable even if CSS was touched.
            if (String(component.getAttributes?.()?.[STYLE_BG_SRC_ATTR] ?? '') !== preservedBgUrl) {
                component.addAttributes?.({ [STYLE_BG_SRC_ATTR]: preservedBgUrl });
            }
            reapplyDecorationBackgroundPaint(editor, component);
        } else if (
            groupId === 'gradient-direction'
            || groupId === 'gradient-from'
            || groupId === 'gradient-via'
            || groupId === 'gradient-to'
            || groupId === 'gradient-from-pos'
            || groupId === 'gradient-via-pos'
            || groupId === 'gradient-to-pos'
        ) {
            // Bake gradient + stop positions for instant canvas feedback.
            paintDecorationGradientPreview(editor, component);
        }

        scheduleClassCompile(
            editor,
            isGradientStyleGroupId(groupId)
                ? String(value ?? '').trim()
                : styleWrittenToken(editor, value),
        );
        // Same dirty signal as CLASSES "+" / other editor mutations.
        editor?.trigger?.('update');
        // Class chips listen to component:update (not plain "update").
        editor?.trigger?.('component:update', component);
    } finally {
        editor.__voodbuilderTwStyleApplying = false;
    }
}

function buildCollapsibleSector({ id, title, bodyHtml }) {
    const sector = document.createElement('div');
    sector.className = `gjs-sm-sector voodbuilder-editor-sm-sector-tw voodbuilder-editor-sm-sector-tw--${id}`;
    sector.dataset.voodbuilderTwSector = id;

    sector.innerHTML = `
        <div class="gjs-sm-title gjs-sm-sector-title" data-voodbuilder-tw-toggle role="button" tabindex="0" aria-expanded="false">
            <span class="voodbuilder-editor-inspector-sector__caret" aria-hidden="true"></span>
            <span class="gjs-sm-sector-label">${escapeHtml(title)}</span>
        </div>
        <div class="gjs-sm-properties voodbuilder-editor-sm-sector-tw__body" hidden>
            ${bodyHtml}
        </div>
    `;

    const toggle = sector.querySelector('[data-voodbuilder-tw-toggle]');
    const body = sector.querySelector('.voodbuilder-editor-sm-sector-tw__body');

    const setOpen = (open) => {
        sector.classList.toggle('gjs-sm-open', open);
        body.hidden = ! open;
        toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    toggle?.addEventListener('click', () => {
        setOpen(! sector.classList.contains('gjs-sm-open'));
    });

    toggle?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            setOpen(! sector.classList.contains('gjs-sm-open'));
        }
    });

    return sector;
}

function bindGroupField(editor, root, groupId, { applyOnChange = false } = {}) {
    const select = root.querySelector(`[data-voodbuilder-tw-group="${groupId}"]`);
    const add = root.querySelector(`[data-voodbuilder-tw-group-add="${groupId}"]`);

    const apply = () => {
        const selected = resolveStyleTarget(editor);

        if (! selected) {
            return;
        }

        applyGroup(editor, selected, groupId, select?.value ?? '');
        syncSelectsFromComponent(root.closest('.gjs-sm-sectors') ?? root, selected, editor);
    };

    add?.addEventListener('click', apply);

    if (applyOnChange) {
        select?.addEventListener('change', apply);
    }
}

function fontOptionsHtml() {
    const options = [
        { value: '', label: '—' },
        ...styleManagerFontOptions().map((opt) => ({
            value: String(opt.id ?? opt.value ?? ''),
            label: String(opt.label ?? opt.name ?? opt.id ?? ''),
        })).filter((opt) => opt.value !== ''),
    ];

    return optionsHtml(options);
}

function buildDimensionSector(labels, addLabel) {
    return buildCollapsibleSector({
        id: 'dimension',
        title: labels.classStyleDimensionTitle ?? 'Dimension',
        bodyHtml: `
            ${fieldHtml({ label: labels.classStyleWidth ?? 'Width', selectAttr: 'data-voodbuilder-tw-group="width"', addAttr: 'data-voodbuilder-tw-group-add="width"', options: WIDTH_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleHeight ?? 'Height', selectAttr: 'data-voodbuilder-tw-group="height"', addAttr: 'data-voodbuilder-tw-group-add="height"', options: HEIGHT_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleMaxWidth ?? 'Max width', selectAttr: 'data-voodbuilder-tw-group="max-width"', addAttr: 'data-voodbuilder-tw-group-add="max-width"', options: MAX_WIDTH_OPTIONS, addLabel })}
        `,
    });
}

function buildSpacingSector(labels) {
    return buildCollapsibleSector({
        id: 'spacing',
        title: labels.classStyleSpacingTitle ?? 'Spacing',
        bodyHtml: spacingBoxHtml(labels),
    });
}

function decoLiveFieldHtml({ label, groupId, options, searchPlaceholder = null, ariaLabel = null }) {
    return fieldHtml({
        label,
        ariaLabel,
        selectAttr: `data-voodbuilder-tw-group="${groupId}"`,
        addAttr: `data-voodbuilder-tw-group-add="${groupId}"`,
        options,
        addLabel: '',
        searchable: Boolean(searchPlaceholder),
        searchPlaceholder: searchPlaceholder || 'Search…',
        live: true,
    });
}

function decoSideSelectHtml(groupId, options, title) {
    return `
        <label class="voodbuilder-editor-deco-sides__cell" title="${escapeAttr(title)}">
            <span class="voodbuilder-editor-deco-sides__cap">${escapeHtml(title)}</span>
            <select class="voodbuilder-editor-input voodbuilder-editor-input--select" data-voodbuilder-tw-group="${escapeAttr(groupId)}" aria-label="${escapeAttr(title)}">
                ${optionsHtml(options)}
            </select>
        </label>
    `;
}

/**
 * Color stop + position slider (Tailwind from-40% / via-50% / to-90%).
 *
 * @param {{
 *   colorLabel: string,
 *   colorGroupId: string,
 *   colorOptions: Array<{value: string, label?: string, hex?: string}>,
 *   posGroupId: string,
 *   kind: 'from'|'via'|'to',
 *   defaultPos: number,
 *   searchPlaceholder?: string,
 *   posLabel?: string,
 * }} opts
 */
function gradientStopRowHtml(opts) {
    const posLabel = opts.posLabel ?? 'Stop';
    const kind = opts.kind;
    const defaultPos = opts.defaultPos ?? GRADIENT_POS_DEFAULTS[kind] ?? 0;

    return `
        <div class="voodbuilder-editor-deco-stop" data-voodbuilder-deco-stop="${escapeAttr(kind)}" data-voodbuilder-deco-stop-scope="${escapeAttr(opts.colorGroupId)}">
            ${decoLiveFieldHtml({
                label: opts.colorLabel,
                groupId: opts.colorGroupId,
                options: opts.colorOptions,
                searchPlaceholder: opts.searchPlaceholder ?? null,
            })}
            <div class="voodbuilder-editor-deco-stop__pos">
                <span class="voodbuilder-editor-deco-stop__pos-label">${escapeHtml(posLabel)}</span>
                <input
                    type="range"
                    class="voodbuilder-editor-deco-stop__range"
                    min="0"
                    max="100"
                    step="5"
                    value="${defaultPos}"
                    data-voodbuilder-tw-group-range="${escapeAttr(opts.posGroupId)}"
                    data-voodbuilder-gradient-pos-kind="${escapeAttr(kind)}"
                    data-voodbuilder-gradient-pos-default="${defaultPos}"
                    aria-label="${escapeAttr(posLabel)}"
                />
                <span class="voodbuilder-editor-deco-stop__pos-value" data-voodbuilder-gradient-pos-readout>${defaultPos}%</span>
            </div>
        </div>
    `;
}

function gradientStopsBlockHtml(prefix, labels, searchPh) {
    const hint = labels.classStyleGradientStopHint
        ?? 'Soft fade: drag Start to ~0% (not 90%), End ~100%, Via empty. Otherwise the color becomes a hard thin band.';

    return `
        <div class="voodbuilder-editor-deco-stops">
            <p class="voodbuilder-editor-deco-stops__hint">${escapeHtml(hint)}</p>
            ${gradientStopRowHtml({
                colorLabel: labels.classStyleGradientFrom ?? 'From',
                colorGroupId: `${prefix}-from`,
                colorOptions: GRADIENT_FROM_OPTIONS,
                posGroupId: `${prefix}-from-pos`,
                kind: 'from',
                defaultPos: GRADIENT_POS_DEFAULTS.from,
                searchPlaceholder: searchPh,
                posLabel: labels.classStyleGradientStopFrom ?? 'Start',
            })}
            ${gradientStopRowHtml({
                colorLabel: labels.classStyleGradientVia ?? 'Via',
                colorGroupId: `${prefix}-via`,
                colorOptions: GRADIENT_VIA_OPTIONS,
                posGroupId: `${prefix}-via-pos`,
                kind: 'via',
                defaultPos: GRADIENT_POS_DEFAULTS.via,
                searchPlaceholder: searchPh,
                posLabel: labels.classStyleGradientStopVia ?? 'Middle',
            })}
            ${gradientStopRowHtml({
                colorLabel: labels.classStyleGradientTo ?? 'To',
                colorGroupId: `${prefix}-to`,
                colorOptions: GRADIENT_TO_OPTIONS,
                posGroupId: `${prefix}-to-pos`,
                kind: 'to',
                defaultPos: GRADIENT_POS_DEFAULTS.to,
                searchPlaceholder: searchPh,
                posLabel: labels.classStyleGradientStopTo ?? 'End',
            })}
        </div>
    `;
}

function buildDecorationsSector(labels) {
    const searchPh = labels.classStyleFieldSearch ?? 'Search…';
    const clearLabel = labels.classStyleClear ?? 'None';
    const linkAll = labels.classStyleSpacingLinkAll ?? 'All sides linked';
    const linkSides = labels.classStyleDecorationsLinkSides ?? 'Per side';
    const linkCorners = labels.classStyleDecorationsLinkCorners ?? 'Per corner';
    const gradientLabel = labels.classStyleGradient ?? 'Gradient';

    return buildCollapsibleSector({
        id: 'decorations',
        title: labels.classStyleDecorationsTitle ?? 'Decorations',
        bodyHtml: `
            <div class="voodbuilder-editor-deco" data-voodbuilder-decorations>
                <div class="voodbuilder-editor-deco-block" data-voodbuilder-deco-block="background">
                    <div class="voodbuilder-editor-deco-block__head">
                        <span class="voodbuilder-editor-deco-block__label">${escapeHtml(labels.classStyleBackground ?? 'Background')}</span>
                        <span class="voodbuilder-editor-deco-block__dot" data-voodbuilder-deco-dot="background" hidden title="${escapeAttr(labels.classStyleAuthoredHint ?? 'Value set')}" aria-hidden="true"></span>
                    </div>
                    ${decoLiveFieldHtml({
                        label: labels.classStyleBackgroundColor ?? 'Color',
                        groupId: 'background',
                        options: BACKGROUND_OPTIONS,
                        searchPlaceholder: searchPh,
                    })}
                    ${decoLiveFieldHtml({
                        label: labels.classStyleBackgroundColorOpacity ?? 'Opacity',
                        groupId: 'background-opacity',
                        options: BG_COLOR_OPACITY_OPTIONS,
                    })}
                    <details class="voodbuilder-editor-deco-fold" data-voodbuilder-deco-fold="image">
                        <summary class="voodbuilder-editor-deco-fold__summary">
                            <span>${escapeHtml(labels.classStyleBackgroundImage ?? 'Image')}</span>
                        </summary>
                        <div class="voodbuilder-editor-deco-fold__body">
                            <div class="voodbuilder-editor-deco-bg-image" data-voodbuilder-deco-bg-image></div>
                            <div class="voodbuilder-editor-deco-bg-opacity" data-voodbuilder-deco-bg-opacity></div>
                            ${decoLiveFieldHtml({
                                label: labels.classStyleBackgroundSize ?? 'Size',
                                groupId: 'bg-size',
                                options: BG_SIZE_OPTIONS,
                            })}
                            ${decoLiveFieldHtml({
                                label: labels.classStyleBackgroundPosition ?? 'Position',
                                groupId: 'bg-position',
                                options: BG_POSITION_OPTIONS,
                            })}
                            ${decoLiveFieldHtml({
                                label: labels.classStyleBackgroundRepeat ?? 'Repeat',
                                groupId: 'bg-repeat',
                                options: BG_REPEAT_OPTIONS,
                            })}
                        </div>
                    </details>
                    <details class="voodbuilder-editor-deco-fold" data-voodbuilder-deco-fold="gradient">
                        <summary class="voodbuilder-editor-deco-fold__summary">
                            <span>${escapeHtml(gradientLabel)}</span>
                            <span class="voodbuilder-editor-deco-block__dot" data-voodbuilder-deco-dot="gradient" hidden title="${escapeAttr(labels.classStyleAuthoredHint ?? 'Value set')}" aria-hidden="true"></span>
                        </summary>
                        <div class="voodbuilder-editor-deco-fold__body">
                            ${decoLiveFieldHtml({
                                label: labels.classStyleGradientDir ?? 'Direction',
                                groupId: 'gradient-direction',
                                options: GRADIENT_DIRECTION_OPTIONS,
                            })}
                            ${gradientStopsBlockHtml('gradient', labels, searchPh)}
                        </div>
                    </details>
                </div>

                <div class="voodbuilder-editor-deco-block" data-voodbuilder-deco-block="border" data-link="all">
                    <div class="voodbuilder-editor-deco-block__head">
                        <span class="voodbuilder-editor-deco-block__label">${escapeHtml(labels.classStyleBorder ?? 'Border')}</span>
                        <span class="voodbuilder-editor-deco-block__dot" data-voodbuilder-deco-dot="border" hidden title="${escapeAttr(labels.classStyleAuthoredHint ?? 'Value set')}" aria-hidden="true"></span>
                        <div class="voodbuilder-editor-deco-block__links" role="group" aria-label="${escapeAttr(labels.classStyleBorderWidth ?? 'Border width')}">
                            <button type="button" class="voodbuilder-editor-deco-block__link is-active" data-voodbuilder-deco-link="all" title="${escapeAttr(linkAll)}" aria-pressed="true">
                                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M4.5 2.5a2.5 2.5 0 0 1 3.5 0l.7.7-.7.7a1.5 1.5 0 1 0 0 2.1l.7.7-.7.7a2.5 2.5 0 1 1-3.5-3.5l.7-.7-.7-.7zm7 7a2.5 2.5 0 0 1-3.5 0l-.7-.7.7-.7a1.5 1.5 0 1 0 0-2.1l-.7-.7.7-.7a2.5 2.5 0 1 1 3.5 3.5l-.7.7.7.7z"/></svg>
                            </button>
                            <button type="button" class="voodbuilder-editor-deco-block__link" data-voodbuilder-deco-link="sides" title="${escapeAttr(linkSides)}" aria-pressed="false">
                                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M4 2h8v2H4V2zm0 10h8v2H4v-2zM2.5 6.5h3v3h-3v-3zm8 0h3v3h-3v-3z"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="voodbuilder-editor-deco-border-all" data-voodbuilder-deco-border-mode="all">
                        ${decoLiveFieldHtml({
                            label: labels.classStyleBorderWidth ?? 'Width',
                            groupId: 'border-width',
                            options: BORDER_WIDTH_OPTIONS,
                        })}
                    </div>
                    <div class="voodbuilder-editor-deco-sides" data-voodbuilder-deco-border-mode="sides" hidden>
                        ${decoSideSelectHtml('border-t-width', BORDER_T_WIDTH_OPTIONS, labels.classStyleBorderT ?? 'Top')}
                        ${decoSideSelectHtml('border-l-width', BORDER_L_WIDTH_OPTIONS, labels.classStyleBorderL ?? 'Left')}
                        ${decoSideSelectHtml('border-r-width', BORDER_R_WIDTH_OPTIONS, labels.classStyleBorderR ?? 'Right')}
                        ${decoSideSelectHtml('border-b-width', BORDER_B_WIDTH_OPTIONS, labels.classStyleBorderB ?? 'Bottom')}
                    </div>
                    ${segmentControlHtml({
                        label: labels.classStyleBorderStyle ?? 'Style',
                        groupId: 'border-style',
                        segments: BORDER_STYLE_SEGMENTS,
                        clearLabel,
                        authoredHint: labels.classStyleAuthoredHint ?? 'Value set',
                    })}
                    ${decoLiveFieldHtml({
                        label: labels.classStyleBorderColor ?? 'Color',
                        groupId: 'border-color',
                        options: BORDER_COLOR_OPTIONS,
                        searchPlaceholder: searchPh,
                    })}
                </div>

                <div class="voodbuilder-editor-deco-block" data-voodbuilder-deco-block="radius" data-link="all">
                    <div class="voodbuilder-editor-deco-block__head">
                        <span class="voodbuilder-editor-deco-block__label">${escapeHtml(labels.classStyleRounded ?? 'Rounded')}</span>
                        <span class="voodbuilder-editor-deco-block__dot" data-voodbuilder-deco-dot="radius" hidden title="${escapeAttr(labels.classStyleAuthoredHint ?? 'Value set')}" aria-hidden="true"></span>
                        <div class="voodbuilder-editor-deco-block__links" role="group" aria-label="${escapeAttr(labels.classStyleRounded ?? 'Rounded')}">
                            <button type="button" class="voodbuilder-editor-deco-block__link is-active" data-voodbuilder-deco-link="all" title="${escapeAttr(linkAll)}" aria-pressed="true">
                                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M4.5 2.5a2.5 2.5 0 0 1 3.5 0l.7.7-.7.7a1.5 1.5 0 1 0 0 2.1l.7.7-.7.7a2.5 2.5 0 1 1-3.5-3.5l.7-.7-.7-.7zm7 7a2.5 2.5 0 0 1-3.5 0l-.7-.7.7-.7a1.5 1.5 0 1 0 0-2.1l-.7-.7.7-.7a2.5 2.5 0 1 1 3.5 3.5l-.7.7.7.7z"/></svg>
                            </button>
                            <button type="button" class="voodbuilder-editor-deco-block__link" data-voodbuilder-deco-link="corners" title="${escapeAttr(linkCorners)}" aria-pressed="false">
                                <svg viewBox="0 0 16 16" width="14" height="14" aria-hidden="true"><path fill="currentColor" d="M3 3h4v2H5v2H3V3zm6 0h4v4h-2V5H9V3zM3 9h2v2h2v2H3V9zm8 2h2v2H9v-2h2z"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="voodbuilder-editor-deco-radius-all" data-voodbuilder-deco-radius-mode="all">
                        ${decoLiveFieldHtml({
                            // Blank on purpose: the block heading above reads as this
                            // field's label, so repeating it would just add noise.
                            label: '',
                            ariaLabel: labels.classStyleRounded ?? 'Rounded',
                            groupId: 'rounded',
                            options: ROUNDED_OPTIONS,
                        })}
                    </div>
                    <div class="voodbuilder-editor-deco-corners" data-voodbuilder-deco-radius-mode="corners" hidden>
                        ${decoSideSelectHtml('rounded-tl', ROUNDED_TL_OPTIONS, labels.classStyleRoundedTl ?? 'TL')}
                        ${decoSideSelectHtml('rounded-tr', ROUNDED_TR_OPTIONS, labels.classStyleRoundedTr ?? 'TR')}
                        ${decoSideSelectHtml('rounded-bl', ROUNDED_BL_OPTIONS, labels.classStyleRoundedBl ?? 'BL')}
                        ${decoSideSelectHtml('rounded-br', ROUNDED_BR_OPTIONS, labels.classStyleRoundedBr ?? 'BR')}
                    </div>
                </div>

                <div class="voodbuilder-editor-deco-block" data-voodbuilder-deco-block="shadow">
                    <div class="voodbuilder-editor-deco-block__head">
                        <span class="voodbuilder-editor-deco-block__label">${escapeHtml(labels.classStyleShadow ?? 'Shadow')}</span>
                        <span class="voodbuilder-editor-deco-block__dot" data-voodbuilder-deco-dot="shadow" hidden title="${escapeAttr(labels.classStyleAuthoredHint ?? 'Value set')}" aria-hidden="true"></span>
                    </div>
                    ${decoLiveFieldHtml({
                        label: labels.classStyleShadowSize ?? 'Size',
                        groupId: 'shadow',
                        options: SHADOW_OPTIONS,
                    })}
                    ${decoLiveFieldHtml({
                        label: labels.classStyleShadowColor ?? 'Color',
                        groupId: 'shadow-color',
                        options: SHADOW_COLOR_OPTIONS,
                        searchPlaceholder: searchPh,
                    })}
                    ${decoLiveFieldHtml({
                        label: labels.classStyleDropShadow ?? 'Drop shadow',
                        groupId: 'drop-shadow',
                        options: DROP_SHADOW_OPTIONS,
                    })}
                </div>
            </div>
        `,
    });
}

function buildTypographySector(labels, addLabel) {
    const clearLabel = labels.classStyleClear ?? 'None';
    const searchPh = labels.classStyleFieldSearch ?? 'Search…';

    return buildCollapsibleSector({
        id: 'typography',
        title: labels.classStyleTypographyTitle ?? 'Typography',
        bodyHtml: `
            <div class="gjs-sm-property voodbuilder-editor-anim-property voodbuilder-editor-anim-property--live">
                <div class="gjs-sm-label"><span class="gjs-sm-label-text">${escapeHtml(labels.classStyleFontFamily ?? 'Font family')}</span></div>
                <div class="gjs-fields">
                    <div class="voodbuilder-editor-anim-combobox voodbuilder-editor-anim-combobox--solo">
                        <select class="voodbuilder-editor-input voodbuilder-editor-input--select" data-voodbuilder-tw-font-family data-vb-font-search="1" data-vb-font-search-placeholder="${escapeAttr(labels.classStyleFontSearch ?? 'Search fonts…')}">
                            ${fontOptionsHtml()}
                        </select>
                    </div>
                </div>
            </div>
            ${fieldHtml({ label: labels.classStyleFontSize ?? 'Font size', selectAttr: 'data-voodbuilder-tw-group="font-size"', addAttr: 'data-voodbuilder-tw-group-add="font-size"', options: FONT_SIZE_OPTIONS, addLabel, live: true })}
            ${fieldHtml({ label: labels.classStyleFontWeight ?? 'Font weight', selectAttr: 'data-voodbuilder-tw-group="font-weight"', addAttr: 'data-voodbuilder-tw-group-add="font-weight"', options: FONT_WEIGHT_OPTIONS, addLabel, live: true })}
            ${segmentControlHtml({ label: labels.classStyleTextAlign ?? 'Text align', groupId: 'text-align', segments: TEXT_ALIGN_SEGMENTS, clearLabel, authoredHint: labels.classStyleAuthoredHint ?? 'Value set' })}
            ${segmentControlHtml({ label: labels.classStyleTextTransform ?? 'Text transform', groupId: 'text-transform', segments: TEXT_TRANSFORM_SEGMENTS, clearLabel, authoredHint: labels.classStyleAuthoredHint ?? 'Value set' })}
            ${segmentControlHtml({ label: labels.classStyleTextDecoration ?? 'Text decoration', groupId: 'text-decoration', segments: TEXT_DECORATION_SEGMENTS, clearLabel, authoredHint: labels.classStyleAuthoredHint ?? 'Value set' })}
            ${fieldHtml({ label: labels.classStyleTextColor ?? 'Text color', selectAttr: 'data-voodbuilder-tw-group="text-color"', addAttr: 'data-voodbuilder-tw-group-add="text-color"', options: TEXT_COLOR_OPTIONS, addLabel, searchable: true, searchPlaceholder: searchPh, live: true })}
            <details class="voodbuilder-editor-deco-fold voodbuilder-editor-typo-gradient" data-voodbuilder-typo-fold="text-gradient" hidden>
                <summary class="voodbuilder-editor-deco-fold__summary">
                    <span>${escapeHtml(labels.classStyleGradient ?? 'Gradient')}</span>
                    <span class="voodbuilder-editor-deco-block__dot" data-voodbuilder-typo-dot="text-gradient" hidden title="${escapeAttr(labels.classStyleAuthoredHint ?? 'Value set')}" aria-hidden="true"></span>
                </summary>
                <div class="voodbuilder-editor-deco-fold__body">
                    ${decoLiveFieldHtml({
                        label: labels.classStyleGradientDir ?? 'Direction',
                        groupId: 'text-gradient-direction',
                        options: GRADIENT_DIRECTION_OPTIONS,
                    })}
                    ${gradientStopsBlockHtml('text-gradient', labels, searchPh)}
                </div>
            </details>
            ${fieldHtml({ label: labels.classStyleLeading ?? 'Line height', selectAttr: 'data-voodbuilder-tw-group="leading"', addAttr: 'data-voodbuilder-tw-group-add="leading"', options: LEADING_OPTIONS, addLabel, live: true })}
            ${fieldHtml({ label: labels.classStyleTracking ?? 'Letter spacing', selectAttr: 'data-voodbuilder-tw-group="tracking"', addAttr: 'data-voodbuilder-tw-group-add="tracking"', options: TRACKING_OPTIONS, addLabel, live: true })}
        `,
    });
}

const PANEL_SEGMENT_GROUPS = {
    'text-align': TEXT_ALIGN_OPTIONS,
    'text-transform': TEXT_TRANSFORM_OPTIONS,
    'text-decoration': TEXT_DECORATION_OPTIONS,
    'border-style': BORDER_STYLE_OPTIONS,
};

function syncTypographyTextGradient(root, component, editor = null) {
    const classes = componentClassList(component);
    const isGradient = hasTextGradientClasses(classes);
    const fold = root.querySelector('[data-voodbuilder-typo-fold="text-gradient"]');

    if (fold) {
        fold.hidden = ! isGradient;

        if (isGradient && String(fold.tagName ?? '').toUpperCase() === 'DETAILS') {
            fold.open = true;
        }
    }

    const gradDir = resolveStyleGroup(classes, GRADIENT_DIRECTION_OPTIONS, editor);
    const gradFrom = resolveStyleGroup(classes, GRADIENT_FROM_OPTIONS, editor);
    const gradVia = resolveStyleGroup(classes, GRADIENT_VIA_OPTIONS, editor);
    const gradTo = resolveStyleGroup(classes, GRADIENT_TO_OPTIONS, editor);
    const hasStops = Boolean(
        (gradDir && gradDir !== 'bg-none')
        || gradFrom
        || gradVia
        || gradTo,
    );

    const dot = root.querySelector('[data-voodbuilder-typo-dot="text-gradient"]');

    if (dot) {
        dot.hidden = ! hasStops;
    }

    if (isGradient && editor && ! editor.__voodbuilderTwStyleApplying) {
        const wasApplying = Boolean(editor.__voodbuilderTwStyleApplying);
        editor.__voodbuilderTwStyleApplying = true;

        try {
            ensureTextGradientPaint(editor, component);
        } finally {
            if (! wasApplying) {
                editor.__voodbuilderTwStyleApplying = false;
            }
        }
    }
}

function syncTypographySegments(root, component, editor = null) {
    const classes = componentClassList(component);

    for (const [groupId, options] of Object.entries(PANEL_SEGMENT_GROUPS)) {
        const value = resolveStyleGroup(classes, options, editor);
        const wrap = root.querySelector(`[data-voodbuilder-typo-seg="${groupId}"]`);

        if (! wrap) {
            continue;
        }

        const isTypoNone = value === 'normal-case' || value === 'no-underline';
        const activeValue = groupId !== 'border-style' && isTypoNone ? '' : value;
        const hasAuthored = groupId === 'border-style'
            ? Boolean(value) && value !== 'border-none'
            : Boolean(value) && ! isTypoNone;

        wrap.querySelectorAll('[data-voodbuilder-tw-segment]').forEach((button) => {
            const buttonValue = button.getAttribute('data-value') ?? '';
            const pressed = buttonValue === activeValue;

            button.classList.toggle('is-active', pressed);
            button.setAttribute('aria-pressed', pressed ? 'true' : 'false');
        });

        const dot = wrap.querySelector('[data-voodbuilder-typo-dot]');

        if (dot) {
            dot.hidden = ! hasAuthored;
        }
    }
}

function wireTypographySegments(editor, sector) {
    sector.querySelectorAll('[data-voodbuilder-tw-segment]').forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const component = editor.getSelected();
            const groupId = button.getAttribute('data-voodbuilder-tw-segment');
            let value = button.getAttribute('data-value') ?? '';

            if (! component || ! groupId) {
                return;
            }

            const current = resolveStyleGroup(componentClassList(component), PANEL_SEGMENT_GROUPS[groupId] ?? [], editor);

            // Toggle off when re-clicking the active utility.
            if (value !== '' && value === current) {
                value = '';
            }

            applyGroup(editor, component, groupId, value);
            syncSelectsFromComponent(sector.closest('.gjs-sm-sectors') ?? sector, component, editor);
        });
    });
}

function setDecoLinkButtons(block, link) {
    if (! block) {
        return;
    }

    block.dataset.link = link;

    for (const button of block.querySelectorAll('[data-voodbuilder-deco-link]')) {
        const active = button.getAttribute('data-voodbuilder-deco-link') === link;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
    }
}

function readComponentCssProperty(editor, component, property) {
    const inline = component?.getStyle?.({ inline: true }) ?? {};
    const live = component?.getStyle?.() ?? {};
    const id = component?.getId?.();
    const fromId = id && editor?.Css?.getIdRule
        ? (editor.Css.getIdRule(id)?.getStyle?.() ?? {})
        : {};
    const raw = String(
        inline[property]
        ?? live[property]
        ?? fromId[property]
        ?? '',
    ).replace(/\s*!important\s*$/i, '').trim();

    return raw;
}

function readBackgroundImageUrl(component, editor = null) {
    if (! component) {
        return '';
    }

    const target = resolveVisualStyleTarget(component) ?? component;
    const nodes = target === component ? [component] : [component, target];

    // Durable UI reference — must win over stale composed CSS (old photo under Clear / reselect).
    for (const node of nodes) {
        const fromAttr = String(node?.getAttributes?.()?.[STYLE_BG_SRC_ATTR] ?? '').trim();

        if (fromAttr !== '') {
            return fromAttr;
        }
    }

    for (const node of nodes) {
        const fromImage = extractUrlFromBackgroundImage(
            readComponentCssProperty(editor, node, 'background-image'),
        );

        if (fromImage !== '') {
            return fromImage;
        }

        // Shorthand `background: url(...) …` (legacy SM / imported CSS).
        const fromShorthand = extractUrlFromBackgroundImage(
            readComponentCssProperty(editor, node, 'background'),
        );

        if (fromShorthand !== '') {
            return fromShorthand;
        }

        // Raw style="" attribute Grapes may not mirror into getStyle().
        const rawStyle = String(node?.getAttributes?.()?.style ?? '');

        if (rawStyle !== '') {
            const fromRaw = extractUrlFromBackgroundImage(rawStyle);

            if (fromRaw !== '') {
                return fromRaw;
            }
        }
    }

    // Last resort: what the canvas actually paints (live #id / computed).
    for (const node of nodes) {
        try {
            const el = node?.getEl?.() ?? node?.view?.el;
            const view = el?.ownerDocument?.defaultView;

            if (el && view?.getComputedStyle) {
                const found = extractUrlFromBackgroundImage(
                    view.getComputedStyle(el).backgroundImage ?? '',
                );

                if (found !== '') {
                    return found;
                }
            }
        } catch {
            // Frame may be unavailable.
        }
    }

    return '';
}

function persistBackgroundImageSrcAttr(component, url) {
    if (! component) {
        return;
    }

    const src = String(url ?? '').trim();
    const target = resolveVisualStyleTarget(component) ?? component;
    const nodes = target === component ? [component] : [component, target];

    for (const node of nodes) {
        if (src === '') {
            try {
                node.removeAttributes?.(STYLE_BG_SRC_ATTR);
            } catch {
                const attrs = { ...(node.getAttributes?.() ?? {}) };
                delete attrs[STYLE_BG_SRC_ATTR];
                node.setAttributes?.(attrs);
            }

            continue;
        }

        const current = String(node.getAttributes?.()?.[STYLE_BG_SRC_ATTR] ?? '').trim();

        if (current !== src) {
            node.addAttributes?.({ [STYLE_BG_SRC_ATTR]: src });
        }
    }
}

/**
 * Remove background-image for #id from the stored live page CSS string so Save
 * cannot resurrect a cleared photo.
 */
function scrubBackgroundImageFromPageLiveCss(editor, componentId) {
    const id = String(componentId ?? '').trim();
    const live = String(editor?.__voodbuilderPageLiveCss ?? '');

    if (! editor || id === '' || live === '' || ! live.includes(`#${id}`)) {
        return;
    }

    const safeId = id.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const next = live
        .replace(new RegExp(`#${safeId}\\s*\\{([^}]*)\\}`, 'gi'), (match, body) => {
            let cleaned = String(body)
                .replace(/\bbackground-image\s*:\s*[^;]+;?/gi, '')
                .replace(/\bbackground\s*:\s*[^;]*url\s*\([^)]*\)[^;]*;?/gi, '')
                .replace(/;\s*;/g, ';')
                .trim()
                .replace(/^;|;$/g, '')
                .trim();

            if (cleaned === '') {
                return ' ';
            }

            return `#${id}{${cleaned}}`;
        })
        .replace(/\n{3,}/g, '\n\n')
        .trim();

    if (next !== live.trim()) {
        editor.__voodbuilderApplyPageLiveCss?.(next);
    }
}

function clearDecorationBackgroundImage(editor, component) {
    if (! editor || ! component) {
        return;
    }

    const id = String(component.getId?.() ?? '').trim();
    const target = resolveVisualStyleTarget(component) ?? component;

    // Drop durable reference first so reapply / sync cannot resurrect the photo.
    persistBackgroundImageSrcAttr(component, '');

    try {
        component.removeAttributes?.(STYLE_BG_OPACITY_ATTR);
    } catch {
        const attrs = { ...(component.getAttributes?.() ?? {}) };
        delete attrs[STYLE_BG_OPACITY_ATTR];
        component.setAttributes?.(attrs);
    }

    // Wipe image paint from inline + CssComposer #id / private rules.
    clearStyleProperty(editor, target, 'background-image', { family: false });

    const shorthand = readComponentCssProperty(editor, target, 'background');

    if (/url\s*\(/i.test(shorthand)) {
        clearStyleProperty(editor, target, 'background', { family: false });
    }

    if (id && editor.Css?.getIdRule) {
        const rule = editor.Css.getIdRule(id);

        if (rule) {
            const style = { ...(rule.getStyle?.() ?? {}) };
            let changed = false;

            for (const key of Object.keys(style)) {
                if (key === 'background-image' || key === 'background') {
                    delete style[key];
                    changed = true;
                }
            }

            if (changed) {
                if (Object.keys(style).length === 0) {
                    try {
                        editor.Css.remove?.(rule);
                    } catch {
                        rule.setStyle?.({});
                    }
                } else {
                    rule.setStyle?.(style);
                }
            }
        }
    }

    scrubBackgroundImageFromPageLiveCss(editor, id);

    replaceClassGroup(component, GROUP_SETS['bg-size'], null);
    replaceClassGroup(component, GROUP_SETS['bg-position'], null);
    replaceClassGroup(component, GROUP_SETS['bg-repeat'], null);

    // Force canvas DOM — Grapes model clear can lag behind painted el.style.
    try {
        const el = target?.getEl?.() ?? target?.view?.el ?? component?.getEl?.() ?? component?.view?.el;

        if (el?.style) {
            el.style.removeProperty?.('background-image');
            el.style.backgroundImage = '';

            if (/url\s*\(/i.test(String(el.style.background ?? ''))) {
                el.style.removeProperty?.('background');
                el.style.background = '';
            }
        }
    } catch {
        // Frame may be unavailable.
    }

    // Never re-write background-image for gradient-only: TW `.bg-gradient-to-*`
    // must own that property. An author inline/#id paint (even a gradient layer)
    // after Clear left utilities dead on the canvas.
    restoreSolidBackgroundColorAfterImageClear(editor, component);

    try {
        component.view?.updateStyle?.();
        component.view?.updateAttributes?.();
        target.view?.updateStyles?.();
        component.view?.updateClasses?.();
    } catch {
        // View may be unavailable.
    }
}

function readBackgroundImageOpacity(component, editor = null) {
    const attrs = component?.getAttributes?.() ?? {};
    const fromAttr = attrs[STYLE_BG_OPACITY_ATTR];

    if (fromAttr != null && String(fromAttr).trim() !== '') {
        return normalizeBackgroundImageOpacity(fromAttr);
    }

    const inferred = inferBackgroundImageOpacityFromCss(
        readComponentCssProperty(editor, component, 'background-image'),
    );

    return inferred == null ? 1 : inferred;
}

function resolveDecorationGradientLayer(component, photoVisibility = null) {
    const classes = componentClassList(component);
    // Cascade lg→md→base so leftover responsive stops (pre-fix) still resolve.
    const direction = resolveGroupValueAtBreakpoint(classes, GRADIENT_DIRECTION_OPTIONS, 'lg:');

    if (! direction || direction === 'bg-none') {
        return '';
    }

    const fromUtility = resolveGroupValueAtBreakpoint(classes, GRADIENT_FROM_OPTIONS, 'lg:');
    const viaUtility = resolveGroupValueAtBreakpoint(classes, GRADIENT_VIA_OPTIONS, 'lg:');
    const toUtility = resolveGroupValueAtBreakpoint(classes, GRADIENT_TO_OPTIONS, 'lg:');
    const fromPos = gradientStopPositionFromUtility(
        resolveGroupValueAtBreakpoint(classes, GRADIENT_FROM_POS_OPTIONS, 'lg:'),
    );
    const viaPos = gradientStopPositionFromUtility(
        resolveGroupValueAtBreakpoint(classes, GRADIENT_VIA_POS_OPTIONS, 'lg:'),
    );
    const toPos = gradientStopPositionFromUtility(
        resolveGroupValueAtBreakpoint(classes, GRADIENT_TO_POS_OPTIONS, 'lg:'),
    );

    // Bake stops + positions for live canvas (TW from-25% was missing from
    // section-utilities when options were template-built). Same path with/without photo.
    if (fromUtility || viaUtility || toUtility) {
        return composePhotoAwareGradientLayer({
            directionUtility: direction,
            fromUtility,
            viaUtility,
            toUtility,
            fromPos,
            viaPos,
            toPos,
            photoVisibility: photoVisibility == null ? 1 : photoVisibility,
        });
    }

    return composeTailwindGradientLayer(direction);
}

/**
 * Live-paint decoration gradient (with stop positions) when there is no photo.
 * Photo path uses {@link reapplyDecorationBackgroundPaint} instead.
 */
function paintDecorationGradientPreview(editor, component) {
    if (! editor || ! component) {
        return;
    }

    const src = readBackgroundImageUrl(component, editor);

    if (src !== '') {
        reapplyDecorationBackgroundPaint(editor, component, src);

        return;
    }

    const layer = resolveDecorationGradientLayer(component, 1);

    if (! layer) {
        releaseAuthorBackgroundImageForUtilities(editor, component);

        return;
    }

    const target = resolveVisualStyleTarget(component) ?? component;

    target.addStyle?.(
        { 'background-image': layer },
        { inline: true },
    );

    const id = String(target.getId?.() ?? component.getId?.() ?? '').trim();

    if (id && editor.Css?.setIdRule) {
        try {
            const existing = { ...(editor.Css.getIdRule?.(id)?.getStyle?.() ?? {}) };
            editor.Css.setIdRule(id, {
                ...existing,
                'background-image': layer,
            });
        } catch {
            // CssComposer may be unavailable.
        }
    }

    try {
        const el = target?.getEl?.() ?? target?.view?.el;

        if (el?.style) {
            el.style.backgroundImage = layer;
        }

        component.view?.updateStyle?.();
        target.view?.updateStyles?.();
    } catch {
        // Frame may be unavailable.
    }
}

function resolveBackgroundFadeColor(editor, component) {
    // Prefer the authored bg-* utility (survives transparent override used to avoid flash).
    const bgUtility = resolveGroupValue(componentClassList(component), BACKGROUND_OPTIONS);
    const fromUtility = cssColorFromBackgroundUtility(bgUtility);

    if (fromUtility !== '') {
        return fromUtility;
    }

    const authored = readComponentCssProperty(editor, component, 'background-color');

    if (
        authored !== ''
        && authored.toLowerCase() !== 'transparent'
        && authored.toLowerCase() !== 'rgba(0, 0, 0, 0)'
        && ! /^var\(/i.test(authored)
    ) {
        return authored;
    }

    try {
        const el = component?.getEl?.() ?? component?.view?.el;
        const view = el?.ownerDocument?.defaultView;

        if (el && view) {
            const computed = String(view.getComputedStyle(el).backgroundColor ?? '').trim();

            if (
                computed !== ''
                && computed !== 'transparent'
                && computed !== 'rgba(0, 0, 0, 0)'
            ) {
                return computed;
            }
        }
    } catch {
        // Canvas frame may be unavailable during boot.
    }

    if (authored !== '' && authored.toLowerCase() !== 'transparent') {
        return authored;
    }

    return 'var(--color-vp-bg, #0f172a)';
}

/**
 * After Clear photo, drop the transparent background-color override so
 * solid Color utilities (bg-red-700) paint again.
 */
function restoreSolidBackgroundColorAfterImageClear(editor, component) {
    if (! editor || ! component) {
        return;
    }

    const target = resolveVisualStyleTarget(component) ?? component;

    clearStyleProperty(editor, target, 'background-color', { family: false });
}

/**
 * Drop author `background-image` (inline + #id) so Tailwind gradient utilities
 * can paint again after a decoration photo was removed or never applied.
 */
function releaseAuthorBackgroundImageForUtilities(editor, component) {
    if (! editor || ! component) {
        return;
    }

    const target = resolveVisualStyleTarget(component) ?? component;
    const id = String(target.getId?.() ?? component.getId?.() ?? '').trim();

    clearStyleProperty(editor, target, 'background-image', { family: false });

    if (id && editor.Css?.getIdRule) {
        const rule = editor.Css.getIdRule(id);

        if (rule) {
            const style = { ...(rule.getStyle?.() ?? {}) };

            if (style['background-image'] != null || style.background != null) {
                delete style['background-image'];
                delete style.background;

                if (Object.keys(style).length === 0) {
                    try {
                        editor.Css.remove?.(rule);
                    } catch {
                        rule.setStyle?.({});
                    }
                } else {
                    rule.setStyle?.(style);
                }
            }
        }
    }

    try {
        const el = target?.getEl?.() ?? target?.view?.el;

        if (el?.style) {
            el.style.removeProperty?.('background-image');
            el.style.backgroundImage = '';
        }
    } catch {
        // Frame may be unavailable.
    }

    restoreSolidBackgroundColorAfterImageClear(editor, component);
}

/**
 * Re-paint decoration background-image from durable src + opacity + optional gradient.
 *
 * @param {string|null} [forcedSrc] When set (e.g. Choose), wins over stale composed CSS.
 */
function reapplyDecorationBackgroundPaint(editor, component, forcedSrc = null) {
    if (! editor || ! component) {
        return;
    }

    const forced = forcedSrc == null ? '' : String(forcedSrc).trim();
    const src = forced !== '' ? forced : readBackgroundImageUrl(component, editor);

    if (src === '') {
        return;
    }

    const target = resolveVisualStyleTarget(component) ?? component;
    const opacity = readBackgroundImageOpacity(component, editor);
    // Pass photo visibility so gradient stops get alpha — opaque TW stops hide the url.
    const gradientLayer = resolveDecorationGradientLayer(component, opacity);
    const fadeColor = gradientLayer ? '' : resolveBackgroundFadeColor(editor, target);
    const cssValue = composeDecorationBackgroundImageCss(src, opacity, fadeColor, { gradientLayer });

    persistBackgroundImageSrcAttr(component, src);
    component.addAttributes?.({
        [STYLE_BG_OPACITY_ATTR]: String(opacity),
    });

    if (target !== component) {
        target.addAttributes?.({
            [STYLE_BG_OPACITY_ATTR]: String(opacity),
        });
    }

    // Drop previous image paint before rewrite (keeps solid color classes).
    clearStyleProperty(editor, target, 'background-image', { family: false });

    target.addStyle?.(
        {
            'background-image': cssValue,
            // Kill solid Color flash: tint is only in overlay layers above the photo.
            'background-color': 'transparent',
        },
        { inline: true },
    );

    const id = String(target.getId?.() ?? component.getId?.() ?? '').trim();

    if (id && editor.Css?.setIdRule) {
        try {
            const existing = { ...(editor.Css.getIdRule?.(id)?.getStyle?.() ?? {}) };
            editor.Css.setIdRule(id, {
                ...existing,
                'background-image': cssValue,
                'background-color': 'transparent',
            });
        } catch {
            // CssComposer may be unavailable during boot.
        }
    }

    // Do NOT scrub live page CSS here — Save / reload need #id{url} as a
    // recovery source. Scrub only happens on Clear.
}

function applyDecorationBackgroundImage(editor, component, url, opacity = null) {
    if (! editor || ! component) {
        return;
    }

    const wasApplying = Boolean(editor.__voodbuilderTwStyleApplying);
    editor.__voodbuilderTwStyleApplying = true;

    try {
        const src = String(url ?? '').trim();

        if (src === '') {
            clearDecorationBackgroundImage(editor, component);
        } else {
            const nextOpacity = opacity == null
                ? readBackgroundImageOpacity(component, editor)
                : normalizeBackgroundImageOpacity(opacity);

            component.addAttributes?.({
                [STYLE_BG_OPACITY_ATTR]: String(nextOpacity),
                [STYLE_BG_SRC_ATTR]: src,
            });

            const classes = componentClassList(component);

            if (! resolveGroupValue(classes, BG_SIZE_OPTIONS)) {
                replaceClassGroup(component, GROUP_SETS['bg-size'], 'bg-cover');
            }

            if (! resolveGroupValue(classes, BG_POSITION_OPTIONS)) {
                replaceClassGroup(component, GROUP_SETS['bg-position'], 'bg-center');
            }

            if (! resolveGroupValue(classes, BG_REPEAT_OPTIONS)) {
                replaceClassGroup(component, GROUP_SETS['bg-repeat'], 'bg-no-repeat');
            }

            reapplyDecorationBackgroundPaint(editor, component, src);
        }

        try {
            component.view?.updateClasses?.();
            component.view?.updateStyle?.();
        } catch {
            // View may be unavailable.
        }

        scheduleClassCompile(editor);
        editor?.trigger?.('update');
    } finally {
        if (! wasApplying) {
            editor.__voodbuilderTwStyleApplying = false;
        }
    }
}

function wireBackgroundImageField(editor, sector, labels = {}) {
    const mount = sector.querySelector('[data-voodbuilder-deco-bg-image]');
    const opacityMount = sector.querySelector('[data-voodbuilder-deco-bg-opacity]');

    if (mount && mount.dataset.vbWired !== '1') {
        mount.dataset.vbWired = '1';
        mount.replaceChildren();

        const selected = editor.getSelected?.();
        const field = createImageUrlField({
            label: labels.classStyleBackgroundImageSrc ?? labels.imageSettingsHeroSrc ?? 'Background image',
            name: 'styleBgImage',
            value: readBackgroundImageUrl(selected, editor),
            editor,
            chooseLabel: labels.imageSettingsChoose ?? labels.logoChoose ?? 'Choose',
            clearLabel: labels.imageSettingsClear ?? labels.logoClear ?? 'Clear',
            onChange: (url) => {
                const component = editor.getSelected?.();

                if (! component) {
                    return;
                }

                applyDecorationBackgroundImage(editor, component, url);
                syncSelectsFromComponent(sector.closest('.gjs-sm-sectors') ?? sector, component, editor);
            },
        });

        mount.appendChild(field);
        mount.__vbBgImageInput = field.querySelector('input');
    }

    if (opacityMount && opacityMount.dataset.vbWired !== '1') {
        opacityMount.dataset.vbWired = '1';
        opacityMount.replaceChildren();

        const selected = editor.getSelected?.();
        const current = String(readBackgroundImageOpacity(selected, editor));
        const field = createSelectField({
            label: labels.classStyleBackgroundImageOpacity
                ?? labels.imageSettingsOpacity
                ?? 'Photo visibility',
            name: 'styleBgImageOpacity',
            value: current,
            options: STYLE_BG_OPACITY_OPTIONS,
            onChange: (value) => {
                const component = editor.getSelected?.();
                const url = readBackgroundImageUrl(component, editor);

                if (! component || url === '') {
                    return;
                }

                applyDecorationBackgroundImage(editor, component, url, value);
                syncSelectsFromComponent(sector.closest('.gjs-sm-sectors') ?? sector, component, editor);
            },
        });

        opacityMount.appendChild(field);
        opacityMount.__vbBgOpacitySelect = field.querySelector('select');
    }
}

function syncBackgroundImageField(root, component, editor = null) {
    const mount = root.querySelector?.('[data-voodbuilder-deco-bg-image]');
    const input = mount?.__vbBgImageInput ?? mount?.querySelector?.('input');
    const url = readBackgroundImageUrl(component, editor);
    const fold = root.querySelector?.('[data-voodbuilder-deco-fold="image"]');
    const opacityMount = root.querySelector?.('[data-voodbuilder-deco-bg-opacity]');
    const opacitySelect = opacityMount?.__vbBgOpacitySelect
        ?? opacityMount?.querySelector?.('select');

    // Persist src attr so Color changes / reload can find the photo without CssComposer.
    if (component && url !== '') {
        persistBackgroundImageSrcAttr(component, url);
    }

    if (input && String(input.value ?? '') !== url) {
        input.value = url;
    }

    const preview = mount?.querySelector?.('.voodbuilder-editor-image-url-preview');

    if (preview) {
        if (url === '') {
            preview.replaceChildren();
            preview.hidden = true;
        } else {
            preview.hidden = false;
            let img = preview.querySelector('img');

            if (! img) {
                img = document.createElement('img');
                img.alt = '';
                preview.replaceChildren(img);
            }

            if (img.getAttribute('src') !== url) {
                img.src = url;
            }
        }
    }

    if (opacitySelect) {
        const opacity = String(readBackgroundImageOpacity(component, editor));
        const hasOption = [...opacitySelect.options].some((option) => option.value === opacity);

        if (! hasOption && opacity !== '1') {
            const option = document.createElement('option');
            option.value = opacity;
            option.textContent = `${Math.round(Number.parseFloat(opacity) * 100)}%`;
            opacitySelect.appendChild(option);
        }

        if (String(opacitySelect.value) !== opacity) {
            opacitySelect.value = opacity;
        }

        opacitySelect.disabled = url === '';
        opacityMount?.classList.toggle('is-disabled', url === '');
    }

    if (fold && String(fold.tagName ?? '').toUpperCase() === 'DETAILS' && url) {
        fold.open = true;
    }
}

function syncDecorationBlocks(root, component, options = {}, editor = null) {
    const resetLinkPref = options.resetLinkPref === true;
    const classes = componentClassList(component);
    const decoRoot = root.querySelector('[data-voodbuilder-decorations]');

    if (! decoRoot || typeof decoRoot.querySelector !== 'function') {
        return;
    }

    const bgParsed = resolveBackgroundColorAndOpacity(classes, component);
    const bg = bgParsed.color;
    const gradDir = resolveStyleGroup(classes, GRADIENT_DIRECTION_OPTIONS, editor);
    const gradFrom = resolveStyleGroup(classes, GRADIENT_FROM_OPTIONS, editor);
    const gradVia = resolveStyleGroup(classes, GRADIENT_VIA_OPTIONS, editor);
    const gradTo = resolveStyleGroup(classes, GRADIENT_TO_OPTIONS, editor);
    const hasGradient = Boolean(
        (gradDir && gradDir !== 'bg-none')
        || gradFrom
        || gradVia
        || gradTo,
    );

    const borderAll = resolveStyleGroup(classes, BORDER_WIDTH_OPTIONS, editor);
    const borderT = resolveStyleGroup(classes, BORDER_T_WIDTH_OPTIONS, editor);
    const borderR = resolveStyleGroup(classes, BORDER_R_WIDTH_OPTIONS, editor);
    const borderB = resolveStyleGroup(classes, BORDER_B_WIDTH_OPTIONS, editor);
    const borderL = resolveStyleGroup(classes, BORDER_L_WIDTH_OPTIONS, editor);
    const borderStyle = resolveStyleGroup(classes, BORDER_STYLE_OPTIONS, editor);
    const borderColor = resolveStyleGroup(classes, BORDER_COLOR_OPTIONS, editor);
    const hasBorderSides = Boolean(borderT || borderR || borderB || borderL);
    const hasBorder = Boolean(borderAll || hasBorderSides || borderStyle || borderColor);

    const roundedAll = resolveStyleGroup(classes, ROUNDED_OPTIONS, editor);
    const roundedTl = resolveStyleGroup(classes, ROUNDED_TL_OPTIONS, editor);
    const roundedTr = resolveStyleGroup(classes, ROUNDED_TR_OPTIONS, editor);
    const roundedBr = resolveStyleGroup(classes, ROUNDED_BR_OPTIONS, editor);
    const roundedBl = resolveStyleGroup(classes, ROUNDED_BL_OPTIONS, editor);
    const hasRoundedCorners = Boolean(roundedTl || roundedTr || roundedBr || roundedBl);
    const hasRounded = Boolean(roundedAll || hasRoundedCorners
        || resolveStyleGroup(classes, ROUNDED_T_OPTIONS, editor)
        || resolveStyleGroup(classes, ROUNDED_R_OPTIONS, editor)
        || resolveStyleGroup(classes, ROUNDED_B_OPTIONS, editor)
        || resolveStyleGroup(classes, ROUNDED_L_OPTIONS, editor));

    const shadow = resolveStyleGroup(classes, SHADOW_OPTIONS, editor);
    const shadowColor = resolveStyleGroup(classes, SHADOW_COLOR_OPTIONS, editor);
    const dropShadow = resolveStyleGroup(classes, DROP_SHADOW_OPTIONS, editor);
    const hasBgImage = Boolean(readBackgroundImageUrl(component, editor));

    const setDot = (key, on) => {
        const dot = decoRoot.querySelector(`[data-voodbuilder-deco-dot="${key}"]`);

        if (dot) {
            dot.hidden = ! on;
        }
    };

    setDot('background', Boolean(bg) || hasGradient || hasBgImage);
    setDot('gradient', hasGradient);
    setDot('border', hasBorder);
    setDot('radius', hasRounded);
    setDot(
        'shadow',
        (Boolean(shadow) && shadow !== 'shadow-none')
        || Boolean(shadowColor)
        || (Boolean(dropShadow) && dropShadow !== 'drop-shadow-none'),
    );

    const gradientFold = decoRoot.querySelector('[data-voodbuilder-deco-fold="gradient"]');

    if (gradientFold && String(gradientFold.tagName ?? '').toUpperCase() === 'DETAILS' && hasGradient) {
        gradientFold.open = true;
    }

    const borderBlock = decoRoot.querySelector('[data-voodbuilder-deco-block="border"]');

    if (borderBlock) {
        if (resetLinkPref || ! borderBlock.dataset.linkPref) {
            borderBlock.dataset.linkPref = hasBorderSides && ! borderAll ? 'sides' : 'all';
        }

        const borderLink = borderBlock.dataset.linkPref || 'all';
        setDecoLinkButtons(borderBlock, borderLink);

        const allPane = borderBlock.querySelector('[data-voodbuilder-deco-border-mode="all"]');
        const sidesPane = borderBlock.querySelector('[data-voodbuilder-deco-border-mode="sides"]');

        if (allPane) {
            allPane.hidden = borderLink !== 'all';
        }

        if (sidesPane) {
            sidesPane.hidden = borderLink !== 'sides';
        }
    }

    const radiusBlock = decoRoot.querySelector('[data-voodbuilder-deco-block="radius"]');

    if (radiusBlock) {
        if (resetLinkPref || ! radiusBlock.dataset.linkPref) {
            radiusBlock.dataset.linkPref = hasRoundedCorners && ! roundedAll ? 'corners' : 'all';
        }

        const radiusLink = radiusBlock.dataset.linkPref || 'all';
        setDecoLinkButtons(radiusBlock, radiusLink);

        const allPane = radiusBlock.querySelector('[data-voodbuilder-deco-radius-mode="all"]');
        const cornersPane = radiusBlock.querySelector('[data-voodbuilder-deco-radius-mode="corners"]');

        if (allPane) {
            allPane.hidden = radiusLink !== 'all';
        }

        if (cornersPane) {
            cornersPane.hidden = radiusLink !== 'corners';
        }
    }
}

function wireDecorationBlocks(editor, sector) {
    const decoRoot = sector.querySelector('[data-voodbuilder-decorations]');

    if (! decoRoot) {
        return;
    }

    decoRoot.querySelectorAll('[data-voodbuilder-deco-block]').forEach((block) => {
        block.querySelectorAll('[data-voodbuilder-deco-link]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const component = editor.getSelected();
                const nextLink = button.getAttribute('data-voodbuilder-deco-link') || 'all';
                const kind = block.getAttribute('data-voodbuilder-deco-block');

                if (! component) {
                    return;
                }

                block.dataset.linkPref = nextLink;
                setDecoLinkButtons(block, nextLink);

                const classes = componentClassList(component);

                if (kind === 'border') {
                    if (nextLink === 'all') {
                        const token = resolveStyleGroup(classes, BORDER_T_WIDTH_OPTIONS, editor)
                            || resolveStyleGroup(classes, BORDER_R_WIDTH_OPTIONS, editor)
                            || resolveStyleGroup(classes, BORDER_B_WIDTH_OPTIONS, editor)
                            || resolveStyleGroup(classes, BORDER_L_WIDTH_OPTIONS, editor)
                            || resolveStyleGroup(classes, BORDER_WIDTH_OPTIONS, editor)
                            || '';
                        const shorthand = String(token).replace(/^border-[trbl]/, 'border');
                        applyGroup(editor, component, 'border-width', shorthand);
                    } else {
                        const all = resolveStyleGroup(classes, BORDER_WIDTH_OPTIONS, editor) || '';

                        for (const side of ['t', 'r', 'b', 'l']) {
                            let next = '';

                            if (all === 'border') {
                                next = `border-${side}`;
                            } else if (all.startsWith('border-')) {
                                next = all.replace(/^border/, `border-${side}`);
                            }

                            applyGroup(editor, component, `border-${side}-width`, next);
                        }
                    }
                }

                if (kind === 'radius') {
                    if (nextLink === 'all') {
                        const token = resolveStyleGroup(classes, ROUNDED_TL_OPTIONS, editor)
                            || resolveStyleGroup(classes, ROUNDED_TR_OPTIONS, editor)
                            || resolveStyleGroup(classes, ROUNDED_BR_OPTIONS, editor)
                            || resolveStyleGroup(classes, ROUNDED_BL_OPTIONS, editor)
                            || resolveStyleGroup(classes, ROUNDED_OPTIONS, editor)
                            || '';
                        const shorthand = String(token)
                            .replace(/^rounded-(tl|tr|br|bl|t|r|b|l)/, 'rounded');
                        applyGroup(editor, component, 'rounded', shorthand);
                    } else {
                        const all = resolveStyleGroup(classes, ROUNDED_OPTIONS, editor) || '';
                        const corners = ['rounded-tl', 'rounded-tr', 'rounded-br', 'rounded-bl'];

                        for (const corner of corners) {
                            let next = '';

                            if (all === 'rounded') {
                                next = corner;
                            } else if (all.startsWith('rounded-')) {
                                next = all.replace(/^rounded/, corner);
                            }

                            applyGroup(editor, component, corner, next);
                        }
                    }
                }

                syncSelectsFromComponent(sector.closest('.gjs-sm-sectors') ?? sector, component, editor);
            });
        });
    });
}

const SPACING_KIND = {
    margin: {
        all: MARGIN_OPTIONS,
        x: MARGIN_X_OPTIONS,
        y: MARGIN_Y_OPTIONS,
        t: MARGIN_T_OPTIONS,
        r: MARGIN_R_OPTIONS,
        b: MARGIN_B_OPTIONS,
        l: MARGIN_L_OPTIONS,
        prefix: { all: 'm', x: 'mx', y: 'my', t: 'mt', r: 'mr', b: 'mb', l: 'ml' },
        group: { all: 'margin', x: 'margin-x', y: 'margin-y', t: 'margin-t', r: 'margin-r', b: 'margin-b', l: 'margin-l' },
    },
    padding: {
        all: PADDING_OPTIONS,
        x: PADDING_X_OPTIONS,
        y: PADDING_Y_OPTIONS,
        t: PADDING_T_OPTIONS,
        r: PADDING_R_OPTIONS,
        b: PADDING_B_OPTIONS,
        l: PADDING_L_OPTIONS,
        prefix: { all: 'p', x: 'px', y: 'py', t: 'pt', r: 'pr', b: 'pb', l: 'pl' },
        group: { all: 'padding', x: 'padding-x', y: 'padding-y', t: 'padding-t', r: 'padding-r', b: 'padding-b', l: 'padding-l' },
    },
};

const SPACING_SCALE_SET = new Set([...SPACING_SCALE, 'auto']);

function normalizeSpacingToken(raw) {
    let token = String(raw ?? '').trim().toLowerCase();

    if (token === '' || token === '—' || token === '-') {
        return '';
    }

    token = token.replace(/^(m|p|mt|mr|mb|ml|pt|pr|pb|pl|mx|my|px|py)-/, '');
    // Authors often type CSS units; Spacing is a Tailwind scale, so strip them.
    token = token.replace(/(?:px|rem|em|%)$/i, '');

    if (! SPACING_SCALE_SET.has(token)) {
        return null;
    }

    return token;
}

function spacingClass(prefix, token) {
    if (token === '' || token == null) {
        return '';
    }

    return `${prefix}-${token}`;
}

function resolveSpacingState(kind, classes, prefix = '') {
    const cfg = SPACING_KIND[kind];

    if (! cfg) {
        return { link: 'independent', sides: { t: '', r: '', b: '', l: '' }, hasValue: false };
    }

    // Prefer exact utilities at this breakpoint so base `p-4` does not hide `md:py-12`.
    const allExact = resolveGroupValueExact(classes, cfg.all, prefix);
    const xExact = resolveGroupValueExact(classes, cfg.x, prefix);
    const yExact = resolveGroupValueExact(classes, cfg.y, prefix);
    const tExact = resolveGroupValueExact(classes, cfg.t, prefix);
    const rExact = resolveGroupValueExact(classes, cfg.r, prefix);
    const bExact = resolveGroupValueExact(classes, cfg.b, prefix);
    const lExact = resolveGroupValueExact(classes, cfg.l, prefix);
    const hasExact = Boolean(allExact || xExact || yExact || tExact || rExact || bExact || lExact);

    const all = hasExact
        ? allExact
        : resolveGroupValueAtBreakpoint(classes, cfg.all, prefix);

    if (all) {
        const token = spacingTokenFromClass(all);

        return {
            link: 'all',
            sides: { t: token, r: token, b: token, l: token },
            hasValue: Boolean(token),
        };
    }

    const x = hasExact ? xExact : resolveGroupValueAtBreakpoint(classes, cfg.x, prefix);
    const y = hasExact ? yExact : resolveGroupValueAtBreakpoint(classes, cfg.y, prefix);
    const tSide = hasExact ? tExact : resolveGroupValueAtBreakpoint(classes, cfg.t, prefix);
    const rSide = hasExact ? rExact : resolveGroupValueAtBreakpoint(classes, cfg.r, prefix);
    const bSide = hasExact ? bExact : resolveGroupValueAtBreakpoint(classes, cfg.b, prefix);
    const lSide = hasExact ? lExact : resolveGroupValueAtBreakpoint(classes, cfg.l, prefix);

    const sides = {
        t: spacingTokenFromClass(tSide) || spacingTokenFromClass(y),
        r: spacingTokenFromClass(rSide) || spacingTokenFromClass(x),
        b: spacingTokenFromClass(bSide) || spacingTokenFromClass(y),
        l: spacingTokenFromClass(lSide) || spacingTokenFromClass(x),
    };

    const hasAxes = Boolean(x || y);
    const hasSides = Boolean(tSide || rSide || bSide || lSide);
    let link = 'independent';

    if (hasAxes && ! hasSides) {
        link = 'opposites';
    }

    return {
        link,
        sides,
        hasValue: Boolean(sides.t || sides.r || sides.b || sides.l),
    };
}

function spacingGroupFor(kind, side, linkMode) {
    const cfg = SPACING_KIND[kind];

    if (! cfg) {
        return null;
    }

    if (linkMode === 'all') {
        return { groupId: cfg.group.all, prefix: cfg.prefix.all, options: cfg.all };
    }

    if (linkMode === 'opposites') {
        if (side === 't' || side === 'b') {
            return { groupId: cfg.group.y, prefix: cfg.prefix.y, options: cfg.y };
        }

        return { groupId: cfg.group.x, prefix: cfg.prefix.x, options: cfg.x };
    }

    return { groupId: cfg.group[side], prefix: cfg.prefix[side], options: cfg[side] };
}

/**
 * When editing one axis while a shorthand (p-N / m-N) is still present, expand to
 * both axes first so the untouched axis is preserved.
 */
function expandSpacingShorthandToAxes(editor, component, kind) {
    const cfg = SPACING_KIND[kind];

    if (! cfg || ! component) {
        return false;
    }

    const all = resolveStyleGroup(componentClassList(component), cfg.all, editor);

    if (! all) {
        return false;
    }

    const token = spacingTokenFromClass(all);
    applyGroup(editor, component, cfg.group.y, spacingClass(cfg.prefix.y, token));
    applyGroup(editor, component, cfg.group.x, spacingClass(cfg.prefix.x, token));

    return true;
}

function applySpacingToken(editor, component, kind, side, rawToken, linkMode) {
    const token = normalizeSpacingToken(rawToken);

    if (token === null) {
        return false;
    }

    if (linkMode === 'opposites') {
        expandSpacingShorthandToAxes(editor, component, kind);
    }

    const target = spacingGroupFor(kind, side, linkMode);

    if (! target) {
        return false;
    }

    applyGroup(editor, component, target.groupId, spacingClass(target.prefix, token));

    return true;
}

function setSpacingLinkMode(editor, component, kind, nextLink) {
    const bp = currentStyleBreakpointPrefix(editor);
    const state = resolveSpacingState(kind, componentClassList(component), bp);
    const { sides } = state;
    const token = sides.t || sides.r || sides.b || sides.l || '';

    if (nextLink === 'all') {
        applySpacingToken(editor, component, kind, 't', token, 'all');

        return;
    }

    if (nextLink === 'opposites') {
        // From shorthand: keep equal axes (py+px / my+mx). From independent sides:
        // map T/B → Y and L/R → X without copying a token across axes (that made
        // opposites feel identical to "all sides").
        if (state.link === 'all') {
            applySpacingToken(editor, component, kind, 't', token, 'opposites');
            applySpacingToken(editor, component, kind, 'l', token, 'opposites');
        } else {
            applySpacingToken(editor, component, kind, 't', sides.t || sides.b || '', 'opposites');
            applySpacingToken(editor, component, kind, 'l', sides.l || sides.r || '', 'opposites');
        }

        return;
    }

    // Expand to four independent sides.
    for (const side of ['t', 'r', 'b', 'l']) {
        applySpacingToken(editor, component, kind, side, sides[side] || '', 'independent');
    }
}

function oppositeSpacingSide(side) {
    if (side === 't') {
        return 'b';
    }

    if (side === 'b') {
        return 't';
    }

    if (side === 'l') {
        return 'r';
    }

    if (side === 'r') {
        return 'l';
    }

    return null;
}

function mirrorLinkedSpacingInputs(block, side, value, linkMode) {
    if (! block || ! side) {
        return;
    }

    const write = (targetSide) => {
        const input = block.querySelector(`input[data-voodbuilder-spacing-side="${targetSide}"]`);

        if (! input || document.activeElement === input) {
            return;
        }

        input.value = value;
        input.classList.toggle('is-set', Boolean(String(value ?? '').trim()));
    };

    if (linkMode === 'all') {
        for (const targetSide of ['t', 'r', 'b', 'l']) {
            if (targetSide !== side) {
                write(targetSide);
            }
        }

        return;
    }

    if (linkMode === 'opposites') {
        const pair = oppositeSpacingSide(side);

        if (pair) {
            write(pair);
        }
    }
}

function syncSpacingBox(root, component, options = {}, editor = null) {
    const resetLinkPref = options.resetLinkPref === true;
    const bp = currentStyleBreakpointPrefix(editor);
    const inheritedHint = options.inheritedHint
        ?? 'Inherited from a smaller viewport — change to override here';

    for (const kind of ['margin', 'padding']) {
        const block = root.querySelector(`[data-voodbuilder-spacing-box="${kind}"]`);

        if (! block) {
            continue;
        }

        const classes = componentClassList(component);
        const state = resolveSpacingState(kind, classes, bp);
        const cfg = SPACING_KIND[kind];

        if (resetLinkPref || ! block.dataset.linkPref) {
            block.dataset.linkPref = state.link;
        }

        // User-chosen link mode wins over class inference so "opposites" does not
        // snap back to "all" when both axes still share the same token.
        const link = block.dataset.linkPref || state.link;
        block.dataset.link = link;

        for (const button of block.querySelectorAll('[data-voodbuilder-spacing-link]')) {
            const active = button.getAttribute('data-voodbuilder-spacing-link') === link;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        }

        const dot = block.querySelector('[data-voodbuilder-spacing-dot]');

        if (dot) {
            // Dot = authored at this breakpoint (not merely cascaded).
            const exactHere = Boolean(
                resolveGroupValueExact(classes, cfg.all, bp)
                || resolveGroupValueExact(classes, cfg.x, bp)
                || resolveGroupValueExact(classes, cfg.y, bp)
                || resolveGroupValueExact(classes, cfg.t, bp)
                || resolveGroupValueExact(classes, cfg.r, bp)
                || resolveGroupValueExact(classes, cfg.b, bp)
                || resolveGroupValueExact(classes, cfg.l, bp),
            );
            dot.hidden = ! exactHere;
        }

        for (const side of ['t', 'r', 'b', 'l']) {
            const input = block.querySelector(
                `input[data-voodbuilder-spacing-kind="${kind}"][data-voodbuilder-spacing-side="${side}"]`,
            );

            if (! input || document.activeElement === input) {
                continue;
            }

            const value = state.sides[side] || '';
            const target = spacingGroupFor(kind, side, link);
            const exactClass = target
                ? resolveGroupValueExact(classes, target.options, bp)
                : '';
            const exactToken = spacingTokenFromClass(exactClass);
            const inherited = Boolean(value) && exactToken !== value;

            input.value = value;
            input.classList.toggle('is-set', Boolean(value) && ! inherited);
            input.classList.toggle('is-inherited', inherited);
            input.title = inherited ? inheritedHint : (input.getAttribute('aria-label') || '');
        }
    }
}

let activeSpacingPopover = null;

function closeSpacingPopover() {
    if (! activeSpacingPopover) {
        return;
    }

    activeSpacingPopover.remove();
    activeSpacingPopover = null;
    document.removeEventListener('pointerdown', onSpacingPopoverOutside, true);
    document.removeEventListener('keydown', onSpacingPopoverKeydown, true);
}

function onSpacingPopoverOutside(event) {
    if (! activeSpacingPopover) {
        return;
    }

    if (
        activeSpacingPopover.contains(event.target)
        || event.target?.closest?.('[data-voodbuilder-spacing-scale]')
        || event.target?.closest?.('.voodbuilder-editor-spacing-cross__cell')
    ) {
        return;
    }

    closeSpacingPopover();
}

function onSpacingPopoverKeydown(event) {
    if (event.key === 'Escape') {
        closeSpacingPopover();
    }
}

function openSpacingScalePopover(editor, sector, anchor, kind, side, labels = {}) {
    const block = sector.querySelector(`[data-voodbuilder-spacing-box="${kind}"]`);
    const linkMode = block?.dataset.link || 'all';
    const target = spacingGroupFor(kind, side, linkMode);

    if (! target || ! (anchor instanceof HTMLElement)) {
        return;
    }

    closeSpacingPopover();

    const selected = editor.getSelected();
    const currentToken = spacingTokenFromClass(
        resolveStyleGroup(componentClassList(selected), target.options, editor),
    );

    const pop = document.createElement('div');
    pop.className = 'voodbuilder-editor-spacing-popover';
    pop.dataset.voodbuilderSpacingPopover = `${kind}-${side}`;
    pop.innerHTML = `
        <div class="voodbuilder-editor-spacing-popover__head">
            <span>${escapeHtml(labels.classStyleSpacingScale ?? 'Tailwind scale')}</span>
            <input type="search" class="voodbuilder-editor-spacing-popover__search" aria-label="${escapeAttr(labels.classStyleSpacingSearch ?? 'Search…')}" placeholder="${escapeAttr(labels.classStyleSpacingSearch ?? 'Search…')}" autocomplete="off" />
        </div>
        <div class="voodbuilder-editor-spacing-popover__grid" role="listbox" aria-label="${escapeAttr(labels.classStyleSpacingScale ?? 'Tailwind scale')}"></div>
    `;

    const grid = pop.querySelector('.voodbuilder-editor-spacing-popover__grid');
    const search = pop.querySelector('.voodbuilder-editor-spacing-popover__search');

    const render = (query = '') => {
        const needle = String(query).trim().toLowerCase();
        grid.replaceChildren();

        for (const opt of target.options) {
            const label = String(opt.label ?? opt.value ?? '');
            const token = spacingTokenFromClass(opt.value);
            const hay = `${label} ${opt.value}`.toLowerCase();

            if (needle !== '' && ! hay.includes(needle)) {
                continue;
            }

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'voodbuilder-editor-spacing-popover__opt';
            btn.role = 'option';
            btn.textContent = label || '—';
            btn.title = opt.value || 'clear';
            btn.dataset.token = token;

            if ((opt.value === '' && currentToken === '') || (token && token === currentToken)) {
                btn.classList.add('is-selected');
            }

            btn.addEventListener('click', () => {
                const component = editor.getSelected();

                if (! component) {
                    closeSpacingPopover();

                    return;
                }

                const liveLink = block?.dataset.link || linkMode;
                applySpacingToken(editor, component, kind, side, token, liveLink);
                syncSelectsFromComponent(sector.closest('.gjs-sm-sectors') ?? sector, component, editor);
                closeSpacingPopover();
            });

            grid.appendChild(btn);
        }
    };

    render();
    search?.addEventListener('input', () => render(search.value));

    document.body.appendChild(pop);
    activeSpacingPopover = pop;

    const rect = anchor.getBoundingClientRect();
    const pad = 8;
    const width = Math.min(240, window.innerWidth - pad * 2);
    let left = rect.left + (rect.width / 2) - (width / 2);
    left = Math.max(pad, Math.min(left, window.innerWidth - width - pad));
    let top = rect.bottom + 6;

    pop.style.width = `${width}px`;
    pop.style.left = `${left}px`;
    pop.style.top = `${top}px`;

    requestAnimationFrame(() => {
        const popRect = pop.getBoundingClientRect();

        if (popRect.bottom > window.innerHeight - pad) {
            top = Math.max(pad, rect.top - popRect.height - 6);
            pop.style.top = `${top}px`;
        }

        search?.focus();
    });

    document.addEventListener('pointerdown', onSpacingPopoverOutside, true);
    document.addEventListener('keydown', onSpacingPopoverKeydown, true);
}

function wireSpacingBoxes(editor, sector, labels = {}) {
    for (const block of sector.querySelectorAll('[data-voodbuilder-spacing-box]')) {
        const kind = block.getAttribute('data-voodbuilder-spacing-box');

        block.querySelectorAll('[data-voodbuilder-spacing-link]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                const component = editor.getSelected();
                const nextLink = button.getAttribute('data-voodbuilder-spacing-link') || 'all';

                if (! component) {
                    return;
                }

                block.dataset.linkPref = nextLink;
                block.dataset.link = nextLink;
                setSpacingLinkMode(editor, component, kind, nextLink);
                syncSelectsFromComponent(sector.closest('.gjs-sm-sectors') ?? sector, component, editor);
            });
        });

        block.querySelectorAll('input[data-voodbuilder-spacing-kind]').forEach((input) => {
            let liveTimer = null;

            const commit = () => {
                window.clearTimeout(liveTimer);
                liveTimer = null;

                const component = editor.getSelected();
                const side = input.getAttribute('data-voodbuilder-spacing-side');
                const linkMode = block.dataset.link || 'all';

                if (! component || ! side) {
                    return;
                }

                const ok = applySpacingToken(editor, component, kind, side, input.value, linkMode);

                if (! ok) {
                    syncSpacingBox(sector.closest('.gjs-sm-sectors') ?? sector, component, {}, editor);
                    input.classList.add('is-invalid');

                    return;
                }

                input.classList.remove('is-invalid');
                syncSelectsFromComponent(sector.closest('.gjs-sm-sectors') ?? sector, component, editor);
            };

            input.addEventListener('input', () => {
                const linkMode = block.dataset.link || 'all';
                const side = input.getAttribute('data-voodbuilder-spacing-side');
                mirrorLinkedSpacingInputs(block, side, input.value, linkMode);

                // Live-apply valid scale tokens so authors need not blur first.
                window.clearTimeout(liveTimer);
                liveTimer = window.setTimeout(() => {
                    if (normalizeSpacingToken(input.value) === null) {
                        return;
                    }

                    commit();
                }, 280);
            });

            input.addEventListener('change', commit);

            input.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    commit();
                    input.blur();
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    window.clearTimeout(liveTimer);
                    const component = editor.getSelected();
                    syncSpacingBox(sector.closest('.gjs-sm-sectors') ?? sector, component, {}, editor);
                    input.blur();
                }
            });

            input.addEventListener('blur', (event) => {
                if (event.relatedTarget?.closest?.('[data-voodbuilder-spacing-scale]')) {
                    return;
                }

                commit();
            });
        });

        block.querySelectorAll('[data-voodbuilder-spacing-scale]').forEach((button) => {
            button.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                openSpacingScalePopover(
                    editor,
                    sector,
                    button,
                    kind,
                    button.getAttribute('data-voodbuilder-spacing-side'),
                    labels,
                );
            });
        });
    }
}

function syncGradientPosSliders(root, component) {
    if (! root || typeof root.querySelectorAll !== 'function') {
        return;
    }

    const classes = componentClassList(component);
    const inputs = [...(root.querySelectorAll('[data-voodbuilder-tw-group-range]') ?? [])];

    for (const input of inputs) {
        const groupId = input.getAttribute('data-voodbuilder-tw-group-range') || '';
        const kind = input.getAttribute('data-voodbuilder-gradient-pos-kind') || 'from';
        const defaultPos = Number.parseInt(input.getAttribute('data-voodbuilder-gradient-pos-default') ?? '', 10);
        const fallback = Number.isFinite(defaultPos) ? defaultPos : (GRADIENT_POS_DEFAULTS[kind] ?? 0);
        const group = STYLE_UTILITY_GROUPS.find((g) => g.id === groupId);
        const authored = group
            ? resolveGroupValueAtBreakpoint(classes, group.options, '')
            : '';
        const pct = gradientStopPositionFromUtility(authored);
        const value = pct == null ? fallback : pct;

        input.value = String(value);
        input.dataset.authored = pct == null ? '0' : '1';

        const readout = input.closest('.voodbuilder-editor-deco-stop__pos')
            ?.querySelector('[data-voodbuilder-gradient-pos-readout]');

        if (readout) {
            readout.textContent = `${value}%`;
        }

        // Disable Via stop when Via color is empty.
        if (kind === 'via') {
            const colorGroupId = groupId.replace(/-pos$/, '');
            const colorGroup = STYLE_UTILITY_GROUPS.find((g) => g.id === colorGroupId);
            const viaColor = colorGroup
                ? resolveGroupValueAtBreakpoint(classes, colorGroup.options, '')
                : '';
            const row = input.closest('[data-voodbuilder-deco-stop="via"]');
            const disabled = ! viaColor;

            input.disabled = disabled;
            row?.classList.toggle('is-disabled', disabled);
        }
    }
}

function wireGradientPosSliders(editor, sector) {
    for (const input of sector.querySelectorAll('[data-voodbuilder-tw-group-range]')) {
        let liveTimer = null;

        const readStopPct = (block, kind) => {
            const el = block?.querySelector?.(
                `[data-voodbuilder-tw-group-range][data-voodbuilder-gradient-pos-kind="${kind}"]`,
            );

            // Ignore disabled / default-only stops (e.g. Via at 50% with no Via color)
            // so they do not clamp From/To.
            if (! el || el.disabled || el.dataset.authored !== '1') {
                return null;
            }

            const n = Number.parseInt(el.value, 10);

            return Number.isFinite(n) ? n : null;
        };

        const clampPct = (kind, raw, block) => {
            let pct = Number.isFinite(raw) ? raw : 0;
            const fromPct = readStopPct(block, 'from');
            const viaPct = readStopPct(block, 'via');
            const toPct = readStopPct(block, 'to');

            if (kind === 'from') {
                const max = viaPct != null ? viaPct : (toPct != null ? toPct : 100);
                pct = Math.min(pct, max);
            } else if (kind === 'via') {
                const min = fromPct != null ? fromPct : 0;
                const max = toPct != null ? toPct : 100;
                pct = Math.min(Math.max(pct, min), max);
            } else if (kind === 'to') {
                const min = viaPct != null ? viaPct : (fromPct != null ? fromPct : 0);
                pct = Math.max(pct, min);
            }

            return Math.min(100, Math.max(0, Math.round(pct / 5) * 5));
        };

        const commit = () => {
            const component = editor.getSelected();
            const groupId = input.getAttribute('data-voodbuilder-tw-group-range') || '';
            const kind = input.getAttribute('data-voodbuilder-gradient-pos-kind') || 'from';
            const block = input.closest('.voodbuilder-editor-deco-stops');

            if (! component || ! groupId) {
                return;
            }

            const pct = clampPct(kind, Number.parseInt(input.value, 10), block);
            input.value = String(pct);
            const utility = gradientStopPositionUtility(kind, pct);

            applyGroup(editor, component, groupId, utility);
            input.dataset.authored = '1';

            const readout = input.closest('.voodbuilder-editor-deco-stop__pos')
                ?.querySelector('[data-voodbuilder-gradient-pos-readout]');

            if (readout) {
                readout.textContent = `${pct}%`;
            }

            syncSelectsFromComponent(sector.closest('.gjs-sm-sectors') ?? sector, component, editor);
        };

        input.addEventListener('input', () => {
            const block = input.closest('.voodbuilder-editor-deco-stops');
            const kind = input.getAttribute('data-voodbuilder-gradient-pos-kind') || 'from';
            const pct = clampPct(kind, Number.parseInt(input.value, 10), block);
            input.value = String(pct);

            const readout = input.closest('.voodbuilder-editor-deco-stop__pos')
                ?.querySelector('[data-voodbuilder-gradient-pos-readout]');

            if (readout) {
                readout.textContent = `${pct}%`;
            }

            window.clearTimeout(liveTimer);
            liveTimer = window.setTimeout(commit, 80);
        });

        input.addEventListener('change', commit);
    }
}

function wireSectorFields(editor, sector, labels = {}) {
    const liveGroups = new Set([
        'font-size',
        'font-weight',
        'text-align',
        'text-color',
        'text-gradient-direction',
        'text-gradient-from',
        'text-gradient-via',
        'text-gradient-to',
        'text-gradient-from-pos',
        'text-gradient-via-pos',
        'text-gradient-to-pos',
        'leading',
        'tracking',
        'text-transform',
        'text-decoration',
        'margin',
        'margin-x',
        'margin-y',
        'margin-t',
        'margin-r',
        'margin-b',
        'margin-l',
        'padding',
        'padding-x',
        'padding-y',
        'padding-t',
        'padding-r',
        'padding-b',
        'padding-l',
        'width',
        'height',
        'max-width',
        'background',
        'gradient-direction',
        'gradient-from',
        'gradient-via',
        'gradient-to',
        'gradient-from-pos',
        'gradient-via-pos',
        'gradient-to-pos',
        'border-width',
        'border-t-width',
        'border-r-width',
        'border-b-width',
        'border-l-width',
        'border-style',
        'border-color',
        'rounded',
        'rounded-t',
        'rounded-r',
        'rounded-b',
        'rounded-l',
        'rounded-tl',
        'rounded-tr',
        'rounded-br',
        'rounded-bl',
        'shadow',
        'shadow-color',
        'drop-shadow',
        'bg-size',
        'bg-position',
        'bg-repeat',
    ]);

    for (const group of STYLE_UTILITY_GROUPS) {
        bindGroupField(editor, sector, group.id, {
            applyOnChange: liveGroups.has(group.id),
        });
    }

    wireSpacingBoxes(editor, sector, labels);
    wireDecorationBlocks(editor, sector);
    wireBackgroundImageField(editor, sector, labels);
    wireTypographySegments(editor, sector);
    wireGradientPosSliders(editor, sector);

    const fontAdd = sector.querySelector('[data-voodbuilder-tw-font-family-add]');
    const fontSelect = sector.querySelector('[data-voodbuilder-tw-font-family]');

    const applyFont = () => {
        const selected = editor.getSelected();

        if (! selected) {
            return;
        }

        const value = String(fontSelect?.value ?? '').trim();

        if (value === '') {
            clearStyleProperty(editor, selected, 'font-family');
            selected.addAttributes?.({ 'data-vb-font': null });
            selected.removeAttributes?.('data-vb-font');

            return;
        }

        selected.addAttributes?.({ 'data-vb-font': cssSafeFontStack(value) });
        void applyEditorFontFamily(editor, selected, value);
    };

    // Font family is live on change (no separate Add click required).
    fontSelect?.addEventListener('change', applyFont);
    fontAdd?.addEventListener('click', applyFont);
    fontSelect?.addEventListener('vb:font-preview', (event) => {
        const selected = editor.getSelected();
        const value = String(event.detail?.value ?? '').trim();

        if (! selected || value === '') {
            return;
        }

        previewEditorFontFamily(editor, selected, value);
    });
}

function placeSectors(stylesMount, sectors) {
    const root = ensureSectorsRoot(stylesMount);
    const animation = stylesMount.querySelector('[data-voodbuilder-animation-sector]');

    for (const sector of sectors) {
        if (stylesMount.querySelector(`[data-voodbuilder-tw-sector="${sector.dataset.voodbuilderTwSector}"]`)) {
            continue;
        }

        if (animation) {
            root.insertBefore(sector, animation);
        } else {
            root.appendChild(sector);
        }
    }
}

/**
 * Neutralize Grapes Style Manager inline inventing and mount Tailwind utility sectors.
 *
 * @param {object} editor
 * @param {{ mount?: HTMLElement, labels?: Record<string, string> }} [options]
 */
export function registerStyleTailwindPanel(editor, options = {}) {
    const stylesMount = options.mount;
    const labels = options.labels ?? {};
    const syncOpts = (extra = {}) => ({
        inheritedHint: labels.classStyleViewportInherited
            ?? 'Inherited from a smaller viewport — change to override here',
        ...extra,
    });

    if (! editor || ! stylesMount || editor.__voodbuilderTailwindStylePanelRegistered) {
        return;
    }

    editor.__voodbuilderTailwindStylePanelRegistered = true;
    editor.__voodbuilderTailwindStyleOnly = true;

    registerEditorBreakpointFontSizeCss(editor);

    const addLabel = labels.classAnimationAdd ?? labels.classStyleAdd ?? 'Add';

    let ensureTimer = null;
    let twObserver = null;
    let ensuring = false;

    const sectorsReady = () => (
        stylesMount.querySelector('[data-voodbuilder-tw-sector="dimension"]')
        && stylesMount.querySelector('[data-voodbuilder-tw-sector="spacing"]')
    );

    const runEnsure = () => {
        if (ensuring) {
            return;
        }

        ensuring = true;
        twObserver?.disconnect();

        try {
            hideNativeStyleManagerSectors(stylesMount);

            if (sectorsReady()) {
                ensureViewportStrip(stylesMount, labels, editor);

                return;
            }

            // Rebuild Tailwind sectors when Spacing was still nested under Dimension.
            for (const stale of stylesMount.querySelectorAll('[data-voodbuilder-tw-sector]')) {
                stale.remove();
            }

            const dimension = buildDimensionSector(labels, addLabel);
            const spacing = buildSpacingSector(labels);
            const decorations = buildDecorationsSector(labels);
            const typography = buildTypographySector(labels, addLabel);

            for (const sector of [dimension, spacing, decorations, typography]) {
                wireSectorFields(editor, sector, labels);
            }

            placeSectors(stylesMount, [dimension, spacing, decorations, typography]);
            ensureViewportStrip(stylesMount, labels, editor);

            // Marker for MutationObserver idempotency when sectors move
            if (! stylesMount.querySelector('[data-voodbuilder-tw-root]')) {
                const marker = document.createElement('div');
                marker.hidden = true;
                marker.dataset.voodbuilderTwRoot = '';
                stylesMount.appendChild(marker);
            }

            syncSelectsFromComponent(stylesMount, resolveStyleTarget(editor) ?? editor.getSelected(), editor, syncOpts());
            syncViewportStrip(stylesMount, editor, labels);
        } finally {
            ensuring = false;
            twObserver?.observe(stylesMount, { childList: true, subtree: true });
        }
    };

    const scheduleEnsure = () => {
        if (isEditorBooting(editor) || ensuring) {
            return;
        }

        window.clearTimeout(ensureTimer);
        ensureTimer = window.setTimeout(runEnsure, 160);
    };

    const ensure = () => {
        scheduleEnsure();
    };

    twObserver = new MutationObserver((mutations) => {
        if (isEditorBooting(editor) || ensuring) {
            return;
        }

        // Ignore churn inside our Tailwind sectors (select sync, chip paint).
        const external = mutations.some((mutation) => {
            for (const node of mutation.addedNodes) {
                if (!(node instanceof Element)) {
                    continue;
                }

                if (node.closest?.('[data-voodbuilder-tw-sector]')) {
                    return false;
                }

                if (node.hasAttribute?.('data-voodbuilder-tw-sector')) {
                    return false;
                }

                return true;
            }

            return false;
        });

        if (! external && sectorsReady()) {
            return;
        }

        scheduleEnsure();
    });
    twObserver.observe(stylesMount, { childList: true, subtree: true });

    let stopClassWatch = null;

    const hydrateFromClasses = (component = null) => {
        if (editor.__voodbuilderTwStyleApplying) {
            return;
        }

        const target = component ?? editor.getSelected();
        const selected = editor.getSelected();

        // Ignore class mutations on non-selected components (symbols / bulk ops).
        if (selected && target && selected !== target) {
            return;
        }

        syncSelectsFromComponent(stylesMount, selected ?? target, editor, syncOpts());
    };

    const attachClassWatch = (component) => {
        stopClassWatch?.();
        stopClassWatch = null;

        if (! component) {
            return;
        }

        stopClassWatch = watchComponentClassList(component, () => {
            hydrateFromClasses(component);
        });
    };

    editor.on('load', () => window.setTimeout(ensure, 60));
    editor.on('load', () => {
        window.setTimeout(() => {
            try {
                hydrateAuthorStylesFromIdRules(editor);
                hydrateDecorationBackgroundImages(editor);
            } catch {
                // Optional hydrate after frame mount.
            }
        }, 120);
    });
    editor.on('component:selected', (component) => {
        window.setTimeout(() => {
            if (! sectorsReady()) {
                ensure();
            }

            // After refresh / dynamic remount, paints often live only on #id rules.
            try {
                hydrateAuthorStylesFromIdRules(editor, component);
                // Prefer durable src attr when CssComposer lost the photo.
                const src = String(component?.getAttributes?.()?.[STYLE_BG_SRC_ATTR] ?? '').trim();
                const painted = extractUrlFromBackgroundImage(
                    readComponentCssProperty(editor, component, 'background-image'),
                );

                if (src !== '' && painted === '') {
                    reapplyDecorationBackgroundPaint(editor, component, src);
                }
            } catch {
                // Optional hydrate — still sync from CssComposer below.
            }

            sanitizeInventedStyles(editor, component);
            syncSelectsFromComponent(stylesMount, component, editor, syncOpts({ resetLinkPref: true }));
            attachClassWatch(component);
        }, 0);
    });
    editor.on('voodbuilder:dynamic-blocks-refreshed', () => {
        window.setTimeout(() => {
            try {
                hydrateAuthorStylesFromIdRules(editor);
                hydrateDecorationBackgroundImages(editor);
            } catch {
                // Ignore hydrate race after remount.
            }

            const selected = editor.getSelected?.();

            if (selected && sectorsReady()) {
                syncSelectsFromComponent(stylesMount, selected, editor, syncOpts());
            }
        }, 0);
    });

    editor.on('component:deselected', () => {
        stopClassWatch?.();
        stopClassWatch = null;

        window.requestAnimationFrame(() => {
            const pageTarget = resolveStyleTarget(editor);

            if (pageTarget && sectorsReady()) {
                syncSelectsFromComponent(stylesMount, pageTarget, editor, syncOpts());
                attachClassWatch(pageTarget);
            }
        });
    });

    editor.on(PAGE_SURFACE_FOCUS_EVENT, () => {
        window.requestAnimationFrame(() => {
            const pageTarget = resolveStyleTarget(editor);

            if (pageTarget && sectorsReady()) {
                syncSelectsFromComponent(stylesMount, pageTarget, editor, syncOpts({ resetLinkPref: true }));
                attachClassWatch(pageTarget);
            }
        });
    });

    // Kept as a fallback when Grapes does emit it (rare for chip edits).
    editor.on('component:update:classes', (component) => {
        hydrateFromClasses(component);
    });

    const onDeviceChange = () => {
        syncViewportStrip(stylesMount, editor, labels);

        if (editor.__voodbuilderTwStyleApplying) {
            return;
        }

        const selected = editor.getSelected?.();

        if (selected && sectorsReady()) {
            syncSelectsFromComponent(stylesMount, selected, editor, syncOpts());
        }
    };

    editor.on('device:select', onDeviceChange);
    editor.on('change:device', onDeviceChange);

    // Chip rename updates the Selector model; collection `change` usually covers it,
    // but selector:update is a cheap extra signal when the selected component owns it.
    editor.on('selector:update', (selector) => {
        const selected = editor.getSelected();
        const selectors = selected?.getSelectors?.() ?? selected?.get?.('classes');

        if (! selected || ! selectors) {
            return;
        }

        const owned = typeof selectors.includes === 'function'
            ? selectors.includes(selector)
            : [...(selectors.models ?? selectors)].includes(selector);

        if (owned) {
            hydrateFromClasses(selected);
        }
    });

    attachClassWatch(editor.getSelected());

    // Block native SM from persisting invented paints while Tailwind panel owns styling.
    editor.on('style:property:update', (event) => {
        if (! editor.__voodbuilderTailwindStyleOnly) {
            return;
        }

        const propertyName = event?.property?.getName?.() ?? event?.property?.get?.('property');
        const opts = event?.opts ?? {};

        if (! propertyName || opts.__up === true || opts.avoidStore === true || opts.noTarget === true) {
            return;
        }

        // Font family remains allowed (Fontsource catalog needs inline stack).
        if (propertyName === 'font-family') {
            return;
        }

        const selected = editor.getSelected();
        const value = event?.value ?? event?.to?.value ?? '';

        if (
            selected
            && (
                isCorruptedStackStyleValue(value)
                || shouldOmitAuthorStyleValue(propertyName, value)
                || isStyleManagerInventedValue(propertyName, value)
                || String(value ?? '').trim() === ''
            )
        ) {
            clearStyleProperty(editor, selected, propertyName);

            return;
        }

        // Drop non-font native SM writes — utilities panel is the source of truth.
        if (selected && propertyName !== 'font-family') {
            clearStyleProperty(editor, selected, propertyName);
        }
    });

    // Also enhance custom Style panel selects after mount.
    window.setTimeout(() => {
        enhanceInspectorSelects(stylesMount);
    }, 0);

    ensure();
}

/**
 * After load / remount: rebuild decoration photos from durable
 * `data-vb-style-bg-src` (and CSS fallback) so refresh cannot leave empty
 * Image fields while Size/Position chips remain.
 *
 * @param {object} editor
 * @returns {number}
 */
export function hydrateDecorationBackgroundImages(editor) {
    if (! editor) {
        return 0;
    }

    const wrapper = editor.getWrapper?.();

    if (! wrapper?.onAll) {
        return 0;
    }

    // Last-resort map from live/saved CSS: #id { background-image: url(...) }
    const liveCss = String(editor.__voodbuilderPageLiveCss ?? '');
    const cssUrlById = new Map();

    if (liveCss.includes('url(')) {
        const ruleRe = /#([A-Za-z][\w-]*)\s*\{([^}]*)\}/g;
        let match;

        while ((match = ruleRe.exec(liveCss)) !== null) {
            const url = extractUrlFromBackgroundImage(match[2]);

            if (url !== '') {
                cssUrlById.set(match[1], url);
            }
        }
    }

    const wasApplying = Boolean(editor.__voodbuilderTwStyleApplying);
    editor.__voodbuilderTwStyleApplying = true;
    let updated = 0;

    try {
        wrapper.onAll((component) => {
            if (! component) {
                return;
            }

            const id = String(component.getId?.() ?? '').trim();
            let src = String(component.getAttributes?.()?.[STYLE_BG_SRC_ATTR] ?? '').trim();

            if (src === '') {
                const target = resolveVisualStyleTarget(component) ?? component;
                src = extractUrlFromBackgroundImage(
                    readComponentCssProperty(editor, target, 'background-image'),
                );

                if (src === '') {
                    src = extractUrlFromBackgroundImage(
                        readComponentCssProperty(editor, target, 'background'),
                    );
                }

                if (src === '') {
                    src = String(target.getAttributes?.()?.[STYLE_BG_SRC_ATTR] ?? '').trim();
                }

                if (src === '' && id !== '' && cssUrlById.has(id)) {
                    src = cssUrlById.get(id);
                }
            }

            if (src === '') {
                return;
            }

            reapplyDecorationBackgroundPaint(editor, component, src);
            updated += 1;
        });
    } finally {
        if (! wasApplying) {
            editor.__voodbuilderTwStyleApplying = false;
        }
    }

    if (updated > 0) {
        try {
            editor.trigger?.('update');
        } catch {
            // Optional.
        }
    }

    return updated;
}

export {
    sanitizeInventedStyles as sanitizeInventedStyleManagerProps,
    syncSelectsFromComponent,
    resolveSpacingState,
    spacingGroupFor,
    applySpacingToken,
    setSpacingLinkMode,
};
