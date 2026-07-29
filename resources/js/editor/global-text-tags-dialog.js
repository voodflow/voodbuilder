/**
 * Topbar modal: list GlobalTextTags ({current_year}, …) and copy tokens for paste into text.
 */

import { copyTextToClipboard } from './clipboard.js';
import { lucideIcon } from './editor-icons.js';
import { globalTextTagCatalog } from './global-text-tags.js';

let activeModal = null;

/**
 * @param {string} value
 * @returns {string}
 */
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * @param {string} title
 * @param {string} [detail]
 */
function showCopiedToast(title, detail = '') {
    let toast = document.getElementById('voodbuilder-editor-classes-toast');

    if (! toast) {
        toast = document.createElement('div');
        toast.id = 'voodbuilder-editor-classes-toast';
        toast.className = 'voodbuilder-editor-classes-toast';
        document.body.appendChild(toast);
    }

    toast.innerHTML = `
        <div class="voodbuilder-editor-classes-toast__title">${escapeHtml(title)}</div>
        ${detail ? `<div class="voodbuilder-editor-classes-toast__detail">${escapeHtml(detail)}</div>` : ''}
    `;
    toast.hidden = false;
    window.clearTimeout(toast._hideTimer);
    toast._hideTimer = window.setTimeout(() => {
        toast.hidden = true;
    }, 2200);
}

function closeActiveModal() {
    if (! activeModal) {
        return;
    }

    const { modal, onKeyDown } = activeModal;

    modal.hidden = true;
    modal.remove();
    window.removeEventListener('keydown', onKeyDown, true);
    activeModal = null;
}

/**
 * @param {object} editor
 * @param {object} [labels]
 */
export function openGlobalTextTagsDialog(editor, labels = {}) {
    closeActiveModal();

    const title = labels.globalTextTagsTitle ?? 'Available text tags';
    const hint = labels.globalTextTagsHint
        ?? 'Click a tag to copy it, then paste it into text.';
    const copyLabel = labels.globalTextTagsCopy ?? 'Copy';
    const previewLabel = labels.globalTextTagsPreview ?? 'Preview';
    const copiedTemplate = labels.globalTextTagsCopied ?? 'Copied {tag}';
    const closeLabel = labels.dialogCancel ?? 'Cancel';

    const catalog = globalTextTagCatalog(editor, labels);

    const rows = catalog.map((entry) => `
        <li class="voodbuilder-editor-global-tags__item">
            <button
                type="button"
                class="voodbuilder-editor-global-tags__row"
                data-voodbuilder-global-tag="${escapeHtml(entry.token)}"
                title="${escapeHtml(copyLabel)} ${escapeHtml(entry.token)}"
            >
                <span class="voodbuilder-editor-global-tags__main">
                    <code class="voodbuilder-editor-global-tags__token">${escapeHtml(entry.token)}</code>
                    <span class="voodbuilder-editor-global-tags__label">${escapeHtml(entry.label)}</span>
                </span>
                <span class="voodbuilder-editor-global-tags__meta">
                    <span class="voodbuilder-editor-global-tags__preview-label">${escapeHtml(previewLabel)}</span>
                    <span class="voodbuilder-editor-global-tags__preview">${escapeHtml(entry.preview || '—')}</span>
                    <span class="voodbuilder-editor-global-tags__copy" aria-hidden="true">${lucideIcon('copy', 14)}</span>
                </span>
            </button>
        </li>
    `).join('');

    const modal = document.createElement('div');
    modal.className = 'voodbuilder-editor-modal voodbuilder-editor-global-tags-modal';
    modal.setAttribute('role', 'presentation');
    modal.innerHTML = `
        <div class="voodbuilder-editor-modal__backdrop" data-voodbuilder-global-tags-close></div>
        <div class="voodbuilder-editor-modal__panel voodbuilder-editor-global-tags-modal__panel" role="dialog" aria-modal="true" aria-label="${escapeHtml(title)}">
            <header class="voodbuilder-editor-modal__head">
                <h2 class="voodbuilder-editor-modal__title">${escapeHtml(title)}</h2>
                <button type="button" class="voodbuilder-editor-modal__close" data-voodbuilder-global-tags-close aria-label="${escapeHtml(closeLabel)}">×</button>
            </header>
            <div class="voodbuilder-editor-modal__body">
                <p class="voodbuilder-editor-hint">${escapeHtml(hint)}</p>
                <ul class="voodbuilder-editor-global-tags__list" role="list">
                    ${rows}
                </ul>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    const onKeyDown = (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            closeActiveModal();
        }
    };

    window.addEventListener('keydown', onKeyDown, true);
    activeModal = { modal, onKeyDown };

    modal.querySelectorAll('[data-voodbuilder-global-tags-close]').forEach((el) => {
        el.addEventListener('click', () => closeActiveModal());
    });

    modal.querySelectorAll('[data-voodbuilder-global-tag]').forEach((button) => {
        button.addEventListener('click', async () => {
            const token = button.getAttribute('data-voodbuilder-global-tag') ?? '';

            if (! token) {
                return;
            }

            const ok = await copyTextToClipboard(token);
            const message = String(copiedTemplate).replace('{tag}', token);

            showCopiedToast(ok ? message : token, ok ? '' : copyLabel);
            button.classList.add('is-copied');
            window.setTimeout(() => button.classList.remove('is-copied'), 600);
        });
    });

    modal.querySelector('[data-voodbuilder-global-tags-close].voodbuilder-editor-modal__close')?.focus?.();
}
