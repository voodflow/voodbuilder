/**
 * Style Manager “Animation” drawer (after Decorations, before Extra).
 * Tailwind CSS Animated utilities — our SM UI, not a clone of the external configurator.
 * @see https://www.tailwindcss-animated.com/configurator.html
 * @see https://github.com/new-data-services/tailwindcss-animated
 */

import { replayEditorCanvasAnimations } from './vb-runtime.js';
import {
    componentClassList,
    replaceClassGroup,
} from './style-tailwind-class-groups.js';
import { VISIBLE_MARKER_CLASS } from './style-animation-safelist.js';

const INTERACTION_OPTIONS = [
    { value: '', label: 'always' },
    { value: 'hover:', label: 'on hover' },
    { value: 'active:', label: 'on click' },
    { value: 'visible', label: 'on visible' },
];

/** Real Tailwind variant prefixes (not the “on visible” marker). */
const CSS_INTERACTION_PREFIXES = ['', 'hover:', 'active:'];
const VISIBLE_INTERACTION = 'visible';

const ANIMATION_OPTIONS = [
    { value: '', label: 'none' },
    { value: 'animate-spin', label: 'spin' },
    { value: 'animate-ping', label: 'ping' },
    { value: 'animate-pulse', label: 'pulse' },
    { value: 'animate-bounce', label: 'bounce' },
    { value: 'animate-wiggle', label: 'wiggle' },
    { value: 'animate-wiggle-more', label: 'wiggle more' },
    { value: 'animate-rotate-y', label: 'rotate y' },
    { value: 'animate-rotate-x', label: 'rotate x' },
    { value: 'animate-jump', label: 'jump' },
    { value: 'animate-jump-in', label: 'jump in' },
    { value: 'animate-jump-out', label: 'jump out' },
    { value: 'animate-shake', label: 'shake' },
    { value: 'animate-fade', label: 'fade' },
    { value: 'animate-fade-down', label: 'fade down' },
    { value: 'animate-fade-up', label: 'fade up' },
    { value: 'animate-fade-left', label: 'fade left' },
    { value: 'animate-fade-right', label: 'fade right' },
    { value: 'animate-flip-up', label: 'flip up' },
    { value: 'animate-flip-down', label: 'flip down' },
];

const ITERATION_OPTIONS = [
    { value: '', label: '—' },
    { value: 'animate-infinite', label: 'infinite' },
    { value: 'animate-once', label: 'once' },
    { value: 'animate-twice', label: 'twice' },
    { value: 'animate-thrice', label: 'thrice' },
];

const DURATION_OPTIONS = [
    { value: '', label: '—' },
    { value: 'animate-duration-75', label: '75ms' },
    { value: 'animate-duration-100', label: '100ms' },
    { value: 'animate-duration-150', label: '150ms' },
    { value: 'animate-duration-200', label: '200ms' },
    { value: 'animate-duration-300', label: '300ms' },
    { value: 'animate-duration-500', label: '500ms' },
    { value: 'animate-duration-700', label: '700ms' },
    { value: 'animate-duration-1000', label: '1s' },
    { value: 'animate-duration-2000', label: '2s' },
    { value: 'animate-duration-3000', label: '3s' },
    { value: 'animate-duration-5000', label: '5s' },
    { value: 'animate-duration-8000', label: '8s' },
    { value: 'animate-duration-10000', label: '10s' },
    { value: 'animate-duration-15000', label: '15s' },
    { value: 'animate-duration-20000', label: '20s' },
    { value: 'animate-duration-30000', label: '30s' },
];

const DELAY_OPTIONS = [
    { value: '', label: '—' },
    { value: 'animate-delay-none', label: '0ms' },
    { value: 'animate-delay-75', label: '75ms' },
    { value: 'animate-delay-100', label: '100ms' },
    { value: 'animate-delay-150', label: '150ms' },
    { value: 'animate-delay-200', label: '200ms' },
    { value: 'animate-delay-300', label: '300ms' },
    { value: 'animate-delay-500', label: '500ms' },
    { value: 'animate-delay-700', label: '700ms' },
    { value: 'animate-delay-1000', label: '1000ms' },
];

