/**
 * Logo cloud Editor blocks — theme-token grids (bordered + split CTA).
 * Uses vp-* colors so light/dark follow the site theme automatically.
 */

import { previewSvg, thumbWrap } from './editor-block-preview-utils.js';
import { resolveBlockLabel } from './section-block-meta.js';
import { widthClassesForItemCount } from './section-item-count.js';

export const LOGO_CLOUD_CATEGORY = 'Animated';

function wireframe(paths) {
    return thumbWrap(previewSvg(paths));
}

export const LOGO_CLOUD_WIREFRAMES = {
    'voodbuilder-logo-grid': wireframe(
        '<rect x="8" y="12" width="34" height="24" rx="3"/>'
        + '<path d="M8 20h34M19 12v24M30 12v24"/>'
        + '<rect x="11" y="15" width="5" height="3" rx="0.5" opacity="0.35"/>'
        + '<rect x="22" y="15" width="5" height="3" rx="0.5" opacity="0.35"/>'
        + '<rect x="33" y="15" width="5" height="3" rx="0.5" opacity="0.35"/>',
    ),
    'voodbuilder-logo-split': wireframe(
        '<rect x="8" y="12" width="14" height="4" rx="1"/>'
        + '<rect x="8" y="19" width="14" height="2" rx="0.5" opacity="0.35"/>'
        + '<rect x="8" y="28" width="8" height="4" rx="1" fill="currentColor" opacity="0.25"/>'
        + '<rect x="26" y="14" width="6" height="4" rx="0.5" opacity="0.4"/>'
        + '<rect x="34" y="14" width="6" height="4" rx="0.5" opacity="0.4"/>'
        + '<rect x="26" y="22" width="6" height="4" rx="0.5" opacity="0.4"/>'
        + '<rect x="34" y="22" width="6" height="4" rx="0.5" opacity="0.4"/>'
        + '<rect x="26" y="30" width="6" height="4" rx="0.5" opacity="0.4"/>'
        + '<rect x="34" y="30" width="6" height="4" rx="0.5" opacity="0.4"/>',
    ),
};

const LOGO_NAMES = ['Transistor', 'Reform', 'Tuple', 'Laravel', 'SavvyCal', 'Statamic', 'Partner', 'Acme'];

/**
 * @param {number} index
 * @param {string} name
 * @returns {string}
 */
