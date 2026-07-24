/**
 * VoodBuilder utility blocks — Basic, Media, and Single (post) elements.
 */

import { previewSvg, thumbWrap } from './editor-block-preview-utils.js';
import { resolveBlockLabel } from './section-block-meta.js';
import { tablerIconSvg } from './tabler-icons-catalog.js';
import { applyIconToComponent } from './basic-elements-settings.js';

export const BASIC_BLOCK_CATEGORY = 'Basic';
export const MEDIA_BLOCK_CATEGORY = 'Media';
export const SINGLE_BLOCK_CATEGORY = 'Single';

const LINK_TYPE_OPTIONS = [
    { id: 'none', label: 'None' },
    { id: 'url', label: 'Custom URL' },
    { id: 'internal', label: 'Internal page' },
    { id: 'media', label: 'Media file' },
    { id: 'lightbox-image', label: 'Lightbox image' },
    { id: 'lightbox-video', label: 'Lightbox video' },
];

const SOCIAL_NETWORKS = [
    { key: 'facebook', label: 'Facebook' },
    { key: 'x', label: 'X' },
    { key: 'linkedin', label: 'LinkedIn' },
    { key: 'whatsapp', label: 'WhatsApp' },
    { key: 'email', label: 'Email' },
    { key: 'copy_link', label: 'Copy link' },
];

const ICON_SVG = tablerIconSvg('star', { sizeClass: 'size-10' });

