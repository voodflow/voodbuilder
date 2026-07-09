/**
 * Popup manager — create and configure popups from the page editor top bar.
 */

import { alertDialog, confirmDialog } from './editor-dialog.js';
import { editorApiHeaders } from './editor-api.js';
import { lucideIcon } from './editor-icons.js';
import {
    createCheckboxField,
    createFormSection,
    createSelectField,
    createTextField,
} from './editor-form-ui.js';
import { previewPopup } from '../popups-runtime.js';

const PAGE_PATH_CUSTOM = '__custom__';

function defaultRules() {
    return {
        trigger: { type: 'delay', delay_seconds: 3, scroll_percent: 50, click_selector: '' },
        frequency: { mode: 'session', days: 7 },
        targeting: { logged_in: 'any', page_path: '' },
        display: { width: 'md', overlay: true, close_on_overlay: true, close_on_escape: true },
    };
}

function mountRulesForm(container, labels, pagePathOptions = []) {
    const root = document.createElement('div');
    root.className = 'voodbuilder-gjs-popup-form';

    const basics = createFormSection();
    basics.section.classList.add('voodbuilder-gjs-popup-form__section');
    const nameField = createTextField({
        label: labels.popupsFieldName ?? 'Name',
        name: 'name',
        required: true,
    });
    nameField.input.maxLength = 120;
    const priorityField = createTextField({
        label: labels.popupsFieldPriority ?? 'Priority',
        name: 'priority',
        type: 'number',
        value: '0',
        min: 0,
        max: 999,
    });
    const enabledField = createCheckboxField({
        label: labels.popupsFieldEnabled ?? 'Enabled',
        name: 'enabled',
        checked: true,
    });
    enabledField.field.classList.add('voodbuilder-gjs-popup-form__enabled');
    basics.fields.append(nameField.field, enabledField.field, priorityField.field);
    root.appendChild(basics.section);

    const timing = createFormSection(labels.popupsSectionTrigger ?? 'When to show');
    timing.section.classList.add('voodbuilder-gjs-popup-form__section');
    timing.fields.append(
        createSelectField({
            label: labels.popupsFieldTriggerType ?? 'Trigger',
            name: 'trigger_type',
            value: 'delay',
            options: [
                { value: 'load', label: labels.popupsTriggerLoad ?? 'On page load' },
                { value: 'delay', label: labels.popupsTriggerDelay ?? 'After delay' },
                { value: 'scroll', label: labels.popupsTriggerScroll ?? 'On scroll depth' },
                { value: 'exit_intent', label: labels.popupsTriggerExit ?? 'On exit intent' },
                { value: 'click', label: labels.popupsTriggerClick ?? 'On element click' },
            ],
        }),
        createTextField({
            label: labels.popupsFieldDelay ?? 'Delay (seconds)',
            name: 'delay_seconds',
            type: 'number',
            value: '3',
            min: 0,
            max: 600,
        }).field,
    );
    timing.fields.lastElementChild?.setAttribute('data-popup-field', 'delay_seconds');

    const scrollField = createTextField({
        label: labels.popupsFieldScroll ?? 'Scroll depth (%)',
        name: 'scroll_percent',
        type: 'number',
        value: '50',
        min: 1,
        max: 100,
    });
    scrollField.field.setAttribute('data-popup-field', 'scroll_percent');
    scrollField.field.hidden = true;

    const clickField = createTextField({
        label: labels.popupsFieldClickSelector ?? 'CSS selector',
        name: 'click_selector',
        placeholder: '#open-promo',
    });
    clickField.field.setAttribute('data-popup-field', 'click_selector');
    clickField.field.hidden = true;
    timing.fields.append(scrollField.field, clickField.field);

    const frequency = createFormSection(labels.popupsSectionFrequency ?? 'How often');
    frequency.section.classList.add('voodbuilder-gjs-popup-form__section');
    frequency.fields.append(
        createSelectField({
            label: labels.popupsFieldFrequency ?? 'Frequency',
            name: 'frequency_mode',
            value: 'session',
            options: [
                { value: 'always', label: labels.popupsFrequencyAlways ?? 'Every visit' },
                { value: 'once', label: labels.popupsFrequencyOnce ?? 'Once ever' },
                { value: 'session', label: labels.popupsFrequencySession ?? 'Once per session' },
                { value: 'days', label: labels.popupsFrequencyDays ?? 'Every N days' },
            ],
        }),
    );
    const frequencyDaysField = createTextField({
        label: labels.popupsFieldFrequencyDays ?? 'Days between views',
        name: 'frequency_days',
        type: 'number',
        value: '7',
        min: 1,
        max: 365,
    });
    frequencyDaysField.field.setAttribute('data-popup-field', 'frequency_days');
    frequencyDaysField.field.hidden = true;
    frequency.fields.append(frequencyDaysField.field);

    const targeting = createFormSection(labels.popupsSectionTargeting ?? 'Who / where');
    targeting.section.classList.add('voodbuilder-gjs-popup-form__section', 'voodbuilder-gjs-popup-form__section--full');
    targeting.fields.append(
        createSelectField({
            label: labels.popupsFieldAudience ?? 'Audience',
            name: 'logged_in',
            value: 'any',
            options: [
                { value: 'any', label: labels.popupsTargetingAny ?? 'Everyone' },
                { value: 'yes', label: labels.popupsTargetingLoggedIn ?? 'Logged-in users only' },
                { value: 'no', label: labels.popupsTargetingLoggedOut ?? 'Guests only' },
            ],
        }),
    );

    const pathOptions = [
        ...pagePathOptions,
        { value: PAGE_PATH_CUSTOM, label: labels.popupsPagePathCustom ?? 'Custom path…' },
    ];
    const pagePathSelect = createSelectField({
        label: labels.popupsFieldPagePath ?? 'Page path contains',
        name: 'page_path_select',
        value: '',
        options: pathOptions,
    });
    pagePathSelect.field.setAttribute('data-popup-field', 'page_path_select');
    targeting.fields.append(pagePathSelect.field);

    const pagePathCustom = createTextField({
        label: labels.popupsPagePathCustom ?? 'Custom path',
        name: 'page_path_custom',
        placeholder: '/blog',
    });
    pagePathCustom.field.setAttribute('data-popup-field', 'page_path_custom');
    pagePathCustom.field.hidden = true;
    targeting.fields.append(pagePathCustom.field);

    const display = createFormSection(labels.popupsSectionDisplay ?? 'Appearance');
    display.section.classList.add('voodbuilder-gjs-popup-form__section', 'voodbuilder-gjs-popup-form__section--full');
    display.fields.append(
        createSelectField({
            label: labels.popupsFieldWidth ?? 'Modal width',
            name: 'display_width',
            value: 'md',
            options: [
                { value: 'sm', label: labels.popupsWidthSm ?? 'Small' },
                { value: 'md', label: labels.popupsWidthMd ?? 'Medium' },
                { value: 'lg', label: labels.popupsWidthLg ?? 'Large' },
                { value: 'xl', label: labels.popupsWidthXl ?? 'Extra large' },
            ],
        }),
        createCheckboxField({
            label: labels.popupsFieldOverlay ?? 'Dim background',
            name: 'display_overlay',
            checked: true,
        }).field,
        createCheckboxField({
            label: labels.popupsFieldCloseOverlay ?? 'Close on overlay click',
            name: 'close_on_overlay',
            checked: true,
        }).field,
        createCheckboxField({
            label: labels.popupsFieldCloseEscape ?? 'Close on Escape',
            name: 'close_on_escape',
            checked: true,
        }).field,
    );

    const grid = document.createElement('div');
    grid.className = 'voodbuilder-gjs-popup-form__grid';
    grid.append(timing.section, frequency.section);
    root.append(grid, targeting.section, display.section);
    container.appendChild(root);

    return root;
}

