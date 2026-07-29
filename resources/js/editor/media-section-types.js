/**
 * Editor component types for vb-bg-image and vb-bg-video media hero sections.
 * Traits on the section sync data-* attributes to child img/video elements.
 */

import { safeFindComponents } from './tailwind-visual-style.js';

const BG_SIZE_OPTIONS = [
    { id: 'cover', label: 'Cover' },
    { id: 'contain', label: 'Contain' },
    { id: 'fill', label: 'Fill' },
];

const BG_POSITION_OPTIONS = [
    { id: 'center', label: 'Center' },
    { id: 'top', label: 'Top' },
    { id: 'bottom', label: 'Bottom' },
];

const MIN_HEIGHT_OPTIONS = [
    { id: '50vh', label: '50vh' },
    { id: '70vh', label: '70vh' },
    { id: '100vh', label: '100vh' },
];

const ON_OFF_OPTIONS = [
    { id: '1', label: 'Yes' },
    { id: '0', label: 'No' },
];

/**
 * @param {import('grapesjs').Component} section
 * @returns {import('grapesjs').Component | undefined}
 */
function findHeroMediaImage(section) {
    return safeFindComponents(section, '[data-voodbuilder-role="media"] img, .voodbuilder-hero-media__img')[0];
}

/**
 * @param {import('grapesjs').Component} section
 * @returns {import('grapesjs').Component | undefined}
 */
function findHeroMediaVideo(section) {
    return safeFindComponents(section, '[data-voodbuilder-role="media"] video, .voodbuilder-hero-media__video')[0];
}

/**
 * @param {import('grapesjs').Component} section
 * @returns {import('grapesjs').Component | undefined}
 */
function findHeroContentContainer(section) {
    return safeFindComponents(section, '[data-voodbuilder-dropzone="content"], [data-voodbuilder-role="content"]')[0];
}

/**
 * @param {string | undefined} position
 * @returns {string}
 */
function objectPositionValue(position) {
    switch (position) {
        case 'top':
            return 'center top';
        case 'bottom':
            return 'center bottom';
        default:
            return 'center';
    }
}

/**
 * @param {import('grapesjs').Component} section
 */
function syncBackgroundImageSection(section) {
    const attrs = section.getAttributes?.() ?? {};
    const image = findHeroMediaImage(section);
    const minHeight = String(attrs['data-vb-min-height'] ?? '70vh');
    const opacity = String(attrs['data-vb-bg-opacity'] ?? '0.55');
    const objectFit = String(attrs['data-vb-bg-size'] ?? 'cover');
    const objectPosition = objectPositionValue(String(attrs['data-vb-bg-position'] ?? 'center'));

    section.addStyle({ 'min-height': minHeight });

    const content = findHeroContentContainer(section);

    if (content) {
        content.addStyle({ 'min-height': minHeight });
    }

    if (image) {
        const imageStyles = {
            position: 'absolute',
            inset: '0',
            display: 'block',
            width: '100%',
            height: '100%',
            'max-width': 'none',
            opacity,
            'object-fit': objectFit,
            'object-position': objectPosition,
            '--vb-object-fit': objectFit,
            '--vb-object-position': objectPosition,
        };

        image.addStyle(imageStyles);

        const bgSrc = String(attrs['data-vb-bg-src'] ?? '').trim();

        if (bgSrc !== '') {
            image.addAttributes({ src: bgSrc });
        }
    }
}

/**
 * @param {import('grapesjs').Component} section
 */