const EASE_OPTIONS = [
    { value: '', label: '—' },
    { value: 'animate-ease', label: 'ease' },
    { value: 'animate-ease-linear', label: 'linear' },
    { value: 'animate-ease-in', label: 'ease in' },
    { value: 'animate-ease-out', label: 'ease out' },
    { value: 'animate-ease-in-out', label: 'ease in out' },
];

const DIRECTION_OPTIONS = [
    { value: '', label: '—' },
    { value: 'animate-normal', label: 'normal' },
    { value: 'animate-reverse', label: 'reverse' },
    { value: 'animate-alternate', label: 'alternate' },
    { value: 'animate-alternate-reverse', label: 'alternate reverse' },
];

const FILL_OPTIONS = [
    { value: '', label: '—' },
    { value: 'animate-fill-none', label: 'none' },
    { value: 'animate-fill-forwards', label: 'forwards' },
    { value: 'animate-fill-backwards', label: 'backwards' },
    { value: 'animate-fill-both', label: 'both' },
];

const TRANSITION_OPTIONS = [
    { value: '', label: 'none' },
    { value: 'transition', label: 'transition' },
    { value: 'transition-all', label: 'all' },
    { value: 'transition-colors', label: 'colors' },
    { value: 'transition-opacity', label: 'opacity' },
    { value: 'transition-shadow', label: 'shadow' },
    { value: 'transition-transform', label: 'transform' },
    { value: 'transition-none', label: 'none (explicit)' },
];

const TRANSITION_DURATION_OPTIONS = [
    { value: '', label: '—' },
    { value: 'duration-75', label: '75ms' },
    { value: 'duration-100', label: '100ms' },
    { value: 'duration-150', label: '150ms' },
    { value: 'duration-200', label: '200ms' },
    { value: 'duration-300', label: '300ms' },
    { value: 'duration-500', label: '500ms' },
    { value: 'duration-700', label: '700ms' },
    { value: 'duration-1000', label: '1000ms' },
];

const TRANSITION_EASE_OPTIONS = [
    { value: '', label: '—' },
    { value: 'ease-linear', label: 'linear' },
    { value: 'ease-in', label: 'ease in' },
    { value: 'ease-out', label: 'ease out' },
    { value: 'ease-in-out', label: 'ease in out' },
];

const TRANSITION_DELAY_OPTIONS = [
    { value: '', label: '—' },
    { value: 'delay-75', label: '75ms' },
    { value: 'delay-100', label: '100ms' },
    { value: 'delay-150', label: '150ms' },
    { value: 'delay-200', label: '200ms' },
    { value: 'delay-300', label: '300ms' },
    { value: 'delay-500', label: '500ms' },
    { value: 'delay-700', label: '700ms' },
    { value: 'delay-1000', label: '1000ms' },
];

/** Legacy SM classes from the first Animation drawer iteration. */
const LEGACY_DURATION_MAP = {
    'duration-75': 'animate-duration-75',
    'duration-100': 'animate-duration-100',
    'duration-150': 'animate-duration-150',
    'duration-200': 'animate-duration-200',
    'duration-300': 'animate-duration-300',
    'duration-500': 'animate-duration-500',
    'duration-700': 'animate-duration-700',
    'duration-1000': 'animate-duration-1000',
};

const LEGACY_EASE_MAP = {
    'ease-linear': 'animate-ease-linear',
    'ease-in': 'animate-ease-in',
    'ease-out': 'animate-ease-out',
    'ease-in-out': 'animate-ease-in-out',
};

const ANIMATION_BASES = ANIMATION_OPTIONS.map((o) => o.value).filter(Boolean);
const ANIMATION_CLASS_SET = new Set(
    ANIMATION_BASES.flatMap((base) => CSS_INTERACTION_PREFIXES.map((prefix) => `${prefix}${base}`)),
);

const ITERATION_BASES = ITERATION_OPTIONS.map((o) => o.value).filter(Boolean);
const DURATION_BASES = DURATION_OPTIONS.map((o) => o.value).filter(Boolean);
const DELAY_BASES = DELAY_OPTIONS.map((o) => o.value).filter(Boolean);
const EASE_BASES = EASE_OPTIONS.map((o) => o.value).filter(Boolean);
const DIRECTION_BASES = DIRECTION_OPTIONS.map((o) => o.value).filter(Boolean);
const FILL_BASES = FILL_OPTIONS.map((o) => o.value).filter(Boolean);

