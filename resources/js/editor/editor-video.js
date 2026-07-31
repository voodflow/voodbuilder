/**
 * Editor video behaviour — no autoplay in canvas, poster facade when autoplay is off.
 */

import {
    buildVideoFacadeMarkup,
    createVideoFacadeElement,
    extendVideoTraits,
    getActivationEmbedSrc,
    hardenEmbedSrcControls,
    isTruthyProp,
    shouldUseVideoFacade,
} from './video-poster.js';
import { initVideoFacades } from './video-runtime.js';

const EMBED_HOST_PATTERN = /(?:youtube(?:-nocookie)?\.com|youtu\.be|player\.vimeo\.com)/i;

/**
 * Grapes exports YouTube/Vimeo as a bare <iframe>. The canvas uses a host
 * wrapper (.vb-video-host) with an absolutely positioned child — mirror that
 * on publish so front layout does not depend on data-gjs-type (stripped).
 *
 * @param {object} component
 * @param {string} html
 * @returns {string}
 */
export function wrapPublishedEmbedVideoHtml(component, html) {
    const markup = String(html ?? '').trim();

    if (markup === '' || ! /^<iframe\b/i.test(markup)) {
        return markup;
    }

    const classes = (component?.getClasses?.() ?? [])
        .filter((name) => typeof name === 'string' && name.trim() !== '')
        .join(' ');
    const hostClasses = ['vb-video-host', 'w-full', 'aspect-video', 'rounded']
        .concat(classes.split(/\s+/).filter(Boolean))
        .filter((name, index, all) => all.indexOf(name) === index)
        .join(' ');

    const id = String(component?.getId?.() ?? component?.getAttributes?.()?.id ?? '').trim();
    const style = component?.getStyle?.() ?? {};
    const styleParts = [];

    for (const [key, value] of Object.entries(style)) {
        if (value === undefined || value === null || String(value).trim() === '') {
            continue;
        }

        const prop = String(key).replace(/[A-Z]/g, (m) => `-${m.toLowerCase()}`);
        styleParts.push(`${prop}:${String(value).trim()}`);
    }

    // Prefer width/max-width/height from model so the host keeps the aspect box.
    if (! styleParts.some((part) => part.startsWith('width:'))) {
        styleParts.push('width:100%');
    }

    if (! styleParts.some((part) => part.startsWith('max-width:'))) {
        styleParts.push('max-width:100%');
    }

    if (! styleParts.some((part) => part.startsWith('height:'))) {
        styleParts.push('height:auto');
    }

    const idAttr = id !== '' ? ` id="${id.replace(/"/g, '&quot;')}"` : '';
    const styleAttr = styleParts.length > 0
        ? ` style="${styleParts.join(';').replace(/"/g, '&quot;')}"`
        : '';

    // Drop host sizing classes / id / style from the iframe — they belong on the wrapper.
    const iframe = markup
        .replace(/\s+id=(["'])[\s\S]*?\1/i, '')
        .replace(/\s+style=(["'])[\s\S]*?\1/i, '')
        .replace(
            /\s+class=(["'])([\s\S]*?)\1/i,
            (_, quote, classValue) => {
                const next = String(classValue)
                    .split(/\s+/)
                    .filter((name) => name && ! ['vb-video-host', 'w-full', 'aspect-video', 'rounded'].includes(name))
                    .join(' ');

                return next === '' ? '' : ` class=${quote}${next}${quote}`;
            },
        );

    return `<div class="${hostClasses}"${idAttr}${styleAttr}>${iframe}</div>`;
}

export function stripEmbedPlaybackParams(url) {
    const value = String(url ?? '').trim();

    if (value === '') {
        return value;
    }

    try {
        const parsed = new URL(value);

        for (const key of ['autoplay', 'mute', 'muted']) {
            parsed.searchParams.delete(key);
        }

        let next = parsed.toString();

        next = next
            .replace(/([?&])(autoplay|mute|muted)=1(?=&|$)/gi, '$1')
            .replace(/\?&/, '?')
            .replace(/&&+/g, '&')
            .replace(/[?&]$/, '');

        return next;
    } catch {
        return value
            .replace(/([?&])(autoplay|mute|muted)=1(?=&|$)/gi, '$1')
            .replace(/\?&/, '?')
            .replace(/&&+/g, '&')
            .replace(/[?&]$/, '');
    }
}

function isEmbeddableIframe(el) {
    return el?.tagName === 'IFRAME' && EMBED_HOST_PATTERN.test(el.getAttribute('src') ?? '');
}

export function applyEditorPreviewSrc(el, model) {
    if (! el || el.tagName !== 'IFRAME') {
        return;
    }

    const src = String(model.get('src') ?? '').trim();

    if (src === '') {
        return;
    }

    const previewSrc = stripEmbedPlaybackParams(src);

    if (el.getAttribute('src') !== previewSrc) {
        el.setAttribute('src', previewSrc);
    }
}

/**
 * GrapesJS HTML5 provider ('so'). Embed providers use yt / ytnc / vi.
 *
 * @param {unknown} provider
 * @returns {boolean}
 */
export function isEmbedVideoProvider(provider) {
    return ['yt', 'ytnc', 'vi'].includes(String(provider ?? ''));
}

/**
 * Rebuild embed `src` from provider traits. Must NOT run for HTML5 file videos:
 * Grapes `updateSrc()` sets `src` to '' for provider `so`, wiping the file URL.
 *
 * @param {object} component
 * @param {object} VideoModel
 */
function syncVideoModelSrc(component, VideoModel) {
    if (! component?.is?.('video') || ! VideoModel?.prototype?.updateSrc) {
        return;
    }

    if (! isEmbedVideoProvider(component.get('provider'))) {
        return;
    }

    VideoModel.prototype.updateSrc.call(component);
}

/**
 * Browsers block unmuted autoplay. When autoplay is on for HTML5 videos,
 * force muted (+ playsinline for iOS).
 *
 * @param {object} component
 * @returns {boolean} true if muted was forced on
 */
export function ensureHtml5AutoplayMute(component) {
    if (! component?.is?.('video') || isEmbedVideoProvider(component.get?.('provider'))) {
        return false;
    }

    if (! isTruthyProp(component.get?.('autoplay'))) {
        return false;
    }

    let forced = false;

    if (! isTruthyProp(component.get?.('muted'))) {
        component.set('muted', true);
        forced = true;
    }

    component.addAttributes?.({
        muted: true,
        playsinline: true,
        'webkit-playsinline': true,
    });

    return forced;
}

/**
 * Keep native <video poster> in sync for file-URL videos (canvas + attribute).
 *
 * @param {object|null|undefined} view
 */
export function applyLocalVideoPoster(view) {
    const el = view?.videoEl;
    const model = view?.model;

    if (! el || el.tagName !== 'VIDEO' || ! model) {
        return;
    }

    if (isEmbedVideoProvider(model.get('provider'))) {
        return;
    }

    const poster = String(model.get('poster') ?? '').trim();
    const usable = poster !== '' && ! /^data:image\/svg\+xml/i.test(poster);

    if (usable) {
        if (el.getAttribute('poster') !== poster) {
            el.setAttribute('poster', poster);
        }

        el.poster = poster;
        model.addAttributes?.({ poster });

        // Native poster only paints before playback / after reload. If a frame was
        // already decoded (common after our previous src wipe+restore), reload.
        if (! model.get('autoplay')) {
            el.pause?.();

            try {
                el.load();
            } catch {
                // ignore
            }
        }
    } else {
        el.removeAttribute('poster');
        el.poster = '';
        model.removeAttributes?.('poster');
    }
}

export function syncVideoComponentsForExport(editor) {
    const VideoModel = editor.DomComponents.getType('video')?.model;

    editor.getWrapper()?.findType?.('video')?.forEach?.((component) => {
        syncVideoModelSrc(component, VideoModel);

        if (component.get?.('src')) {
            const next = hardenEmbedSrcControls(component.get('src'), component);
            component.set('src', next, { silent: true });
            component.addAttributes?.({ src: next });
        }

        if (! isEmbedVideoProvider(component.get?.('provider'))) {
            ensureHtml5AutoplayMute(component);

            const poster = String(component.get?.('poster') ?? '').trim();

            if (poster !== '' && ! /^data:image\/svg\+xml/i.test(poster)) {
                component.addAttributes?.({ poster });
            } else {
                component.removeAttributes?.('poster');
            }
        }
    });
}

/**
 * GrapesJS serializes embed videos as <iframe> even when the canvas shows our
 * click-to-play facade. Rewrite matching iframes in exported HTML.
 *
 * @param {import('grapesjs').Editor} editor
 * @param {string} html
 * @returns {string}
 */
export function applyVideoFacadesToExportedHtml(editor, html) {
    const source = String(html ?? '');

    if (source === '' || ! /<(?:iframe|video)\b/i.test(source)) {
        return source;
    }

    const VideoModel = editor.DomComponents.getType('video')?.model;
    const videos = editor.getWrapper()?.findType?.('video') ?? [];

    if (! VideoModel || videos.length === 0) {
        return source;
    }

    let next = source;

    videos.forEach((component) => {
        if (! shouldUseVideoFacade(component)) {
            return;
        }

        const facade = buildVideoFacadeMarkup(
            component,
            hardenEmbedSrcControls(getActivationEmbedSrc(component, VideoModel), component),
        );

        const id = String(component.getId?.() ?? component.getAttributes?.()?.id ?? '').trim();
        const src = String(component.get('src') ?? component.getAttributes?.()?.src ?? '').trim();

        if (id !== '') {
            const byId = new RegExp(
                `<iframe\\b[^>]*\\bid=(["'])${escapeRegExp(id)}\\1[^>]*>\\s*(?:</iframe>)?`,
                'i',
            );

            if (byId.test(next)) {
                next = next.replace(byId, facade);

                return;
            }
        }

        if (src !== '') {
            const srcVariants = [src, src.replace(/&/g, '&amp;')];

            for (const variant of srcVariants) {
                const bySrc = new RegExp(
                    `<iframe\\b[^>]*\\bsrc=(["'])${escapeRegExp(variant)}\\1[^>]*>\\s*(?:</iframe>)?`,
                    'i',
                );

                if (bySrc.test(next)) {
                    next = next.replace(bySrc, facade);

                    return;
                }
            }
        }
    });

    return next;
}

/**
 * @param {string} value
 * @returns {string}
 */
function escapeRegExp(value) {
    return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Re-hydrate a published click-to-play facade into a GrapesJS video component.
 * Preserves id + inline style so CssComposer #id rules rematch after reload
 * (same contract as animated-counter).
 *
 * @param {HTMLElement} element
 * @returns {false | Record<string, unknown>}
 */
export function parseFacadeElementAsVideo(element) {
    if (! element?.getAttribute || element.getAttribute('data-vb-video-facade') == null) {
        return false;
    }

    const embedSrc = String(element.getAttribute('data-vb-embed-src') ?? '').trim();
    const poster = String(element.getAttribute('data-vb-poster') ?? '').trim();
    const id = String(element.getAttribute('id') ?? '').trim();
    const style = String(element.getAttribute('style') ?? '').trim();
    const classes = String(element.getAttribute('class') ?? '')
        .split(/\s+/)
        .filter((name) => name && name !== 'vb-video-facade' && name !== 'vb-video-facade--fill');

    let provider = 'yt';
    let videoId = '';

    try {
        const url = new URL(embedSrc);

        if (url.hostname.includes('vimeo.com')) {
            provider = 'vi';
            videoId = url.pathname.split('/').filter(Boolean).pop() ?? '';
        } else if (url.hostname.includes('youtube-nocookie.com')) {
            provider = 'ytnc';
            videoId = url.pathname.split('/').filter(Boolean).pop() ?? '';
        } else if (url.hostname.includes('youtube.com') || url.hostname.includes('youtu.be')) {
            provider = 'yt';
            videoId = url.pathname.split('/').filter(Boolean).pop() ?? '';
        }
    } catch {
        // keep defaults
    }

    /** @type {Record<string, string>} */
    const attributes = {};

    if (id !== '') {
        attributes.id = id;
    }

    if (style !== '') {
        attributes.style = style;
    }

    return {
        type: 'video',
        tagName: 'video',
        provider,
        videoId,
        src: embedSrc,
        poster,
        autoplay: false,
        controls: false,
        muted: true,
        loop: true,
        classes,
        ...(Object.keys(attributes).length > 0 ? { attributes } : {}),
    };
}

function sanitizeEditorVideoElement(el) {
    if (! el) {
        return;
    }

    if (el.tagName === 'VIDEO') {
        el.autoplay = false;
        el.removeAttribute('autoplay');
        el.pause?.();
    }
}

function sanitizeEditorPreviewSrc(el, model) {
    if (! el) {
        return;
    }

    if (el.tagName === 'VIDEO') {
        sanitizeEditorVideoElement(el);

        return;
    }

    if (isEmbeddableIframe(el)) {
        applyEditorPreviewSrc(el, model);
    }
}

function findVideoComponentByIframe(editor, iframe) {
    let match = null;

    editor?.getWrapper?.()?.onAll?.((component) => {
        if (match || ! component.is?.('video')) {
            return;
        }

        if (component.view?.videoEl === iframe) {
            match = component;
        }
    });

    return match;
}

function sanitizeEditorMediaRoot(root, editor) {
    if (! root?.querySelectorAll) {
        return;
    }

    root.querySelectorAll('video').forEach((video) => {
        sanitizeEditorVideoElement(video);
    });

    root.querySelectorAll('iframe').forEach((iframe) => {
        if (! isEmbeddableIframe(iframe)) {
            return;
        }

        const component = findVideoComponentByIframe(editor, iframe);

        if (component) {
            applyEditorPreviewSrc(iframe, component);

            return;
        }

        const current = iframe.getAttribute('src') ?? '';
        const next = stripEmbedPlaybackParams(current);

        if (next !== current) {
            iframe.setAttribute('src', next);
        }
    });
}

function sanitizeEditorComponentTree(component) {
    if (! component) {
        return;
    }

    if (component.is?.('video')) {
        if (shouldUseVideoFacade(component)) {
            return;
        }

        sanitizeEditorPreviewSrc(component.view?.videoEl, component);

        return;
    }

    sanitizeEditorMediaRoot(component.getEl?.(), component.em);
    component.components?.().forEach((child) => {
        sanitizeEditorComponentTree(child);
    });
}

function sanitizeEditorCanvas(editor) {
    const doc = editor.Canvas.getDocument();

    if (doc?.body) {
        sanitizeEditorMediaRoot(doc.body, editor);
        initVideoFacades(doc);
    }

    editor.getWrapper()?.forEachChild?.((child) => {
        sanitizeEditorComponentTree(child);
    });
}

function refreshVideoPreview(view, VideoModel) {
    if (! view?.model) {
        return;
    }

    syncVideoModelSrc(view.model, VideoModel);

    if (shouldUseVideoFacade(view.model)) {
        renderFacadeView(view, VideoModel);

        return;
    }

    if (view.videoEl) {
        applyEditorPreviewSrc(view.videoEl, view.model);
        applyLocalVideoPoster(view);
    }
}

function normalizeVideoComponent(component) {
    if (! component?.is?.('video')) {
        return;
    }

    for (const className of ['w-full', 'aspect-video', 'rounded', 'vb-video-host']) {
        if (! component.getClasses().includes(className)) {
            component.addClass(className);
        }
    }

    const style = { ...component.getStyle() };
    const height = String(style.height ?? '').trim();

    if (height !== '' && height !== 'auto' && ! height.endsWith('%')) {
        delete style.height;
    }

    style.width = style.width || '100%';
    style['max-width'] = style['max-width'] || '100%';
    style.height = 'auto';

    component.setStyle(style);
}

function ensureVideoHostLayout(view) {
    const host = view?.el;

    if (! host) {
        return;
    }

    host.classList.add('vb-video-host');

    if (view.videoEl) {
        view.videoEl.style.position = 'absolute';
        view.videoEl.style.inset = '0';
        view.videoEl.style.width = '100%';
        view.videoEl.style.height = '100%';
        view.videoEl.style.border = '0';
        view.videoEl.style.maxHeight = 'none';
    }

    if (view.facadeEl) {
        view.facadeEl.classList.add('vb-video-facade', 'vb-video-facade--fill');
    }
}

function renderFacadeView(view, VideoModel) {
    view.el.innerHTML = '';
    const facade = createVideoFacadeElement(
        view.model,
        getActivationEmbedSrc(view.model, VideoModel),
        view.el.ownerDocument,
    );

    if (! facade) {
        return null;
    }

    view.videoEl = null;
    view.facadeEl = facade;
    facade.classList.add('vb-video-facade--fill');
    view.el.appendChild(facade);
    ensureVideoHostLayout(view);
    initVideoFacades(view.el.ownerDocument);

    return facade;
}

function normalizeEditorVideos(editor) {
    const VideoModel = editor.DomComponents.getType('video')?.model;

    editor.getWrapper()?.findType?.('video')?.forEach?.((component) => {
        normalizeVideoComponent(component);
        syncVideoModelSrc(component, VideoModel);
        refreshVideoPreview(component.view, VideoModel);
    });
}

export function registerEditorVideoSafety(editor) {
    const domc = editor.DomComponents;
    const videoType = domc.getType('video');
    const VideoModel = videoType?.model;
    const VideoView = videoType?.view;
    const previousIsComponent = videoType?.isComponent
        ?? VideoModel?.isComponent
        ?? null;

    if (! VideoModel || ! VideoView) {
        return;
    }

    domc.addType('video', {
        extend: 'video',
        isComponent: (element) => {
            const facade = parseFacadeElementAsVideo(element);

            if (facade) {
                return facade;
            }

            if (typeof previousIsComponent === 'function') {
                return previousIsComponent(element);
            }

            return false;
        },
        model: {
            init() {
                if (typeof VideoModel.prototype.init === 'function') {
                    VideoModel.prototype.init.apply(this, arguments);
                }

                this.on('change:autoplay', () => {
                    ensureHtml5AutoplayMute(this);
                });

                this.on(
                    'change:autoplay change:loop change:controls change:muted change:rel change:modestbranding change:color change:list change:poster',
                    () => {
                        if (isEmbedVideoProvider(this.get('provider'))) {
                            this.updateSrc();
                        }
                    },
                );

                ensureHtml5AutoplayMute(this);
            },
            getYoutubeTraits() {
                return extendVideoTraits(VideoModel.prototype.getYoutubeTraits.call(this));
            },
            getVimeoTraits() {
                return extendVideoTraits(VideoModel.prototype.getVimeoTraits.call(this));
            },
            getYoutubeSrc() {
                return hardenEmbedSrcControls(
                    VideoModel.prototype.getYoutubeSrc.call(this),
                    this,
                );
            },
            getYoutubeNoCookieSrc() {
                return hardenEmbedSrcControls(
                    VideoModel.prototype.getYoutubeNoCookieSrc.call(this),
                    this,
                );
            },
            getVimeoSrc() {
                return hardenEmbedSrcControls(
                    VideoModel.prototype.getVimeoSrc.call(this),
                    this,
                );
            },
            getAttrToHTML() {
                const attrs = VideoModel.prototype.getAttrToHTML.call(this);

                if (isEmbedVideoProvider(this.get('provider'))) {
                    return attrs;
                }

                ensureHtml5AutoplayMute(this);

                const poster = String(this.get('poster') ?? '').trim();

                if (poster !== '' && ! /^data:image\/svg\+xml/i.test(poster)) {
                    attrs.poster = poster;
                } else {
                    delete attrs.poster;
                }

                // Boolean HTML attrs: omit when false (presence alone enables them).
                if (! this.get('autoplay')) {
                    delete attrs.autoplay;
                } else {
                    attrs.autoplay = true;
                    // Unmuted autoplay is blocked by browsers — always pair with mute.
                    attrs.muted = true;
                    attrs.playsinline = true;
                }

                if (! this.get('loop')) {
                    delete attrs.loop;
                }

                if (! this.get('muted') && ! this.get('autoplay')) {
                    delete attrs.muted;
                } else if (this.get('muted') || this.get('autoplay')) {
                    attrs.muted = true;
                }

                if (this.get('controls') === false) {
                    delete attrs.controls;
                } else {
                    attrs.controls = true;
                }

                return attrs;
            },
            toHTML(opts) {
                syncVideoModelSrc(this, VideoModel);

                if (shouldUseVideoFacade(this)) {
                    return buildVideoFacadeMarkup(
                        this,
                        hardenEmbedSrcControls(getActivationEmbedSrc(this, VideoModel), this),
                    );
                }

                const html = VideoModel.prototype.toHTML.call(this, opts);

                if (isEmbedVideoProvider(this.get('provider'))) {
                    return wrapPublishedEmbedVideoHtml(this, html);
                }

                return html;
            },
        },
        view: {
            renderByProvider(prov) {
                if (shouldUseVideoFacade(this.model)) {
                    return renderFacadeView(this, VideoModel);
                }

                const el = VideoView.prototype.renderByProvider.call(this, prov);
                ensureVideoHostLayout(this);
                applyEditorPreviewSrc(this.videoEl, this.model);
                applyLocalVideoPoster(this);

                return el;
            },
            updateProvider(...args) {
                VideoView.prototype.updateProvider.apply(this, args);
                refreshVideoPreview(this, VideoModel);
            },
            updateSrc(...args) {
                if (shouldUseVideoFacade(this.model)) {
                    renderFacadeView(this, VideoModel);

                    return;
                }

                VideoView.prototype.updateSrc.apply(this, args);
                applyEditorPreviewSrc(this.videoEl, this.model);
                applyLocalVideoPoster(this);
            },
            updateVideo(...args) {
                VideoView.prototype.updateVideo.apply(this, args);

                if (shouldUseVideoFacade(this.model)) {
                    renderFacadeView(this, VideoModel);

                    return;
                }

                syncVideoModelSrc(this.model, VideoModel);
                applyEditorPreviewSrc(this.videoEl, this.model);
                applyLocalVideoPoster(this);
            },
            render(...args) {
                const result = VideoView.prototype.render.apply(this, args);

                normalizeVideoComponent(this.model);
                ensureVideoHostLayout(this);
                refreshVideoPreview(this, VideoModel);

                return result;
            },
        },
    });

    const scheduleCanvasSanitize = () => {
        window.requestAnimationFrame(() => {
            sanitizeEditorCanvas(editor);
        });
    };

    editor.on('canvas:frame:load', scheduleCanvasSanitize);
    editor.on('load', () => {
        scheduleCanvasSanitize();
        normalizeEditorVideos(editor);
    });
    editor.on('component:add', (component) => {
        window.requestAnimationFrame(() => {
            if (component?.is?.('video')) {
                normalizeVideoComponent(component);
                ensureVideoHostLayout(component.view);
            }

            sanitizeEditorComponentTree(component);
        });
    });
}
