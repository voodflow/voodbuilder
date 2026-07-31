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
import { applyEditorFontFamily } from './fonts/fonts-ui.js';
import { styleManagerFontOptions } from './fonts/catalog.js';
import {
    BACKGROUND_OPTIONS,
    BORDER_COLOR_OPTIONS,
    BORDER_STYLE_OPTIONS,
    BORDER_WIDTH_OPTIONS,
    FONT_SIZE_OPTIONS,
    FONT_WEIGHT_OPTIONS,
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
    ROUNDED_OPTIONS,
    SHADOW_OPTIONS,
    STYLE_UTILITY_GROUPS,
    TEXT_ALIGN_OPTIONS,
    TEXT_COLOR_OPTIONS,
    WIDTH_OPTIONS,
    classSetFromOptions,
    componentClassList,
    replaceClassGroup,
    resolveGroupValue,
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
    return options.map((opt) => (
        `<option value="${escapeAttr(opt.value)}">${escapeHtml(opt.label)}</option>`
    )).join('');
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

function fieldHtml({ label, selectAttr, addAttr, options, addLabel }) {
    return `
        <div class="gjs-sm-property voodbuilder-editor-anim-property">
            <div class="gjs-sm-label"><span class="gjs-sm-label-text">${escapeHtml(label)}</span></div>
            <div class="gjs-fields">
                <div class="voodbuilder-editor-anim-combobox">
                    <select class="voodbuilder-editor-input voodbuilder-editor-input--select" ${selectAttr}>
                        ${optionsHtml(options)}
                    </select>
                    <button type="button" class="voodbuilder-editor-anim-combobox__add" ${addAttr}>
                        ${escapeHtml(addLabel)}
                    </button>
                </div>
            </div>
        </div>
    `;
}

function scheduleClassCompile(editor) {
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

    for (const property of properties) {
        clearStyleProperty(editor, target, property, {
            family: property === 'background' || property === 'background-color' || property === 'background-image',
        });
    }

    if (properties.some((property) => String(property).startsWith('background'))) {
        clearBackgroundCssRules(editor, target);
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

function syncSelectsFromComponent(root, component) {
    if (! root) {
        return;
    }

    const classes = componentClassList(component);

    for (const group of STYLE_UTILITY_GROUPS) {
        const el = root.querySelector(`[data-voodbuilder-tw-group="${group.id}"]`);

        if (el) {
            el.value = resolveGroupValue(classes, group.options);
        }
    }

    const fontSelect = root.querySelector('[data-voodbuilder-tw-font-family]');

    if (fontSelect) {
        const family = String(
            component?.getStyle?.({ inline: true })?.['font-family']
            ?? component?.getStyle?.()?.['font-family']
            ?? '',
        ).trim();

        fontSelect.value = family && [...fontSelect.options].some((opt) => opt.value === family)
            ? family
            : '';

        if (family && fontSelect.value === '') {
            const orphan = document.createElement('option');
            orphan.value = family;
            orphan.textContent = family;
            fontSelect.appendChild(orphan);
            fontSelect.value = family;
        }
    }
}

function applyGroup(editor, component, groupId, value) {
    const groupSet = GROUP_SETS[groupId];

    if (! groupSet || ! component) {
        return;
    }

    replaceClassGroup(component, groupSet, value || null);
    clearInlineProps(editor, component, GROUP_INLINE[groupId] ?? []);
    scheduleClassCompile(editor);
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

function bindGroupField(editor, root, groupId) {
    const select = root.querySelector(`[data-voodbuilder-tw-group="${groupId}"]`);
    const add = root.querySelector(`[data-voodbuilder-tw-group-add="${groupId}"]`);

    const apply = () => {
        const selected = editor.getSelected();

        if (! selected) {
            return;
        }

        applyGroup(editor, selected, groupId, select?.value ?? '');
        syncSelectsFromComponent(root.closest('.gjs-sm-sectors') ?? root, selected);
    };

    add?.addEventListener('click', apply);
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
            ${fieldHtml({ label: labels.classStyleMargin ?? 'Margin', selectAttr: 'data-voodbuilder-tw-group="margin"', addAttr: 'data-voodbuilder-tw-group-add="margin"', options: MARGIN_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleMarginX ?? 'Margin X', selectAttr: 'data-voodbuilder-tw-group="margin-x"', addAttr: 'data-voodbuilder-tw-group-add="margin-x"', options: MARGIN_X_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleMarginY ?? 'Margin Y', selectAttr: 'data-voodbuilder-tw-group="margin-y"', addAttr: 'data-voodbuilder-tw-group-add="margin-y"', options: MARGIN_Y_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleMarginT ?? 'Margin top', selectAttr: 'data-voodbuilder-tw-group="margin-t"', addAttr: 'data-voodbuilder-tw-group-add="margin-t"', options: MARGIN_T_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleMarginR ?? 'Margin right', selectAttr: 'data-voodbuilder-tw-group="margin-r"', addAttr: 'data-voodbuilder-tw-group-add="margin-r"', options: MARGIN_R_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleMarginB ?? 'Margin bottom', selectAttr: 'data-voodbuilder-tw-group="margin-b"', addAttr: 'data-voodbuilder-tw-group-add="margin-b"', options: MARGIN_B_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleMarginL ?? 'Margin left', selectAttr: 'data-voodbuilder-tw-group="margin-l"', addAttr: 'data-voodbuilder-tw-group-add="margin-l"', options: MARGIN_L_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStylePadding ?? 'Padding', selectAttr: 'data-voodbuilder-tw-group="padding"', addAttr: 'data-voodbuilder-tw-group-add="padding"', options: PADDING_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStylePaddingX ?? 'Padding X', selectAttr: 'data-voodbuilder-tw-group="padding-x"', addAttr: 'data-voodbuilder-tw-group-add="padding-x"', options: PADDING_X_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStylePaddingY ?? 'Padding Y', selectAttr: 'data-voodbuilder-tw-group="padding-y"', addAttr: 'data-voodbuilder-tw-group-add="padding-y"', options: PADDING_Y_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStylePaddingT ?? 'Padding top', selectAttr: 'data-voodbuilder-tw-group="padding-t"', addAttr: 'data-voodbuilder-tw-group-add="padding-t"', options: PADDING_T_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStylePaddingR ?? 'Padding right', selectAttr: 'data-voodbuilder-tw-group="padding-r"', addAttr: 'data-voodbuilder-tw-group-add="padding-r"', options: PADDING_R_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStylePaddingB ?? 'Padding bottom', selectAttr: 'data-voodbuilder-tw-group="padding-b"', addAttr: 'data-voodbuilder-tw-group-add="padding-b"', options: PADDING_B_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStylePaddingL ?? 'Padding left', selectAttr: 'data-voodbuilder-tw-group="padding-l"', addAttr: 'data-voodbuilder-tw-group-add="padding-l"', options: PADDING_L_OPTIONS, addLabel })}
        `,
    });
}

function buildDecorationsSector(labels, addLabel) {
    return buildCollapsibleSector({
        id: 'decorations',
        title: labels.classStyleDecorationsTitle ?? 'Decorations',
        bodyHtml: `
            ${fieldHtml({ label: labels.classStyleBackground ?? 'Background', selectAttr: 'data-voodbuilder-tw-group="background"', addAttr: 'data-voodbuilder-tw-group-add="background"', options: BACKGROUND_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleBorderWidth ?? 'Border width', selectAttr: 'data-voodbuilder-tw-group="border-width"', addAttr: 'data-voodbuilder-tw-group-add="border-width"', options: BORDER_WIDTH_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleBorderStyle ?? 'Border style', selectAttr: 'data-voodbuilder-tw-group="border-style"', addAttr: 'data-voodbuilder-tw-group-add="border-style"', options: BORDER_STYLE_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleBorderColor ?? 'Border color', selectAttr: 'data-voodbuilder-tw-group="border-color"', addAttr: 'data-voodbuilder-tw-group-add="border-color"', options: BORDER_COLOR_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleRounded ?? 'Rounded', selectAttr: 'data-voodbuilder-tw-group="rounded"', addAttr: 'data-voodbuilder-tw-group-add="rounded"', options: ROUNDED_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleShadow ?? 'Shadow', selectAttr: 'data-voodbuilder-tw-group="shadow"', addAttr: 'data-voodbuilder-tw-group-add="shadow"', options: SHADOW_OPTIONS, addLabel })}
        `,
    });
}

function buildTypographySector(labels, addLabel) {
    return buildCollapsibleSector({
        id: 'typography',
        title: labels.classStyleTypographyTitle ?? 'Typography',
        bodyHtml: `
            <div class="gjs-sm-property voodbuilder-editor-anim-property">
                <div class="gjs-sm-label"><span class="gjs-sm-label-text">${escapeHtml(labels.classStyleFontFamily ?? 'Font family')}</span></div>
                <div class="gjs-fields">
                    <div class="voodbuilder-editor-anim-combobox">
                        <select class="voodbuilder-editor-input voodbuilder-editor-input--select" data-voodbuilder-tw-font-family>
                            ${fontOptionsHtml()}
                        </select>
                        <button type="button" class="voodbuilder-editor-anim-combobox__add" data-voodbuilder-tw-font-family-add>
                            ${escapeHtml(addLabel)}
                        </button>
                    </div>
                </div>
            </div>
            ${fieldHtml({ label: labels.classStyleFontSize ?? 'Font size', selectAttr: 'data-voodbuilder-tw-group="font-size"', addAttr: 'data-voodbuilder-tw-group-add="font-size"', options: FONT_SIZE_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleFontWeight ?? 'Font weight', selectAttr: 'data-voodbuilder-tw-group="font-weight"', addAttr: 'data-voodbuilder-tw-group-add="font-weight"', options: FONT_WEIGHT_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleTextAlign ?? 'Text align', selectAttr: 'data-voodbuilder-tw-group="text-align"', addAttr: 'data-voodbuilder-tw-group-add="text-align"', options: TEXT_ALIGN_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleTextColor ?? 'Text color', selectAttr: 'data-voodbuilder-tw-group="text-color"', addAttr: 'data-voodbuilder-tw-group-add="text-color"', options: TEXT_COLOR_OPTIONS, addLabel })}
            ${fieldHtml({ label: labels.classStyleLeading ?? 'Line height', selectAttr: 'data-voodbuilder-tw-group="leading"', addAttr: 'data-voodbuilder-tw-group-add="leading"', options: LEADING_OPTIONS, addLabel })}
            <p class="voodbuilder-editor-hint voodbuilder-editor-sm-sector-tw__hint">${escapeHtml(labels.classStyleEmptyHint ?? 'Select utilities to style this element. Empty fields mean nothing authored.')}</p>
        `,
    });
}

function wireSectorFields(editor, sector) {
    for (const group of STYLE_UTILITY_GROUPS) {
        bindGroupField(editor, sector, group.id);
    }

    const fontAdd = sector.querySelector('[data-voodbuilder-tw-font-family-add]');
    const fontSelect = sector.querySelector('[data-voodbuilder-tw-font-family]');

    fontAdd?.addEventListener('click', () => {
        const selected = editor.getSelected();

        if (! selected) {
            return;
        }

        const value = String(fontSelect?.value ?? '').trim();

        if (value === '') {
            clearStyleProperty(editor, selected, 'font-family');

            return;
        }

        void applyEditorFontFamily(editor, selected, value);
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

        if (stylesMount.querySelector('[data-voodbuilder-tw-sector="dimension"]')) {
            return;
        }

        const dimension = buildDimensionSector(labels, addLabel);
        const decorations = buildDecorationsSector(labels, addLabel);
        const typography = buildTypographySector(labels, addLabel);

        for (const sector of [dimension, decorations, typography]) {
            wireSectorFields(editor, sector);
        }

        placeSectors(stylesMount, [dimension, decorations, typography]);

        // Marker for MutationObserver idempotency when sectors move
        if (! stylesMount.querySelector('[data-voodbuilder-tw-root]')) {
            const marker = document.createElement('div');
            marker.hidden = true;
            marker.dataset.voodbuilderTwRoot = '';
            stylesMount.appendChild(marker);
        }

        syncSelectsFromComponent(stylesMount, editor.getSelected());
    };

    const observer = new MutationObserver(() => {
        hideNativeStyleManagerSectors(stylesMount);
        ensure();
    });
    observer.observe(stylesMount, { childList: true, subtree: true });

    editor.on('load', () => window.setTimeout(ensure, 60));
    editor.on('component:selected', (component) => {
        window.setTimeout(() => {
            ensure();
            sanitizeInventedStyles(editor, component);
            syncSelectsFromComponent(stylesMount, component);
        }, 0);
    });

    editor.on('component:update:classes', (component) => {
        syncSelectsFromComponent(stylesMount, component ?? editor.getSelected());
    });

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

    ensure();
}

export { sanitizeInventedStyles as sanitizeInventedStyleManagerProps };