function readRulesForm(form) {
    const data = new FormData(form);
    const pagePathSelect = String(data.get('page_path_select') ?? '');
    const pagePath = pagePathSelect === PAGE_PATH_CUSTOM
        ? String(data.get('page_path_custom') ?? '').trim()
        : pagePathSelect.trim();

    return {
        name: String(data.get('name') ?? '').trim(),
        enabled: data.get('enabled') != null,
        priority: Number.parseInt(String(data.get('priority') ?? '0'), 10) || 0,
        rules: {
            trigger: {
                type: String(data.get('trigger_type') ?? 'delay'),
                delay_seconds: Number.parseInt(String(data.get('delay_seconds') ?? '3'), 10) || 0,
                scroll_percent: Number.parseInt(String(data.get('scroll_percent') ?? '50'), 10) || 50,
                click_selector: String(data.get('click_selector') ?? '').trim(),
            },
            frequency: {
                mode: String(data.get('frequency_mode') ?? 'session'),
                days: Number.parseInt(String(data.get('frequency_days') ?? '7'), 10) || 7,
            },
            targeting: {
                logged_in: String(data.get('logged_in') ?? 'any'),
                page_path: pagePath,
            },
            display: {
                width: String(data.get('display_width') ?? 'md'),
                overlay: data.get('display_overlay') != null,
                close_on_overlay: data.get('close_on_overlay') != null,
                close_on_escape: data.get('close_on_escape') != null,
            },
        },
    };
}