function syncBackgroundVideoSection(section) {
    const attrs = section.getAttributes?.() ?? {};
    const video = findHeroMediaVideo(section);
    const minHeight = String(attrs['data-vb-min-height'] ?? '70vh');

    section.addStyle({ 'min-height': minHeight });

    const content = findHeroContentContainer(section);

    if (content) {
        content.addStyle({ 'min-height': minHeight });
    }

    if (! video) {
        return;
    }

    const videoSrc = String(attrs['data-vb-video-src'] ?? '').trim();
    const poster = String(attrs['data-vb-video-poster'] ?? '').trim();
    const controls = attrs['data-vb-controls'] === '1';
    const muted = attrs['data-vb-muted'] !== '0';
    const autoplay = attrs['data-vb-autoplay'] !== '0';
    const loop = attrs['data-vb-loop'] !== '0';

    const videoAttrs = {
        playsinline: '',
        ...(videoSrc !== '' ? { src: videoSrc } : {}),
        ...(poster !== '' ? { poster } : {}),
        ...(controls ? { controls: '' } : {}),
        ...(muted ? { muted: '' } : {}),
        ...(autoplay ? { autoplay: '' } : {}),
        ...(loop ? { loop: '' } : {}),
    };

    video.addAttributes(videoAttrs);

    if (! controls) {
        video.removeAttributes('controls');
    }

    if (! muted) {
        video.removeAttributes('muted');
    }

    if (! autoplay) {
        video.removeAttributes('autoplay');
    }

    if (! loop) {
        video.removeAttributes('loop');
    }
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {string} typeId
 * @param {string} blockId
 * @param {string} name
 * @param {() => void} sync
 * @param {Array<Record<string, unknown>>} traits
 */
function registerMediaHeroType(editor, typeId, blockId, name, sync, traits) {
    if (editor.DomComponents.getType(typeId)) {
        return;
    }

    editor.DomComponents.addType(typeId, {
        isComponent: (element) => {
            if (element?.tagName !== 'SECTION') {
                return false;
            }

            return element.getAttribute?.('data-voodbuilder-section-block') === blockId;
        },
        extend: 'default',
        model: {
            defaults: {
                tagName: 'section',
                name,
                droppable: (srcComponent) => {
                    if (! srcComponent?.get) {
                        return false;
                    }

                    if (String(srcComponent.get('tagName') ?? '').toLowerCase() === 'section') {
                        return false;
                    }

                    return safeFindComponents(srcComponent, 'section[data-voodbuilder-section-block]').length === 0;
                },
                traits,
            },
            init() {
                const traitNames = traits
                    .map((trait) => trait.name)
                    .filter((traitName) => typeof traitName === 'string');

                traitNames.forEach((traitName) => {
                    this.on(`change:${traitName}`, () => sync(this));
                });

                this.on('change:attributes', () => sync(this));
                sync(this);
            },
        },
    });
}

function registerBackgroundImageType(editor) {
    registerMediaHeroType(
        editor,
        'vb-bg-image',
        'vb-bg-image',
        'Background image',
        syncBackgroundImageSection,
        [
            { type: 'text', name: 'data-vb-bg-src', label: 'Background image URL', changeProp: true },
            {
                type: 'select',
                name: 'data-vb-bg-size',
                label: 'Image fit',
                options: BG_SIZE_OPTIONS,
                changeProp: true,
            },
            {
                type: 'select',
                name: 'data-vb-bg-position',
                label: 'Image position',
                options: BG_POSITION_OPTIONS,
                changeProp: true,
            },
            {
                type: 'number',
                name: 'data-vb-bg-opacity',
                label: 'Image opacity',
                min: 0.3,
                max: 1,
                step: 0.05,
                changeProp: true,
            },
            {
                type: 'select',
                name: 'data-vb-min-height',
                label: 'Minimum height',
                options: MIN_HEIGHT_OPTIONS,
                changeProp: true,
            },
        ],
    );
}

function registerBackgroundVideoType(editor) {
    registerMediaHeroType(
        editor,
        'vb-bg-video',
        'vb-bg-video',
        'Background video',
        syncBackgroundVideoSection,
        [
            { type: 'text', name: 'data-vb-video-src', label: 'Video URL', changeProp: true },
            { type: 'text', name: 'data-vb-video-poster', label: 'Poster image URL', changeProp: true },
            {
                type: 'select',
                name: 'data-vb-controls',
                label: 'Show controls',
                options: ON_OFF_OPTIONS,
                changeProp: true,
            },
            {
                type: 'select',
                name: 'data-vb-muted',
                label: 'Muted',
                options: ON_OFF_OPTIONS,
                changeProp: true,
            },
            {
                type: 'select',
                name: 'data-vb-autoplay',
                label: 'Autoplay',
                options: ON_OFF_OPTIONS,
                changeProp: true,
            },
            {
                type: 'select',
                name: 'data-vb-loop',
                label: 'Loop',
                options: ON_OFF_OPTIONS,
                changeProp: true,
            },
            {
                type: 'select',
                name: 'data-vb-min-height',
                label: 'Minimum height',
                options: MIN_HEIGHT_OPTIONS,
                changeProp: true,
            },
        ],
    );
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function registerMediaSectionTypes(editor) {
    registerBackgroundImageType(editor);
    registerBackgroundVideoType(editor);
}
