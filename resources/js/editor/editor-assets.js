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
 * @param {{ replace?: boolean }} [options]
 */
export function seedAssetManager(editor, assets, options = {}) {
    const am = editor?.AssetManager ?? editor?.Assets;

    if (! am || typeof am.add !== 'function') {
        return;
    }

    if (options.replace) {
        const all = am.getAll?.();

        if (all && typeof all.reset === 'function') {
            all.reset();
        } else if (all && typeof am.remove === 'function') {
            const models = [...(all.models ?? all ?? [])];

            for (const asset of models) {
                am.remove(asset);
            }
        }
    }

    if (! Array.isArray(assets) || assets.length === 0) {
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
 * @param {string | null | undefined} mediaLibraryUrl
 * @param {{ galleryId?: number|null, type?: 'image'|'video'|null }} [query]
 */
export function buildMediaLibraryUrl(mediaLibraryUrl, query = {}) {
    const url = String(mediaLibraryUrl ?? '').trim();

    if (url === '') {
        return '';
    }

    try {
        const parsed = new URL(url, window.location.origin);

        if (query.galleryId != null && Number.isFinite(Number(query.galleryId))) {
            parsed.searchParams.set('gallery_id', String(query.galleryId));
        } else {
            parsed.searchParams.delete('gallery_id');
        }

        if (query.type === 'image' || query.type === 'video') {
            parsed.searchParams.set('type', query.type);
        }

        return parsed.pathname + parsed.search;
    } catch {
        return url;
    }
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {string | null | undefined} mediaLibraryUrl
 * @param {{ galleryId?: number|null, type?: 'image'|'video'|null, replace?: boolean }} [options]
 */
export async function loadMediaLibrary(editor, mediaLibraryUrl, options = {}) {
    const url = buildMediaLibraryUrl(mediaLibraryUrl, options);

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
        seedAssetManager(editor, assets, { replace: Boolean(options.replace) });
    } catch {
        // Library is optional; Choose still works via upload.
    }
}

/**
 * @param {string | null | undefined} galleriesUrl
 * @param {'image'|'video'|null} [type]
 * @returns {Promise<{ galleries: Array<object>, defaultGalleryId: number|null, uploadGalleryId: number|null }>}
 */
export async function fetchMediaGalleries(galleriesUrl, type = null) {
    const base = String(galleriesUrl ?? '').trim();

    if (base === '' || typeof fetch !== 'function') {
        return { galleries: [], defaultGalleryId: null, uploadGalleryId: null };
    }

    try {
        const parsed = new URL(base, window.location.origin);

        if (type === 'image' || type === 'video') {
            parsed.searchParams.set('type', type);
        }

        const response = await fetch(parsed.pathname + parsed.search, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });

        if (! response.ok) {
            return { galleries: [], defaultGalleryId: null, uploadGalleryId: null };
        }

        const payload = await response.json();

        return {
            galleries: Array.isArray(payload?.data) ? payload.data : [],
            defaultGalleryId: payload?.default_gallery_id ?? null,
            uploadGalleryId: payload?.upload_gallery_id ?? null,
        };
    } catch {
        return { galleries: [], defaultGalleryId: null, uploadGalleryId: null };
    }
}

/**
 * Gallery chips above the Asset Manager grid (browse any gallery; upload stays on default).
 *
 * @param {{
 *   galleries: Array<{ id: number, name: string, is_default?: boolean, media_count?: number }>,
 *   activeGalleryId: number|null,
 *   uploadGalleryId: number|null,
 *   onSelect: (galleryId: number|null) => void,
 * }} args
 * @returns {() => void} cleanup
 */
export function mountGalleryBrowser({
    galleries,
    activeGalleryId,
    uploadGalleryId,
    onSelect,
}) {
    const previous = document.querySelector('.voodbuilder-am-galleries');

    if (previous) {
        previous.remove();
    }

    if (! Array.isArray(galleries) || galleries.length === 0) {
        return () => {};
    }

    const modal = document.querySelector('.gjs-mdl-dialog')
        ?? document.querySelector('.gjs-mdl-container');
    const assetsContainer = modal?.querySelector?.('.gjs-am-assets-cont')
        ?? modal?.querySelector?.('.gjs-am-assets')
        ?? modal?.querySelector?.('.gjs-am-assets-header')
        ?? null;

    if (! assetsContainer?.parentElement) {
        return () => {};
    }

    const bar = document.createElement('div');
    bar.className = 'voodbuilder-am-galleries';
    bar.setAttribute('role', 'tablist');
    bar.setAttribute('aria-label', 'Galleries');

    const hint = document.createElement('p');
    hint.className = 'voodbuilder-am-galleries__hint';
    hint.textContent = uploadGalleryId
        ? 'Upload goes to the default gallery. Browse any gallery to choose.'
        : 'Browse galleries to choose media.';
    bar.appendChild(hint);

    const chips = document.createElement('div');
    chips.className = 'voodbuilder-am-galleries__chips';

    const makeChip = (id, label, count = null) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'voodbuilder-am-galleries__chip';
        button.setAttribute('role', 'tab');
        button.dataset.galleryId = id == null ? '' : String(id);

        const active = (id == null && activeGalleryId == null)
            || (id != null && Number(id) === Number(activeGalleryId));

        if (active) {
            button.classList.add('is-active');
            button.setAttribute('aria-selected', 'true');
        } else {
            button.setAttribute('aria-selected', 'false');
        }

        button.textContent = count != null ? `${label} (${count})` : label;
        button.addEventListener('click', () => onSelect(id));
        chips.appendChild(button);
    };

    makeChip(null, 'All');

    for (const gallery of galleries) {
        const name = gallery.is_default ? `${gallery.name} · default` : gallery.name;
        makeChip(gallery.id, name, gallery.media_count ?? null);
    }

    bar.appendChild(chips);
    assetsContainer.parentElement.insertBefore(bar, assetsContainer);

    return () => {
        bar.remove();
    };
}

