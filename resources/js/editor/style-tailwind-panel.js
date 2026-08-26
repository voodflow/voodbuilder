/**
 * Style Manager Tailwind sectors — Dimension / Decorations / Typography.
 * Same pattern as Animation: apply exclusive utility classes, never invent Grapes inline CSS.
 */

import {
    clearBackgroundCssRules,
    clearStyleProperty,
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
import { createImageUrlField } from './editor-form-ui.js';
import {
    BACKGROUND_OPTIONS,
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
    GRADIENT_TO_OPTIONS,
    GRADIENT_VIA_OPTIONS,
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
    TEXT_COLOR_OPTIONS,
    TEXT_DECORATION_OPTIONS,
    TEXT_DECORATION_SEGMENTS,
    TEXT_TRANSFORM_OPTIONS,
    TEXT_TRANSFORM_SEGMENTS,
    TRACKING_OPTIONS,
    WIDTH_OPTIONS,
    classSetFromOptions,
    componentClassList,
    replaceClassGroup,
    resolveGroupValue,
    utilityConflictGroupIds,
} from './style-tailwind-class-groups.js';

const GROUP_SETS = Object.fromEntries(
    STYLE_UTILITY_GROUPS.map((group) => [group.id, classSetFromOptions(group.options)]),
);

const GROUP_INLINE = Object.fromEntries(
    STYLE_UTILITY_GROUPS.map((group) => [group.id, group.inlineProps ?? []]),
);

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

function fieldHtml({ label, selectAttr, addAttr, options, addLabel, searchable = false, searchPlaceholder = 'Search…', live = false }) {
    const searchAttr = searchable
        ? ` data-vb-search="1" data-vb-search-placeholder="${escapeAttr(searchPlaceholder)}"`
        : '';

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
                    <select class="voodbuilder-editor-input voodbuilder-editor-input--select"${searchAttr} ${selectAttr}>
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

    return String(value).replace(/^(m|p|mt|mr|mb|ml|pt|pr|pb|pl|mx|my|px|py)-/, '') || '';
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

function scheduleClassCompile(editor) {
    // Soft schedule only: Style panel catalogs ship in section-utilities.css, so
    // pageCssCoversClass usually no-ops — no compile overlay, realtime canvas.
    // Force rebuild would always show "Compiling styles…" and is reserved for
    // save / template / invalidate paths.
    editor.__voodbuilderSchedulePageCssRebuild?.(0);
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
            clearStyleProperty(editor, target, property, {
                family: property === 'background' || property === 'background-color' || property === 'background-image',
            });
        }

        // Only wipe the full background family for paint (color/image/shorthand).
        // background-size / position / repeat must NOT clear background-image —
        // that made Size/Position/Repeat deletes the chosen photo.
        const paintProps = new Set(['background', 'background-color', 'background-image']);

        if (properties.some((property) => paintProps.has(String(property)))) {
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

    for (const group of STYLE_UTILITY_GROUPS) {
        const el = root.querySelector(`[data-voodbuilder-tw-group="${group.id}"]`);

        if (el) {
            el.value = resolveGroupValue(classes, group.options);
            el.dispatchEvent(new Event('vb:options-changed', { bubbles: true }));
        }
    }

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

    syncSpacingBox(root, component, options);
    syncDecorationBlocks(root, component, options);
    syncBackgroundImageField(root, component);
    syncTypographySegments(root, component);
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

function applyGroup(editor, component, groupId, value) {
    const groupSet = GROUP_SETS[groupId];

    if (! groupSet || ! component) {
        return;
    }

    // Settings → classes writes; class-list watch re-hydrates selects only (no write-back).
    editor.__voodbuilderTwStyleApplying = true;

    try {
        const alsoClearIds = [...utilityConflictGroupIds(groupId)];

        // Solid color clears gradient stops; gradient direction clears solid color + image.
        if (groupId === 'background' && value) {
            alsoClearIds.push('gradient-direction', 'gradient-from', 'gradient-via', 'gradient-to');
        }

        if (groupId === 'gradient-direction' && value && value !== 'bg-none') {
            alsoClearIds.push('background');
            clearStyleProperty(editor, component, 'background-image');
        }

        if (
            (groupId === 'gradient-from' || groupId === 'gradient-via' || groupId === 'gradient-to')
            && value
        ) {
            clearStyleProperty(editor, component, 'background-image');
        }

        const alsoClear = alsoClearIds
            .map((id) => GROUP_SETS[id])
            .filter(Boolean);

        replaceClassGroup(component, groupSet, value || null, { alsoClear });
        clearInlineProps(editor, component, GROUP_INLINE[groupId] ?? []);
        // DOM first (realtime), compile only if the utility is missing from canvas CSS.
        try {
            component.view?.updateClasses?.();
        } catch {
            // View may be unavailable during bulk updates.
        }
        scheduleClassCompile(editor);
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
        const selected = editor.getSelected();

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

function decoLiveFieldHtml({ label, groupId, options, searchPlaceholder = null }) {
    return fieldHtml({
        label,
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
                    <details class="voodbuilder-editor-deco-fold" data-voodbuilder-deco-fold="image">
                        <summary class="voodbuilder-editor-deco-fold__summary">
                            <span>${escapeHtml(labels.classStyleBackgroundImage ?? 'Image')}</span>
                        </summary>
                        <div class="voodbuilder-editor-deco-fold__body">
                            <div class="voodbuilder-editor-deco-bg-image" data-voodbuilder-deco-bg-image></div>
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
                            <div class="voodbuilder-editor-deco-stops">
                                ${decoLiveFieldHtml({
                                    label: labels.classStyleGradientFrom ?? 'From',
                                    groupId: 'gradient-from',
                                    options: GRADIENT_FROM_OPTIONS,
                                    searchPlaceholder: searchPh,
                                })}
                                ${decoLiveFieldHtml({
                                    label: labels.classStyleGradientVia ?? 'Via',
                                    groupId: 'gradient-via',
                                    options: GRADIENT_VIA_OPTIONS,
                                    searchPlaceholder: searchPh,
                                })}
                                ${decoLiveFieldHtml({
                                    label: labels.classStyleGradientTo ?? 'To',
                                    groupId: 'gradient-to',
                                    options: GRADIENT_TO_OPTIONS,
                                    searchPlaceholder: searchPh,
                                })}
                            </div>
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
                            label: '',
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

function syncTypographySegments(root, component) {
    const classes = componentClassList(component);

    for (const [groupId, options] of Object.entries(PANEL_SEGMENT_GROUPS)) {
        const value = resolveGroupValue(classes, options);
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

            const current = resolveGroupValue(componentClassList(component), PANEL_SEGMENT_GROUPS[groupId] ?? []);

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

function readBackgroundImageUrl(component) {
    const inline = component?.getStyle?.({ inline: true }) ?? {};
    const live = component?.getStyle?.() ?? {};
    const raw = String(inline['background-image'] ?? live['background-image'] ?? '').trim();

    if (
        raw === ''
        || raw === 'none'
        || /^linear-gradient\(/i.test(raw)
        || /^radial-gradient\(/i.test(raw)
        || /^conic-gradient\(/i.test(raw)
    ) {
        return '';
    }

    const match = raw.match(/url\(\s*(['"]?)([^'")]+)\1\s*\)/i);

    return String(match?.[2] ?? '').trim();
}

function cssBackgroundImageValue(url) {
    const src = String(url ?? '').trim();

    if (src === '') {
        return '';
    }

    const escaped = src.replace(/\\/g, '\\\\').replace(/'/g, "\\'");

    return `url('${escaped}')`;
}

function clearDecorationBackgroundImage(editor, component) {
    if (! editor || ! component) {
        return;
    }

    clearStyleProperty(editor, component, 'background-image');
    replaceClassGroup(component, GROUP_SETS['bg-size'], null);
    replaceClassGroup(component, GROUP_SETS['bg-position'], null);
    replaceClassGroup(component, GROUP_SETS['bg-repeat'], null);
}

function applyDecorationBackgroundImage(editor, component, url) {
    if (! editor || ! component) {
        return;
    }

    editor.__voodbuilderTwStyleApplying = true;

    try {
        const src = String(url ?? '').trim();

        if (src === '') {
            clearDecorationBackgroundImage(editor, component);
        } else {
            // Image wins over gradient utilities.
            replaceClassGroup(component, GROUP_SETS['gradient-direction'], null, {
                alsoClear: [
                    GROUP_SETS['gradient-from'],
                    GROUP_SETS['gradient-via'],
                    GROUP_SETS['gradient-to'],
                ].filter(Boolean),
            });
            // Only strip the image paint — keep solid bg-* color as fallback.
            clearStyleProperty(editor, component, 'background-image');
            component.addStyle?.(
                { 'background-image': cssBackgroundImageValue(src) },
                { inline: true },
            );

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
        editor.__voodbuilderTwStyleApplying = false;
    }
}

function wireBackgroundImageField(editor, sector, labels = {}) {
    const mount = sector.querySelector('[data-voodbuilder-deco-bg-image]');

    if (! mount || mount.dataset.vbWired === '1') {
        return;
    }

    mount.dataset.vbWired = '1';
    mount.replaceChildren();

    const selected = editor.getSelected?.();
    const field = createImageUrlField({
        label: labels.classStyleBackgroundImageSrc ?? labels.imageSettingsHeroSrc ?? 'Background image',
        name: 'styleBgImage',
        value: readBackgroundImageUrl(selected),
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

function syncBackgroundImageField(root, component) {
    const mount = root.querySelector?.('[data-voodbuilder-deco-bg-image]');
    const input = mount?.__vbBgImageInput ?? mount?.querySelector?.('input');
    const url = readBackgroundImageUrl(component);
    const fold = root.querySelector?.('[data-voodbuilder-deco-fold="image"]');

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

    if (fold && String(fold.tagName ?? '').toUpperCase() === 'DETAILS' && url) {
        fold.open = true;
    }
}

function syncDecorationBlocks(root, component, options = {}) {
    const resetLinkPref = options.resetLinkPref === true;
    const classes = componentClassList(component);
    const decoRoot = root.querySelector('[data-voodbuilder-decorations]');

    if (! decoRoot || typeof decoRoot.querySelector !== 'function') {
        return;
    }

    const bg = resolveGroupValue(classes, BACKGROUND_OPTIONS);
    const gradDir = resolveGroupValue(classes, GRADIENT_DIRECTION_OPTIONS);
    const gradFrom = resolveGroupValue(classes, GRADIENT_FROM_OPTIONS);
    const gradVia = resolveGroupValue(classes, GRADIENT_VIA_OPTIONS);
    const gradTo = resolveGroupValue(classes, GRADIENT_TO_OPTIONS);
    const hasGradient = Boolean(
        (gradDir && gradDir !== 'bg-none')
        || gradFrom
        || gradVia
        || gradTo,
    );

    const borderAll = resolveGroupValue(classes, BORDER_WIDTH_OPTIONS);
    const borderT = resolveGroupValue(classes, BORDER_T_WIDTH_OPTIONS);
    const borderR = resolveGroupValue(classes, BORDER_R_WIDTH_OPTIONS);
    const borderB = resolveGroupValue(classes, BORDER_B_WIDTH_OPTIONS);
    const borderL = resolveGroupValue(classes, BORDER_L_WIDTH_OPTIONS);
    const borderStyle = resolveGroupValue(classes, BORDER_STYLE_OPTIONS);
    const borderColor = resolveGroupValue(classes, BORDER_COLOR_OPTIONS);
    const hasBorderSides = Boolean(borderT || borderR || borderB || borderL);
    const hasBorder = Boolean(borderAll || hasBorderSides || borderStyle || borderColor);

    const roundedAll = resolveGroupValue(classes, ROUNDED_OPTIONS);
    const roundedTl = resolveGroupValue(classes, ROUNDED_TL_OPTIONS);
    const roundedTr = resolveGroupValue(classes, ROUNDED_TR_OPTIONS);
    const roundedBr = resolveGroupValue(classes, ROUNDED_BR_OPTIONS);
    const roundedBl = resolveGroupValue(classes, ROUNDED_BL_OPTIONS);
    const hasRoundedCorners = Boolean(roundedTl || roundedTr || roundedBr || roundedBl);
    const hasRounded = Boolean(roundedAll || hasRoundedCorners
        || resolveGroupValue(classes, ROUNDED_T_OPTIONS)
        || resolveGroupValue(classes, ROUNDED_R_OPTIONS)
        || resolveGroupValue(classes, ROUNDED_B_OPTIONS)
        || resolveGroupValue(classes, ROUNDED_L_OPTIONS));

    const shadow = resolveGroupValue(classes, SHADOW_OPTIONS);
    const shadowColor = resolveGroupValue(classes, SHADOW_COLOR_OPTIONS);
    const dropShadow = resolveGroupValue(classes, DROP_SHADOW_OPTIONS);
    const hasBgImage = Boolean(readBackgroundImageUrl(component));

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
                        const token = resolveGroupValue(classes, BORDER_T_WIDTH_OPTIONS)
                            || resolveGroupValue(classes, BORDER_R_WIDTH_OPTIONS)
                            || resolveGroupValue(classes, BORDER_B_WIDTH_OPTIONS)
                            || resolveGroupValue(classes, BORDER_L_WIDTH_OPTIONS)
                            || resolveGroupValue(classes, BORDER_WIDTH_OPTIONS)
                            || '';
                        const shorthand = String(token).replace(/^border-[trbl]/, 'border');
                        applyGroup(editor, component, 'border-width', shorthand);
                    } else {
                        const all = resolveGroupValue(classes, BORDER_WIDTH_OPTIONS) || '';

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
                        const token = resolveGroupValue(classes, ROUNDED_TL_OPTIONS)
                            || resolveGroupValue(classes, ROUNDED_TR_OPTIONS)
                            || resolveGroupValue(classes, ROUNDED_BR_OPTIONS)
                            || resolveGroupValue(classes, ROUNDED_BL_OPTIONS)
                            || resolveGroupValue(classes, ROUNDED_OPTIONS)
                            || '';
                        const shorthand = String(token)
                            .replace(/^rounded-(tl|tr|br|bl|t|r|b|l)/, 'rounded');
                        applyGroup(editor, component, 'rounded', shorthand);
                    } else {
                        const all = resolveGroupValue(classes, ROUNDED_OPTIONS) || '';
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

function resolveSpacingState(kind, classes) {
    const cfg = SPACING_KIND[kind];

    if (! cfg) {
        return { link: 'independent', sides: { t: '', r: '', b: '', l: '' }, hasValue: false };
    }

    const all = resolveGroupValue(classes, cfg.all);

    if (all) {
        const token = spacingTokenFromClass(all);

        return {
            link: 'all',
            sides: { t: token, r: token, b: token, l: token },
            hasValue: Boolean(token),
        };
    }

    const x = resolveGroupValue(classes, cfg.x);
    const y = resolveGroupValue(classes, cfg.y);
    const tSide = resolveGroupValue(classes, cfg.t);
    const rSide = resolveGroupValue(classes, cfg.r);
    const bSide = resolveGroupValue(classes, cfg.b);
    const lSide = resolveGroupValue(classes, cfg.l);

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

    const all = resolveGroupValue(componentClassList(component), cfg.all);

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
    const state = resolveSpacingState(kind, componentClassList(component));
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

function syncSpacingBox(root, component, options = {}) {
    const resetLinkPref = options.resetLinkPref === true;

    for (const kind of ['margin', 'padding']) {
        const block = root.querySelector(`[data-voodbuilder-spacing-box="${kind}"]`);

        if (! block) {
            continue;
        }

        const state = resolveSpacingState(kind, componentClassList(component));

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
            dot.hidden = ! state.hasValue;
        }

        for (const side of ['t', 'r', 'b', 'l']) {
            const input = block.querySelector(
                `input[data-voodbuilder-spacing-kind="${kind}"][data-voodbuilder-spacing-side="${side}"]`,
            );

            if (! input || document.activeElement === input) {
                continue;
            }

            const value = state.sides[side] || '';
            input.value = value;
            input.classList.toggle('is-set', Boolean(value));
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
        resolveGroupValue(componentClassList(selected), target.options),
    );

    const pop = document.createElement('div');
    pop.className = 'voodbuilder-editor-spacing-popover';
    pop.dataset.voodbuilderSpacingPopover = `${kind}-${side}`;
    pop.innerHTML = `
        <div class="voodbuilder-editor-spacing-popover__head">
            <span>${escapeHtml(labels.classStyleSpacingScale ?? 'Tailwind scale')}</span>
            <input type="search" class="voodbuilder-editor-spacing-popover__search" placeholder="${escapeAttr(labels.classStyleSpacingSearch ?? 'Search…')}" autocomplete="off" />
        </div>
        <div class="voodbuilder-editor-spacing-popover__grid" role="listbox"></div>
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
                    syncSpacingBox(sector.closest('.gjs-sm-sectors') ?? sector, component);
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
                    syncSpacingBox(sector.closest('.gjs-sm-sectors') ?? sector, component);
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

function wireSectorFields(editor, sector, labels = {}) {
    const liveGroups = new Set([
        'font-size',
        'font-weight',
        'text-align',
        'text-color',
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

    if (! editor || ! stylesMount || editor.__voodbuilderTailwindStylePanelRegistered) {
        return;
    }

    editor.__voodbuilderTailwindStylePanelRegistered = true;
    editor.__voodbuilderTailwindStyleOnly = true;

    const addLabel = labels.classAnimationAdd ?? labels.classStyleAdd ?? 'Add';

    const ensure = () => {
        hideNativeStyleManagerSectors(stylesMount);

        if (
            stylesMount.querySelector('[data-voodbuilder-tw-sector="dimension"]')
            && stylesMount.querySelector('[data-voodbuilder-tw-sector="spacing"]')
        ) {
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

        // Marker for MutationObserver idempotency when sectors move
        if (! stylesMount.querySelector('[data-voodbuilder-tw-root]')) {
            const marker = document.createElement('div');
            marker.hidden = true;
            marker.dataset.voodbuilderTwRoot = '';
            stylesMount.appendChild(marker);
        }

        syncSelectsFromComponent(stylesMount, editor.getSelected(), editor);
    };

    const observer = new MutationObserver(() => {
        hideNativeStyleManagerSectors(stylesMount);
        ensure();
    });
    observer.observe(stylesMount, { childList: true, subtree: true });

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

        syncSelectsFromComponent(stylesMount, selected ?? target, editor);
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
    editor.on('component:selected', (component) => {
        window.setTimeout(() => {
            ensure();
            sanitizeInventedStyles(editor, component);
            syncSelectsFromComponent(stylesMount, component, editor, { resetLinkPref: true });
            attachClassWatch(component);
        }, 0);
    });

    editor.on('component:deselected', () => {
        stopClassWatch?.();
        stopClassWatch = null;
    });

    // Kept as a fallback when Grapes does emit it (rare for chip edits).
    editor.on('component:update:classes', (component) => {
        hydrateFromClasses(component);
    });

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

export {
    sanitizeInventedStyles as sanitizeInventedStyleManagerProps,
    syncSelectsFromComponent,
    resolveSpacingState,
    spacingGroupFor,
    applySpacingToken,
    setSpacingLinkMode,
};
