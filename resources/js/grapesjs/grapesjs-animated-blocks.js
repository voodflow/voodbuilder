/**
 * Animated GrapesJS elements — CTA, counter, stats, logo scroll.
 */

import { previewSvg, thumbWrap } from './editor-block-preview-utils.js';
import { resolveBlockLabel } from './section-block-meta.js';
import { widthClassesForItemCount } from './section-item-count.js';
import {
    initAnimatedCounters,
    initAnimatedCtas,
    initLogoScroll,
    replayEditorCanvasAnimations,
} from './vb-runtime.js';

export const ANIMATED_BLOCK_CATEGORY = 'Animated';

function wireframe(paths) {
    return thumbWrap(previewSvg(paths));
}

export const ANIMATED_BLOCK_WIREFRAMES = {
    'voodbuilder-animated-cta': wireframe(
        '<rect x="10" y="14" width="28" height="6" rx="1"/>'
        + '<rect x="14" y="26" width="20" height="6" rx="2" fill="currentColor" opacity="0.2"/>'
        + '<path d="M12 10l2 2M36 10l-2 2"/>',
    ),
    'voodbuilder-animated-counter': wireframe(
        '<path d="M14 30V16M14 16l4 4M14 16l-4 4"/>'
        + '<path d="M24 14h10M24 22h14M24 30h8"/>',
    ),
    'voodbuilder-animated-stats': wireframe(
        '<path d="M10 30V18M22 30V14M34 30V20"/>'
        + '<path d="M8 32h32"/>',
    ),
    'voodbuilder-logo-scroll': wireframe(
        '<rect x="6" y="18" width="10" height="10" rx="1.5"/>'
        + '<rect x="19" y="18" width="10" height="10" rx="1.5"/>'
        + '<rect x="32" y="18" width="10" height="10" rx="1.5"/>'
        + '<path d="M8 36h32"/>',
    ),
};

