/**
 * Styled editor dialogs — replaces native browser confirm/alert/prompt.
 */

import { enhanceInspectorSelects } from './inspector-select-ui.js';

let activeDialog = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function dismissActiveDialog(result) {
    if (! activeDialog) {
        return;
    }

    const { modal, onKeyDown, resolve } = activeDialog;

    modal.hidden = true;
    modal.remove();
    window.removeEventListener('keydown', onKeyDown, true);
    activeDialog = null;
    resolve(result);
}

function openDialog({ title, message, type, labels = {}, confirmLabel, cancelLabel, danger = false, defaultValue = '', placeholder = '' }) {
    if (activeDialog) {
        dismissActiveDialog(type === 'alert' ? undefined : false);
    }

    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'voodbuilder-gjs-modal voodbuilder-gjs-dialog';
        modal.setAttribute('role', 'presentation');

        const okLabel = confirmLabel ?? labels.dialogOk ?? 'OK';
        const dismissLabel = cancelLabel ?? labels.dialogCancel ?? 'Cancel';
        const dialogTitle = title ?? (type === 'confirm'
            ? (labels.dialogConfirmTitle ?? 'Confirm')
            : type === 'prompt'
                ? (labels.dialogPromptTitle ?? 'Input')
                : (labels.dialogAlertTitle ?? 'Notice'));

        const promptField = type === 'prompt'
            ? `<label class="voodbuilder-gjs-dialog__field">
                    <input
                        type="text"
                        class="voodbuilder-gjs-input"
                        data-voodbuilder-dialog-input
                        value="${escapeHtml(defaultValue)}"
                        placeholder="${escapeHtml(placeholder)}"
                    />
               </label>`
            : '';

        const messageBlock = message
            ? `<p class="voodbuilder-gjs-dialog__message">${escapeHtml(message)}</p>`
            : '';

        const cancelButton = type === 'alert'
            ? ''
            : `<button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost" data-voodbuilder-dialog-cancel>
                    ${escapeHtml(dismissLabel)}
               </button>`;

        const confirmClass = danger
            ? 'voodbuilder-gjs-btn voodbuilder-gjs-btn--danger'
            : 'voodbuilder-gjs-btn';

        modal.innerHTML = `
            <div class="voodbuilder-gjs-modal__backdrop" data-voodbuilder-dialog-cancel></div>
            <div class="voodbuilder-gjs-modal__panel voodbuilder-gjs-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="voodbuilder-dialog-title">
                <header class="voodbuilder-gjs-modal__head">
                    <h2 class="voodbuilder-gjs-modal__title" id="voodbuilder-dialog-title">${escapeHtml(dialogTitle)}</h2>
                    <button type="button" class="voodbuilder-gjs-modal__close" data-voodbuilder-dialog-cancel aria-label="${escapeHtml(dismissLabel)}">×</button>
                </header>
                <div class="voodbuilder-gjs-modal__body voodbuilder-gjs-dialog__body">
                    ${messageBlock}
                    ${promptField}
                </div>
                <footer class="voodbuilder-gjs-dialog__footer">
                    ${cancelButton}
                    <button type="button" class="${confirmClass}" data-voodbuilder-dialog-confirm>
                        ${escapeHtml(okLabel)}
                    </button>
                </footer>
            </div>
        `;

        document.body.appendChild(modal);
        modal.hidden = false;

        const input = modal.querySelector('[data-voodbuilder-dialog-input]');
        const confirmButton = modal.querySelector('[data-voodbuilder-dialog-confirm]');

        const finish = (result) => {
            if (! activeDialog) {
                return;
            }

            dismissActiveDialog(result);
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                finish(type === 'alert' ? undefined : type === 'prompt' ? null : false);
            }

            if (event.key === 'Enter' && type === 'prompt' && input) {
                event.preventDefault();
                finish(input.value.trim() || null);
            }
        };

        activeDialog = { modal, onKeyDown, resolve };

        modal.querySelectorAll('[data-voodbuilder-dialog-cancel]').forEach((element) => {
            element.addEventListener('click', () => {
                finish(type === 'alert' ? undefined : type === 'prompt' ? null : false);
            });
        });

        confirmButton?.addEventListener('click', () => {
            if (type === 'prompt') {
                finish(input?.value?.trim() || null);

                return;
            }

            finish(type === 'confirm' ? true : undefined);
        });

        window.addEventListener('keydown', onKeyDown, true);

        window.requestAnimationFrame(() => {
            if (input) {
                input.focus();
                input.select();
            } else {
                confirmButton?.focus();
            }
        });
    });
}

