/**
 * Component library media helpers.
 *
 * Strips embeddable video markup from component HTML so library previews and
 * persisted templates do not autoplay third-party players in the sidebar.
 */

const VIDEO_EMBED_HOST_PATTERN = /(?:youtube(?:-nocookie)?\.com|youtu\.be|vimeo\.com|player\.vimeo\.com)/i;

const MEDIA_SLOT_CLASS = 'voodbuilder-component-library-media-slot';

function isVideoEmbedSrc(src) {
    const value = String(src ?? '').trim();

    return value !== '' && VIDEO_EMBED_HOST_PATTERN.test(value);
}

function createMediaSlot(doc) {
    const slot = doc.createElement('div');
    slot.className = MEDIA_SLOT_CLASS;
    slot.setAttribute('aria-hidden', 'true');

    return slot;
}

function replaceNodeWithMediaSlot(node) {
    const doc = node.ownerDocument;
    const slot = createMediaSlot(doc);

    node.parentNode?.replaceChild(slot, node);
}

/**
 * Remove playable video markup from component library HTML (preview + persisted templates).
 */
export function stripEmbeddableMediaFromHtml(html) {
    const source = String(html ?? '').trim();

    if (source === '' || ! /<(?:video|iframe)\b|data-gjs-type=(["'])video\1/i.test(source)) {
        return source;
    }

    const doc = new DOMParser().parseFromString(`<body>${source}</body>`, 'text/html');
    const nodesToReplace = new Set();

    doc.body.querySelectorAll('video').forEach((node) => {
        nodesToReplace.add(node);
    });

    doc.body.querySelectorAll('iframe').forEach((node) => {
        if (isVideoEmbedSrc(node.getAttribute('src'))) {
            nodesToReplace.add(node);
        }
    });

    doc.body.querySelectorAll('[data-gjs-type="video"]').forEach((node) => {
        nodesToReplace.add(node);
    });

    nodesToReplace.forEach((node) => {
        replaceNodeWithMediaSlot(node);
    });

    return doc.body.innerHTML.trim();
}
