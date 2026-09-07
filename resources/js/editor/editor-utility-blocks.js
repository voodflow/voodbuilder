/**
 * VoodBuilder utility blocks — Basic, Media, and Single (post) elements.
 */

import { previewSvg, thumbWrap } from './editor-block-preview-utils.js';
import { resolveBlockLabel } from './section-block-meta.js';
import { isEditorBlockAllowed } from './block-allowlist.js';
import { DEFAULT_TABLER_ICON, tablerIconSvg } from './tabler-icons-catalog.js';
import { applyIconToComponent, findIconHost, isIconComponent, readIconColor } from './basic-elements-settings.js';
import { registerTextElementTypes, lockRichTextChildren } from './text-elements.js';

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

const ICON_SVG = tablerIconSvg(DEFAULT_TABLER_ICON, { sizeClass: 'w-full h-full' });

function galleryPlaceholderSrc(index) {
    const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="400" height="400"><rect width="400" height="400" fill="#e2e8f0"/><text x="200" y="205" text-anchor="middle" fill="#94a3b8" font-family="system-ui" font-size="16">Image ${index}</text></svg>`;

    return `data:image/svg+xml,${encodeURIComponent(svg)}`;
}

function wireframe(paths) {
    return thumbWrap(previewSvg(paths));
}

/** Library thumbs: Tabler outline paths (MIT) on 24×24 viewBox. */
function tablerThumb(paths) {
    return thumbWrap(previewSvg(paths, '0 0 24 24'));
}

export const UTILITY_BLOCK_WIREFRAMES = {
    'voodbuilder-heading': tablerThumb(
        '<path d="M7 12h10"/><path d="M7 5v14"/><path d="M17 5v14"/><path d="M15 19h4"/><path d="M15 5h4"/><path d="M5 19h4"/><path d="M5 5h4"/>',
    ),
    'voodbuilder-text': tablerThumb(
        '<path d="M4 6l16 0"/><path d="M4 12l10 0"/><path d="M4 18l14 0"/>',
    ),
    'voodbuilder-rich-text': tablerThumb(
        // ti-text-plus
        '<path d="M19 10h-14"/><path d="M5 6h14"/><path d="M14 14h-9"/><path d="M5 18h6"/><path d="M18 15v6"/><path d="M15 18h6"/>',
    ),
    'voodbuilder-icon': tablerThumb(
        '<path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>',
    ),
    'voodbuilder-text-link': tablerThumb(
        '<path d="M9 15l6 -6"/><path d="M11 6l.463 -.536a5 5 0 0 1 7.071 7.072l-.534 .464"/><path d="M13 18l-.397 .534a5.068 5.068 0 0 1 -7.127 0a4.972 4.972 0 0 1 0 -7.071l.524 -.463"/>',
    ),
    'voodbuilder-button': tablerThumb(
        '<path d="M3 5m0 2a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z"/>',
    ),
    'voodbuilder-divider': tablerThumb(
        '<path d="M3 12l0 .01"/><path d="M7 12l10 0"/><path d="M21 12l0 .01"/>',
    ),
    image: tablerThumb(
        '<path d="M15 8h.01"/><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"/><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5"/><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3"/>',
    ),
    video: tablerThumb(
        '<path d="M15 10l4.553 -2.276a1 1 0 0 1 1.447 .894v6.764a1 1 0 0 1 -1.447 .894l-4.553 -2.276v-4z"/><path d="M3 6m0 2a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v8a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2z"/>',
    ),
    'voodbuilder-reading-time': tablerThumb(
        // ti-clock
        '<path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 7v5l3 3"/>',
    ),
    'voodbuilder-reading-progress': tablerThumb(
        // ti-line
        '<path d="M4 12h16"/><path d="M4 12v.01"/><path d="M20 12v.01"/><path d="M8 12h8" stroke-width="2.5"/>',
    ),
    'voodbuilder-social-share': tablerThumb(
        // ti-share
        '<path d="M6 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M18 6m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M18 18m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"/><path d="M8.7 10.7l6.6 -3.4"/><path d="M8.7 13.3l6.6 3.4"/>',
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
    const attrs = component.getAttributes?.() ?? {};
    const linkType = component.get('linkType')
        ?? attrs['data-vb-link-type']
        ?? component.get('data-vb-link-type')
        ?? 'url';
    const href = String(component.get('href') ?? attrs.href ?? '#').trim() || '#';
    const target = component.get('target') ?? attrs.target ?? '';

    component.addAttributes({
        'data-vb-link-type': linkType,
        href: linkType === 'none' ? null : href,
        target: linkType === 'none' || ! target ? null : target,
        rel: linkType !== 'none' && target === '_blank' ? 'noopener noreferrer' : null,
        'data-vb-lightbox': String(linkType).startsWith('lightbox-') ? String(linkType).replace('lightbox-', '') : null,
    });
}

function registerLinkableType(editor, typeName, defaults = {}) {
    if (editor.DomComponents.getType(typeName)) {
        return;
    }

    const base = editor.DomComponents.getType('link');
    const baseDefaults = base?.model?.prototype?.defaults ?? {};

    editor.DomComponents.addType(typeName, {
        extend: 'link',
        isComponent: (element) => {
            if (! element || String(element.tagName ?? '').toUpperCase() !== 'A') {
                return false;
            }

            if (element.getAttribute?.('data-voodbuilder-cta') === 'true'
                || element.hasAttribute?.('data-voodbuilder-skip-cta')
                || element.hasAttribute?.('data-voodbuilder-icon')) {
                return false;
            }

            if (element.classList?.contains?.('vb-text-link')
                || element.getAttribute?.('data-vb-link-type')) {
                return { type: typeName };
            }

            // Plain content anchors from catalog sections (e.g. gallery "Read more").
            if (element.closest?.('nav, [data-voodbuilder-editor-site-header], [data-voodbuilder-editor-site-footer], [data-voodbuilder-chrome-shell], [data-voodbuilder-chrome-shell-part]')) {
                return false;
            }

            return { type: typeName };
        },
        model: {
            defaults: {
                ...baseDefaults,
                ...defaults,
                traits: linkTraitSchema(),
            },
            init() {
                this.on('change:linkType change:data-vb-link-type change:href change:target', () => applyLinkProps(this));

                const classes = this.getClasses?.() ?? [];

                if (! classes.includes('vb-text-link')) {
                    this.addClass?.('vb-text-link');
                }

                applyLinkProps(this);
            },
        },
    });
}

/**
 * Icon is a span by default (not a Grapes `link`) so it drops into catalog Heroes
 * and dropzones reliably; it morphs to `<a>` only when a link type is set.
 *
 * @param {import('grapesjs').Editor} editor
 */
function registerIconType(editor) {
    if (editor.DomComponents.getType('voodbuilder-icon')) {
        return;
    }

    editor.DomComponents.addType('voodbuilder-icon', {
        extend: 'default',
        isComponent: (element) => {
            if (element?.getAttribute?.('data-voodbuilder-icon') != null) {
                return { type: 'voodbuilder-icon' };
            }

            return false;
        },
        model: {
            defaults: {
                tagName: 'span',
                name: 'Icon',
                droppable: false,
                editable: false,
                attributes: {
                    'data-voodbuilder-icon': '',
                    'data-vb-link-type': 'none',
                },
                linkType: 'none',
                href: '#',
                traits: linkTraitSchema(),
            },
            init() {
                this.on('change:linkType change:attributes:data-vb-link-type change:href change:target', () => {
                    applyLinkProps(this);
                    const type = this.get('linkType')
                        ?? this.getAttributes?.()?.['data-vb-link-type']
                        ?? 'none';
                    const nextTag = type === 'none' ? 'span' : 'a';

                    if (String(this.get('tagName') ?? '').toLowerCase() !== nextTag) {
                        this.set('tagName', nextTag);
                    }
                });
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
            type: 'voodbuilder-text',
            tagName: 'p',
            classes: ['text-base', 'leading-relaxed', 'text-vp-text-2'],
            attributes: {
                'data-voodbuilder-text': '',
            },
            content: 'Insert your text here. Click to edit.',
            editable: true,
        },
    },
    {
        id: 'voodbuilder-rich-text',
        label: 'Rich Text',
        category: BASIC_BLOCK_CATEGORY,
        content: {
            type: 'voodbuilder-rich-text',
            tagName: 'div',
            classes: ['vb-rich-text', 'space-y-3', 'text-base', 'leading-relaxed', 'text-vp-text-2'],
            attributes: {
                'data-voodbuilder-rich-text': '',
            },
            components: '<p>Write longer copy here. Use Content → Visual to format.</p>',
            editable: false,
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
            tagName: 'span',
            classes: ['inline-flex', 'items-center', 'justify-center', 'text-vp-text-2', 'vb-icon-link', 'size-10'],
            attributes: {
                'data-voodbuilder-icon': '',
                'data-vb-icon': DEFAULT_TABLER_ICON,
                'data-vb-icon-size': 'size-10',
                'data-vb-icon-style': 'outline',
                'data-vb-icon-stroke': '1.75',
                'data-vb-link-type': 'none',
            },
            linkType: 'none',
            href: '#',
            components: ICON_SVG,
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
        id: 'image',
        label: 'Image',
        category: MEDIA_BLOCK_CATEGORY,
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
        category: MEDIA_BLOCK_CATEGORY,
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
    // Embed folded into Video (YouTube / Vimeo / file). Legacy data-voodbuilder-embed markup still renders.
];

export function registerUtilityBlockComponentTypes(editor) {
    registerTextElementTypes(editor);

    registerIconType(editor);

    const iconType = editor.DomComponents.getType('voodbuilder-icon');

    if (iconType?.model) {
        const proto = iconType.model.prototype;
        const previousInit = proto.init;

        proto.init = function initIcon() {
            previousInit?.call(this);

            const syncIcon = () => {
                // Skip while settings apply is writing attrs — avoids nested apply that
                // sees the new data-vb-icon but the still-stale SVG and bails early.
                if (Number(editor.__voodbuilderSettingsChangeDepth ?? 0) > 0) {
                    return;
                }

                this.__vbIconPainted = false;
                const attrs = this.getAttributes?.() ?? {};
                applyIconToComponent(this, editor, {
                    name: attrs['data-vb-icon'] || DEFAULT_TABLER_ICON,
                    sizeClass: attrs['data-vb-icon-size'] || 'size-10',
                    style: attrs['data-vb-icon-style'] || 'outline',
                    stroke: attrs['data-vb-icon-stroke'] || '1.75',
                    color: readIconColor(this),
                    href: attrs.href || this.get('href'),
                    linkType: this.get('linkType') || attrs['data-vb-link-type'] || 'none',
                    linkRef: this.get('linkRef') || attrs['data-vb-link'] || '',
                    target: this.get('target') || attrs.target || '',
                });
            };

            this.on('change:attributes:data-vb-icon change:attributes:data-vb-icon-size change:attributes:data-vb-icon-style change:attributes:data-vb-icon-stroke change:attributes:data-vb-icon-color change:attributes:data-vb-link-type', syncIcon);
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
        if (! isEditorBlockAllowed(editor, block.id)) {
            if (blockManager.get(block.id)) {
                blockManager.remove(block.id);
            }

            continue;
        }

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
        const isIcon = type === 'voodbuilder-icon' || isIconComponent(component);

        if (isIcon || type === 'voodbuilder-text-link') {
            applyLinkProps(component);
        }

        if (isIcon) {
            // Force a canvas paint after frame/view is ready.
            component.__vbIconPainted = false;
            applyIconToComponent(component, editor, {
                name: component.getAttributes?.()?.['data-vb-icon'] || DEFAULT_TABLER_ICON,
                sizeClass: component.getAttributes?.()?.['data-vb-icon-size'] || 'size-10',
                style: component.getAttributes?.()?.['data-vb-icon-style'] || 'outline',
                stroke: component.getAttributes?.()?.['data-vb-icon-stroke'] || '1.75',
                color: readIconColor(component),
                href: component.get('href'),
                linkType: component.get('linkType') || component.getAttributes?.()?.['data-vb-link-type'] || 'none',
                linkRef: component.get('linkRef') || component.getAttributes?.()?.['data-vb-link'] || '',
                target: component.get('target') || '',
            });
        }

        if (type === 'voodbuilder-rich-text') {
            lockRichTextChildren(component);
        }
    };

    const syncAllIcons = () => {
        editor.getWrapper?.()?.find?.('[data-voodbuilder-icon], .vb-text-link, [data-voodbuilder-rich-text], .vb-rich-text')
            ?.forEach?.(bindLinkables);

        // Linked icons may be parsed as Grapes `link` — still walk the tree by attribute.
        editor.getWrapper?.()?.onAll?.((component) => {
            if (isIconComponent(component)) {
                bindLinkables(component);
            }
        });
    };

    editor.on('load', syncAllIcons);
    editor.on('canvas:frame:load', () => {
        // Canvas DOM may not exist on `load` — re-paint colors once the frame is ready.
        window.requestAnimationFrame(syncAllIcons);
        window.setTimeout(syncAllIcons, 50);
        window.setTimeout(syncAllIcons, 250);
    });
    editor.on('component:add', bindLinkables);
    editor.on('component:selected', (component) => {
        const host = findIconHost(component);

        if (! host) {
            return;
        }

        host.__vbIconPainted = false;
        applyIconToComponent(host, editor, {
            name: host.getAttributes?.()?.['data-vb-icon'] || DEFAULT_TABLER_ICON,
            sizeClass: host.getAttributes?.()?.['data-vb-icon-size'] || 'size-10',
            style: host.getAttributes?.()?.['data-vb-icon-style'] || 'outline',
            stroke: host.getAttributes?.()?.['data-vb-icon-stroke'] || '1.75',
            color: readIconColor(host),
            href: host.get('href'),
            linkType: host.get('linkType') || host.getAttributes?.()?.['data-vb-link-type'] || 'none',
            linkRef: host.get('linkRef') || host.getAttributes?.()?.['data-vb-link'] || '',
            target: host.get('target') || '',
        });
    });
}

/**
 * Re-apply icon colors before getHtml so a cold gray canvas cannot be saved.
 *
 * @param {object} editor
 */
export function ensureIconsForExport(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper) {
        return;
    }

    wrapper.onAll?.((component) => {
        if (! isIconComponent(component)) {
            return;
        }

        component.__vbIconPainted = false;
        applyIconToComponent(component, editor, {
            name: component.getAttributes?.()?.['data-vb-icon'] || DEFAULT_TABLER_ICON,
            sizeClass: component.getAttributes?.()?.['data-vb-icon-size'] || 'size-10',
            style: component.getAttributes?.()?.['data-vb-icon-style'] || 'outline',
            stroke: component.getAttributes?.()?.['data-vb-icon-stroke'] || '1.75',
            color: readIconColor(component),
            href: component.get('href'),
            linkType: component.get('linkType') || component.getAttributes?.()?.['data-vb-link-type'] || 'none',
            linkRef: component.get('linkRef') || component.getAttributes?.()?.['data-vb-link'] || '',
            target: component.get('target') || '',
        });
    });
}