export function confirmDialog(options = {}) {
    return openDialog({
        ...options,
        type: 'confirm',
        danger: options.danger ?? false,
        confirmLabel: options.confirmLabel ?? options.labels?.dialogConfirm ?? 'Confirm',
    });
}

export function alertDialog(options = {}) {
    return openDialog({
        ...options,
        type: 'alert',
        confirmLabel: options.confirmLabel ?? options.labels?.dialogOk ?? 'OK',
    });
}

export function promptDialog(options = {}) {
    return openDialog({
        ...options,
        type: 'prompt',
        confirmLabel: options.confirmLabel ?? options.labels?.dialogConfirm ?? 'OK',
    });
}

export function choiceDialog(options = {}) {
    const {
        title,
        message,
        choices = [],
        labels = {},
    } = options;

    if (activeDialog) {
        dismissActiveDialog(null);
    }

    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'voodbuilder-gjs-modal voodbuilder-gjs-dialog voodbuilder-gjs-choice-dialog';
        modal.setAttribute('role', 'presentation');

        const dialogTitle = title ?? labels.dialogConfirmTitle ?? 'Choose an option';
        const messageBlock = message
            ? `<p class="voodbuilder-gjs-dialog__message">${escapeHtml(message)}</p>`
            : '';

        const buttons = choices.map((choice) => {
            const buttonClass = choice.danger
                ? 'voodbuilder-gjs-btn voodbuilder-gjs-btn--danger'
                : choice.primary
                    ? 'voodbuilder-gjs-btn voodbuilder-gjs-btn--primary'
                    : choice.ghost
                        ? 'voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost'
                        : 'voodbuilder-gjs-btn';

            return `<button type="button" class="${buttonClass}" data-voodbuilder-dialog-choice="${escapeHtml(choice.id)}">${escapeHtml(choice.label)}</button>`;
        }).join('');

        modal.innerHTML = `
            <div class="voodbuilder-gjs-modal__backdrop" data-voodbuilder-dialog-cancel></div>
            <div class="voodbuilder-gjs-modal__panel voodbuilder-gjs-dialog__panel" role="dialog" aria-modal="true">
                <header class="voodbuilder-gjs-modal__head">
                    <h2 class="voodbuilder-gjs-modal__title">${escapeHtml(dialogTitle)}</h2>
                    <button type="button" class="voodbuilder-gjs-modal__close" data-voodbuilder-dialog-cancel aria-label="${escapeHtml(labels.dialogCancel ?? 'Cancel')}">×</button>
                </header>
                <div class="voodbuilder-gjs-modal__body voodbuilder-gjs-dialog__body">
                    ${messageBlock}
                </div>
                <footer class="voodbuilder-gjs-dialog__footer voodbuilder-gjs-choice-dialog__footer">
                    ${buttons}
                </footer>
            </div>
        `;

        document.body.appendChild(modal);
        modal.hidden = false;

        const finish = (result) => {
            if (! activeDialog) {
                return;
            }

            dismissActiveDialog(result);
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                finish(null);
            }
        };

        activeDialog = { modal, onKeyDown, resolve };

        modal.querySelectorAll('[data-voodbuilder-dialog-cancel]').forEach((element) => {
            element.addEventListener('click', () => finish(null));
        });

        modal.querySelectorAll('[data-voodbuilder-dialog-choice]').forEach((element) => {
            element.addEventListener('click', () => {
                finish(element.getAttribute('data-voodbuilder-dialog-choice'));
            });
        });

        window.addEventListener('keydown', onKeyDown, true);

        window.requestAnimationFrame(() => {
            modal.querySelector('[data-voodbuilder-dialog-choice]')?.focus();
        });
    });
}