/**
 * Open the media picker.
 * Uses the Media companion browser only when galleries API is available;
 * otherwise falls back to the GrapesJS Asset Manager.
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
export async function openMediaAssets(args) {
    const { editor, labels = {} } = args;
    const libraryUrl = editor?.__voodbuilderMediaLibraryUrl
        ?? labels.mediaLibraryUrl
        ?? null;
    const galleriesUrl = editor?.__voodbuilderMediaGalleriesUrl
        ?? labels.mediaGalleriesUrl
        ?? null;

    // Custom browser is owned by voodflow/vmedia (galleries + vault).
    if (shouldUseMediaCompanionBrowser(libraryUrl, galleriesUrl)) {
        try {
            const { openMediaBrowser } = await import('./media-browser.js');
            const opened = await openMediaBrowser(args);

            if (opened) {
                return;
            }
        } catch (error) {
            console.error('Voodbuilder Editor: vmedia browser failed; falling back to GrapesJS Asset Manager.', error);
        }
    } else if (String(libraryUrl ?? '').trim() !== '' && String(galleriesUrl ?? '').trim() === '') {
        console.warn(
            'Voodbuilder Editor: media library URL is set but galleries URL is missing. '
            + 'Activate voodflow/vmedia (galleries route) to use the media browser instead of GrapesJS Asset Manager.',
        );
    }

    return openGrapesAssetManager(args);
}

/**
 * @param {string | null | undefined} libraryUrl
 * @param {string | null | undefined} galleriesUrl
 */
export function shouldUseMediaCompanionBrowser(libraryUrl, galleriesUrl) {
    return String(libraryUrl ?? '').trim() !== ''
        && String(galleriesUrl ?? '').trim() !== '';
}

/**
 * Redirect GrapesJS `open-assets` (image drop / toolbar / dblclick) to vmedia
 * when the companion browser is configured.
 *
 * @param {import('grapesjs').Editor} editor
 */