function bindRulesFormVisibility(form) {
    const triggerType = form.querySelector('[name="trigger_type"]');
    const frequencyMode = form.querySelector('[name="frequency_mode"]');
    const pagePathSelect = form.querySelector('[name="page_path_select"]');

    const sync = () => {
        const type = triggerType?.value ?? 'delay';
        form.querySelector('[data-popup-field="delay_seconds"]')?.toggleAttribute('hidden', type !== 'delay');
        form.querySelector('[data-popup-field="scroll_percent"]')?.toggleAttribute('hidden', type !== 'scroll');
        form.querySelector('[data-popup-field="click_selector"]')?.toggleAttribute('hidden', type !== 'click');
        form.querySelector('[data-popup-field="frequency_days"]')?.toggleAttribute('hidden', frequencyMode?.value !== 'days');
        form.querySelector('[data-popup-field="page_path_custom"]')?.toggleAttribute('hidden', pagePathSelect?.value !== PAGE_PATH_CUSTOM);
    };

    triggerType?.addEventListener('change', sync);
    frequencyMode?.addEventListener('change', sync);
    pagePathSelect?.addEventListener('change', sync);
    sync();
}

function resolvePagePathSelectValue(pagePath, pagePathOptions) {
    const normalized = String(pagePath ?? '').trim();
    const known = pagePathOptions.some((option) => option.value === normalized);

    if (normalized === '' || known) {
        return { select: normalized, custom: '' };
    }

    return { select: PAGE_PATH_CUSTOM, custom: normalized };
}

function fillRulesForm(form, popup, pagePathOptions = []) {
    const rules = popup.rules ?? defaultRules();
    form.querySelector('[name="name"]').value = popup.name ?? '';
    form.querySelector('[name="enabled"]').checked = popup.enabled !== false;
    form.querySelector('[name="priority"]').value = String(popup.priority ?? 0);
    form.querySelector('[name="trigger_type"]').value = rules.trigger?.type ?? 'delay';
    form.querySelector('[name="delay_seconds"]').value = String(rules.trigger?.delay_seconds ?? 3);
    form.querySelector('[name="scroll_percent"]').value = String(rules.trigger?.scroll_percent ?? 50);
    form.querySelector('[name="click_selector"]').value = rules.trigger?.click_selector ?? '';
    form.querySelector('[name="frequency_mode"]').value = rules.frequency?.mode ?? 'session';
    form.querySelector('[name="frequency_days"]').value = String(rules.frequency?.days ?? 7);
    form.querySelector('[name="logged_in"]').value = rules.targeting?.logged_in ?? 'any';

    const pagePath = resolvePagePathSelectValue(rules.targeting?.page_path ?? '', pagePathOptions);
    form.querySelector('[name="page_path_select"]').value = pagePath.select;
    form.querySelector('[name="page_path_custom"]').value = pagePath.custom;

    form.querySelector('[name="display_width"]').value = rules.display?.width ?? 'md';
    form.querySelector('[name="display_overlay"]').checked = rules.display?.overlay !== false;
    form.querySelector('[name="close_on_overlay"]').checked = rules.display?.close_on_overlay !== false;
    form.querySelector('[name="close_on_escape"]').checked = rules.display?.close_on_escape !== false;
    bindRulesFormVisibility(form);
}

async function loadPagePathOptions(url) {
    if (! url) {
        return [{ value: '', label: 'All pages', group: 'General' }];
    }

    try {
        const response = await fetch(url.replace(/\/$/, ''), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            throw new Error('paths failed');
        }

        return (await response.json()).paths ?? [];
    } catch {
        return [{ value: '', label: 'All pages' }];
    }
}