/** hover:animate-spin resets the animation shorthand — modifiers must share the same variant. */
function prefixedClassSet(bases) {
    return new Set(bases.flatMap((base) => CSS_INTERACTION_PREFIXES.map((prefix) => `${prefix}${base}`)));
}

const ITERATION_CLASS_SET = prefixedClassSet(ITERATION_BASES);
const DURATION_CLASS_SET = prefixedClassSet(DURATION_BASES);
const DELAY_CLASS_SET = prefixedClassSet(DELAY_BASES);
const EASE_CLASS_SET = prefixedClassSet(EASE_BASES);
const DIRECTION_CLASS_SET = prefixedClassSet(DIRECTION_BASES);
const FILL_CLASS_SET = prefixedClassSet(FILL_BASES);
const TRANSITION_CLASS_SET = new Set(TRANSITION_OPTIONS.map((o) => o.value).filter(Boolean));
const TRANSITION_DURATION_CLASS_SET = new Set(TRANSITION_DURATION_OPTIONS.map((o) => o.value).filter(Boolean));
const TRANSITION_EASE_CLASS_SET = new Set(TRANSITION_EASE_OPTIONS.map((o) => o.value).filter(Boolean));
const TRANSITION_DELAY_CLASS_SET = new Set(TRANSITION_DELAY_OPTIONS.map((o) => o.value).filter(Boolean));

const ANIMATION_MODIFIER_GROUPS = [
    { bases: ITERATION_BASES, set: ITERATION_CLASS_SET },
    { bases: DURATION_BASES, set: DURATION_CLASS_SET },
    { bases: DELAY_BASES, set: DELAY_CLASS_SET },
    { bases: EASE_BASES, set: EASE_CLASS_SET },
    { bases: DIRECTION_BASES, set: DIRECTION_CLASS_SET },
    { bases: FILL_BASES, set: FILL_CLASS_SET },
];

function optionsHtml(options) {
    return options.map((opt) => (
        `<option value="${opt.value}">${opt.label}</option>`
    )).join('');
}

function attrValue(value) {
    return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
}

/** Compact SM row: label | select (live apply on change, same as Style Dimension/Typography). */
function fieldHtml({ label, selectAttr, options }) {
    return `
        <div class="gjs-sm-property voodbuilder-editor-anim-property voodbuilder-editor-anim-property--live">
            <div class="gjs-sm-label"><span class="gjs-sm-label-text">${label}</span></div>
            <div class="gjs-fields">
                <div class="voodbuilder-editor-anim-combobox voodbuilder-editor-anim-combobox--solo">
                    <select class="voodbuilder-editor-input voodbuilder-editor-input--select" aria-label="${attrValue(label)}" ${selectAttr}>
                        ${optionsHtml(options)}
                    </select>
                </div>
            </div>
        </div>
    `;
}

const TAB_ICONS = {
    preset: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"/></svg>',
    timing: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>',
    easing: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 19.5c5.25-1.5 7.5-7.5 8.25-12 1.5 6 4.5 9.75 8.25 12"/></svg>',
    direction: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-9L21 12m0 0L16.5 16.5M21 12H7.5"/></svg>',
    fill: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42"/></svg>',
    transition: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21H6a2.25 2.25 0 0 1-2.25-2.25v-1.5A2.25 2.25 0 0 1 6 15h1.5m9 6H18a2.25 2.25 0 0 0 2.25-2.25v-1.5A2.25 2.25 0 0 0 18 15h-1.5m-6-9H6A2.25 2.25 0 0 0 3.75 8.25v1.5A2.25 2.25 0 0 0 6 12h1.5m9-6H18a2.25 2.25 0 0 1 2.25 2.25v1.5A2.25 2.25 0 0 1 18 12h-1.5"/></svg>',
};

function tabButtonHtml(id, title, icon) {
    return `
        <button type="button"
            class="voodbuilder-editor-anim-tab"
            data-voodbuilder-anim-tab="${id}"
            title="${title}"
            aria-label="${title}"
            aria-selected="false">
            ${icon}
        </button>
    `;
}

function panelHtml(id, fields, { active = false } = {}) {
    return `
        <div class="voodbuilder-editor-anim-panel${active ? ' is-active' : ''}"
            data-voodbuilder-anim-panel="${id}"
            ${active ? '' : 'hidden'}>
            ${fields}
        </div>
    `;
}

