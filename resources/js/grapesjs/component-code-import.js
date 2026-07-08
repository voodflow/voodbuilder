/**
 * Import reusable components from pasted HTML / CSS (e.g. Tailwind blocks).
 */

import { alertDialog } from './editor-dialog.js';
import { editorApiHeaders, resolveApiErrorMessage } from './editor-api.js';
import { enhanceInspectorSelects } from './inspector-select-ui.js';
import { normalizeComponentCategory, resolveComponentCategories } from './component-categories.js';
import { stripEmbeddableMediaFromHtml } from './component-media.js';
import { migrateImportedTailwindHtml } from './imported-tailwind-support.js';
import { renderCompatibilityPlaceholder, renderCompatibilityReport } from './import-compatibility-report.js';
import { lucideIcon } from './editor-icons.js';
import {
    createCodeEditorField,
    destroyCodeEditorFields,
    formatCodeForEditor,
} from './code-editor-field.js';

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

function createFallbackCodeField({
    mount,
    value = '',
    minHeight = '12rem',
    lineWrapping = false,
    onChange = null,
}) {
    if (! mount) {
        return null;
    }

    const textarea = document.createElement('textarea');
    textarea.className = 'voodbuilder-gjs-input voodbuilder-gjs-input--textarea voodbuilder-gjs-component-code-modal__textarea';
    textarea.spellcheck = false;
    textarea.value = value;
    textarea.style.minHeight = minHeight;
    textarea.wrap = lineWrapping ? 'soft' : 'off';
    mount.replaceChildren(textarea);

    let wrapping = lineWrapping;

    textarea.addEventListener('input', () => {
        onChange?.(textarea.value);
    });

    const field = {
        get lineWrapping() {
            return wrapping;
        },
        getValue: () => textarea.value,
        setValue: (nextValue) => {
            textarea.value = nextValue;
        },
        setLineWrapping(enabled) {
            wrapping = Boolean(enabled);
            textarea.wrap = wrapping ? 'soft' : 'off';
        },
        toggleLineWrapping() {
            field.setLineWrapping(! wrapping);
        },
        setReviewClasses() {},
        scrollToClass(className) {
            const text = textarea.value;
            const index = text.indexOf(className);

            if (index < 0) {
                return false;
            }

            textarea.focus();
            textarea.setSelectionRange(index, index + className.length);

            return true;
        },
        focus: () => {
            textarea.focus();
        },
        destroy: () => {
            textarea.remove();
        },
    };

    return field;
}

function mountCodeField(options) {
    try {
        return createCodeEditorField(options) ?? createFallbackCodeField(options);
    } catch (error) {
        console.error('Voodbuilder: falling back to textarea code field.', error);

        return createFallbackCodeField(options);
    }
}

function syncWrapToggleButton(button, enabled) {
    if (! button) {
        return;
    }

    button.setAttribute('aria-pressed', enabled ? 'true' : 'false');
    button.classList.toggle('is-active', enabled);
}