function galleryPlaceholderSrc(index) {
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect width="400" height="400" fill="#e2e8f0"/><text x="200" y="205" text-anchor="middle" fill="#94a3b8" font-family="system-ui" font-size="16">Image ${index}</text></svg>`;

    return `data:image/svg+xml,${encodeURIComponent(svg)}`;
}

function wireframe(paths) {
    return thumbWrap(previewSvg(paths));
}

export const UTILITY_BLOCK_WIREFRAMES = {
    'voodbuilder-heading': wireframe('<path d="M14 8v8M10 8v8M10 12h4"/><path d="M8 6h8"/>'),
    'voodbuilder-text': wireframe('<path d="M10 12h28M10 18h22M10 24h26M10 30h18"/>'),
    'voodbuilder-rich-text': wireframe('<path d="M10 12h28M10 18h24M10 24h28M10 30h16"/><path d="M10 36h20"/>'),
    'voodbuilder-icon': wireframe(
        '<path d="M12 17l-4.2 2.2 1-4.7L4 10.2l4.8-.7L12 5l3.2 4.5 4.8.7-3.2 4.3 1 4.7z"/>',
    ),
    'voodbuilder-text-link': wireframe('<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.5 1.5"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.5-1.5"/>'),
    'voodbuilder-button': wireframe(
        '<rect x="8" y="16" width="32" height="14" rx="3"/>'
        + '<path d="M14 23h20"/>',
    ),
    image: wireframe(
        '<rect x="9" y="12" width="30" height="24" rx="2.5" />'
        + '<circle cx="17" cy="20" r="2.25" />'
        + '<path d="M11 32l8-7 6 5 5-4 7 6" />',
    ),
    video: wireframe(
        '<rect x="9" y="14" width="30" height="20" rx="2.5" />'
        + '<path d="M22 20l8 4-8 4z" />',
    ),
    'voodbuilder-reading-time': wireframe('<circle cx="24" cy="24" r="10"/><path d="M24 18v6l4 2"/>'),
    'voodbuilder-reading-progress': wireframe('<path d="M8 28h32"/><rect x="8" y="26" width="18" height="3" rx="1.5" fill="currentColor" opacity="0.35"/>'),
    'voodbuilder-social-share': wireframe(
        '<path d="M18 8a3 3 0 1 0-2.8 4"/><path d="M6 15l9.2-5"/><path d="M18 16a3 3 0 1 0-2.8 4"/><path d="M6 9l9.2 5"/>',
    ),
    'voodbuilder-image-gallery': wireframe(
        '<rect x="8" y="12" width="13" height="13" rx="1.5"/>'
        + '<rect x="23" y="12" width="13" height="13" rx="1.5"/>'
        + '<rect x="8" y="27" width="13" height="13" rx="1.5"/>'
        + '<rect x="23" y="27" width="13" height="13" rx="1.5"/>',
    ),
    'voodbuilder-audio': wireframe(
        '<rect x="10" y="18" width="28" height="10" rx="2"/>'
        + '<path d="M16 23h3M22 23h6"/><circle cx="16" cy="23" r="2" fill="currentColor"/>',
    ),
    'voodbuilder-carousel': wireframe(
        '<rect x="8" y="14" width="32" height="18" rx="2"/>'
        + '<path d="M12 23h8M28 23h8"/><path d="M10 23l-2-2M38 23l2-2"/>',
    ),
    'voodbuilder-slider': wireframe(
        '<rect x="8" y="14" width="32" height="18" rx="2"/>'
        + '<circle cx="18" cy="36" r="1.5" fill="currentColor"/><circle cx="24" cy="36" r="1.5"/><circle cx="30" cy="36" r="1.5"/>',
    ),
    'voodbuilder-divider': wireframe('<path d="M8 24h32"/>'),
    'voodbuilder-embed': wireframe(
        '<rect x="8" y="14" width="32" height="18" rx="2"/>'
        + '<path d="M20 20l8 4-8 4z"/>',
    ),
};

function linkTraitSchema() {
    return [
        {
            type: 'select',
            name: 'data-vb-link-type',
            label: 'Link type',
            options: LINK_TYPE_OPTIONS.map((option) => ({
                id: option.id,
                label: option.label,
            })),
            changeProp: true,
        },
        {
            type: 'text',
            name: 'href',
            label: 'URL',
            changeProp: true,
        },
        {
            type: 'select',
            name: 'target',
            label: 'Open in',
            options: [
                { id: '', label: 'Same tab' },
                { id: '_blank', label: 'New tab' },
            ],
            changeProp: true,
        },
    ];
}

function applyLinkProps(component) {
    const linkType = component.get('data-vb-link-type') ?? 'url';
    const href = String(component.get('href') ?? '#').trim() || '#';
    const target = component.get('target') ?? '';

    component.addAttributes({
        'data-vb-link-type': linkType,
        href: linkType === 'none' ? '#' : href,
        target: target || null,
        rel: target === '_blank' ? 'noopener noreferrer' : null,
        'data-vb-lightbox': linkType.startsWith('lightbox-') ? linkType.replace('lightbox-', '') : null,
    });

    if (linkType === 'none') {
        component.addAttributes({ href: null, target: null, rel: null });
    }
}

function registerLinkableType(editor, typeName, defaults = {}) {
    if (editor.DomComponents.getType(typeName)) {
        return;
    }

    const base = editor.DomComponents.getType('link');
    const baseDefaults = base?.model?.prototype?.defaults ?? {};

    editor.DomComponents.addType(typeName, {
        extend: 'link',
        model: {
            defaults: {
                ...baseDefaults,
                ...defaults,
                traits: linkTraitSchema(),
            },
            init() {
                this.on('change:data-vb-link-type change:href change:target', () => applyLinkProps(this));
                applyLinkProps(this);
            },
        },
    });
}

function syncImageGalleryCount(component) {
    const count = Math.max(2, Math.min(12, Number(component.get('data-vb-item-count') ?? 6) || 6));
    const children = [...(component.components?.() ?? [])];
    const template = children[0];

    component.addAttributes({
        'data-voodbuilder-image-gallery': '',
        'data-vb-item-count': String(count),
    });

    if (! template) {
        return;
    }

    while (children.length > count) {
        children.pop()?.remove?.();
    }

    while (children.length < count) {
        const index = children.length + 1;
        component.append({
            type: 'image',
            classes: ['w-full', 'rounded-lg', 'object-cover', 'aspect-square'],
            attributes: {
                'data-vb-item': '',
                src: galleryPlaceholderSrc(index),
                alt: `Gallery image ${index}`,
            },
        });
        children.push(component.components().at(component.components().length - 1));
    }
}

function registerImageGalleryType(editor) {
    if (editor.DomComponents.getType('voodbuilder-image-gallery')) {
        return;
    }

    editor.DomComponents.addType('voodbuilder-image-gallery', {
        isComponent: (element) => element?.hasAttribute?.('data-voodbuilder-image-gallery') === true,
        model: {
            defaults: {
                tagName: 'div',
                name: 'Image gallery',
                attributes: {
                    'data-voodbuilder-image-gallery': '',
                    'data-vb-item-count': '6',
                    class: 'grid grid-cols-2 gap-3 md:grid-cols-3 vb-image-gallery',
                },
                traits: [
                    { type: 'number', name: 'data-vb-item-count', label: 'Images', min: 2, max: 12, changeProp: true },
                ],
                'data-vb-item-count': 6,
            },
            init() {
                this.on('change:data-vb-item-count', () => syncImageGalleryCount(this));
            },
        },
    });
}

function registerReadingTimeType(editor) {
    if (editor.DomComponents.getType('voodbuilder-reading-time')) {
        return;
    }

    editor.DomComponents.addType('voodbuilder-reading-time', {
        isComponent: (element) => element?.hasAttribute?.('data-voodbuilder-reading-time') === true,
        model: {
            defaults: {
                tagName: 'span',
                attributes: {
                    'data-voodbuilder-reading-time': '',
                    class: 'inline-flex items-center gap-1.5 text-sm text-vp-text-3 vb-reading-time',
                },
                components: '5 min read',
                traits: [
                    {
                        type: 'number',
                        name: 'data-vb-words-per-minute',
                        label: 'Words per minute',
                        min: 100,
                        max: 400,
                        changeProp: true,
                    },
                ],
                'data-vb-words-per-minute': 200,
            },
            init() {
                this.on('change:data-vb-words-per-minute', () => {
                    this.addAttributes({
                        'data-vb-words-per-minute': String(this.get('data-vb-words-per-minute') ?? 200),
                    });
                });
            },
        },
    });
}

function buildSocialShareLinks() {
    return SOCIAL_NETWORKS.map((network) => ({
        tagName: network.key === 'copy_link' ? 'button' : 'a',
        type: network.key === 'copy_link' ? 'button' : 'link',
        classes: [
            'inline-flex',
            'items-center',
            'rounded-md',
            'border',
            'border-current/20',
            'px-3',
            'py-1.5',
            'text-sm',
            'font-medium',
            'transition',
            'hover:border-current/40',
        ],
        attributes: {
            'data-network': network.key,
            ...(network.key === 'copy_link'
                ? { type: 'button', 'data-copy-url': '' }
                : { href: '#', target: '_blank', rel: 'noopener noreferrer' }),
        },
        components: network.label,
    }));
}

const BLOCKS = [
    {
        id: 'voodbuilder-heading',
        label: 'Heading',
        category: BASIC_BLOCK_CATEGORY,
        content: {
            type: 'text',
            tagName: 'h2',
            classes: ['text-3xl', 'font-bold', 'tracking-tight', 'text-vp-text-1'],
            content: 'Heading',
            editable: true,
        },
    },
    {
        id: 'voodbuilder-text',
        label: 'Basic Text',
        category: BASIC_BLOCK_CATEGORY,
        content: {
            type: 'text',
            tagName: 'p',
            classes: ['text-base', 'leading-relaxed', 'text-vp-text-2'],
            content: 'Insert your text here. Click to edit.',
            editable: true,
        },
    },
    {
        id: 'voodbuilder-rich-text',
        label: 'Rich Text',
        category: BASIC_BLOCK_CATEGORY,
        content: {
            type: 'text',
            tagName: 'div',
            classes: ['vb-rich-text', 'space-y-3', 'text-base', 'leading-relaxed', 'text-vp-text-2'],
            content: '<p>Write longer copy here. Select text to bold, italic, link, or wrap for styles.</p>',
            editable: true,
        },
    },
    {
        id: 'voodbuilder-text-link',
        label: 'Text link',
        category: BASIC_BLOCK_CATEGORY,
        content: {
            type: 'voodbuilder-text-link',
            classes: ['text-vp-brand-1', 'underline', 'underline-offset-2', 'vb-text-link'],
            attributes: {
                href: '#',
                'data-vb-link-type': 'url',
            },
            href: '#',
            components: 'Text link',
        },
    },
    {
        id: 'voodbuilder-button',
        label: 'Button',
        category: BASIC_BLOCK_CATEGORY,
        content: {
            type: 'voodbuilder-cta-button',
            tagName: 'a',
            classes: [
                'inline-flex',
                'items-center',
                'justify-center',
                'rounded',
                'border-0',
                'bg-indigo-500',
                'px-8',
                'py-2',
                'text-lg',
                'text-white',
                'hover:bg-indigo-600',
                'focus:outline-none',
            ],
            attributes: {
                href: '#',
                role: 'button',
                'data-voodbuilder-cta': 'true',
                'data-voodbuilder-cta-label': 'Button',
                'data-vb-link-type': 'url',
            },
            ctaLabel: 'Button',
            linkType: 'url',
            href: '#',
            components: 'Button',
        },
    },
    {
        id: 'voodbuilder-icon',
        label: 'Icon',
        category: BASIC_BLOCK_CATEGORY,
        content: {
            type: 'voodbuilder-icon',
            classes: ['inline-flex', 'items-center', 'justify-center', 'text-vp-text-2', 'vb-icon-link', 'size-10'],
            attributes: {
                'data-voodbuilder-icon': '',
                'data-vb-icon': 'star',
                'data-vb-icon-size': 'size-10',
                'data-vb-link-type': 'none',
            },
            components: ICON_SVG,
        },
    },
    {
        id: 'image',
        label: 'Image',
        category: BASIC_BLOCK_CATEGORY,
        content: {
            type: 'image',
            classes: ['w-full', 'h-auto', 'rounded'],
            attributes: {
                src: '',
                alt: 'Image',
            },
        },
    },
    {
        id: 'video',
        label: 'Video',
        category: BASIC_BLOCK_CATEGORY,
        content: {
            type: 'video',
            provider: 'yt',
            videoId: '',
            classes: ['w-full', 'rounded', 'aspect-video'],
            style: {
                width: '100%',
                'max-width': '100%',
                height: 'auto',
            },
        },
    },
    {
        id: 'voodbuilder-divider',
        label: 'Divider',
        category: BASIC_BLOCK_CATEGORY,
        content: `
            <hr class="vb-divider my-6 w-full border-0 border-t border-vp-divider" data-voodbuilder-divider data-vb-divider-color="border-vp-divider" />
        `,
    },
    {
        id: 'voodbuilder-reading-time',
        label: 'Reading time',
        category: SINGLE_BLOCK_CATEGORY,
        content: {
            type: 'voodbuilder-reading-time',
            attributes: { 'data-voodbuilder-reading-time': '' },
            components: '5 min read',
        },
    },
    {
        id: 'voodbuilder-reading-progress',
        label: 'Reading progress',
        category: SINGLE_BLOCK_CATEGORY,
        content: `
            <div class="vb-reading-progress" data-voodbuilder-progress>
                <div class="vb-reading-progress__bar" data-reading-progress></div>
            </div>
        `,
    },
    {
        id: 'voodbuilder-social-share',
        label: 'Social sharing',
        category: SINGLE_BLOCK_CATEGORY,
        content: {
            tagName: 'div',
            classes: ['vp-social-links', 'flex', 'flex-wrap', 'gap-2', 'vb-social-share'],
            attributes: {
                'data-voodbuilder-social-share': '',
                'data-share-url': '',
            },
            components: buildSocialShareLinks(),
        },
    },
    {
        id: 'voodbuilder-image-gallery',
        label: 'Image gallery',
        category: MEDIA_BLOCK_CATEGORY,
        content: {
            type: 'voodbuilder-image-gallery',
            classes: ['grid', 'grid-cols-2', 'gap-3', 'md:grid-cols-3', 'vb-image-gallery'],
            attributes: {
                'data-voodbuilder-image-gallery': '',
                'data-vb-item-count': '6',
            },
            'data-vb-item-count': 6,
            components: [1, 2, 3, 4, 5, 6].map((index) => ({
                type: 'image',
                classes: ['w-full', 'rounded-lg', 'object-cover', 'aspect-square'],
                attributes: {
                    'data-vb-item': '',
                    src: galleryPlaceholderSrc(index),
                    alt: `Gallery image ${index}`,
                },
            })),
        },
    },
    {
        id: 'voodbuilder-audio',
        label: 'Audio',
        category: MEDIA_BLOCK_CATEGORY,
        content: `
            <div class="vb-audio" data-voodbuilder-audio>
                <audio controls class="w-full max-w-xl rounded-lg" src=""></audio>
            </div>
        `,
    },
    {
        id: 'voodbuilder-carousel',
        label: 'Carousel',
        category: MEDIA_BLOCK_CATEGORY,
        content: {
            tagName: 'div',
            classes: ['vb-carousel', 'relative'],
            attributes: { 'data-voodbuilder-carousel': '' },
            components: [
                {
                    tagName: 'div',
                    classes: ['vb-carousel__track', 'flex', 'gap-4', 'overflow-x-auto', 'snap-x', 'snap-mandatory', 'pb-2'],
                    components: [1, 2, 3].map((slide) => ({
                        tagName: 'div',
                        classes: ['vb-carousel__slide', 'min-w-[85%]', 'snap-start', 'rounded-xl', 'border', 'border-vp-divider', 'bg-vp-bg-alt', 'p-8'],
                        components: `<p class="text-lg font-medium text-vp-text-1">Slide ${slide}</p><p class="mt-2 text-sm text-vp-text-2">Add images or content inside each slide.</p>`,
                    })),
                },
                {
                    tagName: 'div',
                    classes: ['vb-carousel__nav', 'mt-3', 'flex', 'justify-center', 'gap-2'],
                    components: [
                        { tagName: 'button', type: 'button', classes: ['vb-carousel__btn'], attributes: { type: 'button', 'data-carousel-prev': '' }, components: '‹' },
                        { tagName: 'button', type: 'button', classes: ['vb-carousel__btn'], attributes: { type: 'button', 'data-carousel-next': '' }, components: '›' },
                    ],
                },
            ],
        },
    },
    {
        id: 'voodbuilder-slider',
        label: 'Slider',
        category: MEDIA_BLOCK_CATEGORY,
        content: {
            tagName: 'div',
            classes: ['vb-slider', 'relative'],
            attributes: { 'data-voodbuilder-slider': '' },
            components: [
                {
                    tagName: 'div',
                    classes: ['vb-slider__viewport', 'overflow-hidden', 'rounded-xl', 'border', 'border-vp-divider'],
                    components: [1, 2, 3].map((slide, index) => ({
                        tagName: 'div',
                        classes: ['vb-slider__slide', 'p-10', 'bg-vp-bg-alt', ...(index === 0 ? ['is-active'] : [])],
                        attributes: { 'data-slider-index': String(index) },
                        components: `<p class="text-lg font-medium text-vp-text-1">Slide ${slide}</p><p class="mt-2 text-sm text-vp-text-2">Use the dots below to switch slides on the frontend.</p>`,
                    })),
                },
                {
                    tagName: 'div',
                    classes: ['vb-slider__dots', 'mt-3', 'flex', 'justify-center', 'gap-2'],
                    components: [0, 1, 2].map((index) => ({
                        tagName: 'button',
                        type: 'button',
                        classes: ['vb-slider__dot', ...(index === 0 ? ['is-active'] : [])],
                        attributes: { type: 'button', 'data-slider-dot': String(index), 'aria-label': `Slide ${index + 1}` },
                    })),
                },
            ],
        },
    },
    {
        id: 'voodbuilder-embed',
        label: 'Embed',
        category: MEDIA_BLOCK_CATEGORY,
        content: `
            <div class="vb-embed aspect-video w-full overflow-hidden rounded-xl border border-vp-divider bg-vp-bg-alt" data-voodbuilder-embed>
                <iframe
                    class="h-full w-full"
                    src="https://www.youtube.com/embed/dQw4w9WgXcQ"
                    title="Embedded content"
                    loading="lazy"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </div>
        `,
    },
];

export function registerUtilityBlockComponentTypes(editor) {
    registerLinkableType(editor, 'voodbuilder-icon', {
        name: 'Icon',
        droppable: false,
        editable: false,
    });

    const iconType = editor.DomComponents.getType('voodbuilder-icon');

    if (iconType?.model) {
        const proto = iconType.model.prototype;
        const previousInit = proto.init;

        proto.init = function initIcon() {
            previousInit?.call(this);

            const syncIcon = () => {
                const attrs = this.getAttributes?.() ?? {};
                applyIconToComponent(this, {
                    name: attrs['data-vb-icon'] || 'star',
                    sizeClass: attrs['data-vb-icon-size'] || 'size-10',
                    href: attrs.href || this.get('href'),
                    linkType: attrs['data-vb-link-type'] || 'none',
                });
            };

            this.on('change:attributes:data-vb-icon change:attributes:data-vb-icon-size', syncIcon);
            syncIcon();
        };
    }

    registerLinkableType(editor, 'voodbuilder-text-link', {
        name: 'Text link',
    });
    registerReadingTimeType(editor);
    registerImageGalleryType(editor);
}

export function registerUtilityBlocks(editor) {
    const blockManager = editor.BlockManager;

    registerUtilityBlockComponentTypes(editor);

    // Drop stock grapesjs-blocks-basic leftovers + retired elements.
    for (const id of [
        'column1',
        'column2',
        'column3',
        'column3-7',
        'text',
        'link',
        'image',
        'video',
        'map',
        'voodbuilder-icon-box',
        'voodbuilder-styled-list',
    ]) {
        if (blockManager.get(id)) {
            blockManager.remove(id);
        }
    }

    for (const block of BLOCKS) {
        if (blockManager.get(block.id)) {
            blockManager.remove(block.id);
        }

        blockManager.add(block.id, {
            label: resolveBlockLabel(block.id, block.label),
            category: block.category,
            content: block.content,
            media: UTILITY_BLOCK_WIREFRAMES[block.id] ?? wireframe('<rect x="10" y="14" width="28" height="20" rx="2"/>'),
            attributes: {
                title: block.label,
            },
            activate: block.id === 'image' || block.id === 'voodbuilder-text' || block.id === 'voodbuilder-heading',
        });
    }
}

export function configureUtilityBlocksCanvas(editor) {
    const bindLinkables = (component) => {
        const type = component.get('type');

        if (type === 'voodbuilder-icon' || type === 'voodbuilder-text-link') {
            applyLinkProps(component);
        }
    };

    editor.on('load', () => {
        editor.getWrapper().find('[data-voodbuilder-icon], .vb-text-link').forEach(bindLinkables);
    });

    editor.on('component:add', bindLinkables);
}