function findSectorByTitle(stylesMount, titleNeedle) {
    const needle = String(titleNeedle ?? '').toLowerCase();

    for (const sector of stylesMount.querySelectorAll('.gjs-sm-sector')) {
        const title = sector.querySelector('.gjs-sm-sector-title, .gjs-sm-title');
        const text = String(title?.textContent ?? '').trim().toLowerCase();

        if (text.includes(needle)) {
            return sector;
        }
    }

    return null;
}

function componentHasAnimationUtility(component) {
    return componentClassList(component).some((name) => {
        const base = name.replace(/^!/, '').replace(/^(?:hover|active):/, '');

        return base === VISIBLE_MARKER_CLASS
            || base.startsWith('animate-')
            || base === 'transition'
            || base.startsWith('transition-')
            || /^(?:duration|delay|ease)-/.test(base);
    });
}

function scheduleEditorAnimationReplay(editor, delayMs = 80, component = null) {
    if (! editor) {
        return;
    }

    const previous = editor.__voodbuilderAnimReplayTimer;

    if (previous) {
        window.clearTimeout(previous);
    }

    editor.__voodbuilderAnimReplayTimer = window.setTimeout(() => {
        editor.__voodbuilderAnimReplayTimer = null;

        try {
            const frameDoc = editor.Canvas?.getDocument?.();

            if (! frameDoc) {
                return;
            }

            // Prefer the edited node — replaying the whole frame looks like a canvas reload
            // (every animate-* / on-visible block restarts at once).
            let root = frameDoc;
            const el = component?.getEl?.() ?? component?.view?.el ?? null;

            if (el && frameDoc.contains(el)) {
                root = el;
            }

            replayEditorCanvasAnimations({ root });
        } catch {
            // Optional in editor.
        }
    }, delayMs);
}

function findPrefixedAnimation(classes) {
    for (const base of ANIMATION_BASES) {
        for (const prefix of CSS_INTERACTION_PREFIXES) {
            const full = `${prefix}${base}`;

            if (classes.has(full)) {
                return { base, prefix, full };
            }
        }
    }

    return { base: '', prefix: '', full: '' };
}

function stripInteractionPrefix(className) {
    const name = String(className ?? '');

    for (const prefix of CSS_INTERACTION_PREFIXES) {
        if (prefix !== '' && name.startsWith(prefix)) {
            return { prefix, base: name.slice(prefix.length) };
        }
    }

    return { prefix: '', base: name };
}

function isVisibleInteraction(value) {
    return value === VISIBLE_INTERACTION;
}

function cssPrefixForInteraction(value) {
    return isVisibleInteraction(value) ? '' : String(value ?? '');
}

function syncVisibleMarker(component, interaction) {
    if (! component) {
        return;
    }

    const classes = new Set(componentClassList(component));
    const wantsMarker = isVisibleInteraction(interaction);

    if (wantsMarker && ! classes.has(VISIBLE_MARKER_CLASS)) {
        component.addClass?.(VISIBLE_MARKER_CLASS);
    } else if (! wantsMarker && classes.has(VISIBLE_MARKER_CLASS)) {
        component.removeClass?.(VISIBLE_MARKER_CLASS);
    }
}

function interactionSelectValue(component, root) {
    const classes = new Set(componentClassList(component));

    if (classes.has(VISIBLE_MARKER_CLASS)) {
        return VISIBLE_INTERACTION;
    }

    const found = findPrefixedAnimation(classes);

    if (found.base) {
        return found.prefix;
    }

    return root?.querySelector?.('[data-voodbuilder-anim-interaction]')?.value ?? '';
}

function resolveMappedClass(classes, options, legacyMap = {}, { useLegacy = true } = {}) {
    for (const opt of options) {
        if (! opt.value) {
            continue;
        }

        for (const prefix of CSS_INTERACTION_PREFIXES) {
            if (classes.has(`${prefix}${opt.value}`)) {
                return opt.value;
            }
        }
    }

    if (! useLegacy) {
        return '';
    }

    for (const [legacy, next] of Object.entries(legacyMap)) {
        if (classes.has(legacy)) {
            return next;
        }
    }

    return '';
}

