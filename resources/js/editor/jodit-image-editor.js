/**
 * In-canvas image editing via @jodit/image-editor (MIT).
 * Opens from the component toolbar on <img> / image components (and vb-bg-image sections).
 * On save, uploads the blob through the Editor asset endpoint and updates src.
 *
 * Not available for: SVG / placeholder sources, or images with dynamic bindings.
 */

import { editorApiHeaders, resolveApiErrorMessage, resolveCsrfToken } from './editor-api.js';
import { isBackgroundImageHeroId } from './media-hero.js';
import { safeFindComponents } from './tailwind-visual-style.js';

export const CMD_EDIT_IMAGE = 'voodbuilder:edit-image';

const MODAL_FLAG = 'data-voodbuilder-image-editor';
const MAX_UPLOAD_ATTEMPTS = 3;

/** @type {HTMLElement | null} */
let activeModal = null;

/** @type {import('@jodit/image-editor').ImageEditor | null} */
let activeEditor = null;

/** @type {Promise<typeof import('@jodit/image-editor').ImageEditor>|null} */
let imageEditorCtorPromise = null;

async function loadImageEditorCtor() {
    imageEditorCtorPromise ??= import('@jodit/image-editor').then((mod) => mod.ImageEditor);

    return imageEditorCtorPromise;
}

/**
 * Kept behind import() alongside the constructor: the plugin imports @jodit/image-editor
 * statically, so a top-level import here pulls the whole library into the boot chunk even
 * though the constructor itself is lazy.
 */
