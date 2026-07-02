/**
 * Import reusable components from pasted HTML / CSS (e.g. Tailwind blocks).
 */

import { alertDialog } from './editor-dialog.js';
import { editorApiHeaders, resolveApiErrorMessage } from './editor-api.js';
import { normalizeComponentCategory, resolveComponentCategories } from './component-categories.js';
import { stripEmbeddableMediaFromHtml } from './component-media.js';
import { migrateImportedTailwindHtml } from './imported-tailwind-support.js';
import { renderCompatibilityPlaceholder, renderCompatibilityReport } from './import-compatibility-report.js';
import { lucideIcon } from './editor-icons.js';

let activeModal = null;
let previewTimer = null;
let compileTimer = null;

export function parsePastedComponentSource(raw) {
    let html = String(raw ?? '').trim();
    const cssParts = [];

    html = html.replace(/<style\b[^>]*>([\s\S]*?)<\/style>/gi, (_, style) => {
        const chunk = String(style ?? '').trim();

        if (chunk) {
            cssParts.push(chunk);
        }

        return '';
    });

    html = html.replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '');

    const bodyMatch = html.match(/<body\b[^>]*>([\s\S]*)<\/body>/i);

    if (bodyMatch) {
        html = bodyMatch[1];
    }

    html = html
        .replace(/<!DOCTYPE[^>]*>/gi, '')
        .replace(/<\/?html[^>]*>/gi, '')
        .replace(/<head\b[\s\S]*?<\/head>/gi, '')
        .trim();

    html = migrateImportedTailwindHtml(html);

    if (html && (html.match(/<(section|nav|header|footer|main|article)\b/gi) ?? []).length > 1
        && ! /^<div[^>]*class="[^"]*voodbuilder-pasted-component/i.test(html)) {
        html = `<div class="voodbuilder-pasted-component">${html}</div>`;
    }

    return {
        html: stripEmbeddableMediaFromHtml(html),
        extractedCss: cssParts.join('\n\n').trim(),
    };
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function buildPreviewDocument({ html, css, canvasStyles = [] }) {
    const links = canvasStyles
        .map((href) => `<link rel="stylesheet" href="${escapeHtml(href)}">`)
        .join('');

    return `<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=1280">
${links}
<style>
html, body { margin: 0; padding: 0; background: var(--color-vp-bg, #fff); }
.voodbuilder-code-import-preview-viewport {
    width: 1280px;
    min-height: 100%;
    transform: scale(0.58);
    transform-origin: top left;
}
.voodbuilder-component-code-preview { min-height: 100%; }
</style>
${css ? `<style>${css}</style>` : ''}
</head>
<body>
<div class="voodbuilder-code-import-preview-viewport">
<div class="voodbuilder-component-code-preview">${html}</div>
</div>
</body>
</html>`;
}

function closeModal() {
    if (! activeModal) {
        return;
    }

    window.clearTimeout(previewTimer);
    window.clearTimeout(compileTimer);
    activeModal.remove();
    activeModal = null;
}

function schedulePreviewUpdate(iframe, getValues, canvasStyles) {
    window.clearTimeout(previewTimer);
    previewTimer = window.setTimeout(() => {
        const { html, css } = getValues();
        const doc = iframe.contentDocument;

        if (! doc) {
            return;
        }

        doc.open();
        doc.write(buildPreviewDocument({ html, css, canvasStyles }));
        doc.close();
    }, 180);
}