/**
 * Keep iteration/direction/duration/… on the same variant as the animation
 * (hover:animate-spin + hover:animate-twice). Bare modifiers lose to the
 * animation shorthand when it is re-applied on :hover/:active.
 *
 * Uses shared replaceClassGroup (removeClass + addClass) — never setClass,
 * which races Grapes SelectorManager and can drop utilities from Classes.
 */
function reprefixAnimationModifiers(component, prefix) {
    if (! component) {
        return;
    }

    for (const { bases, set } of ANIMATION_MODIFIER_GROUPS) {
        const current = componentClassList(component).find((name) => set.has(name));

        if (! current) {
            continue;
        }

        const { base } = stripInteractionPrefix(current);

        if (! bases.includes(base)) {
            continue;
        }

        const desired = `${prefix}${base}`;
        const duplicates = componentClassList(component).filter((name) => set.has(name));

        if (current === desired && duplicates.length === 1) {
            continue;
        }

        replaceClassGroup(component, set, desired);
    }
}

function interactionPrefixForComponent(component, root) {
    const selectValue = interactionSelectValue(component, root);

    return cssPrefixForInteraction(selectValue);
}

function hasTransitionUtility(classes) {
    return TRANSITION_OPTIONS.some((opt) => opt.value && classes.has(opt.value));
}

function syncSelectsFromComponent(root, component) {
    if (! root || root.__voodbuilderAnimSyncingSelects) {
        return;
    }

    root.__voodbuilderAnimSyncingSelects = true;

    try {
        const classes = new Set(componentClassList(component));
        const found = findPrefixedAnimation(classes);
        const useLegacyTiming = ! hasTransitionUtility(classes);

        const setSelect = (attr, value) => {
            const el = root.querySelector(attr);

            if (el && el.value !== value) {
                el.value = value;
                // Refresh custom-select trigger label without dispatching change
                // (that would re-enter apply → overlay / replay loops).
                el.dispatchEvent(new Event('vb:sync-label', { bubbles: false }));
            }
        };

        setSelect('[data-voodbuilder-anim-interaction]', interactionSelectValue(component, root));
        setSelect('[data-voodbuilder-anim-type]', found.base);
        setSelect('[data-voodbuilder-anim-iteration]', resolveMappedClass(classes, ITERATION_OPTIONS));
        setSelect('[data-voodbuilder-anim-duration]', resolveMappedClass(classes, DURATION_OPTIONS, LEGACY_DURATION_MAP, { useLegacy: useLegacyTiming }));
        setSelect('[data-voodbuilder-anim-delay]', resolveMappedClass(classes, DELAY_OPTIONS));
        setSelect('[data-voodbuilder-anim-ease]', resolveMappedClass(classes, EASE_OPTIONS, LEGACY_EASE_MAP, { useLegacy: useLegacyTiming }));
        setSelect('[data-voodbuilder-anim-direction]', resolveMappedClass(classes, DIRECTION_OPTIONS));
        setSelect('[data-voodbuilder-anim-fill]', resolveMappedClass(classes, FILL_OPTIONS));
        setSelect('[data-voodbuilder-anim-transition]', resolveMappedClass(classes, TRANSITION_OPTIONS));
        setSelect('[data-voodbuilder-anim-transition-duration]', resolveMappedClass(classes, TRANSITION_DURATION_OPTIONS));
        setSelect('[data-voodbuilder-anim-transition-ease]', resolveMappedClass(classes, TRANSITION_EASE_OPTIONS));
        setSelect('[data-voodbuilder-anim-transition-delay]', resolveMappedClass(classes, TRANSITION_DELAY_OPTIONS));
    } finally {
        root.__voodbuilderAnimSyncingSelects = false;
    }
}

function applyAnimationWithInteraction(component, root) {
    const base = root.querySelector('[data-voodbuilder-anim-type]')?.value ?? '';
    const interaction = root.querySelector('[data-voodbuilder-anim-interaction]')?.value ?? '';
    const prefix = cssPrefixForInteraction(interaction);
    const next = base ? `${prefix}${base}` : null;
    replaceClassGroup(component, ANIMATION_CLASS_SET, next);
    reprefixAnimationModifiers(component, prefix);
    syncVisibleMarker(component, base ? interaction : '');
}

function applyModifierWithInteraction(component, root, groupSet, selectAttr) {
    const prefix = interactionPrefixForComponent(component, root);
    const base = root.querySelector(selectAttr)?.value ?? '';
    const next = base ? `${prefix}${base}` : null;
    replaceClassGroup(component, groupSet, next);
}

