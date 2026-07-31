/**
 * Editor component types for vb-bg-image and vb-bg-video media hero sections.
 * Traits on the section sync data-* attributes to child img/video/embed elements.
 */

import {
    buildHeroEmbedSrc,
    isHeroEmbedProvider,
    normalizeHeroVideoId,
    normalizeHeroVideoProvider,
} from './hero-video-embed.js';
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

const PROVIDER_OPTIONS = [
    { id: 'file', label: 'File' },
    { id: 'yt', label: 'YouTube' },
    { id: 'ytnc', label: 'YouTube (nocookie)' },
    { id: 'vi', label: 'Vimeo' },
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
function findHeroMediaHost(section) {
    return safeFindComponents(section, '[data-voodbuilder-role="media"], .voodbuilder-hero-media')[0];
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
function findHeroMediaEmbed(section) {
    return safeFindComponents(section, '[data-vb-embed-bg], .voodbuilder-hero-media__embed')[0];
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
 * @returns {import('grapesjs').Component | undefined}
 */
function ensureHeroMediaVideo(section) {
    const existing = findHeroMediaVideo(section);

    if (existing) {
        return existing;
    }

    const host = findHeroMediaHost(section);

    if (! host?.append) {
        return undefined;
    }

    host.append({
        tagName: 'video',
        type: 'default',
        classes: ['voodbuilder-hero-media__video'],
        attributes: {
            playsinline: '',
            muted: '',
            autoplay: '',
            loop: '',
        },
    }, { at: 0 });

    return findHeroMediaVideo(section);
}

/**
 * @param {import('grapesjs').Component} section
 * @returns {import('grapesjs').Component | undefined}
 */
function ensureHeroMediaEmbed(section) {
    const existing = findHeroMediaEmbed(section);

    if (existing) {
        return existing;
    }

    const host = findHeroMediaHost(section);

    if (! host?.append) {
        return undefined;
    }

    host.append({
        tagName: 'div',
        type: 'default',
        classes: ['voodbuilder-hero-media__embed'],
        attributes: {
            'data-vb-embed-bg': '',
            'aria-hidden': 'true',
        },
        components: [{
            tagName: 'iframe',
            type: 'default',
            classes: ['voodbuilder-hero-media__iframe'],
            attributes: {
                title: 'Background video',
                frameborder: '0',
                allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
                allowfullscreen: 'allowfullscreen',
                loading: 'eager',
                referrerpolicy: 'strict-origin-when-cross-origin',
            },
        }],
    }, { at: 0 });

    return findHeroMediaEmbed(section);
}

/**
 * @param {import('grapesjs').Component} section
 */
function removeHeroMediaEmbed(section) {
    const embed = findHeroMediaEmbed(section);

    if (embed?.remove) {
        embed.remove();
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
 * @param {Record<string, string>} attrs
 * @param {string} opacity
 * @param {string} objectFit
 * @param {string} objectPosition
 */
function syncNativeHeroVideo(section, attrs, opacity, objectFit, objectPosition) {
    removeHeroMediaEmbed(section);

    const video = ensureHeroMediaVideo(section);

    if (! video) {
        return;
    }

    const videoSrc = String(attrs['data-vb-video-src'] ?? '').trim();
    const poster = String(attrs['data-vb-video-poster'] ?? '').trim();
    const controls = attrs['data-vb-controls'] === '1';
    const muted = attrs['data-vb-muted'] !== '0';
    const autoplay = attrs['data-vb-autoplay'] !== '0';
    const loop = attrs['data-vb-loop'] !== '0';

    video.addStyle({
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
    });

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

    if (videoSrc === '') {
        video.removeAttributes('src');
    }

    if (poster === '') {
        video.removeAttributes('poster');
    }

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
 * @param {import('grapesjs').Component} section
 * @param {Record<string, string>} attrs
 * @param {'yt'|'ytnc'|'vi'} provider
 * @param {string} opacity
 * @param {string} objectFit
 * @param {string} objectPosition
 */
function syncEmbedHeroVideo(section, attrs, provider, opacity, objectFit, objectPosition) {
    const video = findHeroMediaVideo(section);

    if (video) {
        video.addStyle({ display: 'none' });
        video.removeAttributes('src');
        video.removeAttributes('autoplay');
    }

    const embed = ensureHeroMediaEmbed(section);

    if (! embed) {
        return;
    }

    const videoId = normalizeHeroVideoId(String(attrs['data-vb-video-id'] ?? ''), provider);
    const muted = attrs['data-vb-muted'] !== '0';
    const autoplay = attrs['data-vb-autoplay'] !== '0';
    const loop = attrs['data-vb-loop'] !== '0';
    const embedSrc = buildHeroEmbedSrc(provider, videoId, { autoplay, loop, muted });

    embed.addAttributes({
        'data-vb-embed-bg': '',
        'data-vb-embed-fit': objectFit,
        'data-vb-embed-position': String(attrs['data-vb-bg-position'] ?? 'center'),
        'aria-hidden': 'true',
    });

    embed.addStyle({
        position: 'absolute',
        inset: '0',
        display: 'block',
        overflow: 'hidden',
        opacity,
        'pointer-events': 'none',
        '--vb-object-fit': objectFit,
        '--vb-object-position': objectPosition,
    });

    const iframe = safeFindComponents(embed, 'iframe, .voodbuilder-hero-media__iframe')[0]
        ?? safeFindComponents(section, '.voodbuilder-hero-media__iframe')[0];

    if (! iframe) {
        return;
    }

    if (embedSrc !== '') {
        iframe.addAttributes({ src: embedSrc });
    } else {
        iframe.removeAttributes('src');
    }

    // Cover layout must not inherit inline-video host utilities (letterbox + chrome flash).
    for (const className of ['w-full', 'aspect-video', 'rounded', 'vb-video-host']) {
        if (iframe.getClasses?.().includes(className)) {
            iframe.removeClass(className);
        }
    }

    if (! iframe.getClasses?.().includes('voodbuilder-hero-media__iframe')) {
        iframe.addClass('voodbuilder-hero-media__iframe');
    }

    const style = { ...(iframe.getStyle?.() ?? {}) };
    delete style.width;
    delete style.height;
    delete style['max-width'];
    delete style['max-height'];
    delete style['aspect-ratio'];
    iframe.setStyle?.(style);

    iframe.addAttributes({
        title: 'Background video',
        frameborder: '0',
        allow: 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture',
        allowfullscreen: 'allowfullscreen',
        loading: 'eager',
        referrerpolicy: 'strict-origin-when-cross-origin',
    });
}

/**
 * @param {import('grapesjs').Component} section
 */
function syncBackgroundVideoSection(section) {
    const attrs = section.getAttributes?.() ?? {};
    const provider = normalizeHeroVideoProvider(attrs['data-vb-video-provider']);
    const minHeight = String(attrs['data-vb-min-height'] ?? '70vh');
    const opacity = String(attrs['data-vb-bg-opacity'] ?? '1');
    const objectFit = String(attrs['data-vb-bg-size'] ?? 'cover');
    const objectPosition = objectPositionValue(String(attrs['data-vb-bg-position'] ?? 'center'));

    section.addStyle({ 'min-height': minHeight });

    const content = findHeroContentContainer(section);

    if (content) {
        content.addStyle({ 'min-height': minHeight });
    }

    if (! findHeroMediaHost(section)) {
        return;
    }

    if (isHeroEmbedProvider(provider)) {
        syncEmbedHeroVideo(section, attrs, provider, opacity, objectFit, objectPosition);

        return;
    }

    syncNativeHeroVideo(section, attrs, opacity, objectFit, objectPosition);
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
        'Hero · background image',
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
        'Hero · background video',
        syncBackgroundVideoSection,
        [
            {
                type: 'select',
                name: 'data-vb-video-provider',
                label: 'Source',
                options: PROVIDER_OPTIONS,
                changeProp: true,
            },
            { type: 'text', name: 'data-vb-video-id', label: 'YouTube / Vimeo ID', changeProp: true },
            { type: 'text', name: 'data-vb-video-src', label: 'Video URL', changeProp: true },
            { type: 'text', name: 'data-vb-video-poster', label: 'Poster image URL', changeProp: true },
            {
                type: 'select',
                name: 'data-vb-bg-size',
                label: 'Video fit',
                options: BG_SIZE_OPTIONS,
                changeProp: true,
            },
            {
                type: 'select',
                name: 'data-vb-bg-position',
                label: 'Video position',
                options: BG_POSITION_OPTIONS,
                changeProp: true,
            },
            {
                type: 'number',
                name: 'data-vb-bg-opacity',
                label: 'Video opacity',
                min: 0.3,
                max: 1,
                step: 0.05,
                changeProp: true,
            },
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
