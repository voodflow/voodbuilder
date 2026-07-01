/**
 * Page revisions UI for the GrapesJS frontend editor.
 */

import { alertDialog, confirmDialog } from './editor-dialog.js';
import { lucideIcon } from './editor-icons.js';

export function registerRevisionsUi(editor, options = {}) {
    const {
        revisionsUrl,
        revisionsRestoreUrl,
        csrf,
        labels = {},
        toolbarMount,
    } = options;

    if (! revisionsUrl || ! toolbarMount) {
        return;
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'voodbuilder-gjs-topbar__btn voodbuilder-gjs-topbar__btn--ghost';
    button.title = labels.revisions ?? 'Revisions';
    button.innerHTML = lucideIcon('clock', 18);
    button.addEventListener('click', () => openModal());

    const savedIndicator = toolbarMount.parentElement?.querySelector('[data-voodbuilder-grapesjs-saved]');

    if (savedIndicator) {
        toolbarMount.insertBefore(button, savedIndicator);
    } else {
        toolbarMount.appendChild(button);
    }

    const modal = document.createElement('div');
    modal.className = 'voodbuilder-gjs-modal';
    modal.hidden = true;
    modal.innerHTML = `
        <div class="voodbuilder-gjs-modal__backdrop" data-voodbuilder-modal-close></div>
        <div class="voodbuilder-gjs-modal__panel" role="dialog" aria-modal="true">
            <header class="voodbuilder-gjs-modal__head">
                <h2 class="voodbuilder-gjs-modal__title">${labels.revisionsTitle ?? 'Revisions'}</h2>
                <button type="button" class="voodbuilder-gjs-modal__close" data-voodbuilder-modal-close aria-label="Close">×</button>
            </header>
            <div class="voodbuilder-gjs-modal__body" data-voodbuilder-revisions-list></div>
        </div>
    `;
    document.body.appendChild(modal);

    const listEl = modal.querySelector('[data-voodbuilder-revisions-list]');

    const closeModal = () => {
        modal.hidden = true;
    };

    modal.querySelectorAll('[data-voodbuilder-modal-close]').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    async function openModal() {
        modal.hidden = false;
        listEl.innerHTML = '<p class="voodbuilder-gjs-hint">Loading…</p>';

        try {
            const response = await fetch(revisionsUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (! response.ok) {
                throw new Error('Failed');
            }

            const payload = await response.json();
            renderList(payload.revisions ?? []);
        } catch {
            listEl.innerHTML = '<p class="voodbuilder-gjs-hint">Could not load revisions.</p>';
        }
    }

    function renderList(revisions) {
        if (revisions.length === 0) {
            listEl.innerHTML = `<p class="voodbuilder-gjs-hint">${labels.revisionsEmpty ?? 'No revisions yet.'}</p>`;

            return;
        }

        listEl.innerHTML = '';

        for (const revision of revisions) {
            const row = document.createElement('div');
            row.className = 'voodbuilder-gjs-revision-row';

            const meta = document.createElement('div');
            meta.className = 'voodbuilder-gjs-revision-row__meta';
            meta.textContent = `${revision.created_at ?? ''}${revision.created_by ? ` — ${revision.created_by}` : ''}`;

            const actions = document.createElement('div');
            actions.className = 'voodbuilder-gjs-revision-row__actions';

            const previewBtn = document.createElement('button');
            previewBtn.type = 'button';
            previewBtn.className = 'voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost';
            previewBtn.textContent = labels.revisionsPreview ?? 'Preview';
            previewBtn.addEventListener('click', () => {
                const payload = revision.builder_payload ?? {};

                editor.setComponents(payload.html ?? '');
                editor.setStyle(payload.css ?? '');
                closeModal();
            });

            const restoreBtn = document.createElement('button');
            restoreBtn.type = 'button';
            restoreBtn.className = 'voodbuilder-gjs-btn voodbuilder-gjs-btn--primary';
            restoreBtn.textContent = labels.revisionsRestore ?? 'Restore';
            restoreBtn.addEventListener('click', async () => {
                const confirmed = await confirmDialog({
                    title: labels.dialogConfirmTitle ?? 'Confirm',
                    message: labels.revisionsRestoreConfirm ?? labels.revisionsRestore ?? 'Restore this revision?',
                    labels,
                    confirmLabel: labels.revisionsRestore ?? labels.dialogConfirm ?? 'Restore',
                });

                if (! confirmed) {
                    return;
                }

                const url = revisionsRestoreUrl.replace('__REVISION__', String(revision.id));

                const response = await fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                });

                if (! response.ok) {
                    await alertDialog({
                        message: labels.revisionsRestoreError ?? 'Could not restore revision.',
                        labels,
                    });

                    return;
                }

                const payload = await response.json();
                const restored = payload.builder_payload ?? {};

                editor.setComponents(restored.html ?? '');
                editor.setStyle(restored.css ?? '');
                closeModal();
            });

            actions.append(previewBtn, restoreBtn);
            row.append(meta, actions);
            listEl.appendChild(row);
        }
    }
}
