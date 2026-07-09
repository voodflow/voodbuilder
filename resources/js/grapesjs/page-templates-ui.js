/**
 * Page templates — save and apply full-page layouts from the editor top bar.
 */

import { alertDialog, componentMetaDialog, confirmDialog, promptDialog } from './editor-dialog.js';
import { editorApiHeaders, resolveApiErrorMessage } from './editor-api.js';
import { buildPayload } from './editor.js';
import { lucideIcon } from './editor-icons.js';
import { applyPageTemplateWithPrompt } from './page-template-apply.js';

export { applyTemplatePayload } from './page-template-apply.js';

export function registerPageTemplatesUi(editor, options = {}) {
    const {
        pageTemplatesUrl,
        pageTemplatesCatalogUrl = null,
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
                    <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost" data-voodbuilder-page-template-import>
                        ${labels.pageTemplatesImport ?? 'Import bundle'}
                    </button>
                    <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost" data-voodbuilder-page-template-import-url>
                        ${labels.pageTemplatesImportUrl ?? 'Install from URL'}
                    </button>
                    <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost" data-voodbuilder-page-template-export>
                        ${labels.pageTemplatesExport ?? 'Export all'}
                    </button>
                    <input type="file" accept="application/json,.json" hidden data-voodbuilder-page-template-import-input />
                </div>
                <div class="voodbuilder-gjs-page-templates-catalog" data-voodbuilder-page-templates-catalog hidden></div>
                <div data-voodbuilder-page-templates-list></div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const listEl = modal.querySelector('[data-voodbuilder-page-templates-list]');
    const catalogEl = modal.querySelector('[data-voodbuilder-page-templates-catalog]');
    const saveBtn = modal.querySelector('[data-voodbuilder-page-template-save]');
    const importBtn = modal.querySelector('[data-voodbuilder-page-template-import]');
    const importUrlBtn = modal.querySelector('[data-voodbuilder-page-template-import-url]');
    const exportBtn = modal.querySelector('[data-voodbuilder-page-template-export]');
    const importInput = modal.querySelector('[data-voodbuilder-page-template-import-input]');

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
                const applied = await applyPageTemplateWithPrompt(editor, template, labels);

                if (applied) {
                    closeModal();
                }
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
            await loadTemplates().then(renderList);
        } catch {
            listEl.innerHTML = `<p class="voodbuilder-gjs-hint">${labels.pageTemplatesLoadError ?? 'Could not load page templates.'}</p>`;
        }

        try {
            await loadCatalog();
        } catch {
            if (catalogEl && pageTemplatesCatalogUrl) {
                catalogEl.hidden = false;
                catalogEl.innerHTML = `<p class="voodbuilder-gjs-hint">${labels.pageTemplatesCatalogInstallError ?? 'Could not install that template.'}</p>`;
            }
        }
    }

    async function loadCatalog() {
        if (! catalogEl || ! pageTemplatesCatalogUrl) {
            catalogEl?.replaceChildren();
            if (catalogEl) {
                catalogEl.hidden = true;
            }

            return;
        }

        catalogEl.hidden = false;
        catalogEl.innerHTML = `<p class="voodbuilder-gjs-hint">${labels.pageTemplatesLoading ?? 'Loading…'}</p>`;

        const response = await fetch(pageTemplatesCatalogUrl, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            throw new Error('catalog failed');
        }

        const payload = await response.json();
        const entries = payload.templates ?? [];

        if (entries.length === 0) {
            catalogEl.innerHTML = `<p class="voodbuilder-gjs-hint">${labels.pageTemplatesCatalogEmpty ?? 'No remote templates are available.'}</p>`;

            return;
        }

        catalogEl.innerHTML = `
            <h3 class="voodbuilder-gjs-subtitle">${labels.pageTemplatesCatalogTitle ?? 'Template marketplace'}</h3>
            <div class="voodbuilder-gjs-page-templates-catalog__grid"></div>
        `;

        const grid = catalogEl.querySelector('.voodbuilder-gjs-page-templates-catalog__grid');

        for (const entry of entries) {
            const card = document.createElement('article');
            card.className = 'voodbuilder-gjs-page-templates-catalog__card';

            const title = document.createElement('h4');
            title.className = 'voodbuilder-gjs-page-templates-catalog__title';
            title.textContent = entry.name ?? '';

            const meta = document.createElement('p');
            meta.className = 'voodbuilder-gjs-hint';
            const metaParts = [entry.category, entry.price_label].filter(Boolean);
            meta.textContent = metaParts.join(' · ');

            const description = document.createElement('p');
            description.className = 'voodbuilder-gjs-page-templates-catalog__description';
            description.textContent = entry.description ?? '';

            const actions = document.createElement('div');
            actions.className = 'voodbuilder-gjs-revision-row__actions';

            const installBtn = document.createElement('button');
            installBtn.type = 'button';
            installBtn.className = 'voodbuilder-gjs-btn voodbuilder-gjs-btn--primary';
            installBtn.textContent = labels.pageTemplatesCatalogInstall ?? 'Install';
            installBtn.addEventListener('click', async () => {
                if (! entry.bundle_url) {
                    await alertDialog({
                        message: labels.pageTemplatesCatalogInstallError ?? 'Could not install that template.',
                        labels,
                    });

                    return;
                }

                installBtn.disabled = true;

                try {
                    const response = await fetch(`${baseUrl}/install`, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: editorApiHeaders(csrf, { json: true }),
                        body: JSON.stringify({ bundle_url: entry.bundle_url }),
                    });

                    if (! response.ok) {
                        throw new Error('install failed');
                    }

                    await alertDialog({
                        message: (labels.pageTemplatesCatalogInstallSuccess ?? 'Installed :name.')
                            .replace(':name', String(entry.name ?? '')),
                        labels,
                    });

                    renderList(await loadTemplates());
                } catch {
                    await alertDialog({
                        message: labels.pageTemplatesCatalogInstallError ?? 'Could not install that template.',
                        labels,
                    });
                } finally {
                    installBtn.disabled = false;
                }
            });

            actions.append(installBtn);
            card.append(title, meta, description, actions);
            grid?.appendChild(card);
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

    exportBtn?.addEventListener('click', async () => {
        try {
            const response = await fetch(`${baseUrl}/export`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: editorApiHeaders(csrf, { json: true }),
                body: JSON.stringify({}),
            });

            if (! response.ok) {
                throw new Error('export failed');
            }

            const payload = await response.json();
            const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = `voodbuilder-page-templates-${new Date().toISOString().slice(0, 10)}.json`;
            anchor.click();
            URL.revokeObjectURL(url);
        } catch {
            await alertDialog({
                message: labels.pageTemplatesExportError ?? 'Could not export page templates.',
                labels,
            });
        }
    });

    importBtn?.addEventListener('click', () => importInput?.click());

    importUrlBtn?.addEventListener('click', async () => {
        const url = await promptDialog({
            title: labels.pageTemplatesImportUrl ?? 'Install from URL',
            message: labels.pageTemplatesImportUrlPrompt ?? 'Paste the HTTPS URL of a VoodBuilder page template bundle (.json).',
            labels,
            confirmLabel: labels.pageTemplatesCatalogInstall ?? 'Install',
        });

        if (! url) {
            return;
        }

        try {
            const response = await fetch(`${baseUrl}/import-url`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: editorApiHeaders(csrf, { json: true }),
                body: JSON.stringify({ url: String(url).trim() }),
            });

            if (! response.ok) {
                throw new Error('import url failed');
            }

            const payload = await response.json();
            const count = (payload.templates ?? []).length;

            await alertDialog({
                message: (labels.pageTemplatesImportUrlSuccess ?? 'Installed :count page template(s) from URL.')
                    .replace(':count', String(count)),
                labels,
            });

            await openModal();
        } catch {
            await alertDialog({
                message: labels.pageTemplatesImportUrlError ?? 'Could not install templates from that URL.',
                labels,
            });
        }
    });

    importInput?.addEventListener('change', async () => {
        const file = importInput.files?.[0];
        importInput.value = '';

        if (! file) {
            return;
        }

        try {
            const parsed = JSON.parse(await file.text());
            const response = await fetch(`${baseUrl}/import`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: editorApiHeaders(csrf, { json: true }),
                body: JSON.stringify({ import: parsed }),
            });

            if (! response.ok) {
                throw new Error('import failed');
            }

            const payload = await response.json();
            const count = (payload.templates ?? []).length;

            await alertDialog({
                message: (labels.pageTemplatesImportSuccess ?? 'Imported :count page template(s).').replace(':count', String(count)),
                labels,
            });

            await openModal();
        } catch {
            await alertDialog({
                message: labels.pageTemplatesImportError ?? 'Could not import page templates.',
                labels,
            });
        }
    });
}