function buildAnimationSector(editor, labels = {}) {
    const sector = document.createElement('div');
    sector.className = 'gjs-sm-sector voodbuilder-editor-sm-sector-animation';
    sector.dataset.voodbuilderAnimationSector = '';

    const tabPreset = labels.classAnimationGroupPreset ?? 'Preset';
    const tabTiming = labels.classAnimationGroupTiming ?? 'Timing';
    const tabEasing = labels.classAnimationGroupEasing ?? 'Easing';
    const tabDirection = labels.classAnimationGroupDirection ?? 'Direction';
    const tabFill = labels.classAnimationGroupFill ?? 'Fill mode';
    const tabTransition = labels.classAnimationGroupTransition ?? 'Transitions';

    sector.innerHTML = `
        <div class="gjs-sm-title gjs-sm-sector-title" data-voodbuilder-anim-toggle role="button" tabindex="0" aria-expanded="false">
            <span class="voodbuilder-editor-inspector-sector__caret" aria-hidden="true"></span>
            <span class="gjs-sm-sector-label voodbuilder-editor-sm-sector-animation__label">${labels.classAnimationTitle ?? 'Animation'}</span>
        </div>
        <div class="gjs-sm-properties voodbuilder-editor-sm-sector-animation__body" hidden>
            <div class="voodbuilder-editor-anim-tabs" role="tablist" aria-label="${labels.classAnimationTitle ?? 'Animation'}">
                ${tabButtonHtml('preset', tabPreset, TAB_ICONS.preset)}
                ${tabButtonHtml('timing', tabTiming, TAB_ICONS.timing)}
                ${tabButtonHtml('easing', tabEasing, TAB_ICONS.easing)}
                ${tabButtonHtml('direction', tabDirection, TAB_ICONS.direction)}
                ${tabButtonHtml('fill', tabFill, TAB_ICONS.fill)}
                ${tabButtonHtml('transition', tabTransition, TAB_ICONS.transition)}
            </div>
            <div class="voodbuilder-editor-anim-panels">
                ${panelHtml('preset', `
                    ${fieldHtml({
                        label: labels.classAnimationType ?? 'Preset',
                        selectAttr: 'data-voodbuilder-anim-type',
                        options: ANIMATION_OPTIONS,
                    })}
                    ${fieldHtml({
                        label: labels.classAnimationInteraction ?? 'Interaction',
                        selectAttr: 'data-voodbuilder-anim-interaction',
                        options: INTERACTION_OPTIONS,
                    })}
                `, { active: true })}
                ${panelHtml('timing', `
                    ${fieldHtml({
                        label: labels.classAnimationIteration ?? 'Iterations',
                        selectAttr: 'data-voodbuilder-anim-iteration',
                        options: ITERATION_OPTIONS,
                    })}
                    ${fieldHtml({
                        label: labels.classAnimationDuration ?? 'Cycle duration',
                        selectAttr: 'data-voodbuilder-anim-duration',
                        options: DURATION_OPTIONS,
                    })}
                    ${fieldHtml({
                        label: labels.classAnimationDelay ?? 'Delay',
                        selectAttr: 'data-voodbuilder-anim-delay',
                        options: DELAY_OPTIONS,
                    })}
                `)}
                ${panelHtml('easing', `
                    ${fieldHtml({
                        label: labels.classAnimationMotion ?? 'Easing',
                        selectAttr: 'data-voodbuilder-anim-ease',
                        options: EASE_OPTIONS,
                    })}
                `)}
                ${panelHtml('direction', `
                    ${fieldHtml({
                        label: labels.classAnimationDirection ?? 'Direction',
                        selectAttr: 'data-voodbuilder-anim-direction',
                        options: DIRECTION_OPTIONS,
                    })}
                `)}
                ${panelHtml('fill', `
                    ${fieldHtml({
                        label: labels.classAnimationFill ?? 'Fill mode',
                        selectAttr: 'data-voodbuilder-anim-fill',
                        options: FILL_OPTIONS,
                    })}
                `)}
                ${panelHtml('transition', `
                    ${fieldHtml({
                        label: labels.classAnimationTransition ?? 'Transition',
                        selectAttr: 'data-voodbuilder-anim-transition',
                        options: TRANSITION_OPTIONS,
                    })}
                    ${fieldHtml({
                        label: labels.classAnimationTransitionDuration ?? 'Duration',
                        selectAttr: 'data-voodbuilder-anim-transition-duration',
                        options: TRANSITION_DURATION_OPTIONS,
                    })}
                    ${fieldHtml({
                        label: labels.classAnimationTransitionEase ?? 'Easing',
                        selectAttr: 'data-voodbuilder-anim-transition-ease',
                        options: TRANSITION_EASE_OPTIONS,
                    })}
                    ${fieldHtml({
                        label: labels.classAnimationTransitionDelay ?? 'Delay',
                        selectAttr: 'data-voodbuilder-anim-transition-delay',
                        options: TRANSITION_DELAY_OPTIONS,
                    })}
                `)}
            </div>
        </div>
    `;

    const title = sector.querySelector('[data-voodbuilder-anim-toggle]');
    const body = sector.querySelector('.voodbuilder-editor-sm-sector-animation__body');

    const setOpen = (open) => {
        sector.classList.toggle('gjs-sm-open', open);
        body.hidden = ! open;
        title?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    title?.addEventListener('click', () => {
        setOpen(! sector.classList.contains('gjs-sm-open'));
    });

    title?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            setOpen(! sector.classList.contains('gjs-sm-open'));
        }
    });

    const activateTab = (tabId) => {
        sector.querySelectorAll('[data-voodbuilder-anim-tab]').forEach((tab) => {
            const active = tab.dataset.voodbuilderAnimTab === tabId;
            tab.classList.toggle('is-active', active);
            tab.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        sector.querySelectorAll('[data-voodbuilder-anim-panel]').forEach((panel) => {
            const active = panel.dataset.voodbuilderAnimPanel === tabId;
            panel.classList.toggle('is-active', active);
            panel.hidden = ! active;
        });
    };

    sector.querySelectorAll('[data-voodbuilder-anim-tab]').forEach((tab) => {
        tab.addEventListener('click', (event) => {
            event.stopPropagation();
            activateTab(tab.dataset.voodbuilderAnimTab);
        });
    });

    activateTab('preset');

    const bindField = (selectAttr, groupSet, { transform } = {}) => {
        const apply = () => {
            if (sector.__voodbuilderAnimSyncingSelects) {
                return;
            }

            const selected = editor.getSelected();

            if (! selected) {
                return;
            }

            if (typeof transform === 'function') {
                transform(selected, sector);
            } else {
                const value = sector.querySelector(selectAttr)?.value ?? '';
                replaceClassGroup(selected, groupSet, value || null);
            }

            try {
                selected.view?.updateClasses?.();
            } catch {
                // View may be unavailable during bulk updates.
            }

            // Replay only the edited node — never the whole canvas document.
            scheduleEditorAnimationReplay(editor, 80, selected);
            syncSelectsFromComponent(sector, selected);
            editor?.trigger?.('component:update', selected);
        };

        sector.querySelector(selectAttr)?.addEventListener('change', apply);
    };

    const bindModifierField = (selectAttr, groupSet) => {
        bindField(selectAttr, groupSet, {
            transform: (component, root) => applyModifierWithInteraction(component, root, groupSet, selectAttr),
        });
    };

    bindField('[data-voodbuilder-anim-type]', ANIMATION_CLASS_SET, {
        transform: applyAnimationWithInteraction,
    });
    bindField('[data-voodbuilder-anim-interaction]', ANIMATION_CLASS_SET, {
        transform: applyAnimationWithInteraction,
    });
    bindModifierField('[data-voodbuilder-anim-iteration]', ITERATION_CLASS_SET);
    bindModifierField('[data-voodbuilder-anim-duration]', DURATION_CLASS_SET);
    bindModifierField('[data-voodbuilder-anim-delay]', DELAY_CLASS_SET);
    bindModifierField('[data-voodbuilder-anim-ease]', EASE_CLASS_SET);
    bindModifierField('[data-voodbuilder-anim-direction]', DIRECTION_CLASS_SET);
    bindModifierField('[data-voodbuilder-anim-fill]', FILL_CLASS_SET);
    bindField('[data-voodbuilder-anim-transition]', TRANSITION_CLASS_SET);
    bindField('[data-voodbuilder-anim-transition-duration]', TRANSITION_DURATION_CLASS_SET);
    bindField('[data-voodbuilder-anim-transition-ease]', TRANSITION_EASE_CLASS_SET);
    bindField('[data-voodbuilder-anim-transition-delay]', TRANSITION_DELAY_CLASS_SET);

    editor.on('component:selected', (component) => {
        if (! component) {
            syncSelectsFromComponent(sector, null);

            return;
        }

        const selectValue = interactionSelectValue(component, sector);
        const prefix = cssPrefixForInteraction(selectValue);

        if (isVisibleInteraction(selectValue) || prefix !== '') {
            const before = componentClassList(component).join(' ');
            reprefixAnimationModifiers(component, prefix);
            syncVisibleMarker(component, selectValue);

            if (before !== componentClassList(component).join(' ')) {
                scheduleEditorAnimationReplay(editor, 80, component);
            }
        }

        syncSelectsFromComponent(sector, component);
    });

    editor.on('component:update:classes', (component) => {
        const selected = editor.getSelected();
        const target = component ?? selected;

        if (selected && target === selected) {
            syncSelectsFromComponent(sector, selected);
        }

        // Only the changed node — a full-frame replay flashed every animated block
        // and felt like the editor was reloading / recompiling.
        if (target && componentHasAnimationUtility(target)) {
            scheduleEditorAnimationReplay(editor, 120, target);
        }
    });

    // Real page JIT (rare) may need a wider refresh; keep it scoped to selection when possible.
    editor.on('voodbuilder:page-css-compiled', () => {
        const selected = editor.getSelected();

        if (selected && componentHasAnimationUtility(selected)) {
            scheduleEditorAnimationReplay(editor, 40, selected);
        }
    });

    syncSelectsFromComponent(sector, editor.getSelected());

    return sector;
}

function placeAnimationSector(stylesMount, sector) {
    const twDecorations = stylesMount.querySelector('[data-voodbuilder-tw-sector="decorations"]');
    const twTypography = stylesMount.querySelector('[data-voodbuilder-tw-sector="typography"]');
    const decorations = twDecorations ?? findSectorByTitle(stylesMount, 'decoration');
    const extra = findSectorByTitle(stylesMount, 'extra');

    if (twTypography) {
        twTypography.parentNode?.insertBefore(sector, twTypography.nextSibling);

        return;
    }

    if (decorations?.nextSibling) {
        decorations.parentNode.insertBefore(sector, decorations.nextSibling);

        return;
    }

    if (decorations) {
        decorations.parentNode?.appendChild(sector);

        return;
    }

    if (extra) {
        extra.parentNode.insertBefore(sector, extra);

        return;
    }

    const sectorsRoot = stylesMount.querySelector('.gjs-sm-sectors') ?? stylesMount;
    sectorsRoot.appendChild(sector);
}

/** @internal Exported for unit tests. */
export {
    ANIMATION_CLASS_SET,
    DURATION_CLASS_SET,
    applyAnimationWithInteraction,
    applyModifierWithInteraction,
    reprefixAnimationModifiers,
    replaceClassGroup,
};

export { STYLE_ANIMATION_BUNDLED_UTILITIES } from './style-animation-safelist.js';

export function registerStyleAnimationSector(editor, options = {}) {
    const stylesMount = options.mount;
    const labels = options.labels ?? {};

    if (! editor || ! stylesMount || editor.__voodbuilderAnimationSectorRegistered) {
        return;
    }

    editor.__voodbuilderAnimationSectorRegistered = true;

    const ensure = () => {
        if (stylesMount.querySelector('[data-voodbuilder-animation-sector]')) {
            return;
        }

        // Tailwind panel may mount first; otherwise create an empty sectors root.
        if (! stylesMount.querySelector('.gjs-sm-sectors') && ! stylesMount.querySelector('.gjs-sm-sector')) {
            const root = document.createElement('div');
            root.className = 'gjs-sm-sectors';
            stylesMount.appendChild(root);
        }

        const sector = buildAnimationSector(editor, labels);
        placeAnimationSector(stylesMount, sector);
    };

    const observer = new MutationObserver(() => ensure());
    observer.observe(stylesMount, { childList: true, subtree: true });

    editor.on('load', () => window.setTimeout(ensure, 80));
    editor.on('component:selected', () => window.setTimeout(ensure, 0));
    ensure();
}