async function loadFocusPointPluginFactory() {
    const mod = await import('./jodit-focus-point-plugin.js');

    return mod.createFocusPointPlugin;
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {boolean}
 */
export function isEditableImageComponent(component) {
    if (! component) {
        return false;
    }

    const type = String(component.get?.('type') ?? '');

    if (type === 'image') {
        return true;
    }

    const tag = String(component.get?.('tagName') ?? '').toLowerCase();

    return tag === 'img';
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {boolean}
 */
export function isDynamicallyBoundImage(component) {
    if (! component) {
        return false;
    }

    let current = component;

    while (current) {
        const attrs = current.getAttributes?.() ?? {};

        if (attrs['data-voodbuilder-bind'] || attrs['data-voodbuilder-repeat'] || attrs['data-voodbuilder-repeat-item']) {
            return true;
        }

        current = current.parent?.() ?? null;
    }

    return false;
}

/**
 * @param {string} src
 * @returns {boolean}
 */
export function isRasterEditableSrc(src) {
    const value = String(src ?? '').trim();

    if (value === '' || value === '#' || value === 'about:blank') {
        return false;
    }

    if (/^data:image\/svg\+xml/i.test(value)) {
        return false;
    }

    if (/^data:image\/(png|jpe?g|webp|gif|bmp)/i.test(value)) {
        return true;
    }

    if (/^data:/i.test(value)) {
        return false;
    }

    // Neutral SVG placeholders sometimes survive without the data: prefix quirks.
    if (/Image placeholder/i.test(value)) {
        return false;
    }

    return true;
}

/**
 * @param {import('grapesjs').Component} component
 * @returns {import('grapesjs').Component | null}
 */
function findBackgroundSectionImage(component) {
    const type = String(component.get?.('type') ?? '');
    const attrs = component.getAttributes?.() ?? {};
    const blockId = String(
        attrs['data-voodbuilder-block']
        ?? attrs['data-voodbuilder-section-block']
        ?? '',
    );

    if (! isBackgroundImageHeroId(type) && ! isBackgroundImageHeroId(blockId)
        && ! Object.prototype.hasOwnProperty.call(attrs, 'data-vb-bg-src')) {
        return null;
    }

    // Video heroes are not image-edit targets.
    if (blockId.includes('vb-bg-video') || type === 'vb-bg-video') {
        return null;
    }

    return safeFindComponents(
        component,
        '[data-voodbuilder-role="media"] img, .voodbuilder-hero-media__img',
    )[0] ?? null;
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {import('grapesjs').Component | null}
 */
export function resolveImageEditTarget(component) {
    if (! component) {
        return null;
    }

    if (isEditableImageComponent(component)) {
        return component;
    }

    return findBackgroundSectionImage(component);
}

/**
 * Toolbar / command gate: real raster src, not dynamically bound.
 *
 * @param {import('grapesjs').Component | null | undefined} component
 * @returns {import('grapesjs').Component | null}
 */
export function resolveEditableImageTarget(component) {
    const target = resolveImageEditTarget(component);

    if (! target || isDynamicallyBoundImage(target)) {
        return null;
    }

    if (! isRasterEditableSrc(resolveImageSrc(target))) {
        return null;
    }

    return target;
}

/**
 * @param {import('grapesjs').Component} component
 * @returns {string}
 */
function resolveImageSrc(component) {
    const fromModel = String(component.get?.('src') ?? '').trim();

    if (fromModel !== '') {
        return fromModel;
    }

    return String(component.getAttributes?.()?.src ?? '').trim();
}

/**
 * @param {Blob} blob
 * @returns {Promise<void>}
 */
async function assertDecodableRaster(blob) {
    const type = String(blob.type || '').toLowerCase();

    if (type.includes('svg')) {
        throw new Error('svg-placeholder');
    }

    if (typeof createImageBitmap === 'function') {
        const bitmap = await createImageBitmap(blob);
        bitmap.close();

        return;
    }

    await new Promise((resolve, reject) => {
        const url = URL.createObjectURL(blob);
        const image = new Image();

        image.onload = () => {
            URL.revokeObjectURL(url);
            resolve();
        };
        image.onerror = () => {
            URL.revokeObjectURL(url);
            reject(new Error('decode-failed'));
        };
        image.src = url;
    });
}

/**
 * @param {string} src
 * @returns {Promise<Blob>}
 */
async function blobFromSrc(src) {
    if (src === '') {
        throw new Error('missing-src');
    }

    if (/^data:image\/svg\+xml/i.test(src)) {
        throw new Error('svg-placeholder');
    }

    const response = await fetch(src, {
        credentials: 'same-origin',
        mode: 'cors',
    });

    if (! response.ok) {
        throw new Error(`fetch-failed:${response.status}`);
    }

    const blob = await response.blob();
    await assertDecodableRaster(blob);

    return blob;
}

/**
 * Prefer the live <img> in the canvas when fetch/CORS would fail.
 *
 * @param {import('grapesjs').Component} component
 * @returns {Promise<Blob | null>}
 */
async function blobFromComponentElement(component) {
    const element = component.getEl?.() ?? component.view?.el ?? null;

    if (! (element instanceof HTMLImageElement) || ! element.naturalWidth) {
        return null;
    }

    const canvas = document.createElement('canvas');
    canvas.width = element.naturalWidth;
    canvas.height = element.naturalHeight;
    const context = canvas.getContext('2d');

    if (! context) {
        return null;
    }

    try {
        context.drawImage(element, 0, 0);
    } catch {
        return null;
    }

    const blob = await new Promise((resolve) => {
        canvas.toBlob((result) => resolve(result), 'image/png');
    });

    if (! blob) {
        return null;
    }

    await assertDecodableRaster(blob);

    return blob;
}

/**
 * @param {import('grapesjs').Component} component
 * @param {string} src
 * @returns {Promise<Blob>}
 */
async function loadImageBlob(component, src) {
    try {
        return await blobFromSrc(src);
    } catch (error) {
        const fromElement = await blobFromComponentElement(component);

        if (fromElement) {
            return fromElement;
        }

        throw error;
    }
}

/**
 * @param {Response} response
 * @param {Record<string, string>} labels
 * @returns {Promise<string>}
 */
async function resolveUploadErrorMessage(response, labels = {}) {
    try {
        const payload = await response.clone().json();
        const fileError = payload?.errors?.file;

        if (Array.isArray(fileError) && typeof fileError[0] === 'string' && fileError[0].trim() !== '') {
            return fileError[0];
        }

        if (typeof payload?.message === 'string' && payload.message.trim() !== '') {
            return payload.message;
        }
    } catch {
        // fall through
    }

    return resolveApiErrorMessage(
        response,
        labels.imageEditorUploadError ?? 'Could not upload the edited image.',
        labels,
    );
}

/**
 * Prefer a stable human title for edited uploads (never bare "edited").
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {import('grapesjs').Component | null | undefined} component
 * @param {string} src
 * @returns {string}
 */
export function resolveEditedDisplayName(editor, component, src) {
    const assets = editor?.Assets?.getAll?.() ?? editor?.AssetManager?.getAll?.() ?? [];
    const list = typeof assets?.models !== 'undefined'
        ? assets.models
        : (Array.isArray(assets) ? assets : []);

    for (const asset of list) {
        const assetSrc = String(asset?.get?.('src') ?? asset?.src ?? '').trim();

        if (assetSrc === '' || assetSrc !== String(src ?? '').trim()) {
            continue;
        }

        const assetName = String(asset?.get?.('name') ?? asset?.name ?? '').trim();

        if (assetName !== '' && ! /^edited(?:[-_.].*)?$/i.test(assetName)) {
            return pathinfoFilename(assetName);
        }
    }

    const alt = String(component?.getAttributes?.()?.alt ?? '').trim();

    if (alt !== '') {
        return sanitizeUploadBaseName(alt);
    }

    const fromUrl = pathinfoFilename(String(src ?? ''));

    if (fromUrl !== '' && ! /^edited$/i.test(fromUrl)) {
        if (/^[a-f0-9_-]{16,}$/i.test(fromUrl)) {
            return `edited-${fromUrl.slice(0, 8)}`;
        }

        return fromUrl;
    }

    return 'edited-image';
}

/**
 * @param {string} value
 * @returns {string}
 */
function pathinfoFilename(value) {
    const cleaned = String(value ?? '').split('?')[0].split('#')[0];
    const base = cleaned.includes('/')
        ? cleaned.slice(cleaned.lastIndexOf('/') + 1)
        : cleaned;
    const withoutExt = base.includes('.')
        ? base.slice(0, base.lastIndexOf('.'))
        : base;

    return sanitizeUploadBaseName(withoutExt);
}

/**
 * @param {string} value
 * @returns {string}
 */
function sanitizeUploadBaseName(value) {
    const cleaned = String(value ?? '')
        .trim()
        .replace(/[^\p{L}\p{N}\-_ .]+/gu, '-')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '')
        .slice(0, 120);

    return cleaned;
}

/**
 * @param {Blob} blob
 * @param {string} type
 * @param {number} quality
 * @param {string} [displayName]
 * @returns {Promise<File>}
 */
async function blobToUploadFile(blob, type = 'image/jpeg', quality = 0.88, displayName = 'edited-image') {
    let output = blob;

    if (blob.type !== type || type === 'image/jpeg') {
        output = await reencodeBlob(blob, type, quality);
    }

    const extension = type === 'image/png' ? 'png' : (type === 'image/webp' ? 'webp' : 'jpg');
    const mime = output.type || type;
    const base = sanitizeUploadBaseName(displayName) || 'edited-image';

    return new File([output], `${base}.${extension}`, { type: mime });
}

/**
 * @param {Blob} blob
 * @param {string} type
 * @param {number} quality
 * @returns {Promise<Blob>}
 */
async function reencodeBlob(blob, type, quality) {
    if (typeof createImageBitmap !== 'function') {
        return blob;
    }

    const bitmap = await createImageBitmap(blob);
    const canvas = document.createElement('canvas');
    canvas.width = bitmap.width;
    canvas.height = bitmap.height;
    const context = canvas.getContext('2d');

    if (! context) {
        bitmap.close();

        return blob;
    }

    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(bitmap, 0, 0);
    bitmap.close();

    const encoded = await new Promise((resolve) => {
        canvas.toBlob((result) => resolve(result), type, quality);
    });

    return encoded ?? blob;
}

/**
 * @param {import('grapesjs').Component} component
 * @returns {string|null}
 */
function resolveMediaUuid(component) {
    const attrs = component?.getAttributes?.() ?? {};
    const uuid = String(attrs['data-vb-media-uuid'] ?? '').trim();

    return uuid !== '' ? uuid : null;
}

/**
 * @param {File} file
 * @param {{ replaceUrl: string, uuid: string, csrf?: string, displayName?: string }} options
 * @param {Record<string, string>} labels
 * @returns {Promise<{ url: string, media?: object }>}
 */
async function replaceFile(file, options, labels = {}) {
    const form = new FormData();
    form.append('file', file);
    form.append('uuid', options.uuid);

    const displayName = String(options.displayName ?? pathinfoFilename(file.name) ?? '').trim();

    if (displayName !== '') {
        form.append('name', displayName);
    }

    const response = await fetch(options.replaceUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: editorApiHeaders(options.csrf),
        body: form,
    });

    if (! response.ok) {
        throw new Error(await resolveUploadErrorMessage(response, labels));
    }

    const payload = await response.json();
    const url = payload?.data?.[0];

    if (typeof url !== 'string' || url.trim() === '') {
        throw new Error(labels.imageEditorUploadError ?? 'Could not upload the edited image.');
    }

    return {
        url: url.trim(),
        media: payload?.media ?? null,
    };
}

/**
 * @param {File} file
 * @param {{ uploadUrl: string, csrf?: string, displayName?: string, derivedFromUuid?: string|null }} options
 * @param {Record<string, string>} labels
 * @returns {Promise<string>}
 */
async function uploadFile(file, options, labels = {}) {
    const form = new FormData();
    form.append('file', file);

    const displayName = String(options.displayName ?? pathinfoFilename(file.name) ?? '').trim();

    if (displayName !== '') {
        form.append('name', displayName);
    }

    if (options.derivedFromUuid) {
        form.append('derived_from_uuid', options.derivedFromUuid);
    }

    const response = await fetch(options.uploadUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: editorApiHeaders(options.csrf),
        body: form,
    });

    if (! response.ok) {
        throw new Error(await resolveUploadErrorMessage(response, labels));
    }

    const payload = await response.json();
    const url = payload?.data?.[0];

    if (typeof url !== 'string' || url.trim() === '') {
        throw new Error(labels.imageEditorUploadError ?? 'Could not upload the edited image.');
    }

    return url.trim();
}