function logoMarkPlaceholder(index, name) {
    const label = name || `Logo ${index + 1}`;
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="180" height="40" viewBox="0 0 180 40" fill="none">`
        + `<rect x="2" y="8" width="24" height="24" rx="6" fill="#94a3b8" fill-opacity="0.45"/>`
        + `<text x="36" y="26" fill="#64748b" font-family="ui-sans-serif,system-ui,sans-serif" font-size="16" font-weight="600">${label}</text>`
        + `</svg>`;

    return `data:image/svg+xml,${encodeURIComponent(svg)}`;
}

/**
 * @param {number} index
 * @param {'bordered'|'plain'} variant
 * @returns {object}
 */
function buildLogoCloudItem(index, variant = 'bordered') {
    const name = LOGO_NAMES[index] ?? `Partner ${index + 1}`;
    const widthClasses = widthClassesForItemCount(6);
    const base = [
        'vb-logo-cloud__item',
        'flex',
        'items-center',
        'justify-center',
        ...widthClasses,
    ];

    if (variant === 'bordered') {
        base.push(
            'border-r',
            'border-b',
            'border-vp-divider',
            'p-8',
            'sm:p-10',
        );
    } else {
        base.push('px-4', 'py-3');
    }

    return {
        tagName: 'div',
        classes: base,
        attributes: { 'data-vb-item': '' },
        components: [
            {
                type: 'link',
                classes: [
                    'vb-logo-cloud__link',
                    'inline-flex',
                    'max-w-[11rem]',
                    'items-center',
                    'justify-center',
                    'opacity-80',
                    'transition',
                    'hover:opacity-100',
                ],
                attributes: {
                    href: '#',
                    title: name,
                    'aria-label': name,
                },
                components: [
                    {
                        type: 'image',
                        classes: ['h-8', 'w-auto', 'max-w-full', 'object-contain'],
                        attributes: {
                            src: logoMarkPlaceholder(index, name),
                            alt: name,
                        },
                    },
                ],
            },
        ],
    };
}

/**
 * @param {object} component
 * @param {number} fallback
 * @returns {number}
 */
function readLogoCount(component, fallback = 6) {
    return Math.max(
        2,
        Math.min(8, Number(component.get('data-vb-item-count') ?? fallback) || fallback),
    );
}

/**
 * @param {object} component
 * @param {'bordered'|'plain'} variant
 */
function syncLogoCloudItems(component, variant) {
    const count = readLogoCount(component);
    const attrKey = variant === 'bordered'
        ? 'data-voodbuilder-logo-grid'
        : 'data-voodbuilder-logo-split';

    component.addAttributes({
        [attrKey]: '',
        'data-vb-item-count': String(count),
        'data-vb-item-min': '2',
        'data-vb-item-max': '8',
    });

    const roots = component.find('[data-vb-items-root]');
    const root = roots[0];

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
        root.append(buildLogoCloudItem(index, variant));
        items.push(root.components().at(root.components().length - 1));
    }

    items.forEach((item) => {
        const classes = [...(item.getClasses?.() ?? [])].filter(
            (className) => ! /^(?:sm|md|lg|xl):w-1\/\d+$|^w-1\/\d+$|^w-full$/.test(className),
        );
        item.setClass([...classes, ...widthClasses]);
    });
}

function registerLogoGridType(editor) {
    if (editor.DomComponents.getType('voodbuilder-logo-grid')) {
        return;
    }

    editor.DomComponents.addType('voodbuilder-logo-grid', {
        isComponent: (element) => element?.hasAttribute?.('data-voodbuilder-logo-grid') === true,
        model: {
            defaults: {
                tagName: 'section',
                name: 'Logo grid',
                droppable: false,
                attributes: {
                    'data-voodbuilder-logo-grid': '',
                    'data-voodbuilder-section-block': 'voodbuilder-logo-grid',
                    'data-vb-item-count': '6',
                    'data-vb-item-min': '2',
                    'data-vb-item-max': '8',
                    class: 'vb-logo-grid text-vp-text-2',
                },
                traits: [
                    { type: 'number', name: 'data-vb-item-count', label: 'Logos', min: 2, max: 8, changeProp: true },
                ],
                'data-vb-item-count': 6,
            },
            init() {
                this.on('change:data-vb-item-count', () => syncLogoCloudItems(this, 'bordered'));
            },
        },
    });
}

function registerLogoSplitType(editor) {
    if (editor.DomComponents.getType('voodbuilder-logo-split')) {
        return;
    }

    editor.DomComponents.addType('voodbuilder-logo-split', {
        isComponent: (element) => element?.hasAttribute?.('data-voodbuilder-logo-split') === true,
        model: {
            defaults: {
                tagName: 'section',
                name: 'Logo split',
                droppable: false,
                attributes: {
                    'data-voodbuilder-logo-split': '',
                    'data-voodbuilder-section-block': 'voodbuilder-logo-split',
                    'data-vb-item-count': '6',
                    'data-vb-item-min': '2',
                    'data-vb-item-max': '8',
                    class: 'vb-logo-split text-vp-text-2',
                },
                traits: [
                    { type: 'number', name: 'data-vb-item-count', label: 'Logos', min: 2, max: 8, changeProp: true },
                ],
                'data-vb-item-count': 6,
            },
            init() {
                this.on('change:data-vb-item-count', () => syncLogoCloudItems(this, 'plain'));
            },
        },
    });
}

const BLOCKS = [
    {
        id: 'voodbuilder-logo-grid',
        label: 'Logo grid',
        category: LOGO_CLOUD_CATEGORY,
        content: {
            type: 'voodbuilder-logo-grid',
            classes: ['vb-logo-grid', 'text-vp-text-2'],
            attributes: {
                'data-voodbuilder-logo-grid': '',
                'data-voodbuilder-section-block': 'voodbuilder-logo-grid',
                'data-vb-item-count': '6',
                'data-vb-item-min': '2',
                'data-vb-item-max': '8',
            },
            'data-vb-item-count': 6,
            droppable: false,
            components: [
                {
                    tagName: 'div',
                    classes: ['voodbuilder-editor-container', 'px-5', 'py-24'],
                    components: [
                        {
                            tagName: 'div',
                            classes: [
                                'vb-logo-cloud__track',
                                'mx-auto',
                                'flex',
                                'max-w-lg',
                                'flex-wrap',
                                'overflow-hidden',
                                'rounded-2xl',
                                'bg-vp-bg-alt',
                                'ring-1',
                                'ring-vp-divider',
                                'sm:max-w-none',
                            ],
                            attributes: {
                                'data-vb-items-root': '',
                                'data-vb-item-min': '2',
                                'data-vb-item-max': '8',
                            },
                            components: [0, 1, 2, 3, 4, 5].map((index) => buildLogoCloudItem(index, 'bordered')),
                        },
                    ],
                },
            ],
        },
    },
    {
        id: 'voodbuilder-logo-split',
        label: 'Logo split',
        category: LOGO_CLOUD_CATEGORY,
        content: {
            type: 'voodbuilder-logo-split',
            classes: ['vb-logo-split', 'text-vp-text-2'],
            attributes: {
                'data-voodbuilder-logo-split': '',
                'data-voodbuilder-section-block': 'voodbuilder-logo-split',
                'data-vb-item-count': '6',
                'data-vb-item-min': '2',
                'data-vb-item-max': '8',
            },
            'data-vb-item-count': 6,
            droppable: false,
            components: [
                {
                    tagName: 'div',
                    classes: ['voodbuilder-editor-container', 'px-5', 'py-24'],
                    components: [
                        {
                            tagName: 'div',
                            classes: [
                                'flex',
                                'flex-wrap',
                                'items-center',
                                '-mx-4',
                            ],
                            components: [
                                {
                                    tagName: 'div',
                                    classes: ['w-full', 'lg:w-1/2', 'px-4', 'mb-10', 'lg:mb-0'],
                                    components: [
                                        {
                                            tagName: 'h2',
                                            classes: [
                                                'sm:text-4xl',
                                                'text-3xl',
                                                'font-semibold',
                                                'text-vp-text-1',
                                                'mb-4',
                                            ],
                                            components: 'Trusted by the most innovative teams',
                                        },
                                        {
                                            tagName: 'p',
                                            classes: [
                                                'leading-relaxed',
                                                'text-base',
                                                'mb-8',
                                            ],
                                            components: 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Et, egestas tempus tellus etiam sed. Quam a scelerisque amet ullamcorper eu enim et fermentum, augue.',
                                        },
                                        {
                                            tagName: 'div',
                                            classes: ['flex', 'flex-wrap', 'items-center', 'gap-3'],
                                            components: [
                                                {
                                                    type: 'link',
                                                    classes: [
                                                        'inline-flex',
                                                        'items-center',
                                                        'rounded-lg',
                                                        'bg-vp-brand-1',
                                                        'px-6',
                                                        'py-3',
                                                        'text-base',
                                                        'font-medium',
                                                        'text-white',
                                                        'transition',
                                                        'hover:opacity-90',
                                                    ],
                                                    attributes: { href: '#' },
                                                    components: 'Create account',
                                                },
                                                {
                                                    type: 'link',
                                                    classes: [
                                                        'inline-flex',
                                                        'items-center',
                                                        'rounded-lg',
                                                        'border',
                                                        'border-vp-divider',
                                                        'px-6',
                                                        'py-3',
                                                        'text-base',
                                                        'font-medium',
                                                        'text-vp-text-1',
                                                        'transition',
                                                        'hover:bg-vp-bg-alt',
                                                    ],
                                                    attributes: { href: '#' },
                                                    components: 'Contact us →',
                                                },
                                            ],
                                        },
                                    ],
                                },
                                {
                                    tagName: 'div',
                                    classes: [
                                        'vb-logo-cloud__track',
                                        'w-full',
                                        'lg:w-1/2',
                                        'px-4',
                                        'flex',
                                        'flex-wrap',
                                        'items-center',
                                        'justify-center',
                                        'lg:justify-end',
                                    ],
                                    attributes: {
                                        'data-vb-items-root': '',
                                        'data-vb-item-min': '2',
                                        'data-vb-item-max': '8',
                                    },
                                    components: [0, 1, 2, 3, 4, 5].map((index) => buildLogoCloudItem(index, 'plain')),
                                },
                            ],
                        },
                    ],
                },
            ],
        },
    },
];

export function registerLogoCloudComponentTypes(editor) {
    registerLogoGridType(editor);
    registerLogoSplitType(editor);
}

export function registerLogoCloudBlocks(editor) {
    const blockManager = editor.BlockManager;

    registerLogoCloudComponentTypes(editor);

    for (const block of BLOCKS) {
        if (blockManager.get(block.id)) {
            blockManager.remove(block.id);
        }

        blockManager.add(block.id, {
            label: resolveBlockLabel(block.id, block.label),
            category: block.category,
            content: block.content,
            media: LOGO_CLOUD_WIREFRAMES[block.id] ?? wireframe('<rect x="10" y="14" width="28" height="20" rx="2"/>'),
            attributes: {
                title: block.label,
            },
        });
    }
}

export function configureLogoCloudCanvas(editor) {
    editor.on('component:add', (component) => {
        const type = component.get('type');

        if (type === 'voodbuilder-logo-grid') {
            component.addAttributes({
                'data-voodbuilder-logo-grid': '',
                'data-voodbuilder-section-block': 'voodbuilder-logo-grid',
                'data-vb-item-count': String(component.get('data-vb-item-count') ?? 6),
                'data-vb-item-min': '2',
                'data-vb-item-max': '8',
            });
            component.set?.('droppable', false);
        }

        if (type === 'voodbuilder-logo-split') {
            component.addAttributes({
                'data-voodbuilder-logo-split': '',
                'data-voodbuilder-section-block': 'voodbuilder-logo-split',
                'data-vb-item-count': String(component.get('data-vb-item-count') ?? 6),
                'data-vb-item-min': '2',
                'data-vb-item-max': '8',
            });
            component.set?.('droppable', false);
        }
    });
}