export function componentMetaDialog(options = {}) {
    const {
        title,
        message,
        labels = {},
        categories = [],
        defaultCategory = 'General',
        defaultName = '',
        namePlaceholder = '',
        nameLabel,
        categoryLabel,
        confirmLabel,
    } = options;

    if (activeDialog) {
        dismissActiveDialog(false);
    }

    const dismissLabel = labels.dialogCancel ?? 'Cancel';
    const okLabel = confirmLabel ?? labels.dialogConfirm ?? 'OK';
    const dialogTitle = title ?? (labels.componentsSave ?? 'Save component');
    const categoryOptions = (categories.length > 0 ? categories : [defaultCategory])
        .map((category) => {
            const value = String(category);
            const selected = value === defaultCategory ? ' selected' : '';

            return `<option value="${escapeHtml(value)}"${selected}>${escapeHtml(value)}</option>`;
        })
        .join('');

    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'voodbuilder-gjs-modal voodbuilder-gjs-dialog voodbuilder-gjs-component-meta-dialog';
        modal.setAttribute('role', 'presentation');

        const messageBlock = message
            ? `<p class="voodbuilder-gjs-dialog__message">${escapeHtml(message)}</p>`
            : '';

        modal.innerHTML = `
            <div class="voodbuilder-gjs-modal__backdrop" data-voodbuilder-dialog-cancel></div>
            <div class="voodbuilder-gjs-modal__panel voodbuilder-gjs-dialog__panel" role="dialog" aria-modal="true" aria-labelledby="voodbuilder-component-meta-title">
                <header class="voodbuilder-gjs-modal__head">
                    <h2 class="voodbuilder-gjs-modal__title" id="voodbuilder-component-meta-title">${escapeHtml(dialogTitle)}</h2>
                    <button type="button" class="voodbuilder-gjs-modal__close" data-voodbuilder-dialog-cancel aria-label="${escapeHtml(dismissLabel)}">×</button>
                </header>
                <div class="voodbuilder-gjs-modal__body voodbuilder-gjs-dialog__body">
                    ${messageBlock}
                    <div class="voodbuilder-gjs-component-code-modal__meta">
                        <label class="voodbuilder-gjs-form-field voodbuilder-gjs-form-field--stacked">
                            <span class="voodbuilder-gjs-form-field__label">${escapeHtml(nameLabel ?? labels.componentsNamePrompt ?? 'Component name')}</span>
                            <input
                                type="text"
                                class="voodbuilder-gjs-input"
                                data-voodbuilder-dialog-name
                                value="${escapeHtml(defaultName)}"
                                placeholder="${escapeHtml(namePlaceholder)}"
                                required
                            />
                        </label>
                        <label class="voodbuilder-gjs-form-field voodbuilder-gjs-form-field--stacked">
                            <span class="voodbuilder-gjs-form-field__label">${escapeHtml(categoryLabel ?? labels.componentsCodeImportCategory ?? 'Category')}</span>
                            <select class="voodbuilder-gjs-input voodbuilder-gjs-input--select" data-voodbuilder-dialog-category>
                                ${categoryOptions}
                            </select>
                        </label>
                    </div>
                </div>
                <footer class="voodbuilder-gjs-dialog__footer">
                    <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost" data-voodbuilder-dialog-cancel>
                        ${escapeHtml(dismissLabel)}
                    </button>
                    <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--primary" data-voodbuilder-dialog-confirm>
                        ${escapeHtml(okLabel)}
                    </button>
                </footer>
            </div>
        `;

        document.body.appendChild(modal);
        modal.hidden = false;
        enhanceInspectorSelects(modal);

        const nameInput = modal.querySelector('[data-voodbuilder-dialog-name]');
        const categoryInput = modal.querySelector('[data-voodbuilder-dialog-category]');
        const confirmButton = modal.querySelector('[data-voodbuilder-dialog-confirm]');

        const finish = (result) => {
            if (! activeDialog) {
                return;
            }

            dismissActiveDialog(result);
        };

        const submit = () => {
            const name = nameInput?.value?.trim() ?? '';

            if (name === '') {
                nameInput?.focus();

                return;
            }

            finish({
                name,
                category: categoryInput?.value?.trim() || defaultCategory,
            });
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                finish(null);
            }

            if (event.key === 'Enter' && nameInput) {
                event.preventDefault();
                submit();
            }
        };

        activeDialog = { modal, onKeyDown, resolve };

        modal.querySelectorAll('[data-voodbuilder-dialog-cancel]').forEach((element) => {
            element.addEventListener('click', () => finish(null));
        });

        confirmButton?.addEventListener('click', submit);

        window.addEventListener('keydown', onKeyDown, true);

        window.requestAnimationFrame(() => {
            nameInput?.focus();
            nameInput?.select();
        });
    });
}
