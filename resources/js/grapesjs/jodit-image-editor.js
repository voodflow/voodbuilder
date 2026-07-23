/**
 * In-canvas image editing via @jodit/image-editor (MIT).
 * Opens from the component toolbar on <img> / image components (and vb-bg-image sections).
 * On save, uploads the blob through the GrapesJS asset endpoint and updates src.
 */

import { ImageEditor } from '@jodit/image-editor';
import { editorApiHeaders, resolveApiErrorMessage, resolveCsrfToken } from './editor-api.js';
import { safeFindComponents } from './tailwind-visual-style.js';

export const CMD_EDIT_IMAGE = 'voodbuilder:edit-image';

const MODAL_FLAG = 'data-voodbuilder-image-editor';

/** @type {HTMLElement | null} */
let activeModal = null;

/** @type {ImageEditor | null} */
let activeEditor = null;

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
 * @param {import('grapesjs').Component} component
 * @returns {import('grapesjs').Component | null}
 */
function findBackgroundSectionImage(component) {
    const type = String(component.get?.('type') ?? '');
    const blockId = String(component.getAttributes?.()?.['data-voodbuilder-section-block'] ?? '');

    if (type !== 'vb-bg-image' && ! blockId.includes('vb-bg-image')) {
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
 * @param {string} src
 * @returns {Promise<Blob>}
 */
async function blobFromSrc(src) {
    if (src === '') {
        throw new Error('missing-src');
    }

    const response = await fetch(src, {
        credentials: 'same-origin',
        mode: 'cors',
    });

    if (! response.ok) {
        throw new Error(`fetch-failed:${response.status}`);
    }

    return response.blob();
}

/**
 * @param {Blob} blob
 * @param {{ uploadUrl: string, csrf?: string }} options
 * @param {Record<string, string>} labels
 * @returns {Promise<string>}
 */
async function uploadEditedBlob(blob, options, labels = {}) {
    const form = new FormData();
    const extension = blob.type === 'image/png'
        ? 'png'
        : (blob.type === 'image/webp' ? 'webp' : 'jpg');

    form.append('file', blob, `edited.${extension}`);

    const response = await fetch(options.uploadUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: editorApiHeaders(options.csrf, {
            // Let the browser set multipart boundary.
            extra: {},
        }),
        body: form,
    });

    if (! response.ok) {
        const message = await resolveApiErrorMessage(
            response,
            labels.imageEditorUploadError ?? 'Could not upload the edited image.',
            labels,
        );

        throw new Error(message);
    }

    const payload = await response.json();
    const url = payload?.data?.[0];

    if (typeof url !== 'string' || url.trim() === '') {
        throw new Error(labels.imageEditorUploadError ?? 'Could not upload the edited image.');
    }

    return url.trim();
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {import('grapesjs').Component} component
 * @param {string} url
 */
function applyEditedSrc(editor, component, url) {
    component.set('src', url);
    component.addAttributes({ src: url });

    let parent = component.parent?.();

    while (parent) {
        const attrs = parent.getAttributes?.() ?? {};
        const type = String(parent.get?.('type') ?? '');
        const blockId = String(attrs['data-voodbuilder-section-block'] ?? '');

        if (type === 'vb-bg-image' || blockId.includes('vb-bg-image') || Object.prototype.hasOwnProperty.call(attrs, 'data-vb-bg-src')) {
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
 * @param {import('grapesjs').Editor} editor
 * @param {import('grapesjs').Component} target
 * @param {{ uploadUrl?: string, csrf?: string, labels?: Record<string, string> }} options
 */
async function openImageEditorModal(editor, target, options = {}) {
    const labels = options.labels ?? editor.__voodbuilderLabels ?? {};
    const uploadUrl = options.uploadUrl ?? editor.__voodbuilderUploadUrl ?? '';
    const csrf = resolveCsrfToken(options.csrf ?? editor.__voodbuilderCsrf ?? '');
    const src = resolveImageSrc(target);

    if (uploadUrl === '') {
        setModalStatus(labels.imageEditorUploadMissing ?? 'Image upload is not configured.');

        return;
    }

    closeImageEditorModal();

    const modal = document.createElement('div');
    modal.className = 'voodbuilder-gjs-modal voodbuilder-gjs-modal--image-editor';
    modal.setAttribute('role', 'presentation');
    modal.setAttribute(MODAL_FLAG, '1');

    const title = labels.imageEditorTitle ?? 'Edit image';
    const cancelLabel = labels.dialogCancel ?? labels.modalCancel ?? 'Cancel';
    const applyLabel = labels.imageEditorApply ?? 'Apply';
    const loadingLabel = labels.imageEditorLoading ?? 'Loading image…';

    modal.innerHTML = `
        <div class="voodbuilder-gjs-modal__backdrop" data-voodbuilder-image-editor-close></div>
        <div class="voodbuilder-gjs-modal__panel voodbuilder-gjs-image-editor-modal__panel" role="dialog" aria-modal="true" aria-label="${escapeHtml(title)}">
            <header class="voodbuilder-gjs-modal__head">
                <h2 class="voodbuilder-gjs-modal__title">${escapeHtml(title)}</h2>
                <button type="button" class="voodbuilder-gjs-modal__close" data-voodbuilder-image-editor-close aria-label="${escapeHtml(cancelLabel)}">×</button>
            </header>
            <div class="voodbuilder-gjs-modal__body voodbuilder-gjs-image-editor-modal__body">
                <p class="voodbuilder-gjs-hint" data-voodbuilder-image-editor-status hidden></p>
                <div class="voodbuilder-gjs-image-editor-host" data-voodbuilder-image-editor-host></div>
            </div>
            <footer class="voodbuilder-gjs-modal__foot voodbuilder-gjs-image-editor-modal__foot">
                <button type="button" class="voodbuilder-gjs-btn" data-voodbuilder-image-editor-close>${escapeHtml(cancelLabel)}</button>
                <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--primary" data-voodbuilder-image-editor-apply disabled>${escapeHtml(applyLabel)}</button>
            </footer>
        </div>
    `;

    document.body.appendChild(modal);
    activeModal = modal;

    const host = modal.querySelector('[data-voodbuilder-image-editor-host]');
    const applyButton = modal.querySelector('[data-voodbuilder-image-editor-apply]');

    const onClose = () => {
        closeImageEditorModal();
        editor.stopCommand?.(CMD_EDIT_IMAGE);
    };

    modal.querySelectorAll('[data-voodbuilder-image-editor-close]').forEach((el) => {
        el.addEventListener('click', onClose);
    });

    setModalStatus(loadingLabel);

    let saving = false;

    /**
     * @param {Blob} blob
     */
    const persistBlob = async (blob) => {
        if (saving) {
            return;
        }

        saving = true;

        if (applyButton instanceof HTMLButtonElement) {
            applyButton.disabled = true;
        }

        setModalStatus(labels.imageEditorSaving ?? 'Saving…');

        try {
            const url = await uploadEditedBlob(blob, { uploadUrl, csrf }, labels);
            applyEditedSrc(editor, target, url);
            onClose();
        } catch (error) {
            const message = error instanceof Error && error.message
                ? error.message
                : (labels.imageEditorUploadError ?? 'Could not upload the edited image.');

            setModalStatus(message);
            saving = false;

            if (applyButton instanceof HTMLButtonElement) {
                applyButton.disabled = false;
            }
        }
    };

    try {
        const blob = await blobFromSrc(src);
        setModalStatus('');

        activeEditor = new ImageEditor({
            container: host,
            image: blob,
            state: {
                theme: 'light',
            },
            onSave: (editedBlob) => {
                void persistBlob(editedBlob);
            },
            onSaveAs: (editedBlob) => {
                void persistBlob(editedBlob);
            },
        });

        if (applyButton instanceof HTMLButtonElement) {
            applyButton.disabled = false;
            applyButton.addEventListener('click', () => {
                void activeEditor?.save();
            });
        }
    } catch {
        setModalStatus(
            labels.imageEditorLoadError
                ?? 'Could not load this image for editing (missing source or blocked by CORS).',
        );

        if (applyButton instanceof HTMLButtonElement) {
            applyButton.disabled = true;
        }
    }
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {{ enabled?: boolean, uploadUrl?: string, csrf?: string, labels?: Record<string, string> }} [options]
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

                await openImageEditorModal(ed, target, {
                    uploadUrl: ed.__voodbuilderUploadUrl,
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
