/**
 * Editor video behaviour — no autoplay in canvas, poster facade when autoplay is off.
 */

import {
    buildVideoFacadeMarkup,
    createVideoFacadeElement,
    extendVideoTraits,
    getActivationEmbedSrc,
    shouldUseVideoFacade,
} from './video-poster.js';
import { initVideoFacades } from './video-runtime.js';

const EMBED_HOST_PATTERN = /(?:youtube(?:-nocookie)?\.com|youtu\.be|player\.vimeo\.com)/i;

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

function syncVideoModelSrc(component, VideoModel) {
    if (! component?.is?.('video') || ! VideoModel?.prototype?.updateSrc) {
        return;
    }

    VideoModel.prototype.updateSrc.call(component);
}

export function syncVideoComponentsForExport(editor) {
    const VideoModel = editor.DomComponents.getType('video')?.model;

    editor.getWrapper()?.findType?.('video')?.forEach?.((component) => {
        syncVideoModelSrc(component, VideoModel);
    });
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

    if (! VideoModel || ! VideoView) {
        return;
    }

    domc.addType('video', {
        extend: 'video',
        model: {
            init() {
                if (typeof VideoModel.prototype.init === 'function') {
                    VideoModel.prototype.init.apply(this, arguments);
                }

                this.on(
                    'change:autoplay change:loop change:controls change:muted change:rel change:modestbranding change:color change:list change:poster',
                    () => {
                        if (['yt', 'ytnc', 'vi'].includes(String(this.get('provider') ?? ''))) {
                            this.updateSrc();
                        }
                    },
                );
            },
            getYoutubeTraits() {
                return extendVideoTraits(VideoModel.prototype.getYoutubeTraits.call(this));
            },
            getVimeoTraits() {
                return extendVideoTraits(VideoModel.prototype.getVimeoTraits.call(this));
            },
            toHTML(opts) {
                syncVideoModelSrc(this, VideoModel);

                if (shouldUseVideoFacade(this)) {
                    return buildVideoFacadeMarkup(this, getActivationEmbedSrc(this, VideoModel));
                }

                return VideoModel.prototype.toHTML.call(this, opts);
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
            },
            updateVideo(...args) {
                VideoView.prototype.updateVideo.apply(this, args);

                if (shouldUseVideoFacade(this.model)) {
                    renderFacadeView(this, VideoModel);

                    return;
                }

                syncVideoModelSrc(this.model, VideoModel);
                applyEditorPreviewSrc(this.videoEl, this.model);
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
