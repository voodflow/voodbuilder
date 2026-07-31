/**
 * GrapesJS AssetManager helpers: video type, modal copy, shared media library.
 *
 * GrapesJS ships only an `image` asset type; opening with types:['video'] shows an
 * empty gallery even after a successful MP4 upload. Register `video` first so
 * recognizeType() classifies .mp4 URLs correctly, and seed uploads from disk.
 */

const VIDEO_EXT_RE = /\.(mp4|webm|ogg|ogv|mov|m4v)(?:\?|#|$)/i;

/**
 * @param {string | null | undefined} src
 * @returns {boolean}
 */
export function isVideoAssetSrc(src) {
    return VIDEO_EXT_RE.test(String(src ?? '').trim());
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function registerVideoAssetType(editor) {
    const am = editor?.AssetManager ?? editor?.Assets;

    if (! am || typeof am.addType !== 'function' || am.getType?.('video')) {
        return;
    }

    am.addType('video', {
        isType(value) {
            if (typeof value === 'string') {
                return isVideoAssetSrc(value)
                    ? { type: 'video', src: value }
                    : null;
            }

            if (value && typeof value === 'object') {
                const src = String(value.src ?? value.getSrc?.() ?? '').trim();

                if (value.type === 'video' || isVideoAssetSrc(src)) {
                    return {
                        type: 'video',
                        src: src || value.src,
                        name: value.name,
                    };
                }
            }

            return null;
        },
        view: {
            getPreview() {
                const src = String(this.model?.get?.('src') ?? '').trim();
                const name = String(this.model?.get?.('name') ?? src.split('/').pop() ?? 'Video');
                const safeSrc = src.replace(/"/g, '&quot;');
                const safeName = name.replace(/</g, '&lt;').replace(/>/g, '&gt;');

                return `<div class="voodbuilder-am-video-preview" title="${safeName}">
                  <video src="${safeSrc}" muted playsinline preload="metadata"></video>
                  <span class="voodbuilder-am-video-preview__label">${safeName}</span>
                </div>`;
            },
        },
    });
}

/**
 * @param {'image'|'video'} kind
 * @param {Record<string, string>} labels
 * @returns {{ modalTitle: string, addButton: string, inputPlh: string, uploadTitle: string }}
 */
export function resolveAssetManagerCopy(kind, labels = {}) {
    const video = kind === 'video';

    return {
        modalTitle: video
            ? (labels.assetManagerVideoTitle ?? labels.videoSettingsTitle ?? 'Select video')
            : (labels.assetManagerImageTitle ?? 'Select image'),
        addButton: video
            ? (labels.assetManagerVideoAdd ?? 'Add video')
            : (labels.assetManagerImageAdd ?? 'Add image'),
        inputPlh: video
            ? (labels.assetManagerVideoInput ?? 'https://…/video.mp4')
            : (labels.assetManagerImageInput ?? 'https://…/image.jpg'),
        uploadTitle: video
            ? (labels.assetManagerVideoUpload ?? 'Drop a video here or click to upload')
            : (labels.assetManagerImageUpload ?? 'Drop files here or click to upload'),
    };
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {'image'|'video'|'media'} kind
 * @param {Record<string, string>} labels
 * @returns {() => void} restore previous messages
 */
export function applyAssetManagerLabels(editor, kind, labels = {}) {
    const i18n = editor?.I18n;

    if (! i18n || typeof i18n.addMessages !== 'function') {
        return () => {};
    }

    const locale = i18n.getLocale?.() || 'en';
    const previous = i18n.getMessages?.(locale)?.assetManager
        ? { ...i18n.getMessages(locale).assetManager }
        : null;

    const copy = resolveAssetManagerCopy(kind === 'video' ? 'video' : 'image', labels);
    const messages = {
        assetManager: {
            modalTitle: copy.modalTitle,
            addButton: copy.addButton,
            inputPlh: copy.inputPlh,
            uploadTitle: copy.uploadTitle,
        },
    };

    i18n.addMessages({ [locale]: messages });

    return () => {
        if (! previous) {
            return;
        }

        i18n.addMessages({ [locale]: { assetManager: previous } });
    };
}

/**
 * Grapes may keep a stale FileUploader/add-form from a previous video open.
 * Patch the live modal chrome so poster/cover pickers always show image copy.
 *
 * @param {{ modalTitle: string, addButton: string, inputPlh: string, uploadTitle: string }} copy
 */
export function syncAssetManagerChrome(copy) {
    const modal = document.querySelector('.gjs-mdl-dialog')
        ?? document.querySelector('.gjs-mdl-container')
        ?? document;

    const title = modal.querySelector?.('.gjs-mdl-title');

    if (title) {
        title.textContent = copy.modalTitle;
    }

    const uploadTitle = modal.querySelector?.('.gjs-am-file-uploader [id$="title"]')
        ?? modal.querySelector?.('.gjs-am-file-uploader div[id$="title"]')
        ?? document.getElementById('gjs-am-title');

    if (uploadTitle) {
        uploadTitle.textContent = copy.uploadTitle;
    }

    const addButton = modal.querySelector?.('.gjs-am-add-asset button, .gjs-am-add-asset .gjs-btn-prim');

    if (addButton) {
        addButton.textContent = copy.addButton;
    }

    const input = modal.querySelector?.('.gjs-am-add-asset input[type="text"], .gjs-am-add-asset input:not([type="file"])');

    if (input) {
        input.setAttribute('placeholder', copy.inputPlh);
        input.placeholder = copy.inputPlh;
    }
}

/**
 * Merge remote/library assets into the AssetManager (dedupe by src).
 *
 * @param {import('grapesjs').Editor} editor
 * @param {Array<{ src: string, type?: string, name?: string }>} assets
 */
export function seedAssetManager(editor, assets) {
    const am = editor?.AssetManager ?? editor?.Assets;

    if (! am || typeof am.add !== 'function' || ! Array.isArray(assets) || assets.length === 0) {
        return;
    }

    const existing = new Set(
        (am.getAll?.()?.models ?? am.getAll?.() ?? [])
            .map((asset) => {
                if (typeof asset?.getSrc === 'function') {
                    return asset.getSrc();
                }

                return asset?.get?.('src') ?? asset?.src ?? '';
            })
            .filter(Boolean),
    );

    for (const item of assets) {
        const src = String(item?.src ?? '').trim();

        if (src === '' || existing.has(src)) {
            continue;
        }

        const type = item.type === 'video' || isVideoAssetSrc(src) ? 'video' : 'image';
        am.add({
            src,
            type,
            name: item.name || src.split('/').pop() || src,
        });
        existing.add(src);
    }
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {string | null | undefined} mediaLibraryUrl
 */
export async function loadMediaLibrary(editor, mediaLibraryUrl) {
    const url = String(mediaLibraryUrl ?? '').trim();

    if (url === '' || typeof fetch !== 'function') {
        return;
    }

    try {
        const response = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (! response.ok) {
            return;
        }

        const payload = await response.json();
        const assets = Array.isArray(payload?.data) ? payload.data : [];
        seedAssetManager(editor, assets);
    } catch {
        // Library is optional; Choose still works via upload.
    }
}

/**
 * Open AssetManager for image or video with correct filters and labels.
 *
 * @param {{
 *   editor: object,
 *   kinds?: Array<'image'|'video'>,
 *   labelKind?: 'image'|'video',
 *   labels?: Record<string, string>,
 *   onSelect: (src: string) => void,
 *   onClose?: () => void,
 * }} args
 */
export function openMediaAssets({
    editor,
    kinds = ['image'],
    labelKind = null,
    labels = {},
    onSelect,
    onClose,
}) {
    const assets = editor?.Assets ?? editor?.AssetManager;

    if (! assets || typeof assets.open !== 'function') {
        return;
    }

    registerVideoAssetType(editor);

    const wantsVideo = kinds.includes('video');
    const wantsImage = kinds.includes('image') || ! wantsVideo;
    const openTypes = [];

    if (wantsVideo) {
        openTypes.push('video');
    }

    if (wantsImage) {
        openTypes.push('image');
    }

    // Poster / cover must stay image copy even if the parent settings panel is video.
    const resolvedLabelKind = labelKind === 'video' || labelKind === 'image'
        ? labelKind
        : (wantsVideo && ! wantsImage ? 'video' : 'image');
    const copy = resolveAssetManagerCopy(resolvedLabelKind, labels);

    const accept = wantsVideo && ! wantsImage
        ? 'video/mp4,video/webm,video/ogg,video/quicktime,.mp4,.webm,.ogg,.mov,.m4v'
        : (wantsVideo
            ? 'image/*,video/mp4,video/webm,video/ogg,video/quicktime,.mp4,.webm,.ogg,.mov,.m4v'
            : 'image/*');

    const amConfig = assets.getConfig?.() ?? null;
    const previousAccept = amConfig && typeof amConfig.accept === 'string'
        ? amConfig.accept
        : null;

    if (amConfig && accept) {
        amConfig.accept = accept;
    }

    const restoreLabels = applyAssetManagerLabels(editor, resolvedLabelKind, labels);

    let cleaned = false;

    const cleanup = () => {
        if (cleaned) {
            return;
        }

        cleaned = true;
        restoreLabels();

        if (amConfig) {
            if (previousAccept != null) {
                amConfig.accept = previousAccept;
            } else {
                delete amConfig.accept;
            }
        }

        onClose?.();
    };

    assets.open({
        types: openTypes,
        accept,
        modalTitle: copy.modalTitle,
        select(asset, complete) {
            const src = typeof asset?.getSrc === 'function'
                ? asset.getSrc()
                : (asset?.get?.('src') ?? asset?.src ?? '');

            const trimmed = String(src ?? '').trim();

            if (trimmed === '') {
                return;
            }

            if (wantsVideo && ! wantsImage && ! isVideoAssetSrc(trimmed)) {
                return;
            }

            if (wantsImage && ! wantsVideo && isVideoAssetSrc(trimmed)) {
                return;
            }

            onSelect(trimmed);

            if (typeof assets.close === 'function') {
                assets.close();
            } else if (complete) {
                // default UI closes on double-click
            }

            cleanup();
        },
    });

    const syncChrome = () => syncAssetManagerChrome(copy);

    syncChrome();
    window.requestAnimationFrame(syncChrome);
    window.setTimeout(syncChrome, 0);

    editor?.once?.('modal:close', cleanup);
}