async function openPopupFormDialog({ title, labels, initial = null, pagePathOptions = [] }) {
    return new Promise((resolve) => {
        const modal = document.createElement('div');
        modal.className = 'voodbuilder-gjs-modal voodbuilder-gjs-modal--popup-form';
        modal.innerHTML = `
            <div class="voodbuilder-gjs-modal__backdrop" data-popup-form-close></div>
            <div class="voodbuilder-gjs-modal__panel voodbuilder-gjs-modal__panel--popup-form" role="dialog" aria-modal="true">
                <header class="voodbuilder-gjs-modal__head">
                    <h2 class="voodbuilder-gjs-modal__title">${title}</h2>
                    <button type="button" class="voodbuilder-gjs-modal__close" data-popup-form-close aria-label="Close">×</button>
                </header>
                <form class="voodbuilder-gjs-popup-form-shell" data-popup-form>
                    <div class="voodbuilder-gjs-popup-form-shell__body" data-popup-form-body></div>
                    <footer class="voodbuilder-gjs-popup-form-shell__footer">
                        <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost" data-popup-form-close>${labels.dialogCancel ?? 'Cancel'}</button>
                        <button type="submit" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--primary">${labels.dialogConfirm ?? 'Save'}</button>
                    </footer>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        const form = modal.querySelector('[data-popup-form]');
        const body = modal.querySelector('[data-popup-form-body]');
        const panel = modal.querySelector('.voodbuilder-gjs-modal__panel');

        mountRulesForm(body, labels, pagePathOptions);
        bindRulesFormVisibility(form);

        if (initial) {
            fillRulesForm(form, initial, pagePathOptions);
        }

        const close = (value = null) => {
            document.removeEventListener('keydown', onKeyDown);
            modal.remove();
            resolve(value);
        };

        const onKeyDown = (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                close(null);
            }
        };

        document.addEventListener('keydown', onKeyDown);

        modal.querySelectorAll('[data-popup-form-close]').forEach((element) => {
            element.addEventListener('click', () => close(null));
        });

        panel?.addEventListener('click', (event) => {
            event.stopPropagation();
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const payload = readRulesForm(form);

            if (! payload.name) {
                return;
            }

            close(payload);
        });
    });
}

export function registerPopupsUi(editor, options = {}) {
    const { popupsUrl, popupsPagePathsUrl, csrf, labels = {}, toolbarMount, popupMode = false } = options;

    if (! popupsUrl || ! toolbarMount || popupMode) {
        return;
    }

    const baseUrl = popupsUrl.replace(/\/$/, '');
    let pagePathOptionsPromise = loadPagePathOptions(popupsPagePathsUrl);

    async function openPopupForm(initial = null, title) {
        const pagePathOptions = await pagePathOptionsPromise;

        return openPopupFormDialog({
            title,
            labels,
            initial,
            pagePathOptions,
        });
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'voodbuilder-gjs-topbar__btn voodbuilder-gjs-topbar__btn--ghost';
    button.title = labels.popupsTitle ?? 'Popups';
    button.setAttribute('aria-label', labels.popupsTitle ?? 'Popups');
    button.innerHTML = lucideIcon('message-square', 18);
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
        <div class="voodbuilder-gjs-modal__backdrop" data-voodbuilder-popups-close></div>
        <div class="voodbuilder-gjs-modal__panel" role="dialog" aria-modal="true">
            <header class="voodbuilder-gjs-modal__head">
                <h2 class="voodbuilder-gjs-modal__title">${labels.popupsTitle ?? 'Popups'}</h2>
                <button type="button" class="voodbuilder-gjs-modal__close" data-voodbuilder-popups-close aria-label="Close">×</button>
            </header>
            <div class="voodbuilder-gjs-modal__body">
                <p class="voodbuilder-gjs-hint">${labels.popupsHint ?? 'Create and manage site popups without leaving the editor.'}</p>
                <div class="voodbuilder-gjs-dynamic-panel__actions">
                    <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--primary" data-voodbuilder-popup-create>
                        ${labels.popupsCreate ?? 'Add popup'}
                    </button>
                </div>
                <div data-voodbuilder-popups-list></div>
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const listEl = modal.querySelector('[data-voodbuilder-popups-list]');
    const createBtn = modal.querySelector('[data-voodbuilder-popup-create]');

    modal.querySelectorAll('[data-voodbuilder-popups-close]').forEach((element) => {
        element.addEventListener('click', () => {
            modal.hidden = true;
        });
    });

    async function loadPopups() {
        const response = await fetch(baseUrl, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            throw new Error('load failed');
        }

        return (await response.json()).popups ?? [];
    }

    function renderList(popups) {
        if (popups.length === 0) {
            listEl.innerHTML = `<p class="voodbuilder-gjs-hint">${labels.popupsEmpty ?? 'No popups yet.'}</p>`;

            return;
        }

        listEl.innerHTML = '';

        for (const popup of popups) {
            const row = document.createElement('div');
            row.className = 'voodbuilder-gjs-revision-row';

            const meta = document.createElement('div');
            meta.className = 'voodbuilder-gjs-revision-row__meta';
            const status = popup.enabled ? (labels.popupsEnabled ?? 'Enabled') : (labels.popupsDisabled ?? 'Disabled');
            meta.textContent = `${popup.name ?? ''} · ${status}`;

            const actions = document.createElement('div');
            actions.className = 'voodbuilder-gjs-revision-row__actions';

            const editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.className = 'voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost';
            editBtn.textContent = labels.popupsEditRules ?? 'Rules';
            editBtn.addEventListener('click', async () => {
                modal.hidden = true;
                const payload = await openPopupForm(popup, labels.popupsEditTitle ?? 'Edit popup');

                if (! payload) {
                    await openModal();

                    return;
                }

                const response = await fetch(`${baseUrl}/${popup.id}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: editorApiHeaders(csrf, { json: true }),
                    body: JSON.stringify(payload),
                });

                if (! response.ok) {
                    await alertDialog({ message: labels.popupsSaveError ?? 'Could not save popup.', labels });
                    await openModal();

                    return;
                }

                await openModal();
            });

            const testBtn = document.createElement('button');
            testBtn.type = 'button';
            testBtn.className = 'voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost';
            testBtn.textContent = labels.popupsTest ?? 'Test popup';
            testBtn.addEventListener('click', () => {
                modal.hidden = true;
                previewPopup(popup);
            });

            const designBtn = document.createElement('a');
            designBtn.className = 'voodbuilder-gjs-btn voodbuilder-gjs-btn--primary';
            designBtn.textContent = labels.popupsOpenEditor ?? 'Design';
            designBtn.href = popup.editor_url ?? '#';
            designBtn.target = '_blank';
            designBtn.rel = 'noopener noreferrer';

            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost';
            deleteBtn.textContent = labels.popupsDelete ?? 'Delete';
            deleteBtn.addEventListener('click', async () => {
                const confirmed = await confirmDialog({
                    title: labels.dialogConfirmTitle ?? 'Confirm',
                    message: labels.popupsDeleteConfirm ?? 'Delete this popup?',
                    labels,
                    danger: true,
                    confirmLabel: labels.popupsDelete ?? 'Delete',
                });

                if (! confirmed) {
                    return;
                }

                const response = await fetch(`${baseUrl}/${popup.id}`, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: editorApiHeaders(csrf),
                });

                if (! response.ok) {
                    await alertDialog({ message: labels.popupsDeleteError ?? 'Could not delete popup.', labels });

                    return;
                }

                await openModal();
            });

            actions.append(editBtn, testBtn, designBtn, deleteBtn);
            row.append(meta, actions);
            listEl.appendChild(row);
        }
    }

    async function openModal() {
        modal.hidden = false;

        try {
            renderList(await loadPopups());
        } catch {
            listEl.innerHTML = `<p class="voodbuilder-gjs-hint">${labels.popupsLoadError ?? 'Could not load popups.'}</p>`;
        }
    }

    createBtn?.addEventListener('click', async () => {
        modal.hidden = true;
        const payload = await openPopupForm(null, labels.popupsCreateTitle ?? 'Add popup');

        if (! payload) {
            await openModal();

            return;
        }

        const response = await fetch(baseUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: editorApiHeaders(csrf, { json: true }),
            body: JSON.stringify(payload),
        });

        if (! response.ok) {
            await alertDialog({ message: labels.popupsSaveError ?? 'Could not save popup.', labels });
            await openModal();

            return;
        }

        const created = await response.json();

        if (created?.popup?.editor_url) {
            window.open(created.popup.editor_url, '_blank', 'noopener,noreferrer');
        }

        await openModal();
    });
}