function syncCompatibilityReview(editor, reviewClasses) {
    editor?.setReviewClasses?.(reviewClasses ?? []);
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
    destroyCodeEditorFields();
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
    grapesComponent = null,
    initialHtml = '',
    onApply = null,
    onCreated,
    onUpdated,
}) {
    if (activeModal) {
        closeModal();
    }

    const isCanvasEdit = grapesComponent == null && typeof onApply === 'function';
    const isEdit = ! isCanvasEdit && component != null && component.id != null;
    const categories = resolveComponentCategories(componentCategories);
    const defaultCategory = normalizeComponentCategory(null, categories, labels.componentsUncategorized ?? 'General');
    const categoryOptions = categories.map((category) => (
        `<option value="${escapeHtml(category)}">${escapeHtml(category)}</option>`
    )).join('');
    const modalTitle = isCanvasEdit
        ? (labels.canvasBlockEditTitle ?? 'Edit block code')
        : isEdit
            ? (labels.componentsEditTitle ?? 'Edit component')
            : (labels.componentsCodeImportTitle ?? 'Import from code');
    const modalHint = isCanvasEdit
        ? (labels.canvasBlockEditHint ?? 'Edit the markup for this block. Preview updates as you type.')
        : isEdit
            ? (labels.componentsEditHint ?? 'Update the component markup, styles, or library metadata.')
            : (labels.componentsCodeImportHint ?? 'Paste HTML from Pagedone or any Tailwind snippet. Optional CSS is extracted from <style> tags automatically.');
    const submitLabel = isCanvasEdit
        ? (labels.canvasBlockEditApply ?? 'Apply to canvas')
        : isEdit
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
                    <div class="voodbuilder-gjs-component-code-modal__meta${isCanvasEdit ? ' voodbuilder-gjs-is-hidden' : ''}">
                        <label class="voodbuilder-gjs-form-field voodbuilder-gjs-form-field--stacked">
                            <span class="voodbuilder-gjs-form-field__label">${escapeHtml(labels.componentsNamePrompt ?? 'Component name')}</span>
                            <input type="text" class="voodbuilder-gjs-input" data-voodbuilder-code-name placeholder="${escapeHtml(labels.componentsCodeImportNamePlaceholder ?? 'Hero · Pagedone')}" required />
                        </label>
                        <label class="voodbuilder-gjs-form-field voodbuilder-gjs-form-field--stacked">
                            <span class="voodbuilder-gjs-form-field__label">${escapeHtml(labels.componentsCodeImportCategory ?? 'Category')}</span>
                            <select class="voodbuilder-gjs-input voodbuilder-gjs-input--select" data-voodbuilder-code-category>
                                ${categoryOptions}
                            </select>
                        </label>
                    </div>
                    <div class="voodbuilder-gjs-component-code-modal__compatibility-top" data-voodbuilder-code-compatibility></div>
                    <div class="voodbuilder-gjs-component-code-modal__workspace">
                        <div class="voodbuilder-gjs-component-code-modal__editor">
                            <div class="voodbuilder-gjs-form-field voodbuilder-gjs-form-field--stacked">
                                <div class="voodbuilder-gjs-code-editor-toolbar">
                                    <span class="voodbuilder-gjs-form-field__label">HTML</span>
                                    <button
                                        type="button"
                                        class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost voodbuilder-gjs-code-editor-wrap-toggle"
                                        data-voodbuilder-code-wrap-toggle
                                        aria-pressed="false"
                                        title="${escapeHtml(labels.codeEditorWrapOff ?? 'Word wrap off')}"
                                    >
                                        ${escapeHtml(labels.codeEditorWrap ?? 'Word wrap')}
                                    </button>
                                </div>
                                <div class="voodbuilder-gjs-code-editor-host voodbuilder-gjs-component-code-modal__code-editor voodbuilder-gjs-component-code-modal__code-editor--html" data-voodbuilder-code-html-host></div>
                            </div>
                            <div class="voodbuilder-gjs-form-field voodbuilder-gjs-form-field--stacked">
                                <span class="voodbuilder-gjs-form-field__label">CSS <span class="voodbuilder-gjs-component-code-modal__optional">${escapeHtml(labels.componentsCodeImportCssOptional ?? 'optional')}</span></span>
                                <div class="voodbuilder-gjs-code-editor-host voodbuilder-gjs-component-code-modal__code-editor voodbuilder-gjs-component-code-modal__code-editor--css" data-voodbuilder-code-css-host></div>
                            </div>
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
        enhanceInspectorSelects(modal);

        const nameInput = modal.querySelector('[data-voodbuilder-code-name]');
        const categoryInput = modal.querySelector('[data-voodbuilder-code-category]');
        const htmlHost = modal.querySelector('[data-voodbuilder-code-html-host]');
        const cssHost = modal.querySelector('[data-voodbuilder-code-css-host]');
        const wrapToggle = modal.querySelector('[data-voodbuilder-code-wrap-toggle]');
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
        let htmlEditor = null;
        let cssEditor = null;

        const getHtmlValue = () => htmlEditor?.getValue?.() ?? '';
        const getCssValue = () => cssEditor?.getValue?.() ?? '';

        const reportCompatibility = (report) => {
            const reviewClasses = renderCompatibilityReport(compatibilityMount, report, labels, {
                onReviewClassClick: (className) => {
                    htmlEditor?.scrollToClass?.(className);
                },
            });

            syncCompatibilityReview(htmlEditor, reviewClasses);
        };

        renderCompatibilityPlaceholder(compatibilityMount, labels);

        const updateSubmitState = () => {
            const hasHtml = getHtmlValue().trim() !== '';

            if (isCanvasEdit) {
                submitButton.disabled = ! hasHtml || ! compileReady || isCompiling;

                return;
            }

            const hasName = nameInput.value.trim() !== '';
            submitButton.disabled = ! hasName || ! hasHtml || ! compileReady || isCompiling;
        };

        const setCompiling = (value) => {
            isCompiling = value;
            previewLoading.hidden = ! value;
            updateSubmitState();
        };

        const readValues = () => {
            const manualCss = getCssValue().trim();

            return {
                name: nameInput.value.trim(),
                category: normalizeComponentCategory(categoryInput?.value, categories, defaultCategory),
                html: normalizedPreviewHtml || parsePastedComponentSource(getHtmlValue()).html,
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
                syncCompatibilityReview(htmlEditor, []);
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
                reportCompatibility(payload.compatibility ?? null);
            } catch (error) {
                if (requestId !== compileRequestId) {
                    return;
                }

                compiledTailwindCss = '';
                normalizedPreviewHtml = parsePastedComponentSource(trimmed).html;
                compileReady = false;
                renderCompatibilityPlaceholder(compatibilityMount, labels);
                syncCompatibilityReview(htmlEditor, []);

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

        modal.querySelectorAll('[data-voodbuilder-code-cancel]').forEach((element) => {
            element.addEventListener('click', () => finish(null));
        });

        if (isEdit) {
            nameInput.value = String(component.name ?? '');
            categoryInput.value = normalizeComponentCategory(component.category, categories, defaultCategory);
        } else if (categoryInput) {
            categoryInput.value = defaultCategory;
        }

        const initialHtmlValue = isEdit
            ? String(component.html ?? '')
            : isCanvasEdit
                ? String(initialHtml ?? '')
                : '';
        const initialCssValue = isEdit ? String(component.css ?? '') : '';

        try {
            htmlEditor = mountCodeField({
                mount: htmlHost,
                value: formatCodeForEditor(initialHtmlValue, 'html'),
                language: 'html',
                minHeight: isCanvasEdit ? '18rem' : '14rem',
                lineWrapping: false,
                onChange: (value) => {
                    const parsed = parsePastedComponentSource(value);

                    if (! getCssValue().trim() && parsed.extractedCss) {
                        cssEditor?.setValue(formatCodeForEditor(parsed.extractedCss, 'css'));
                    }

                    scheduleCompile(value);
                },
            });

            cssEditor = mountCodeField({
                mount: cssHost,
                value: formatCodeForEditor(initialCssValue, 'css'),
                language: 'css',
                minHeight: '6rem',
                onChange: () => {
                    refreshPreview();
                },
            });
        } catch (error) {
            console.error('Voodbuilder: could not mount code editor fields.', error);
        }

        wrapToggle?.addEventListener('click', () => {
            htmlEditor?.toggleLineWrapping?.();
            const enabled = Boolean(htmlEditor?.lineWrapping);
            syncWrapToggleButton(wrapToggle, enabled);
            wrapToggle.title = enabled
                ? (labels.codeEditorWrapOn ?? 'Word wrap on')
                : (labels.codeEditorWrapOff ?? 'Word wrap off');
        });
        syncWrapToggleButton(wrapToggle, false);

        nameInput?.addEventListener('input', updateSubmitState);

        submitButton.addEventListener('click', async () => {
            const parsed = parsePastedComponentSource(getHtmlValue());
            const manualCss = getCssValue().trim();
            const values = {
                name: nameInput.value.trim(),
                category: normalizeComponentCategory(categoryInput?.value, categories, defaultCategory),
                html: normalizedPreviewHtml || parsed.html,
                css: [manualCss, compiledTailwindCss].filter(Boolean).join('\n\n') || null,
            };

            if (isCanvasEdit) {
                if (! values.html) {
                    await alertDialog({
                        message: labels.componentsCodeImportHtmlRequired ?? 'Paste some HTML markup first.',
                        labels,
                    });
                    htmlEditor?.focus?.();

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

                try {
                    onApply?.({
                        html: values.html,
                        css: values.css ?? '',
                    });
                    finish(true);
                } catch (error) {
                    submitButton.disabled = false;
                    updateSubmitState();
                    await alertDialog({
                        message: error instanceof Error ? error.message : (labels.componentsSaveError ?? 'Could not apply block changes.'),
                        labels,
                    });
                }

                return;
            }

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
                htmlEditor?.focus?.();

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
            if (isCanvasEdit) {
                htmlEditor?.focus?.();
            } else {
                nameInput.focus();
            }

            if (getHtmlValue().trim() !== '') {
                scheduleCompile(getHtmlValue());
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

export function openCanvasBlockCodeEditorDialog(options = {}) {
    return openComponentCodeDialog(options);
}
