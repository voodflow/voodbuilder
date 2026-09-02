/**
 * Page revisions UI for the Editor frontend editor.
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

    /**
     * Stored markup needs the boot-time treatment (sanitize, live stylesheet, style
     * hydration) before it is canvas content. setComponents() alone brings the page back
     * unstyled with an empty Style Manager.
     */
    const loadPayload = (payload = {}) => {
        if (typeof editor.__voodbuilderApplyPayload === 'function') {
            editor.__voodbuilderApplyPayload(payload);

            return;
        }

        editor.setComponents(payload.html ?? '');
        editor.setStyle(payload.css ?? '');
    };

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'voodbuilder-editor-topbar__btn voodbuilder-editor-topbar__btn--ghost';
    button.title = labels.revisions ?? 'Revisions';
    button.innerHTML = lucideIcon('clock', 18);
    button.addEventListener('click', () => openModal());

    const savedIndicator = toolbarMount.parentElement?.querySelector('[data-voodbuilder-editor-saved]');

    if (savedIndicator) {
        toolbarMount.insertBefore(button, savedIndicator);
    } else {
        toolbarMount.appendChild(button);
    }

    const modal = document.createElement('div');
    modal.className = 'voodbuilder-editor-modal';
    modal.hidden = true;
    modal.innerHTML = `
        <div class="voodbuilder-editor-modal__backdrop" data-voodbuilder-modal-close></div>
        <div class="voodbuilder-editor-modal__panel" role="dialog" aria-modal="true">
            <header class="voodbuilder-editor-modal__head">
                <h2 class="voodbuilder-editor-modal__title">${labels.revisionsTitle ?? 'Revisions'}</h2>
                <button type="button" class="voodbuilder-editor-modal__close" data-voodbuilder-modal-close aria-label="Close">×</button>
            </header>
            <div class="voodbuilder-editor-modal__body" data-voodbuilder-revisions-list></div>
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
        listEl.innerHTML = '<p class="voodbuilder-editor-hint">Loading…</p>';

        try {
            const response = await fetch(revisionsUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (! response.ok) {
                throw new Error('Failed');
            }

            const payload = await response.json();

            // The autosave is not history — it is work that was never saved — but it
            // restores through the same endpoint, so it belongs at the top of the same
            // list rather than behind a second piece of UI.
            const offer = payload.autosave
                ? [{ ...payload.autosave, kind: 'autosave' }]
                : [];

            renderList([...offer, ...(payload.revisions ?? [])]);
        } catch {
            listEl.innerHTML = '<p class="voodbuilder-editor-hint">Could not load revisions.</p>';
        }
    }

    function renderList(revisions) {
        if (revisions.length === 0) {
            listEl.innerHTML = `<p class="voodbuilder-editor-hint">${labels.revisionsEmpty ?? 'No revisions yet.'}</p>`;

            return;
        }

        listEl.innerHTML = '';

        for (const revision of revisions) {
            const row = document.createElement('div');
            row.className = 'voodbuilder-editor-revision-row';

            const kindLabel = revision.kind === 'autosave'
                ? (labels.revisionsKindAutosave ?? 'Autosaved')
                : (labels.revisionsKindManual ?? null);

            const meta = document.createElement('div');
            meta.className = 'voodbuilder-editor-revision-row__meta';
            meta.textContent = [
                kindLabel,
                revision.created_at ?? '',
                revision.created_by,
            ].filter(Boolean).join(' — ');

            const actions = document.createElement('div');
            actions.className = 'voodbuilder-editor-revision-row__actions';

            const previewBtn = document.createElement('button');
            previewBtn.type = 'button';
            previewBtn.className = 'voodbuilder-editor-btn voodbuilder-editor-btn--ghost';
            previewBtn.textContent = labels.revisionsPreview ?? 'Preview';
            previewBtn.addEventListener('click', () => {
                loadPayload(revision.builder_payload ?? {});
                closeModal();
            });

            const restoreBtn = document.createElement('button');
            restoreBtn.type = 'button';
            restoreBtn.className = 'voodbuilder-editor-btn voodbuilder-editor-btn--primary';
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

                loadPayload(payload.builder_payload ?? {});
                closeModal();
            });

            actions.append(previewBtn, restoreBtn);
            row.append(meta, actions);
            listEl.appendChild(row);
        }
    }
}