export function registerMediaPickerCommands(editor) {
    if (! editor?.Commands || editor.__voodbuilderMediaPickerCommandsRegistered) {
        return;
    }

    editor.__voodbuilderMediaPickerCommandsRegistered = true;

    const commands = editor.Commands;
    const previous = typeof commands.get === 'function' ? commands.get('open-assets') : null;

    commands.add('open-assets', {
        run(ed, sender, opts = {}) {
            const libraryUrl = ed?.__voodbuilderMediaLibraryUrl ?? '';
            const galleriesUrl = ed?.__voodbuilderMediaGalleriesUrl ?? '';
            const labels = ed?.__voodbuilderLabels ?? {};
            const target = opts?.target ?? ed?.getSelected?.() ?? null;

            if (shouldUseMediaCompanionBrowser(libraryUrl, galleriesUrl)) {
                void openMediaAssets({
                    editor: ed,
                    kinds: ['image'],
                    labelKind: 'image',
                    labels,
                    onSelect: (src, meta) => {
                        applyMediaSrcToComponent(target, src, meta);
                    },
                });

                return;
            }

            if (previous && typeof previous.run === 'function') {
                return previous.run(ed, sender, opts);
            }

            return openGrapesAssetManager({
                editor: ed,
                kinds: ['image'],
                labelKind: 'image',
                labels,
                onSelect: (src, meta) => {
                    applyMediaSrcToComponent(target, src, meta);
                },
            });
        },
    });
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @param {string} src
 * @param {Record<string, unknown> | null | undefined} meta
 */
function applyMediaSrcToComponent(component, src, meta) {
    if (! component) {
        return;
    }

    const next = String(src ?? '').trim();
    const tag = String(component.get?.('tagName') ?? '').toLowerCase();
    const type = String(component.get?.('type') ?? '');
    const attrs = {
        src: next || null,
    };

    if (meta && typeof meta === 'object') {
        const libraryCaption = String(meta.caption ?? '').trim();
        const libraryAlt = String(meta.alt ?? '').trim();
        const libraryCredits = String(meta.credits ?? '').trim();
        const libraryName = String(meta.name ?? '').trim();
        const libraryFileName = String(meta.file_name ?? meta.fileName ?? '').trim();

        attrs['data-vb-media-caption'] = libraryCaption !== '' ? libraryCaption : null;
        attrs['data-vb-media-credits'] = libraryCredits !== '' ? libraryCredits : null;
        attrs['data-vb-media-name'] = libraryName !== '' ? libraryName : null;
        attrs['data-vb-media-filename'] = libraryFileName !== '' ? libraryFileName : null;
        attrs['data-vb-media-id'] = meta.id != null ? String(meta.id) : null;
        attrs['data-vb-media-uuid'] = meta.uuid != null ? String(meta.uuid) : null;

        if (libraryAlt !== '') {
            attrs.alt = libraryAlt;
        } else if (libraryName !== '' && (tag === 'img' || type === 'image')) {
            attrs.alt = libraryName;
        }
    }

    if (typeof component.set === 'function' && (tag === 'img' || type === 'image')) {
        component.set('src', next);
    }

    component.addAttributes?.(attrs);
}

/**
 * Re-render the open Asset Manager with the current global collection (type-filtered).
 *
 * @param {object} assets
 * @param {string[]} openTypes
 */
function refreshOpenAssetManager(assets, openTypes = []) {
    if (! assets || typeof assets.render !== 'function') {
        return;
    }

    const all = assets.getAll?.();
    const models = [...(all?.models ?? all ?? [])].filter(Boolean);
    const filtered = openTypes.length > 0
        ? models.filter((asset) => {
            const type = typeof asset?.get === 'function'
                ? asset.get('type')
                : asset?.type;

            return openTypes.includes(String(type ?? 'image'));
        })
        : models;

    assets.render(filtered);
}

/**
 * @param {unknown} payload
 * @returns {string}
 */
function firstUploadedAssetSrc(payload) {
    const data = payload && typeof payload === 'object' ? payload.data : null;

    if (! Array.isArray(data) || data.length === 0) {
        return '';
    }

    const first = data[0];

    if (typeof first === 'string') {
        return first.trim();
    }

    if (first && typeof first === 'object') {
        return String(first.src ?? '').trim();
    }

    return '';
}

/**
 * GrapesJS Asset Manager used by Core (free) when the Media companion is inactive.
 *
 * @param {{
 *   editor: object,
 *   kinds?: Array<'image'|'video'>,
 *   labelKind?: 'image'|'video',
 *   labels?: Record<string, string>,
 *   onSelect: (src: string, meta?: object) => void,
 *   onClose?: () => void,
 * }} args
 */
async function openGrapesAssetManager({
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

    const libraryUrl = editor?.__voodbuilderMediaLibraryUrl
        ?? labels.mediaLibraryUrl
        ?? null;

    const wantsVideo = kinds.includes('video');
    const wantsImage = kinds.includes('image') || ! wantsVideo;
    const openTypes = [];

    if (wantsVideo) {
        openTypes.push('video');
    }

    if (wantsImage) {
        openTypes.push('image');
    }

    const libraryType = wantsVideo && ! wantsImage
        ? 'video'
        : (wantsImage && ! wantsVideo ? 'image' : null);

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
    let applied = false;

    const cleanup = () => {
        if (cleaned) {
            return;
        }

        cleaned = true;
        restoreLabels();
        editor?.off?.('asset:upload:error', onUploadError);
        editor?.off?.('asset:upload:response', onUploadResponse);

        if (amConfig) {
            if (previousAccept != null) {
                amConfig.accept = previousAccept;
            } else {
                delete amConfig.accept;
            }
        }

        onClose?.();
    };

    const applySrc = (src, meta = undefined) => {
        const trimmed = String(src ?? '').trim();

        if (trimmed === '' || applied) {
            return false;
        }

        if (wantsVideo && ! wantsImage && ! isVideoAssetSrc(trimmed)) {
            return false;
        }

        if (wantsImage && ! wantsVideo && isVideoAssetSrc(trimmed)) {
            return false;
        }

        applied = true;
        onSelect(trimmed, meta);

        if (typeof assets.close === 'function') {
            assets.close();
        }

        cleanup();

        return true;
    };

    const onUploadError = (error) => {
        let message = labels.imageEditorUploadError
            ?? 'Upload failed. Check media migrations and storage.';

        if (typeof error === 'string') {
            try {
                const parsed = JSON.parse(error);
                message = String(parsed?.message ?? error);
            } catch {
                message = error.replace(/<[^>]+>/g, ' ').trim().slice(0, 240) || message;
            }
        } else if (error?.message) {
            message = String(error.message);
        }

        window.console?.error?.('[voodbuilder] asset upload failed', error);

        const modal = document.querySelector('.gjs-mdl-dialog')
            ?? document.querySelector('.gjs-am-assets-cont');
        let banner = modal?.querySelector?.('[data-voodbuilder-am-error]');

        if (! banner && modal) {
            banner = document.createElement('p');
            banner.dataset.voodbuilderAmError = '1';
            banner.setAttribute('role', 'alert');
            banner.style.cssText = 'margin:0.5rem 0.75rem;padding:0.5rem 0.75rem;border-radius:0.375rem;background:#fef2f2;color:#991b1b;font-size:0.8125rem;';
            modal.insertBefore(banner, modal.firstChild);
        }

        if (banner) {
            banner.textContent = String(message);
        }
    };

    const onUploadResponse = (payload) => {
        const src = firstUploadedAssetSrc(payload);
        const media = payload && typeof payload === 'object' ? payload.media : null;

        if (src === '') {
            return;
        }

        // Upload from Choose should apply immediately (free Core has no gallery browser).
        window.requestAnimationFrame(() => {
            applySrc(src, media && typeof media === 'object'
                ? {
                    caption: media.caption ?? null,
                    name: media.name ?? null,
                    id: media.id ?? null,
                }
                : undefined);
        });
    };

    // Open immediately — hydrate the library in the background so Choose feels snappy.
    assets.open({
        types: openTypes,
        accept,
        modalTitle: copy.modalTitle,
        select(asset, complete) {
            const src = typeof asset?.getSrc === 'function'
                ? asset.getSrc()
                : (asset?.get?.('src') ?? asset?.src ?? '');

            // Single click is enough for the Choose field (GrapesJS default waits for dblclick).
            if (complete === false || complete === true) {
                applySrc(src);
            }
        },
    });

    editor?.on?.('asset:upload:error', onUploadError);
    editor?.on?.('asset:upload:response', onUploadResponse);

    const syncChrome = () => syncAssetManagerChrome(copy);

    syncChrome();
    window.requestAnimationFrame(syncChrome);
    window.setTimeout(syncChrome, 0);

    editor?.once?.('modal:close', cleanup);

    if (libraryUrl) {
        await loadMediaLibrary(editor, libraryUrl, {
            replace: true,
            type: libraryType,
        });
        refreshOpenAssetManager(assets, openTypes);
        syncChrome();
    }
}