function logoPlaceholder(index) {
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="160" height="64" viewBox="0 0 160 64"><rect width="160" height="64" rx="8" fill="#e2e8f0"/><text x="80" y="38" text-anchor="middle" fill="#94a3b8" font-family="system-ui,sans-serif" font-size="14">Logo ${index}</text></svg>`;

    return `data:image/svg+xml,${encodeURIComponent(svg)}`;
}

function counterTraits() {
    return [
        { type: 'number', name: 'data-vb-count-from', label: 'From', changeProp: true },
        { type: 'number', name: 'data-vb-count-to', label: 'To', changeProp: true },
        { type: 'number', name: 'data-vb-count-duration', label: 'Duration (ms)', min: 200, max: 8000, changeProp: true },
        { type: 'number', name: 'data-vb-count-delay', label: 'Delay (ms)', min: 0, max: 5000, changeProp: true },
        {
            type: 'select',
            name: 'data-vb-count-trigger',
            label: 'Start when',
            options: [
                { id: 'viewport', label: 'In viewport' },
                { id: 'immediate', label: 'Immediately' },
            ],
            changeProp: true,
        },
        { type: 'number', name: 'data-vb-count-decimals', label: 'Decimals', min: 0, max: 2, changeProp: true },
        { type: 'text', name: 'data-vb-count-prefix', label: 'Prefix', changeProp: true },
        { type: 'text', name: 'data-vb-count-suffix', label: 'Suffix', changeProp: true },
        {
            type: 'select',
            name: 'data-vb-count-source',
            label: 'Values source',
            options: [
                { id: 'static', label: 'Static (traits)' },
                { id: 'dynamic', label: 'Dynamic (bindings / text)' },
            ],
            changeProp: true,
        },
    ];
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

function formatCounterLabel({
    to,
    decimals = 0,
    prefix = '',
    suffix = '',
}) {
    const numeric = Number(to);
    const safe = Number.isFinite(numeric) ? numeric : 0;
    const fractionDigits = Math.max(0, Math.min(2, Number(decimals) || 0));
    const formatted = safe.toLocaleString(undefined, {
        minimumFractionDigits: fractionDigits,
        maximumFractionDigits: fractionDigits,
    });

    return `${prefix ?? ''}${formatted}${suffix ?? ''}`;
}

function readCounterConfig(component) {
    return {
        from: Number(component.get('data-vb-count-from') ?? 0) || 0,
        to: Number(component.get('data-vb-count-to') ?? 100) || 0,
        duration: Number(component.get('data-vb-count-duration') ?? 1600) || 1600,
        delay: Number(component.get('data-vb-count-delay') ?? 0) || 0,
        trigger: component.get('data-vb-count-trigger') ?? 'viewport',
        decimals: Number(component.get('data-vb-count-decimals') ?? 0) || 0,
        prefix: component.get('data-vb-count-prefix') ?? '',
        suffix: component.get('data-vb-count-suffix') ?? '',
        source: component.get('data-vb-count-source') ?? 'static',
    };
}

function syncCounterAttributes(component) {
    if (component.__vbCounterSyncing) {
        return;
    }

    component.__vbCounterSyncing = true;

    try {
        const config = readCounterConfig(component);
        const label = formatCounterLabel(config);

        component.addAttributes({
            'data-voodbuilder-animated-counter': '',
            'data-vb-count-from': String(config.from),
            'data-vb-count-to': String(config.to),
            'data-vb-count-duration': String(config.duration),
            'data-vb-count-delay': String(config.delay),
            'data-vb-count-trigger': String(config.trigger),
            'data-vb-count-decimals': String(config.decimals),
            'data-vb-count-prefix': String(config.prefix),
            'data-vb-count-suffix': String(config.suffix),
            'data-vb-count-source': String(config.source),
            'data-vb-count-label': label,
        });

        // Keep the component childless — text lives only in toHTML/view.
        // Mutating components()/textContent during animation was concatenating
        // every animation frame into the saved HTML.
        component.components().reset();
        component.set('content', label, { silent: true });

        const view = component.getView?.() ?? component.view;

        if (view?.el) {
            view.el.textContent = label;
        }
    } finally {
        component.__vbCounterSyncing = false;
    }
}

function buildStatItem(index, value, label, decimals = 0, suffix = '') {
    const countLabel = formatCounterLabel({ to: value, decimals, prefix: '', suffix });

    return {
        tagName: 'div',
        classes: ['p-4', 'text-center', ...widthClassesForItemCount(4)],
        attributes: { 'data-vb-item': '' },
        components: [
            {
                type: 'voodbuilder-animated-counter',
                classes: [
                    'font-semibold',
                    'font-medium',
                    'sm:text-4xl',
                    'text-3xl',
                    'text-vp-text-1',
                    'vb-animated-counter',
                    'tabular-nums',
                ],
                attributes: {
                    'data-voodbuilder-animated-counter': '',
                    'data-vb-count-from': '0',
                    'data-vb-count-to': String(value),
                    'data-vb-count-duration': '1600',
                    'data-vb-count-delay': String(index * 120),
                    'data-vb-count-trigger': 'viewport',
                    'data-vb-count-decimals': String(decimals),
                    'data-vb-count-prefix': '',
                    'data-vb-count-suffix': suffix,
                    'data-vb-count-source': 'static',
                    'data-vb-count-label': countLabel,
                },
                components: [],
                content: countLabel,
                'data-vb-count-from': 0,
                'data-vb-count-to': value,
                'data-vb-count-duration': 1600,
                'data-vb-count-delay': index * 120,
                'data-vb-count-trigger': 'viewport',
                'data-vb-count-decimals': decimals,
                'data-vb-count-prefix': '',
                'data-vb-count-suffix': suffix,
                'data-vb-count-source': 'static',
            },
            {
                tagName: 'p',
                classes: ['leading-relaxed', 'text-vp-text-2', 'mt-1'],
                components: label,
            },
        ],
    };
}

/** @type {Record<string, { height: string, maxWidth: string, itemPad: string }>} */
export const LOGO_SCROLL_SIZES = {
    sm: { height: 'h-6', maxWidth: 'max-w-[6rem]', itemPad: 'px-6' },
    md: { height: 'h-8', maxWidth: 'max-w-[8rem]', itemPad: 'px-7' },
    lg: { height: 'h-10', maxWidth: 'max-w-[9rem]', itemPad: 'px-8' },
    xl: { height: 'h-14', maxWidth: 'max-w-[12rem]', itemPad: 'px-10' },
};

/**
 * @param {string|null|undefined} size
 * @returns {keyof typeof LOGO_SCROLL_SIZES}
 */
export function normalizeLogoScrollSize(size) {
    return Object.prototype.hasOwnProperty.call(LOGO_SCROLL_SIZES, size) ? size : 'lg';
}

/**
 * @param {object} root
 * @returns {object[]}
 */
export function findLogoScrollItems(root) {
    const track = root?.find?.('[data-vb-items-root]')?.[0]
        ?? root?.find?.('.vb-logo-scroll__track')?.[0];

    if (! track) {
        return [];
    }

    return [...(track.components?.() ?? [])].filter((child) => {
        const itemAttrs = child.getAttributes?.() ?? {};

        return Object.prototype.hasOwnProperty.call(itemAttrs, 'data-vb-item');
    });
}

/**
 * @param {object} item
 * @returns {object|null}
 */
export function findLogoScrollLink(item) {
    return item?.find?.('a.vb-logo-scroll__link')?.[0]
        ?? item?.find?.('a')?.[0]
        ?? item?.components?.()?.at?.(0)
        ?? null;
}

/**
 * @param {object} item
 * @returns {object|null}
 */
export function findLogoScrollImage(item) {
    return item?.find?.('img')?.[0] ?? null;
}

/**
 * @param {object} item
 * @param {keyof typeof LOGO_SCROLL_SIZES} size
 */
function applyLogoItemSize(item, size) {
    const preset = LOGO_SCROLL_SIZES[normalizeLogoScrollSize(size)];
    const image = findLogoScrollImage(item);

    item.setClass([
        'vb-logo-scroll__item',
        'flex',
        'shrink-0',
        'items-center',
        'justify-center',
        preset.itemPad,
    ]);

    if (! image) {
        return;
    }

    const nextClasses = (image.getClasses?.() ?? [])
        .filter((className) => ! /^h-\S+$/.test(className) && ! /^max-w-/.test(className) && className !== 'w-auto')
        .concat(['w-auto', preset.height, preset.maxWidth, 'object-contain']);

    if (! nextClasses.includes('opacity-70') && ! nextClasses.some((className) => className.startsWith('opacity-'))) {
        nextClasses.push('opacity-70');
    }

    image.setClass([...new Set(nextClasses)]);
}

/**
 * @param {object} item
 * @param {string} href
 */
export function setLogoScrollItemHref(item, href) {
    const link = findLogoScrollLink(item);

    if (! link) {
        return;
    }

    const nextHref = String(href || '#').trim() || '#';
    link.addAttributes({ href: nextHref });

    if (nextHref !== '#' && /^https?:\/\//i.test(nextHref)) {
        link.addAttributes({ target: '_blank', rel: 'noopener noreferrer' });
    } else if (nextHref === '#') {
        link.removeAttributes?.('target');
        link.removeAttributes?.('rel');
    }
}

/**
 * @param {object} item
 * @returns {string}
 */
export function getLogoScrollItemHref(item) {
    const link = findLogoScrollLink(item);
    const attrs = link?.getAttributes?.() ?? {};

    return String(attrs.href ?? '#');
}

function buildLogoItem(index, size = 'lg') {
    const preset = LOGO_SCROLL_SIZES[normalizeLogoScrollSize(size)];

    return {
        tagName: 'div',
        classes: ['vb-logo-scroll__item', 'flex', 'shrink-0', 'items-center', 'justify-center', preset.itemPad],
        attributes: { 'data-vb-item': '' },
        components: [
            {
                type: 'link',
                classes: ['vb-logo-scroll__link', 'inline-flex', 'items-center', 'justify-center'],
                attributes: {
                    href: '#',
                    title: `Partner logo ${index}`,
                    'aria-label': `Partner logo ${index}`,
                },
                components: [
                    {
                        type: 'image',
                        classes: [preset.height, 'w-auto', preset.maxWidth, 'object-contain', 'opacity-70'],
                        attributes: {
                            src: logoPlaceholder(index),
                            alt: `Partner logo ${index}`,
                        },
                    },
                ],
            },
        ],
    };
}

function syncLogoScroll(component) {
    const attrs = component.getAttributes?.() ?? {};
    const legacyCount = attrs['data-vb-item-count'];
    const count = Math.max(
        3,
        Math.min(12, Number(component.get('data-vb-logo-count') ?? legacyCount ?? 6) || 6),
    );
    const direction = component.get('data-vb-logo-direction') ?? attrs['data-vb-logo-direction'] ?? 'left';
    const speed = component.get('data-vb-logo-speed') ?? attrs['data-vb-logo-speed'] ?? 'normal';
    const pauseHover = component.get('data-vb-logo-pause-hover') ?? attrs['data-vb-logo-pause-hover'] ?? '1';
    const source = component.get('data-vb-logo-source') ?? attrs['data-vb-logo-source'] ?? 'static';
    const size = normalizeLogoScrollSize(
        component.get('data-vb-logo-size') ?? attrs['data-vb-logo-size'] ?? 'lg',
    );
    const speedSeconds = speed === 'slow' ? 45 : speed === 'fast' ? 14 : 28;

    component.set({
        'data-vb-logo-count': count,
        'data-vb-logo-direction': direction,
        'data-vb-logo-speed': speed,
        'data-vb-logo-pause-hover': pauseHover,
        'data-vb-logo-source': source,
        'data-vb-logo-size': size,
    }, { silent: true });

    component.addAttributes({
        'data-voodbuilder-logo-scroll': '',
        'data-vb-logo-count': String(count),
        'data-vb-logo-direction': direction,
        'data-vb-logo-speed': speed,
        'data-vb-logo-pause-hover': pauseHover,
        'data-vb-logo-source': source,
        'data-vb-logo-size': size,
        style: `--logo-marquee-duration:${speedSeconds}s`,
    });
    // Keep item-count off the root so section Layout items settings do not steal this block.
    component.removeAttributes?.('data-vb-item-count');
    component.removeAttributes?.('data-vb-item-min');
    component.removeAttributes?.('data-vb-item-max');

    component.setClass([
        'vb-logo-scroll',
        'w-full',
        'overflow-hidden',
        'border-y',
        'border-vp-divider',
        'py-6',
    ]);

    const movers = component.find('[data-vb-logo-mover]');
    const mover = movers[0];

    if (mover) {
        const moverClasses = [
            'vb-logo-scroll__mover',
            'flex',
            'w-max',
            'items-center',
            'animate-logo-marquee',
            '[animation-duration:var(--logo-marquee-duration,28s)]',
        ];

        if (direction === 'right') {
            moverClasses.push('[animation-direction:reverse]');
        }

        if (pauseHover === '1') {
            moverClasses.push('hover:[animation-play-state:paused]');
        }

        mover.setClass(moverClasses);
    }

    const track = component.find('[data-vb-items-root]')[0]
        ?? component.find('.vb-logo-scroll__track')[0];

    if (! track) {
        return;
    }

    // Remove model-side clones if any leaked into the component tree.
    const parent = track.parent?.();
    if (parent) {
        [...(parent.components?.() ?? [])].forEach((child) => {
            const childAttrs = child.getAttributes?.() ?? {};
            const classes = child.getClasses?.() ?? [];

            if (
                Object.prototype.hasOwnProperty.call(childAttrs, 'data-vb-logo-clone')
                || classes.includes('vb-logo-scroll__track--clone')
            ) {
                child.remove();
            }
        });
    }

    if (source === 'dynamic') {
        prepareLogoScrollDynamicTemplate(track, size);

        return;
    }

    clearLogoScrollRepeatMarkers(track);

    const items = [...(track.components?.() ?? [])].filter((child) => {
        const itemAttrs = child.getAttributes?.() ?? {};

        return Object.prototype.hasOwnProperty.call(itemAttrs, 'data-vb-item');
    });

    while (items.length > count) {
        items.pop()?.remove?.();
    }

    while (items.length < count) {
        const nextIndex = items.length + 1;
        track.append(buildLogoItem(nextIndex, size));
        items.push(track.components().at(track.components().length - 1));
    }

    items.forEach((item) => applyLogoItemSize(item, size));
}

/**
 * @param {object} track
 */
function clearLogoScrollRepeatMarkers(track) {
    if (! track) {
        return;
    }

    track.removeAttributes?.('data-voodbuilder-repeat');
    track.removeAttributes?.('data-voodbuilder-repeat-limit');
    track.removeAttributes?.('data-voodbuilder-repeat-offset');
    track.removeAttributes?.('data-voodbuilder-repeat-sort');
    track.removeAttributes?.('data-voodbuilder-repeat-sort-dir');

    [...(track.components?.() ?? [])].forEach((child) => {
        child.removeAttributes?.('data-voodbuilder-repeat-item');
    });
}

/**
 * Keep a single logo item as the List repeat template (img + link ready to bind).
 *
 * @param {object} track
 * @param {string} size
 */
function prepareLogoScrollDynamicTemplate(track, size) {
    let items = [...(track.components?.() ?? [])].filter((child) => {
        const itemAttrs = child.getAttributes?.() ?? {};

        return Object.prototype.hasOwnProperty.call(itemAttrs, 'data-vb-item');
    });

    if (items.length === 0) {
        track.append(buildLogoItem(1, size));
        items = [...(track.components?.() ?? [])].filter((child) => {
            const itemAttrs = child.getAttributes?.() ?? {};

            return Object.prototype.hasOwnProperty.call(itemAttrs, 'data-vb-item');
        });
    }

    while (items.length > 1) {
        items.pop()?.remove?.();
        items = [...(track.components?.() ?? [])].filter((child) => {
            const itemAttrs = child.getAttributes?.() ?? {};

            return Object.prototype.hasOwnProperty.call(itemAttrs, 'data-vb-item');
        });
    }

    const template = items[0];

    if (! template) {
        return;
    }

    applyLogoItemSize(template, size);

    // Preserve an existing list repeat; otherwise leave the template ready for the Dynamic tab.
    if (track.getAttributes?.()['data-voodbuilder-repeat']) {
        template.addAttributes({ 'data-voodbuilder-repeat-item': '1' });
    }
}

function syncAnimatedCta(component) {
    const anim = component.get('data-vb-anim') ?? 'fade-up';
    const duration = component.get('data-vb-anim-duration') ?? 700;
    const delay = component.get('data-vb-anim-delay') ?? 0;

    component.addAttributes({
        'data-voodbuilder-animated-cta': '',
        'data-vb-anim': String(anim),
        'data-vb-anim-duration': String(duration),
        'data-vb-anim-delay': String(delay),
    });
}

function registerAnimatedCtaType(editor) {
    if (editor.DomComponents.getType('voodbuilder-animated-cta')) {
        return;
    }

    editor.DomComponents.addType('voodbuilder-animated-cta', {
        isComponent: (element) => element?.hasAttribute?.('data-voodbuilder-animated-cta') === true,
        model: {
            defaults: {
                tagName: 'section',
                name: 'Animated CTA',
                attributes: {
                    'data-voodbuilder-animated-cta': '',
                    'data-vb-anim': 'fade-up',
                    'data-vb-anim-duration': '700',
                    'data-vb-anim-delay': '0',
                    class: 'vb-animated-cta text-vp-text-2',
                },
                traits: [
                    {
                        type: 'select',
                        name: 'data-vb-anim',
                        label: 'Animation',
                        options: [
                            { id: 'fade-up', label: 'Fade up' },
                            { id: 'fade-in', label: 'Fade in' },
                            { id: 'scale-in', label: 'Scale in' },
                        ],
                        changeProp: true,
                    },
                    { type: 'number', name: 'data-vb-anim-duration', label: 'Duration (ms)', min: 200, max: 3000, changeProp: true },
                    { type: 'number', name: 'data-vb-anim-delay', label: 'Delay (ms)', min: 0, max: 2000, changeProp: true },
                ],
                'data-vb-anim': 'fade-up',
                'data-vb-anim-duration': 700,
                'data-vb-anim-delay': 0,
            },
            init() {
                this.on(
                    'change:data-vb-anim change:data-vb-anim-duration change:data-vb-anim-delay',
                    () => syncAnimatedCta(this),
                );
                syncAnimatedCta(this);
            },
        },
    });
}

function registerAnimatedCounterType(editor) {
    if (editor.DomComponents.getType('voodbuilder-animated-counter')) {
        return;
    }

    editor.DomComponents.addType('voodbuilder-animated-counter', {
        isComponent: (element) => element?.hasAttribute?.('data-voodbuilder-animated-counter') === true,
        model: {
            defaults: {
                tagName: 'span',
                name: 'Animated counter',
                droppable: false,
                editable: false,
                highlightable: true,
                attributes: {
                    'data-voodbuilder-animated-counter': '',
                    class: 'vb-animated-counter font-semibold text-4xl text-vp-text-1 tabular-nums',
                    'data-vb-count-from': '0',
                    'data-vb-count-to': '100',
                    'data-vb-count-duration': '1600',
                    'data-vb-count-delay': '0',
                    'data-vb-count-trigger': 'viewport',
                    'data-vb-count-decimals': '0',
                    'data-vb-count-prefix': '',
                    'data-vb-count-suffix': '',
                    'data-vb-count-source': 'static',
                    'data-vb-count-label': '100',
                },
                components: [],
                content: '100',
                traits: counterTraits(),
                'data-vb-count-from': 0,
                'data-vb-count-to': 100,
                'data-vb-count-duration': 1600,
                'data-vb-count-delay': 0,
                'data-vb-count-trigger': 'viewport',
                'data-vb-count-decimals': 0,
                'data-vb-count-prefix': '',
                'data-vb-count-suffix': '',
                'data-vb-count-source': 'static',
            },
            init() {
                this.on(
                    'change:data-vb-count-from change:data-vb-count-to change:data-vb-count-duration change:data-vb-count-delay change:data-vb-count-trigger change:data-vb-count-decimals change:data-vb-count-prefix change:data-vb-count-suffix change:data-vb-count-source',
                    () => syncCounterAttributes(this),
                );
                syncCounterAttributes(this);
            },
            getDisplayLabel() {
                const attrs = this.getAttributes?.() ?? {};

                return attrs['data-vb-count-label']
                    || this.get('content')
                    || formatCounterLabel(readCounterConfig(this));
            },
            toHTML() {
                const tag = this.get('tagName') || 'span';
                const attrs = this.getAttrToHTML?.() ?? '';
                const label = escapeHtml(this.getDisplayLabel());

                return `<${tag}${attrs}>${label}</${tag}>`;
            },
        },
        view: {
            tagName: 'span',
            onRender({ el, model }) {
                el.textContent = model.getDisplayLabel?.() ?? formatCounterLabel(readCounterConfig(model));
            },
        },
    });
}

function registerLogoScrollType(editor) {
    if (editor.DomComponents.getType('voodbuilder-logo-scroll')) {
        return;
    }

    editor.DomComponents.addType('voodbuilder-logo-scroll', {
        isComponent: (element) => element?.hasAttribute?.('data-voodbuilder-logo-scroll') === true,
        model: {
            defaults: {
                tagName: 'div',
                name: 'Logo scroll',
                droppable: false,
                attributes: {
                    'data-voodbuilder-logo-scroll': '',
                    class: 'vb-logo-scroll w-full overflow-hidden border-y border-vp-divider py-6',
                    'data-vb-logo-count': '6',
                    'data-vb-logo-direction': 'left',
                    'data-vb-logo-speed': 'normal',
                    'data-vb-logo-pause-hover': '1',
                    'data-vb-logo-source': 'static',
                    'data-vb-logo-size': 'lg',
                    style: '--logo-marquee-duration:28s',
                },
                traits: [
                    { type: 'number', name: 'data-vb-logo-count', label: 'Logos', min: 3, max: 12, changeProp: true },
                    {
                        type: 'select',
                        name: 'data-vb-logo-size',
                        label: 'Size',
                        options: [
                            { id: 'sm', label: 'Small' },
                            { id: 'md', label: 'Medium' },
                            { id: 'lg', label: 'Large' },
                            { id: 'xl', label: 'Extra large' },
                        ],
                        changeProp: true,
                    },
                    {
                        type: 'select',
                        name: 'data-vb-logo-speed',
                        label: 'Speed',
                        options: [
                            { id: 'slow', label: 'Slow' },
                            { id: 'normal', label: 'Normal' },
                            { id: 'fast', label: 'Fast' },
                        ],
                        changeProp: true,
                    },
                    {
                        type: 'select',
                        name: 'data-vb-logo-direction',
                        label: 'Direction',
                        options: [
                            { id: 'left', label: 'Left' },
                            { id: 'right', label: 'Right' },
                        ],
                        changeProp: true,
                    },
                    {
                        type: 'select',
                        name: 'data-vb-logo-pause-hover',
                        label: 'Pause on hover',
                        options: [
                            { id: '1', label: 'Yes' },
                            { id: '0', label: 'No' },
                        ],
                        changeProp: true,
                    },
                    {
                        type: 'select',
                        name: 'data-vb-logo-source',
                        label: 'Source',
                        options: [
                            { id: 'static', label: 'Static logos' },
                            { id: 'dynamic', label: 'Dynamic (list repeat)' },
                        ],
                        changeProp: true,
                    },
                ],
                'data-vb-logo-count': 6,
                'data-vb-logo-direction': 'left',
                'data-vb-logo-speed': 'normal',
                'data-vb-logo-pause-hover': '1',
                'data-vb-logo-source': 'static',
                'data-vb-logo-size': 'lg',
            },
            init() {
                this.on(
                    'change:data-vb-logo-count change:data-vb-logo-direction change:data-vb-logo-speed change:data-vb-logo-pause-hover change:data-vb-logo-source change:data-vb-logo-size',
                    () => syncLogoScroll(this),
                );
                syncLogoScroll(this);
            },
        },
    });
}

function registerAnimatedStatsType(editor) {
    if (editor.DomComponents.getType('voodbuilder-animated-stats')) {
        return;
    }

    editor.DomComponents.addType('voodbuilder-animated-stats', {
        isComponent: (element) => element?.hasAttribute?.('data-voodbuilder-animated-stats') === true,
        model: {
            defaults: {
                tagName: 'section',
                name: 'Animated stats',
                attributes: {
                    'data-voodbuilder-animated-stats': '',
                    'data-vb-item-count': '4',
                    'data-vb-item-min': '2',
                    'data-vb-item-max': '6',
                    class: 'vb-animated-stats text-vp-text-2',
                },
                traits: [
                    { type: 'number', name: 'data-vb-item-count', label: 'Counters', min: 2, max: 6, changeProp: true },
                ],
                'data-vb-item-count': 4,
            },
            init() {
                this.on('change:data-vb-item-count', () => syncAnimatedStats(this));
            },
        },
    });
}

function syncAnimatedStats(component) {
    const count = Math.max(2, Math.min(6, Number(component.get('data-vb-item-count') ?? 4) || 4));
    const labels = ['Users', 'Subscribers', 'Downloads', 'Products', 'Reviews', 'Cities'];
    const values = [
        { value: 2.7, decimals: 1, suffix: 'K' },
        { value: 1.8, decimals: 1, suffix: 'K' },
        { value: 35, decimals: 0, suffix: '' },
        { value: 4, decimals: 0, suffix: '' },
        { value: 120, decimals: 0, suffix: '' },
        { value: 18, decimals: 0, suffix: '' },
    ];

    component.addAttributes({
        'data-voodbuilder-animated-stats': '',
        'data-vb-item-count': String(count),
        'data-vb-item-min': '2',
        'data-vb-item-max': '6',
    });

    const roots = component.find('[data-vb-items-root]');
    let root = roots[0];

    if (! root) {
        const container = component.find('.voodbuilder-gjs-container')[0]
            ?? component.components().at(0);
        root = container?.find?.('[data-vb-items-root]')?.[0] ?? container?.components?.()?.at?.(0);
    }

    if (! root) {
        return;
    }

    const widthClasses = widthClassesForItemCount(count);
    const items = [...(root.components?.() ?? [])].filter((child) => {
        const attrs = child.getAttributes?.() ?? {};

        return Object.prototype.hasOwnProperty.call(attrs, 'data-vb-item');
    });

    while (items.length > count) {
        items.pop()?.remove?.();
    }

    while (items.length < count) {
        const index = items.length;
        const metric = values[index] ?? { value: 10 + index, decimals: 0, suffix: '' };
        root.append(buildStatItem(
            index,
            metric.value,
            labels[index] ?? `Metric ${index + 1}`,
            metric.decimals,
            metric.suffix,
        ));
        items.push(root.components().at(root.components().length - 1));
    }

    findMarkedItems(root).forEach((item) => {
        const classes = [...(item.getClasses?.() ?? [])].filter((className) => ! /^(?:sm|md|lg|xl):w-1\/\d+$|^w-1\/\d+$|^w-full$/.test(className));
        item.setClass([...classes, ...widthClasses]);
    });
}

function findMarkedItems(root) {
    return [...(root.components?.() ?? [])].filter((child) => {
        const attrs = child.getAttributes?.() ?? {};

        return Object.prototype.hasOwnProperty.call(attrs, 'data-vb-item');
    });
}

const BLOCKS = [
    {
        id: 'voodbuilder-animated-cta',
        label: 'Animated CTA',
        category: ANIMATED_BLOCK_CATEGORY,
        content: {
            type: 'voodbuilder-animated-cta',
            classes: ['vb-animated-cta', 'text-vp-text-2'],
            attributes: {
                'data-voodbuilder-animated-cta': '',
                'data-vb-anim': 'fade-up',
                'data-vb-anim-duration': '700',
                'data-vb-anim-delay': '0',
            },
            'data-vb-anim': 'fade-up',
            'data-vb-anim-duration': 700,
            'data-vb-anim-delay': 0,
            components: `
                <div class="voodbuilder-gjs-container px-5 py-20 text-center">
                    <h2 class="vb-animated-cta__title sm:text-4xl text-3xl font-semibold text-vp-text-1 mb-4" data-vb-anim-child="1">Ready to grow with us?</h2>
                    <p class="vb-animated-cta__text leading-relaxed text-base max-w-2xl mx-auto mb-8" data-vb-anim-child="2">A short supporting line for your call to action. Edit the copy and connect a button link.</p>
                    <div class="vb-animated-cta__actions flex flex-wrap items-center justify-center gap-3" data-vb-anim-child="3">
                        <a href="#" class="inline-flex items-center rounded-lg bg-vp-brand-1 px-6 py-3 text-base font-medium text-white transition hover:opacity-90">Get started</a>
                        <a href="#" class="inline-flex items-center rounded-lg border border-vp-divider px-6 py-3 text-base font-medium text-vp-text-1 transition hover:bg-vp-bg-alt">Learn more</a>
                    </div>
                </div>
            `,
        },
    },
    {
        id: 'voodbuilder-animated-counter',
        label: 'Animated counter',
        category: ANIMATED_BLOCK_CATEGORY,
        content: {
            type: 'voodbuilder-animated-counter',
            classes: ['vb-animated-counter', 'font-semibold', 'text-4xl', 'text-vp-text-1', 'tabular-nums'],
            attributes: {
                'data-voodbuilder-animated-counter': '',
                'data-vb-count-from': '0',
                'data-vb-count-to': '1250',
                'data-vb-count-duration': '1800',
                'data-vb-count-delay': '0',
                'data-vb-count-trigger': 'viewport',
                'data-vb-count-decimals': '0',
                'data-vb-count-prefix': '',
                'data-vb-count-suffix': '+',
                'data-vb-count-source': 'static',
                'data-vb-count-label': '1,250+',
            },
            components: [],
            content: '1,250+',
            'data-vb-count-from': 0,
            'data-vb-count-to': 1250,
            'data-vb-count-duration': 1800,
            'data-vb-count-delay': 0,
            'data-vb-count-trigger': 'viewport',
            'data-vb-count-decimals': 0,
            'data-vb-count-prefix': '',
            'data-vb-count-suffix': '+',
            'data-vb-count-source': 'static',
        },
    },
    {
        id: 'voodbuilder-animated-stats',
        label: 'Animated stats',
        category: ANIMATED_BLOCK_CATEGORY,
        content: {
            type: 'voodbuilder-animated-stats',
            classes: ['vb-animated-stats', 'text-vp-text-2'],
            attributes: {
                'data-voodbuilder-animated-stats': '',
                'data-vb-item-count': '4',
                'data-vb-item-min': '2',
                'data-vb-item-max': '6',
            },
            'data-vb-item-count': 4,
            components: [
                {
                    tagName: 'div',
                    classes: ['voodbuilder-gjs-container', 'px-5', 'py-24'],
                    components: [
                        {
                            tagName: 'div',
                            classes: ['flex', 'flex-wrap', '-m-4', 'text-center'],
                            attributes: {
                                'data-vb-items-root': '',
                                'data-vb-item-min': '2',
                                'data-vb-item-max': '6',
                            },
                            components: [
                                buildStatItem(0, 2.7, 'Users', 1, 'K'),
                                buildStatItem(1, 1.8, 'Subscribers', 1, 'K'),
                                buildStatItem(2, 35, 'Downloads'),
                                buildStatItem(3, 4, 'Products'),
                            ],
                        },
                    ],
                },
            ],
        },
    },
    {
        id: 'voodbuilder-logo-scroll',
        label: 'Logo scroll',
        category: ANIMATED_BLOCK_CATEGORY,
        content: {
            type: 'voodbuilder-logo-scroll',
            classes: ['vb-logo-scroll', 'w-full', 'overflow-hidden', 'border-y', 'border-vp-divider', 'py-6'],
            attributes: {
                'data-voodbuilder-logo-scroll': '',
                'data-vb-logo-count': '6',
                'data-vb-logo-direction': 'left',
                'data-vb-logo-speed': 'normal',
                'data-vb-logo-pause-hover': '1',
                'data-vb-logo-source': 'static',
                'data-vb-logo-size': 'lg',
                style: '--logo-marquee-duration:28s',
            },
            'data-vb-logo-count': 6,
            'data-vb-logo-direction': 'left',
            'data-vb-logo-speed': 'normal',
            'data-vb-logo-pause-hover': '1',
            'data-vb-logo-source': 'static',
            'data-vb-logo-size': 'lg',
            components: [
                {
                    tagName: 'div',
                    classes: [
                        'vb-logo-scroll__mover',
                        'flex',
                        'w-max',
                        'items-center',
                        'animate-logo-marquee',
                        '[animation-duration:var(--logo-marquee-duration,28s)]',
                        'hover:[animation-play-state:paused]',
                    ],
                    attributes: { 'data-vb-logo-mover': '' },
                    components: [
                        {
                            tagName: 'div',
                            classes: ['vb-logo-scroll__track', 'flex', 'w-max', 'shrink-0', 'items-center'],
                            attributes: { 'data-vb-items-root': '' },
                            components: [1, 2, 3, 4, 5, 6].map((index) => buildLogoItem(index, 'lg')),
                        },
                    ],
                },
            ],
        },
    },
];

export function registerAnimatedComponentTypes(editor) {
    registerAnimatedCtaType(editor);
    registerAnimatedCounterType(editor);
    registerLogoScrollType(editor);
    registerAnimatedStatsType(editor);
}

export function registerAnimatedBlocks(editor) {
    const blockManager = editor.BlockManager;

    registerAnimatedComponentTypes(editor);

    for (const block of BLOCKS) {
        if (blockManager.get(block.id)) {
            blockManager.remove(block.id);
        }

        blockManager.add(block.id, {
            label: resolveBlockLabel(block.id, block.label),
            category: block.category,
            content: block.content,
            media: ANIMATED_BLOCK_WIREFRAMES[block.id] ?? wireframe('<rect x="10" y="14" width="28" height="20" rx="2"/>'),
            attributes: {
                title: block.label,
            },
        });
    }
}

export function configureAnimatedCanvas(editor) {
    const replayAnimations = () => {
        try {
            const frameDoc = editor.Canvas?.getDocument?.() ?? document;

            // Only when an animated block is added/configured — not on every frame load.
            initAnimatedCounters({ root: frameDoc, force: true, preferImmediate: true });
            initAnimatedCtas({ root: frameDoc, force: true });
            replayEditorCanvasAnimations({ root: frameDoc });
            initLogoScroll({ root: frameDoc });
        } catch {
            // Optional in editor.
        }
    };

    editor.on('voodbuilder:logo-scroll-config', (component) => {
        if (component) {
            syncLogoScroll(component);
        }

        window.setTimeout(replayAnimations, 80);
    });

    editor.on('component:add', (component) => {
        const type = component.get('type');

        if (type === 'voodbuilder-animated-cta') {
            syncAnimatedCta(component);
        }

        if (type === 'voodbuilder-animated-counter') {
            syncCounterAttributes(component);
        }

        if (type === 'voodbuilder-logo-scroll') {
            syncLogoScroll(component);
        }

        if (type === 'voodbuilder-animated-stats') {
            // Do not rebuild children on every add — only sync attributes.
            component.addAttributes({
                'data-voodbuilder-animated-stats': '',
                'data-vb-item-count': String(component.get('data-vb-item-count') ?? 4),
                'data-vb-item-min': '2',
                'data-vb-item-max': '6',
            });
        }

        if (
            type === 'voodbuilder-animated-cta'
            || type === 'voodbuilder-animated-counter'
            || type === 'voodbuilder-animated-stats'
            || type === 'voodbuilder-logo-scroll'
        ) {
            window.setTimeout(replayAnimations, 80);
        }
    });
}