function openComponentCodeDialog({
    componentsUrl,
    csrf,
    labels = {},
    canvasStyles = [],
    componentCategories = [],
    component = null,
    onCreated,
    onUpdated,
}) {
    if (activeModal) {
        closeModal();
    }

    const isEdit = component != null && component.id != null;
    const categories = resolveComponentCategories(componentCategories);
    const defaultCategory = normalizeComponentCategory(null, categories, labels.componentsUncategorized ?? 'General');
    const categoryOptions = categories.map((category) => (
        `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`
    )).join('');
    const modalTitle = isEdit
        ? (labels.componentsEditTitle ?? 'Edit component')
        : (labels.componentsCodeImportTitle ?? 'Import from code');
    const modalHint = isEdit
        ? (labels.componentsEditHint ?? 'Update the component markup, styles, or library metadata.')
        : (labels.componentsCodeImportHint ?? 'Paste HTML from Pagedone or any Tailwind snippet. Optional CSS is extracted from <style> tags automatically.');
    const submitLabel = isEdit
        ? (labels.componentsEditSubmit ?? 'Save changes')
        : (labels.componentsCodeImportSubmit ?? 'Create component');
    const submitIcon = isEdit ? 'save' : 'plus';
    const compileLabel = labels.componentsCodeImportCompiling ?? 'Compiling Tailwind styles…';

    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'voodbuilder-gjs-modal voodbuilder-gjs-component-code-modal';
        modal.setAttribute('role', 'presentation');

        modal.innerHTML = `
            <div class="voodbuilder-gjs-modal__backdrop" data-voodbuilder-code-cancel></div>
            <div class="voodbuilder-gjs-modal__panel voodbuilder-gjs-component-code-modal__panel" role="dialog" aria-modal="true">
                <header class="voodbuilder-gjs-modal__head">
                    <h2 class="voodbuilder-gjs-modal__title">${escapeHtml(modalTitle)}</h2>
                    <button type="button" class="voodbuilder-gjs-modal__close" data-voodbuilder-code-cancel aria-label="${escapeHtml(labels.dialogCancel ?? 'Cancel')}">×</button>
                </header>
                <div class="voodbuilder-gjs-modal__body voodbuilder-gjs-component-code-modal__body">
                    <p class="voodbuilder-gjs-hint voodbuilder-gjs-component-code-modal__hint">
                        ${escapeHtml(modalHint)}
                    </p>
                    <div class="voodbuilder-gjs-component-code-modal__meta">
                        <label class="voodbuilder-gjs-form-field">
                            <span class="voodbuilder-gjs-form-field__label">${escapeHtml(labels.componentsNamePrompt ?? 'Component name')}</span>
                            <input type="text" class="voodbuilder-gjs-input" data-voodbuilder-code-name placeholder="${escapeHtml(labels.componentsCodeImportNamePlaceholder ?? 'Hero · Pagedone')}" required />
                        </label>
                        <label class="voodbuilder-gjs-form-field">
                            <span class="voodbuilder-gjs-form-field__label">${escapeHtml(labels.componentsCodeImportCategory ?? 'Category')}</span>
                            <select class="voodbuilder-gjs-input" data-voodbuilder-code-category>
                                ${categoryOptions}
                            </select>
                        </label>
                    </div>
                    <div class="voodbuilder-gjs-component-code-modal__workspace">
                        <div class="voodbuilder-gjs-component-code-modal__editor">
                            <label class="voodbuilder-gjs-form-field">
                                <span class="voodbuilder-gjs-form-field__label">HTML</span>
                                <textarea class="voodbuilder-gjs-input voodbuilder-gjs-input--textarea voodbuilder-gjs-component-code-modal__textarea" rows="14" data-voodbuilder-code-html placeholder="${escapeHtml(labels.componentsCodeImportHtmlPlaceholder ?? 'Paste HTML here…')}"></textarea>
                            </label>
                            <label class="voodbuilder-gjs-form-field">
                                <span class="voodbuilder-gjs-form-field__label">CSS <span class="voodbuilder-gjs-component-code-modal__optional">${escapeHtml(labels.componentsCodeImportCssOptional ?? 'optional')}</span></span>
                                <textarea class="voodbuilder-gjs-input voodbuilder-gjs-input--textarea voodbuilder-gjs-component-code-modal__textarea" rows="5" data-voodbuilder-code-css placeholder="${escapeHtml(labels.componentsCodeImportCssPlaceholder ?? 'Custom CSS or leave empty to auto-extract <style> blocks')}"></textarea>
                            </label>
                        </div>
                        <div class="voodbuilder-gjs-component-code-modal__preview-wrap">
                            <div class="voodbuilder-gjs-component-code-modal__preview-head">
                                <span>${escapeHtml(labels.componentsCodeImportPreview ?? 'Preview')}</span>
                            </div>
                            <div class="voodbuilder-gjs-component-code-modal__preview-stage">
                                <iframe class="voodbuilder-gjs-component-code-modal__preview" data-voodbuilder-code-preview title="${escapeHtml(labels.componentsCodeImportPreview ?? 'Preview')}"></iframe>
                                <div class="voodbuilder-gjs-component-code-modal__preview-loading" data-voodbuilder-code-preview-loading hidden>
                                    <div class="voodbuilder-gjs-component-code-modal__spinner" aria-hidden="true"></div>
                                    <span>${escapeHtml(compileLabel)}</span>
                                </div>
                            </div>
                            <div data-voodbuilder-code-compatibility></div>
                        </div>
                    </div>
                </div>
                <footer class="voodbuilder-gjs-dialog__footer voodbuilder-gjs-component-code-modal__footer">
                    <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost" data-voodbuilder-code-cancel>
                        ${escapeHtml(labels.dialogCancel ?? 'Cancel')}
                    </button>
                    <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--primary" data-voodbuilder-code-submit disabled>
                        ${lucideIcon(submitIcon, 15)}
                        <span>${escapeHtml(submitLabel)}</span>
                    </button>
                </footer>
            </div>
        `;

        document.body.appendChild(modal);
        activeModal = modal;

        const nameInput = modal.querySelector('[data-voodbuilder-code-name]');
        const categoryInput = modal.querySelector('[data-voodbuilder-code-category]');
        const htmlInput = modal.querySelector('[data-voodbuilder-code-html]');
        const cssInput = modal.querySelector('[data-voodbuilder-code-css]');
        const previewFrame = modal.querySelector('[data-voodbuilder-code-preview]');
        const previewLoading = modal.querySelector('[data-voodbuilder-code-preview-loading]');
        const compatibilityMount = modal.querySelector('[data-voodbuilder-code-compatibility]');
        const submitButton = modal.querySelector('[data-voodbuilder-code-submit]');
        const baseUrl = componentsUrl.replace(/\/$/, '');

        let compileRequestId = 0;
        let compiledTailwindCss = '';
        let normalizedPreviewHtml = '';
        let compileReady = false;
        let isCompiling = false;

        renderCompatibilityPlaceholder(compatibilityMount, labels);

        const updateSubmitState = () => {
            const hasName = nameInput.value.trim() !== '';
            const hasHtml = htmlInput.value.trim() !== '';
            submitButton.disabled = ! hasName || ! hasHtml || ! compileReady || isCompiling;
        };

        const setCompiling = (value) => {
            isCompiling = value;
            previewLoading.hidden = ! value;
            updateSubmitState();
        };

        const readValues = () => {
            const manualCss = cssInput.value.trim();

            return {
                name: nameInput.value.trim(),
                category: normalizeComponentCategory(categoryInput?.value, categories, defaultCategory),
                html: normalizedPreviewHtml || parsePastedComponentSource(htmlInput.value).html,
                css: [manualCss, compiledTailwindCss].filter(Boolean).join('\n\n'),
            };
        };

        const refreshPreview = () => {
            schedulePreviewUpdate(previewFrame, readValues, canvasStyles);
        };

        const requestCompile = async (rawHtml) => {
            const trimmed = String(rawHtml ?? '').trim();

            if (trimmed === '') {
                compileRequestId += 1;
                compiledTailwindCss = '';
                normalizedPreviewHtml = '';
                compileReady = false;
                setCompiling(false);
                renderCompatibilityPlaceholder(compatibilityMount, labels);
                refreshPreview();
                updateSubmitState();

                return;
            }

            const requestId = ++compileRequestId;
            setCompiling(true);
            compileReady = false;
            updateSubmitState();

            try {
                const response = await fetch(`${baseUrl}/compile-css`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: editorApiHeaders(csrf, { json: true }),
                    body: JSON.stringify({ html: trimmed }),
                });

                if (requestId !== compileRequestId) {
                    return;
                }

                if (! response.ok) {
                    throw new Error(await resolveApiErrorMessage(
                        response,
                        labels.componentsCompileError ?? 'Could not compile Tailwind styles for this markup.',
                    ));
                }

                const payload = await response.json();

                if (requestId !== compileRequestId) {
                    return;
                }

                normalizedPreviewHtml = String(payload.html ?? parsePastedComponentSource(trimmed).html);
                compiledTailwindCss = String(payload.css ?? '');
                compileReady = payload.compiled === true;
                renderCompatibilityReport(compatibilityMount, payload.compatibility ?? null, labels);
            } catch (error) {
                if (requestId !== compileRequestId) {
                    return;
                }

                compiledTailwindCss = '';
                normalizedPreviewHtml = parsePastedComponentSource(trimmed).html;
                compileReady = false;
                renderCompatibilityPlaceholder(compatibilityMount, labels);

                await alertDialog({
                    message: error instanceof Error
                        ? error.message
                        : (labels.componentsCompileError ?? 'Could not compile Tailwind styles for this markup.'),
                    labels,
                });
            } finally {
                if (requestId === compileRequestId) {
                    setCompiling(false);
                    refreshPreview();
                    updateSubmitState();
                }
            }
        };

        const scheduleCompile = (rawHtml) => {
            window.clearTimeout(compileTimer);
            compileTimer = window.setTimeout(() => {
                requestCompile(rawHtml);
            }, 420);
        };

        const finish = (result) => {
            closeModal();
            resolve(result);
        };

        if (isEdit) {
            nameInput.value = String(component.name ?? '');
            htmlInput.value = String(component.html ?? '');
            cssInput.value = String(component.css ?? '');
            categoryInput.value = normalizeComponentCategory(component.category, categories, defaultCategory);
        } else if (categoryInput) {
            categoryInput.value = defaultCategory;
        }

        htmlInput.addEventListener('input', () => {
            const parsed = parsePastedComponentSource(htmlInput.value);

            if (! cssInput.value.trim() && parsed.extractedCss) {
                cssInput.value = parsed.extractedCss;
            }

            scheduleCompile(htmlInput.value);
        });

        cssInput.addEventListener('input', () => {
            refreshPreview();
        });

        nameInput.addEventListener('input', updateSubmitState);

        modal.querySelectorAll('[data-voodbuilder-code-cancel]').forEach((element) => {
            element.addEventListener('click', () => finish(null));
        });

        submitButton.addEventListener('click', async () => {
            const parsed = parsePastedComponentSource(htmlInput.value);
            const manualCss = cssInput.value.trim();
            const values = {
                name: nameInput.value.trim(),
                category: normalizeComponentCategory(categoryInput?.value, categories, defaultCategory),
                html: normalizedPreviewHtml || parsed.html,
                css: [manualCss, compiledTailwindCss].filter(Boolean).join('\n\n') || null,
            };

            if (! values.name) {
                await alertDialog({
                    message: labels.componentsCodeImportNameRequired ?? 'Enter a component name.',
                    labels,
                });
                nameInput.focus();

                return;
            }

            if (! values.html) {
                await alertDialog({
                    message: labels.componentsCodeImportHtmlRequired ?? 'Paste some HTML markup first.',
                    labels,
                });
                htmlInput.focus();

                return;
            }

            if (! compileReady || isCompiling) {
                await alertDialog({
                    message: labels.componentsCompilePending ?? 'Wait for Tailwind styles to finish compiling before saving.',
                    labels,
                });

                return;
            }

            submitButton.disabled = true;

            const requestUrl = isEdit ? `${baseUrl}/${component.id}` : baseUrl;
            const requestMethod = isEdit ? 'PUT' : 'POST';

            try {
                const response = await fetch(requestUrl, {
                    method: requestMethod,
                    credentials: 'same-origin',
                    headers: editorApiHeaders(csrf, { json: true }),
                    body: JSON.stringify({
                        name: values.name,
                        category: values.category || null,
                        html: values.html,
                        css: values.css,
                    }),
                });

                if (! response.ok) {
                    throw new Error(await resolveApiErrorMessage(
                        response,
                        labels.componentsSaveError ?? 'Could not save component.',
                    ));
                }

                const payload = await response.json();

                if (isEdit) {
                    onUpdated?.(payload.component);
                } else {
                    onCreated?.(payload.component);
                }

                finish(payload.component);
            } catch (error) {
                submitButton.disabled = false;
                updateSubmitState();
                await alertDialog({
                    message: error instanceof Error ? error.message : (labels.componentsSaveError ?? 'Could not save component.'),
                    labels,
                });
            }
        });

        window.requestAnimationFrame(() => {
            nameInput.focus();

            if (htmlInput.value.trim() !== '') {
                scheduleCompile(htmlInput.value);
            } else {
                updateSubmitState();
            }
        });
    });
}

export function openComponentCodeImportDialog(options = {}) {
    return openComponentCodeDialog(options);
}

export function openComponentCodeEditorDialog(options = {}) {
    return openComponentCodeDialog(options);
}