/**
 * Editor AssetManager uploader (same path as the Assets panel).
 *
 * @param {import('grapesjs').Editor} editor
 * @param {File} file
 * @returns {Promise<string | null>}
 */
function uploadViaAssetManager(editor, file) {
    const uploader = editor.Assets?.FileUploader?.();

    if (! uploader || typeof uploader.uploadFile !== 'function') {
        return Promise.resolve(null);
    }

    return new Promise((resolve) => {
        let settled = false;

        const finish = (url) => {
            if (settled) {
                return;
            }

            settled = true;
            resolve(typeof url === 'string' && url.trim() !== '' ? url.trim() : null);
        };

        try {
            uploader.uploadFile(
                {
                    dataTransfer: { files: [file] },
                },
                (res) => {
                    const obj = res?.data?.[0];
                    const src = typeof obj === 'string' ? obj : obj?.src;
                    finish(src ?? null);
                },
            );
        } catch {
            finish(null);
        }

        window.setTimeout(() => finish(null), 20000);
    });
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {Blob} blob
 * @param {{ uploadUrl: string, replaceUrl?: string, csrf?: string, displayName?: string, component?: object, mode?: 'replace'|'create', mediaUuid?: string|null }} options
 * @param {Record<string, string>} labels
 * @returns {Promise<{ url: string, media?: object|null }>}
 */
async function uploadEditedBlob(editor, blob, options, labels = {}) {
    let lastError = labels.imageEditorUploadError ?? 'Could not upload the edited image.';
    let quality = 0.88;
    const displayName = String(options.displayName ?? 'edited-image').trim() || 'edited-image';
    const mode = options.mode ?? 'create';
    const mediaUuid = options.mediaUuid ?? null;
    const replaceUrl = options.replaceUrl ?? '';

    for (let attempt = 0; attempt < MAX_UPLOAD_ATTEMPTS; attempt += 1) {
        const file = await blobToUploadFile(blob, 'image/jpeg', quality, displayName);

        try {
            if (mode === 'replace' && mediaUuid && replaceUrl !== '') {
                return await replaceFile(file, {
                    replaceUrl,
                    uuid: mediaUuid,
                    csrf: options.csrf,
                    displayName,
                }, labels);
            }

            const url = await uploadFile(file, { ...options, displayName }, labels);

            return { url, media: null };
        } catch (error) {
            lastError = error instanceof Error && error.message
                ? error.message
                : lastError;

            const viaAssets = await uploadViaAssetManager(editor, file);

            if (viaAssets) {
                return { url: viaAssets, media: null };
            }

            // Retry smaller JPEG when validation complains about size.
            if (/kilobytes|max|too large|grande/i.test(lastError) && attempt < MAX_UPLOAD_ATTEMPTS - 1) {
                quality = Math.max(0.55, quality - 0.15);

                continue;
            }

            throw new Error(lastError);
        }
    }

    throw new Error(lastError);
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {import('grapesjs').Component} component
 * @param {string} url
 * @param {{ media?: object|null, preserveUuid?: boolean }} [meta]
 */
function applyEditedSrc(editor, component, url, meta = {}) {
    component.set('src', url);
    component.addAttributes({ src: url });

    const media = meta.media;

    if (media && typeof media === 'object') {
        const uuid = media.uuid != null ? String(media.uuid).trim() : '';
        const id = media.id != null ? String(media.id).trim() : '';

        if (uuid !== '') {
            component.addAttributes({ 'data-vb-media-uuid': uuid });
        }

        if (id !== '') {
            component.addAttributes({ 'data-vb-media-id': id });
        }
    } else if (meta.preserveUuid === false) {
        component.removeAttributes?.('data-vb-media-uuid');
        component.removeAttributes?.('data-vb-media-id');
    }

    let parent = component.parent?.();

    while (parent) {
        const attrs = parent.getAttributes?.() ?? {};
        const type = String(parent.get?.('type') ?? '');
        const blockId = String(
            attrs['data-voodbuilder-block']
            ?? attrs['data-voodbuilder-section-block']
            ?? '',
        );

        if (
            isBackgroundImageHeroId(type)
            || isBackgroundImageHeroId(blockId)
            || Object.prototype.hasOwnProperty.call(attrs, 'data-vb-bg-src')
        ) {
            parent.addAttributes({ 'data-vb-bg-src': url });
            break;
        }

        parent = parent.parent?.();
    }

    try {
        editor.Assets?.add?.({ src: url });
    } catch {
        // AssetManager may be disabled in some editor modes.
    }
}

/**
 * @param {string} message
 */
function setModalStatus(message) {
    const status = activeModal?.querySelector('[data-voodbuilder-image-editor-status]');

    if (! status) {
        return;
    }

    status.textContent = message;
    status.hidden = message === '';
}

function closeImageEditorModal() {
    if (activeEditor) {
        try {
            activeEditor.destroy();
        } catch {
            // ignore teardown races
        }

        activeEditor = null;
    }

    if (activeModal) {
        activeModal.remove();
        activeModal = null;
    }
}

/**
 * @param {string} value
 * @returns {string}
 */
function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;');
}

/**
 * @param {unknown} error
 * @param {Record<string, string>} labels
 * @returns {string}
 */
function resolveLoadErrorMessage(error, labels = {}) {
    const code = error instanceof Error ? error.message : '';

    if (code === 'svg-placeholder' || code === 'missing-src') {
        return labels.imageEditorPlaceholderHint
            ?? 'Upload a real image first (Assets / Content panel). SVG placeholders cannot be edited.';
    }

    return labels.imageEditorLoadError
        ?? 'Could not load this image for editing (missing source or blocked by CORS).';
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {import('grapesjs').Component} target
 * @param {{ uploadUrl?: string, replaceUrl?: string, csrf?: string, labels?: Record<string, string> }} options
 */
async function openImageEditorModal(editor, target, options = {}) {
    const labels = options.labels ?? editor.__voodbuilderLabels ?? {};
    const uploadUrl = options.uploadUrl ?? editor.__voodbuilderUploadUrl ?? '';
    const replaceUrl = options.replaceUrl ?? editor.__voodbuilderMediaReplaceUrl ?? '';
    const csrf = resolveCsrfToken(options.csrf ?? editor.__voodbuilderCsrf ?? '');
    const src = resolveImageSrc(target);
    const mediaUuid = resolveMediaUuid(target);

    if (uploadUrl === '') {
        // Still open a minimal modal so the user sees the message.
    }

    closeImageEditorModal();

    const modal = document.createElement('div');
    modal.className = 'voodbuilder-editor-modal voodbuilder-editor-modal--image-editor';
    modal.setAttribute('role', 'presentation');
    modal.setAttribute(MODAL_FLAG, '1');

    const title = labels.imageEditorTitle ?? 'Edit image';
    const cancelLabel = labels.dialogCancel ?? labels.modalCancel ?? 'Cancel';
    const saveLabel = labels.imageEditorSave ?? 'Save';
    const saveAsLabel = labels.imageEditorSaveAs ?? 'Save as copy';
    const loadingLabel = labels.imageEditorLoading ?? 'Loading image…';

    modal.innerHTML = `
        <div class="voodbuilder-editor-modal__backdrop" data-voodbuilder-image-editor-close></div>
        <div class="voodbuilder-editor-modal__panel voodbuilder-editor-image-editor-modal__panel" role="dialog" aria-modal="true" aria-label="${escapeHtml(title)}">
            <header class="voodbuilder-editor-modal__head">
                <h2 class="voodbuilder-editor-modal__title">${escapeHtml(title)}</h2>
                <button type="button" class="voodbuilder-editor-modal__close" data-voodbuilder-image-editor-close aria-label="${escapeHtml(cancelLabel)}">×</button>
            </header>
            <div class="voodbuilder-editor-modal__body voodbuilder-editor-image-editor-modal__body">
                <div class="voodbuilder-editor-image-editor-host" data-voodbuilder-image-editor-host></div>
            </div>
            <footer class="voodbuilder-editor-modal__foot voodbuilder-editor-image-editor-modal__foot">
                <p class="voodbuilder-editor-hint" data-voodbuilder-image-editor-status hidden></p>
                <div class="voodbuilder-editor-image-editor-modal__foot-actions">
                    <button type="button" class="voodbuilder-editor-btn" data-voodbuilder-image-editor-close>${escapeHtml(cancelLabel)}</button>
                    <button type="button" class="voodbuilder-editor-btn" data-voodbuilder-image-editor-save-as disabled>${escapeHtml(saveAsLabel)}</button>
                    <button type="button" class="voodbuilder-editor-btn voodbuilder-editor-btn--primary" data-voodbuilder-image-editor-save disabled>${escapeHtml(saveLabel)}</button>
                </div>
            </footer>
        </div>
    `;

    document.body.appendChild(modal);
    activeModal = modal;

    const host = modal.querySelector('[data-voodbuilder-image-editor-host]');
    const saveButton = modal.querySelector('[data-voodbuilder-image-editor-save]');
    const saveAsButton = modal.querySelector('[data-voodbuilder-image-editor-save-as]');

    const setSavingState = (disabled) => {
        for (const button of [saveButton, saveAsButton]) {
            if (button instanceof HTMLButtonElement) {
                button.disabled = disabled;
            }
        }
    };

    const onClose = () => {
        closeImageEditorModal();
        editor.stopCommand?.(CMD_EDIT_IMAGE);
    };

    modal.querySelectorAll('[data-voodbuilder-image-editor-close]').forEach((el) => {
        el.addEventListener('click', onClose);
    });

    if (uploadUrl === '') {
        setModalStatus(labels.imageEditorUploadMissing ?? 'Image upload is not configured.');

        return;
    }

    if (! isRasterEditableSrc(src)) {
        setModalStatus(resolveLoadErrorMessage(new Error('svg-placeholder'), labels));

        return;
    }

    setModalStatus(loadingLabel);

    let saving = false;

    /**
     * @param {Blob} blob
     * @param {'replace'|'create'} mode
     */
    const persistBlob = async (blob, mode) => {
        if (saving) {
            return;
        }

        saving = true;
        setSavingState(true);

        setModalStatus(labels.imageEditorSaving ?? 'Saving…');

        try {
            const displayName = resolveEditedDisplayName(editor, target, src);
            const canReplace = mode === 'replace' && mediaUuid !== null && replaceUrl !== '';
            const result = await uploadEditedBlob(editor, blob, {
                uploadUrl,
                replaceUrl,
                csrf,
                displayName,
                component: target,
                mode: canReplace ? 'replace' : 'create',
                mediaUuid,
                derivedFromUuid: canReplace ? null : mediaUuid,
            }, labels);

            applyEditedSrc(editor, target, result.url, {
                media: result.media,
                preserveUuid: canReplace || Boolean(result.media?.uuid),
            });
            onClose();
        } catch (error) {
            const message = error instanceof Error && error.message
                ? error.message
                : (labels.imageEditorUploadError ?? 'Could not upload the edited image.');

            setModalStatus(message);
            saving = false;
            setSavingState(false);
        }
    };

    try {
        const blob = await loadImageBlob(target, src);
        const [ImageEditor, createFocusPointPlugin] = await Promise.all([
            loadImageEditorCtor(),
            loadFocusPointPluginFactory(),
        ]);
        setModalStatus('');

        activeEditor = new ImageEditor({
            container: host,
            image: blob,
            plugins: [createFocusPointPlugin(() => host)],
            state: {
                theme: 'light',
                showToolbar: true,
            },
            onSave: (editedBlob) => {
                void persistBlob(editedBlob, 'replace');
            },
            onSaveAs: (editedBlob) => {
                void persistBlob(editedBlob, 'create');
            },
        });

        setSavingState(false);

        if (saveButton instanceof HTMLButtonElement) {
            saveButton.addEventListener('click', () => {
                void activeEditor?.save();
            });
        }

        if (saveAsButton instanceof HTMLButtonElement) {
            saveAsButton.addEventListener('click', () => {
                void activeEditor?.saveAs();
            });
        }
    } catch (error) {
        setModalStatus(resolveLoadErrorMessage(error, labels));
        setSavingState(true);
    }
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {{ enabled?: boolean, uploadUrl?: string, replaceUrl?: string, csrf?: string, labels?: Record<string, string> }} [options]
 */
export function registerJoditImageEditor(editor, options = {}) {
    if (editor.__voodbuilderJoditImageEditorRegistered) {
        return;
    }

    const enabled = options.enabled !== false;

    if (! enabled) {
        return;
    }

    editor.__voodbuilderJoditImageEditorRegistered = true;
    editor.__voodbuilderImageEditorEnabled = true;
    editor.__voodbuilderUploadUrl = options.uploadUrl ?? editor.__voodbuilderUploadUrl ?? '';
    editor.__voodbuilderMediaReplaceUrl = options.replaceUrl ?? editor.__voodbuilderMediaReplaceUrl ?? '';
    editor.__voodbuilderCsrf = options.csrf ?? editor.__voodbuilderCsrf ?? '';

    if (options.labels) {
        editor.__voodbuilderLabels = {
            ...(editor.__voodbuilderLabels ?? {}),
            ...options.labels,
        };
    }

    const commands = editor.Commands;
    const hasCommand = typeof commands.has === 'function'
        ? commands.has(CMD_EDIT_IMAGE)
        : Boolean(commands.getAll?.()?.[CMD_EDIT_IMAGE]);

    if (! hasCommand) {
        commands.add(CMD_EDIT_IMAGE, {
            async run(ed, _sender, opts = {}) {
                const selected = opts.target ?? ed.getSelected?.();
                const target = resolveImageEditTarget(selected);

                if (! target) {
                    return;
                }

                if (isDynamicallyBoundImage(target)) {
                    return;
                }

                await openImageEditorModal(ed, target, {
                    uploadUrl: ed.__voodbuilderUploadUrl,
                    replaceUrl: ed.__voodbuilderMediaReplaceUrl,
                    csrf: ed.__voodbuilderCsrf,
                    labels: ed.__voodbuilderLabels ?? {},
                });
            },
            stop() {
                // Modal owns its teardown; keep command stop idempotent.
            },
        });
    }
}
