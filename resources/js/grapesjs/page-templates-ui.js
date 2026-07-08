/**
 * Page templates — save and apply full-page layouts from the editor top bar.
 */

import { alertDialog, componentMetaDialog, confirmDialog } from './editor-dialog.js';
import { editorApiHeaders, resolveApiErrorMessage } from './editor-api.js';
import { buildPayload } from './editor.js';
import { lucideIcon } from './editor-icons.js';

function applyTemplatePayload(editor, template) {
    const payload = template?.builder_payload ?? template ?? {};

    editor.setComponents(payload.html ?? '');
    editor.setStyle(payload.css ?? '');

    if (typeof payload.js === 'string' && payload.js.trim() !== '') {
        editor.setJs?.(payload.js);
    }

    editor.__voodbuilderApplyPageLiveCss?.(payload.css ?? '');
    editor.__voodbuilderSchedulePageCssRebuild?.(0);
}

export function registerPageTemplatesUi(editor, options = {}) {
    const {
        pageTemplatesUrl,
        csrf,
        labels = {},
        toolbarMount,
    } = options;

    if (! pageTemplatesUrl || ! toolbarMount) {
        return;
    }

    const baseUrl = pageTemplatesUrl.replace(/\/$/, '');

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'voodbuilder-gjs-topbar__btn voodbuilder-gjs-topbar__btn--ghost';
    button.title = labels.pageTemplates ?? 'Page templates';
    button.innerHTML = lucideIcon('layers', 18);
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
        <div class="voodbuilder-gjs-modal__backdrop" data-voodbuilder-page-templates-close></div>
        <div class="voodbuilder-gjs-modal__panel" role="dialog" aria-modal="true">
            <header class="voodbuilder-gjs-modal__head">
                <h2 class="voodbuilder-gjs-modal__title">${labels.pageTemplatesTitle ?? 'Page templates'}</h2>
                <button type="button" class="voodbuilder-gjs-modal__close" data-voodbuilder-page-templates-close aria-label="Close">×</button>
            </header>
            <div class="voodbuilder-gjs-modal__body">
                <p class="voodbuilder-gjs-hint">${labels.pageTemplatesHint ?? 'Save the current page or apply a saved layout.'}</p>
                <div class="voodbuilder-gjs-dynamic-panel__actions">
                    <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--primary" data-voodbuilder-page-template-save>
                        ${labels.pageTemplatesSave ?? 'Save current page'}
                    </button>
                </div>
                <div data-voodbuilder-page-templates-list></div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const listEl = modal.querySelector('[data-voodbuilder-page-templates-list]');
    const saveBtn = modal.querySelector('[data-voodbuilder-page-template-save]');

    const closeModal = () => {
        modal.hidden = true;
    };

    modal.querySelectorAll('[data-voodbuilder-page-templates-close]').forEach((element) => {
        element.addEventListener('click', closeModal);
    });

    async function loadTemplates() {
        listEl.innerHTML = `<p class="voodbuilder-gjs-hint">${labels.pageTemplatesLoading ?? 'Loading…'}</p>`;

        const response = await fetch(baseUrl, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            throw new Error('load failed');
        }

        const payload = await response.json();

        return payload.templates ?? [];
    }

    function renderList(templates) {
        if (templates.length === 0) {
            listEl.innerHTML = `<p class="voodbuilder-gjs-hint">${labels.pageTemplatesEmpty ?? 'No page templates yet.'}</p>`;

            return;
        }

        listEl.innerHTML = '';

        for (const template of templates) {
            const row = document.createElement('div');
            row.className = 'voodbuilder-gjs-revision-row';

            const meta = document.createElement('div');
            meta.className = 'voodbuilder-gjs-revision-row__meta';
            const category = template.category ? `${template.category} · ` : '';
            meta.textContent = `${category}${template.name ?? ''}`;

            const actions = document.createElement('div');
            actions.className = 'voodbuilder-gjs-revision-row__actions';

            const applyBtn = document.createElement('button');
            applyBtn.type = 'button';
            applyBtn.className = 'voodbuilder-gjs-btn voodbuilder-gjs-btn--primary';
            applyBtn.textContent = labels.pageTemplatesApply ?? 'Apply';
            applyBtn.addEventListener('click', async () => {
                const confirmed = await confirmDialog({
                    title: labels.dialogConfirmTitle ?? 'Confirm',
                    message: labels.pageTemplatesApplyConfirm ?? 'Replace the current page content with this template?',
                    labels,
                    confirmLabel: labels.pageTemplatesApply ?? 'Apply',
                });

                if (! confirmed) {
                    return;
                }

                applyTemplatePayload(editor, template);
                closeModal();
            });

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost';
            deleteBtn.textContent = labels.pageTemplatesDelete ?? 'Delete';
            deleteBtn.addEventListener('click', async () => {
                const confirmed = await confirmDialog({
                    title: labels.dialogConfirmTitle ?? 'Confirm',
                    message: labels.pageTemplatesDeleteConfirm ?? 'Delete this page template?',
                    labels,
                    danger: true,
                    confirmLabel: labels.pageTemplatesDelete ?? 'Delete',
                });

                if (! confirmed) {
                    return;
                }

                const response = await fetch(`${baseUrl}/${template.id}`, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: editorApiHeaders(csrf),
                });

                if (! response.ok) {
                    await alertDialog({
                        message: labels.pageTemplatesDeleteError ?? 'Could not delete template.',
                        labels,
                    });

                    return;
                }

                await openModal();
            });

            actions.append(applyBtn, deleteBtn);
            row.append(meta, actions);
            listEl.appendChild(row);
        }
    }

    async function openModal() {
        modal.hidden = false;

        try {
            renderList(await loadTemplates());
        } catch {
            listEl.innerHTML = `<p class="voodbuilder-gjs-hint">${labels.pageTemplatesLoadError ?? 'Could not load page templates.'}</p>`;
        }
    }

    saveBtn?.addEventListener('click', async () => {
        const meta = await componentMetaDialog({
            title: labels.pageTemplatesSaveTitle ?? 'Save page as template',
            labels,
            categories: options.templateCategories ?? [],
            defaultCategory: options.defaultTemplateCategory ?? 'General',
            categoryLabel: labels.pageTemplatesCategory ?? 'Category',
            confirmLabel: labels.pageTemplatesSave ?? 'Save',
            namePlaceholder: labels.pageTemplatesNamePlaceholder ?? 'Landing · Product',
        });

        if (! meta?.name) {
            return;
        }

        let payload;

        try {
            payload = buildPayload(editor);
        } catch (error) {
            console.error('VoodBuilder template save failed', error);

            await alertDialog({
                message: labels.pageTemplatesSaveError ?? 'Could not read the current page.',
                labels,
            });

            return;
        }

        const response = await fetch(baseUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: editorApiHeaders(csrf, { json: true }),
            body: JSON.stringify({
                name: meta.name,
                category: meta.category,
                html: payload.html,
                css: payload.css,
                js: payload.js,
            }),
        });

        if (! response.ok) {
            await alertDialog({
                message: await resolveApiErrorMessage(
                    response,
                    labels.pageTemplatesSaveError ?? 'Could not save page template.',
                ),
                labels,
            });

            return;
        }

        await openModal();
    });
}
