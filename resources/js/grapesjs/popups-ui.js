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
    createTextareaField,
    createTextField,
} from './editor-form-ui.js';
import { enhanceInspectorSelects } from './inspector-select-ui.js';
import { previewPopup } from '../popups-runtime.js';

const PAGE_PATH_CUSTOM = '__custom__';

function defaultRules() {
    return {
        trigger: { type: 'delay', delay_seconds: 3, scroll_percent: 50, click_selector: '' },
        frequency: { mode: 'session', days: 7 },
        schedule: {
            start_at: '',
            end_at: '',
            weekly_days: [],
            weekly_day: '',
            timezone: '',
            weekly_start_time: '',
            weekly_end_time: '',
        },
        targeting: { logged_in: 'any', page_path: '' },
        display: { width: 'md', overlay: true, close_on_overlay: true, close_on_escape: true },
    };
}

function normalizeWeeklyDays(schedule = {}) {
    const fromArray = Array.isArray(schedule.weekly_days) ? schedule.weekly_days : null;

    if (fromArray && fromArray.length > 0) {
        return [...new Set(fromArray.map((day) => String(day ?? '').trim().toLowerCase()).filter(Boolean))];
    }

    const legacy = String(schedule.weekly_day ?? '').trim().toLowerCase();

    return legacy ? [legacy] : [];
}

function replaceTokens(template, tokens = {}) {
    return Object.entries(tokens).reduce(
        (carry, [key, value]) => carry.replaceAll(`:${key}`, String(value ?? '')),
        String(template ?? ''),
    );
}

function formatDateTime(value) {
    const normalized = String(value ?? '').trim();

    if (! normalized) {
        return '';
    }

    const parsed = new Date(normalized);

    if (Number.isNaN(parsed.getTime())) {
        return normalized;
    }

    return new Intl.DateTimeFormat(undefined, {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(parsed);
}

function scheduleDayLabel(value, labels) {
    return ({
        monday: labels.popupsScheduleMonday ?? 'Monday',
        tuesday: labels.popupsScheduleTuesday ?? 'Tuesday',
        wednesday: labels.popupsScheduleWednesday ?? 'Wednesday',
        thursday: labels.popupsScheduleThursday ?? 'Thursday',
        friday: labels.popupsScheduleFriday ?? 'Friday',
        saturday: labels.popupsScheduleSaturday ?? 'Saturday',
        sunday: labels.popupsScheduleSunday ?? 'Sunday',
    })[String(value ?? '').trim().toLowerCase()] ?? String(value ?? '').trim();
}

function summarizePopup(popup, labels) {
    const rules = popup.rules ?? defaultRules();
    const chips = [];
    let status = labels.popupsStatusActive ?? 'Active';
    let statusTone = 'active';

    if (popup.enabled === false) {
        status = labels.popupsStatusDisabled ?? 'Disabled';
        statusTone = 'disabled';
    } else if (popup.paused) {
        status = labels.popupsStatusPaused ?? 'Paused';
        statusTone = 'paused';
    }

    const frequencyMode = rules.frequency?.mode ?? 'session';

    if (frequencyMode === 'always') {
        chips.push(labels.popupsSummaryAlways ?? 'Every visit');
    } else if (frequencyMode === 'once') {
        chips.push(labels.popupsSummaryOnce ?? 'Once ever');
    } else if (frequencyMode === 'days') {
        chips.push(replaceTokens(labels.popupsSummaryDays ?? 'Every :days days', {
            days: rules.frequency?.days ?? 7,
        }));
    } else {
        chips.push(labels.popupsSummarySession ?? 'Once per session');
    }

    const locale = String(popup.locale ?? '').trim();

    if (locale) {
        const localeOption = Array.isArray(labels.popupsLocaleOptions)
            ? labels.popupsLocaleOptions.find((option) => option.value === locale)
            : null;
        chips.push(localeOption?.label ?? locale.toUpperCase());
    }

    const schedule = rules.schedule ?? {};
    const startAt = formatDateTime(schedule.start_at);
    const endAt = formatDateTime(schedule.end_at);

    if (startAt && endAt) {
        chips.push(replaceTokens(labels.popupsSummaryDateRange ?? 'From :start to :end', { start: startAt, end: endAt }));
    } else if (startAt) {
        chips.push(replaceTokens(labels.popupsSummaryStart ?? 'From :start', { start: startAt }));
    } else if (endAt) {
        chips.push(replaceTokens(labels.popupsSummaryEnd ?? 'Until :end', { end: endAt }));
    }

    const weeklyDays = normalizeWeeklyDays(schedule);
    const timezone = String(schedule.timezone ?? '').trim();

    if (weeklyDays.length > 0) {
        const day = weeklyDays.map((value) => scheduleDayLabel(value, labels)).join(', ');
        chips.push(replaceTokens(labels.popupsSummaryWeeklyDay ?? 'Every :day', { day }));
    }

    if (timezone) {
        chips.push(replaceTokens(labels.popupsSummaryTimezone ?? 'Timezone :tz', { tz: timezone }));
    }

    const audience = String(rules.targeting?.logged_in ?? 'any');

    chips.push(
        audience === 'yes'
            ? (labels.popupsSummaryAudienceYes ?? 'Logged-in users')
            : (audience === 'no'
                ? (labels.popupsSummaryAudienceNo ?? 'Guests')
                : (labels.popupsSummaryAudienceAny ?? 'Everyone')),
    );

    const pagePath = String(rules.targeting?.page_path ?? '').trim();

    if (pagePath !== '' && pagePath !== '/') {
        chips.push(replaceTokens(labels.popupsSummaryPagePath ?? 'Pages matching :path', { path: pagePath }));
    } else {
        chips.push(labels.popupsSummaryAllPages ?? 'All pages');
    }

    return {
        status,
        statusTone,
        chips: chips.filter(Boolean),
    };
}

function renderPopupSummary(popup, labels) {
    const summary = summarizePopup(popup, labels);
    const wrap = document.createElement('div');
    wrap.className = 'voodbuilder-gjs-popup-card__summary';

    const status = document.createElement('span');
    status.className = `voodbuilder-gjs-popup-chip voodbuilder-gjs-popup-chip--${summary.statusTone}`;
    status.textContent = summary.status;
    wrap.appendChild(status);

    for (const chipText of summary.chips) {
        const chip = document.createElement('span');
        chip.className = 'voodbuilder-gjs-popup-chip';
        chip.textContent = chipText;
        wrap.appendChild(chip);
    }

    return wrap;
}

function styleCompactField(field) {
    field.classList.add('voodbuilder-gjs-popup-field');
}

function styleInlineToggle(field) {
    styleCompactField(field);
    field.classList.add('voodbuilder-gjs-popup-field--toggle');
}

function createDayChip(value, dayLabel, shortLabel) {
    const field = createCheckboxField({
        label: shortLabel ?? dayLabel,
        name: `weekly_days_${value}`,
        checked: false,
    });
    const input = field.querySelector('input');
    const label = field.querySelector('.voodbuilder-gjs-form-label--checkbox');

    field.className = 'voodbuilder-gjs-popup-day-chip';
    input?.setAttribute('value', value);
    input?.setAttribute('data-weekly-day', value);
    label?.setAttribute('title', dayLabel);

    return field;
}

function createPanelCard() {
    const { section, fields } = createFormSection(null);
    section.className = 'voodbuilder-gjs-popup-panel';
    fields.className = 'voodbuilder-gjs-popup-panel__fields';

    return { section, fields };
}

function createInfoHint(text, { tone = 'muted' } = {}) {
    const hint = document.createElement('div');
    hint.className = `voodbuilder-gjs-popup-hint voodbuilder-gjs-popup-hint--${tone}`;
    hint.innerHTML = `<span class="voodbuilder-gjs-popup-hint__icon">${lucideIcon('info', 14)}</span>`;
    const body = document.createElement('p');
    body.className = 'voodbuilder-gjs-popup-hint__text';
    body.textContent = text;
    hint.appendChild(body);

    return hint;
}

function createTabButton(id, label, icon) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'voodbuilder-gjs-popup-tabs__btn';
    button.setAttribute('data-popup-tab', id);
    button.setAttribute('role', 'tab');
    button.setAttribute('aria-selected', 'false');
    button.innerHTML = `${lucideIcon(icon, 15)}<span>${label}</span>`;

    return button;
}

function createTabPanel(id) {
    const panel = document.createElement('div');
    panel.className = 'voodbuilder-gjs-popup-tab-panel';
    panel.setAttribute('data-popup-tab-panel', id);
    panel.setAttribute('role', 'tabpanel');
    panel.hidden = true;

    return panel;
}

function shortDayLabel(fullLabel) {
    const value = String(fullLabel ?? '').trim();

    if (value.length <= 3) {
        return value;
    }

    return value.slice(0, 3);
}

function mountRulesForm(container, labels, pagePathOptions = []) {
    const root = document.createElement('div');
    root.className = 'voodbuilder-gjs-popup-form';

    const tabs = document.createElement('div');
    tabs.className = 'voodbuilder-gjs-popup-tabs';
    tabs.setAttribute('role', 'tablist');

    const panels = document.createElement('div');
    panels.className = 'voodbuilder-gjs-popup-tab-panels';

    const tabDefs = [
        { id: 'general', label: labels.popupsTabGeneral ?? 'General', icon: 'sliders-horizontal' },
        { id: 'triggers', label: labels.popupsTabTriggers ?? 'Triggers', icon: 'zap' },
        { id: 'targeting', label: labels.popupsTabTargeting ?? 'Targeting', icon: 'crosshair' },
        { id: 'schedule', label: labels.popupsTabSchedule ?? 'Schedule', icon: 'calendar' },
        { id: 'appearance', label: labels.popupsTabAppearance ?? 'Appearance', icon: 'layers' },
    ];

    const panelMap = {};

    for (const tab of tabDefs) {
        tabs.appendChild(createTabButton(tab.id, tab.label, tab.icon));
        const panel = createTabPanel(tab.id);
        panelMap[tab.id] = panel;
        panels.appendChild(panel);
    }

    const activateTab = (id) => {
        tabs.querySelectorAll('[data-popup-tab]').forEach((button) => {
            const active = button.getAttribute('data-popup-tab') === id;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.querySelectorAll('[data-popup-tab-panel]').forEach((panel) => {
            panel.hidden = panel.getAttribute('data-popup-tab-panel') !== id;
        });
    };

    tabs.addEventListener('click', (event) => {
        const button = event.target.closest('[data-popup-tab]');

        if (! button) {
            return;
        }

        activateTab(button.getAttribute('data-popup-tab'));
    });

    // —— General ——
    const generalCard = createPanelCard();
    const generalGrid = document.createElement('div');
    generalGrid.className = 'voodbuilder-gjs-popup-grid voodbuilder-gjs-popup-grid--general';

    const nameField = createTextField({
        label: labels.popupsFieldName ?? 'Name',
        name: 'name',
        required: true,
    });
    nameField.input.maxLength = 120;
    nameField.field.classList.add('voodbuilder-gjs-popup-grid__name');
    styleCompactField(nameField.field);

    const statusBox = document.createElement('div');
    statusBox.className = 'voodbuilder-gjs-popup-status-box';
    const enabledField = createCheckboxField({
        label: labels.popupsFieldEnabled ?? 'Enabled',
        name: 'enabled',
        checked: true,
    });
    const pausedField = createCheckboxField({
        label: labels.popupsFieldPaused ?? 'Paused',
        name: 'paused',
        checked: false,
    });
    styleInlineToggle(enabledField);
    styleInlineToggle(pausedField);
    statusBox.append(enabledField, pausedField);

    const priorityField = createTextField({
        label: labels.popupsFieldPriority ?? 'Priority',
        name: 'priority',
        type: 'number',
        value: '0',
        min: 0,
        max: 999,
    });
    priorityField.field.classList.add('voodbuilder-gjs-popup-grid__priority');
    styleCompactField(priorityField.field);

    generalGrid.append(nameField.field, statusBox, priorityField.field);

    const localeOptions = Array.isArray(labels.popupsLocaleOptions) && labels.popupsLocaleOptions.length > 0
        ? labels.popupsLocaleOptions
        : [{ value: '', label: labels.popupsLocaleAll ?? 'All languages' }];
    const localeField = createSelectField({
        label: labels.popupsFieldLocale ?? 'Language',
        name: 'locale',
        value: '',
        options: localeOptions,
    });
    styleCompactField(localeField);

    const descriptionField = createTextareaField({
        label: labels.popupsFieldDescription ?? 'Description',
        name: 'description',
        rows: 2,
        placeholder: labels.popupsFieldDescriptionHelp ?? '',
    });
    styleCompactField(descriptionField.field);
    descriptionField.input.maxLength = 2000;

    generalCard.fields.append(
        generalGrid,
        localeField,
        descriptionField.field,
        createInfoHint(
            labels.popupsPriorityHelp
                ?? 'Higher priority wins when multiple popups match the same page and trigger.',
            { tone: 'brand' },
        ),
        createInfoHint(
            labels.popupsLocaleHelp
                ?? 'Create one popup per language for non-builder pages. Empty = every language.',
        ),
    );
    panelMap.general.appendChild(generalCard.section);

    // —— Triggers ——
    const triggersGrid = document.createElement('div');
    triggersGrid.className = 'voodbuilder-gjs-popup-grid voodbuilder-gjs-popup-grid--2';

    const timing = createPanelCard();
    const triggerSelect = createSelectField({
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
    });
    styleCompactField(triggerSelect);
    const delayField = createTextField({
        label: labels.popupsFieldDelay ?? 'Delay (seconds)',
        name: 'delay_seconds',
        type: 'number',
        value: '3',
        min: 0,
        max: 600,
    });
    delayField.field.setAttribute('data-popup-field', 'delay_seconds');
    styleCompactField(delayField.field);
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
    styleCompactField(scrollField.field);
    const clickField = createTextField({
        label: labels.popupsFieldClickSelector ?? 'CSS selector',
        name: 'click_selector',
        placeholder: '#open-promo',
    });
    clickField.field.setAttribute('data-popup-field', 'click_selector');
    clickField.field.hidden = true;
    styleCompactField(clickField.field);
    timing.fields.append(triggerSelect, delayField.field, scrollField.field, clickField.field);

    const frequency = createPanelCard();
    const frequencySelect = createSelectField({
        label: labels.popupsFieldFrequency ?? 'Frequency',
        name: 'frequency_mode',
        value: 'session',
        options: [
            { value: 'always', label: labels.popupsFrequencyAlways ?? 'Every visit' },
            { value: 'once', label: labels.popupsFrequencyOnce ?? 'Once ever' },
            { value: 'session', label: labels.popupsFrequencySession ?? 'Once per session' },
            { value: 'days', label: labels.popupsFrequencyDays ?? 'Every N days' },
        ],
    });
    styleCompactField(frequencySelect);
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
    styleCompactField(frequencyDaysField.field);
    frequency.fields.append(
        frequencySelect,
        frequencyDaysField.field,
        createInfoHint(labels.popupsFrequencyStorageHelp ?? 'Uses local/session storage (not cookies).'),
    );

    triggersGrid.append(timing.section, frequency.section);
    panelMap.triggers.appendChild(triggersGrid);

    // —— Targeting ——
    const targeting = createPanelCard();
    const targetingGrid = document.createElement('div');
    targetingGrid.className = 'voodbuilder-gjs-popup-grid voodbuilder-gjs-popup-grid--2';
    const audienceSelect = createSelectField({
        label: labels.popupsFieldAudience ?? 'Audience',
        name: 'logged_in',
        value: 'any',
        options: [
            { value: 'any', label: labels.popupsTargetingAny ?? 'Everyone' },
            { value: 'yes', label: labels.popupsTargetingLoggedIn ?? 'Logged-in users only' },
            { value: 'no', label: labels.popupsTargetingLoggedOut ?? 'Guests only' },
        ],
    });
    styleCompactField(audienceSelect);
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
    pagePathSelect.setAttribute('data-popup-field', 'page_path_select');
    styleCompactField(pagePathSelect);
    const pagePathCustom = createTextField({
        label: labels.popupsPagePathCustom ?? 'Custom path',
        name: 'page_path_custom',
        placeholder: '/blog',
    });
    pagePathCustom.field.setAttribute('data-popup-field', 'page_path_custom');
    pagePathCustom.field.hidden = true;
    styleCompactField(pagePathCustom.field);
    targetingGrid.append(audienceSelect, pagePathSelect);
    targeting.fields.append(targetingGrid, pagePathCustom.field);
    panelMap.targeting.appendChild(targeting.section);

    // —— Schedule ——
    const schedule = createPanelCard();
    const scheduleGrid = document.createElement('div');
    scheduleGrid.className = 'voodbuilder-gjs-popup-grid voodbuilder-gjs-popup-grid--3';
    const startAtField = createTextField({
        label: labels.popupsFieldStartAt ?? 'Start at',
        name: 'start_at',
        type: 'datetime-local',
    });
    const endAtField = createTextField({
        label: labels.popupsFieldEndAt ?? 'End at',
        name: 'end_at',
        type: 'datetime-local',
    });
    const timezoneOptions = Array.isArray(labels.popupsTimezoneOptions) && labels.popupsTimezoneOptions.length > 0
        ? labels.popupsTimezoneOptions
        : [{ value: labels.popupsAppTimezone || 'UTC', label: labels.popupsAppTimezone || 'UTC' }];
    const timezoneField = createSelectField({
        label: labels.popupsFieldTimezone ?? 'Timezone',
        name: 'timezone',
        value: labels.popupsAppTimezone || timezoneOptions[0]?.value || 'UTC',
        options: timezoneOptions,
    });
    styleCompactField(startAtField.field);
    styleCompactField(endAtField.field);
    styleCompactField(timezoneField);
    scheduleGrid.append(startAtField.field, endAtField.field, timezoneField);

    const weeklyDaysWrap = document.createElement('div');
    weeklyDaysWrap.className = 'voodbuilder-gjs-popup-weekly';
    weeklyDaysWrap.setAttribute('data-popup-field', 'weekly_days');

    const weeklyHead = document.createElement('div');
    weeklyHead.className = 'voodbuilder-gjs-popup-weekly__head';
    const weeklyDaysLabel = document.createElement('span');
    weeklyDaysLabel.className = 'voodbuilder-gjs-popup-weekly__label';
    weeklyDaysLabel.textContent = labels.popupsFieldWeeklyDays ?? 'Active days of week';
    const weeklyPresets = document.createElement('div');
    weeklyPresets.className = 'voodbuilder-gjs-popup-weekly__presets';

    const weekDays = [
        ['monday', labels.popupsScheduleMonday ?? 'Monday'],
        ['tuesday', labels.popupsScheduleTuesday ?? 'Tuesday'],
        ['wednesday', labels.popupsScheduleWednesday ?? 'Wednesday'],
        ['thursday', labels.popupsScheduleThursday ?? 'Thursday'],
        ['friday', labels.popupsScheduleFriday ?? 'Friday'],
        ['saturday', labels.popupsScheduleSaturday ?? 'Saturday'],
        ['sunday', labels.popupsScheduleSunday ?? 'Sunday'],
    ];
    const weeklyDaysList = document.createElement('div');
    weeklyDaysList.className = 'voodbuilder-gjs-popup-weekly__days';

    for (const [value, dayLabel] of weekDays) {
        weeklyDaysList.appendChild(createDayChip(value, dayLabel, shortDayLabel(dayLabel)));
    }

    const setWeeklyDays = (days) => {
        const selected = new Set(days);
        weeklyDaysList.querySelectorAll('[data-weekly-day]').forEach((input) => {
            input.checked = selected.has(String(input.getAttribute('data-weekly-day') ?? ''));
        });
    };

    for (const [presetId, presetLabel, days] of [
        ['all', labels.popupsWeeklyPresetAll ?? 'All', weekDays.map(([value]) => value)],
        ['weekdays', labels.popupsWeeklyPresetWeekdays ?? 'Mon–Fri', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday']],
        ['weekend', labels.popupsWeeklyPresetWeekend ?? 'Weekends', ['saturday', 'sunday']],
    ]) {
        const presetBtn = document.createElement('button');
        presetBtn.type = 'button';
        presetBtn.className = 'voodbuilder-gjs-popup-weekly__preset';
        presetBtn.setAttribute('data-weekly-preset', presetId);
        presetBtn.textContent = presetLabel;
        presetBtn.addEventListener('click', () => setWeeklyDays(days));
        weeklyPresets.appendChild(presetBtn);
    }

    weeklyHead.append(weeklyDaysLabel, weeklyPresets);
    weeklyDaysWrap.append(weeklyHead, weeklyDaysList);
    schedule.fields.append(scheduleGrid, weeklyDaysWrap);
    panelMap.schedule.appendChild(schedule.section);

    // —— Appearance ——
    const display = createPanelCard();
    const displayWidth = createSelectField({
        label: labels.popupsFieldWidth ?? 'Modal width',
        name: 'display_width',
        value: 'md',
        options: [
            { value: 'sm', label: labels.popupsWidthSm ?? 'Small' },
            { value: 'md', label: labels.popupsWidthMd ?? 'Medium' },
            { value: 'lg', label: labels.popupsWidthLg ?? 'Large' },
            { value: 'xl', label: labels.popupsWidthXl ?? 'Extra large' },
        ],
    });
    displayWidth.classList.add('voodbuilder-gjs-popup-field--width');
    styleCompactField(displayWidth);

    const displayToggles = document.createElement('div');
    displayToggles.className = 'voodbuilder-gjs-popup-dismiss-box';
    for (const toggle of [
        createCheckboxField({
            label: labels.popupsFieldOverlay ?? 'Dim background',
            name: 'display_overlay',
            checked: true,
        }),
        createCheckboxField({
            label: labels.popupsFieldCloseOverlay ?? 'Close on overlay click',
            name: 'close_on_overlay',
            checked: true,
        }),
        createCheckboxField({
            label: labels.popupsFieldCloseEscape ?? 'Close on Escape',
            name: 'close_on_escape',
            checked: true,
        }),
    ]) {
        styleInlineToggle(toggle);
        displayToggles.appendChild(toggle);
    }

    display.fields.append(displayWidth, displayToggles);
    panelMap.appearance.appendChild(display.section);

    root.append(tabs, panels);
    container.appendChild(root);
    activateTab('general');

    return root;
}

function readRulesForm(form) {
    const data = new FormData(form);
    const pagePathSelect = String(data.get('page_path_select') ?? '');
    const pagePath = pagePathSelect === PAGE_PATH_CUSTOM
        ? String(data.get('page_path_custom') ?? '').trim()
        : pagePathSelect.trim();
    const weeklyDays = [...form.querySelectorAll('[data-weekly-day]:checked')]
        .map((input) => String(input.getAttribute('data-weekly-day') ?? '').trim().toLowerCase())
        .filter(Boolean);

    return {
        name: String(data.get('name') ?? '').trim(),
        description: String(data.get('description') ?? '').trim(),
        locale: String(data.get('locale') ?? '').trim(),
        enabled: data.get('enabled') != null,
        paused: data.get('paused') != null,
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
            schedule: {
                start_at: String(data.get('start_at') ?? '').trim(),
                end_at: String(data.get('end_at') ?? '').trim(),
                weekly_days: weeklyDays,
                weekly_day: weeklyDays[0] ?? '',
                timezone: String(data.get('timezone') ?? '').trim(),
                weekly_start_time: '',
                weekly_end_time: '',
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
    const description = form.querySelector('[name="description"]');

    if (description) {
        description.value = popup.description ?? '';
    }

    const localeSelect = form.querySelector('[name="locale"]');

    if (localeSelect) {
        localeSelect.value = popup.locale ?? '';
    }

    form.querySelector('[name="enabled"]').checked = popup.enabled !== false;
    form.querySelector('[name="paused"]').checked = popup.paused === true;
    form.querySelector('[name="priority"]').value = String(popup.priority ?? 0);
    form.querySelector('[name="trigger_type"]').value = rules.trigger?.type ?? 'delay';
    form.querySelector('[name="delay_seconds"]').value = String(rules.trigger?.delay_seconds ?? 3);
    form.querySelector('[name="scroll_percent"]').value = String(rules.trigger?.scroll_percent ?? 50);
    form.querySelector('[name="click_selector"]').value = rules.trigger?.click_selector ?? '';
    form.querySelector('[name="frequency_mode"]').value = rules.frequency?.mode ?? 'session';
    form.querySelector('[name="frequency_days"]').value = String(rules.frequency?.days ?? 7);
    form.querySelector('[name="start_at"]').value = rules.schedule?.start_at ?? '';
    form.querySelector('[name="end_at"]').value = rules.schedule?.end_at ?? '';
    const timezoneSelect = form.querySelector('[name="timezone"]');

    if (timezoneSelect) {
        timezoneSelect.value = rules.schedule?.timezone || timezoneSelect.value;
    }

    const selectedDays = new Set(normalizeWeeklyDays(rules.schedule ?? {}));
    form.querySelectorAll('[data-weekly-day]').forEach((input) => {
        input.checked = selectedDays.has(String(input.getAttribute('data-weekly-day') ?? ''));
    });
    form.querySelector('[name="logged_in"]').value = rules.targeting?.logged_in ?? 'any';

    const pagePath = resolvePagePathSelectValue(rules.targeting?.page_path ?? '', pagePathOptions);
    form.querySelector('[name="page_path_select"]').value = pagePath.select;
    form.querySelector('[name="page_path_custom"]').value = pagePath.custom;

    form.querySelector('[name="display_width"]').value = rules.display?.width ?? 'md';
    form.querySelector('[name="display_overlay"]').checked = rules.display?.overlay !== false;
    form.querySelector('[name="close_on_overlay"]').checked = rules.display?.close_on_overlay !== false;
    form.querySelector('[name="close_on_escape"]').checked = rules.display?.close_on_escape !== false;
    bindRulesFormVisibility(form);
    form.querySelectorAll('select').forEach((select) => {
        select.dispatchEvent(new Event('change', { bubbles: true }));
    });
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
        const configuratorTitle = labels.popupsConfiguratorTitle ?? 'Popup configurator';
        const subtitle = labels.popupsConfiguratorSubtitle
            ?? 'Configure behavior, targeting, schedule, and appearance.';
        const saveLabel = labels.popupsSaveApply ?? labels.dialogConfirm ?? 'Save';
        const resetLabel = labels.popupsReset ?? 'Reset';
        const closeLabel = labels.dialogCancel ?? 'Cancel';

        const modal = document.createElement('div');
        modal.className = 'voodbuilder-gjs-modal voodbuilder-gjs-modal--popup-form';
        modal.innerHTML = `
            <div class="voodbuilder-gjs-modal__backdrop" data-popup-form-close></div>
            <div class="voodbuilder-gjs-modal__panel voodbuilder-gjs-modal__panel--popup-form" role="dialog" aria-modal="true" aria-labelledby="voodbuilder-popup-form-title">
                <header class="voodbuilder-gjs-popup-config__head">
                    <div class="voodbuilder-gjs-popup-config__intro">
                        <span class="voodbuilder-gjs-popup-config__mark" aria-hidden="true">${lucideIcon('sliders-horizontal', 18)}</span>
                        <div class="voodbuilder-gjs-popup-config__titles">
                            <div class="voodbuilder-gjs-popup-config__title-row">
                                <h2 id="voodbuilder-popup-form-title" class="voodbuilder-gjs-popup-config__title">${configuratorTitle}</h2>
                                <span class="voodbuilder-gjs-popup-config__badge" data-popup-status-badge></span>
                            </div>
                            <p class="voodbuilder-gjs-popup-config__subtitle">${subtitle}</p>
                            <p class="voodbuilder-gjs-popup-config__context">${title}</p>
                        </div>
                    </div>
                    <button type="button" class="voodbuilder-gjs-popup-config__close" data-popup-form-close aria-label="Close">${lucideIcon('x', 18)}</button>
                </header>
                <form class="voodbuilder-gjs-popup-form-shell" data-popup-form>
                    <div class="voodbuilder-gjs-popup-form-shell__body" data-popup-form-body></div>
                    <footer class="voodbuilder-gjs-popup-form-shell__footer">
                        <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost" data-popup-form-reset>
                            ${lucideIcon('rotate-ccw', 14)}
                            <span>${resetLabel}</span>
                        </button>
                        <div class="voodbuilder-gjs-popup-form-shell__actions">
                            <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost" data-popup-form-close>${closeLabel}</button>
                            <button type="submit" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--primary">
                                ${lucideIcon('check', 14)}
                                <span>${saveLabel}</span>
                            </button>
                        </div>
                    </footer>
                </form>
            </div>
        `;
        document.body.appendChild(modal);

        const form = modal.querySelector('[data-popup-form]');
        const body = modal.querySelector('[data-popup-form-body]');
        const panel = modal.querySelector('.voodbuilder-gjs-modal__panel');
        const statusBadge = modal.querySelector('[data-popup-status-badge]');
        const baseline = initial
            ? structuredClone({
                name: initial.name ?? '',
                description: initial.description ?? '',
                locale: initial.locale ?? '',
                enabled: initial.enabled !== false,
                paused: initial.paused === true,
                priority: initial.priority ?? 0,
                rules: initial.rules ?? defaultRules(),
            })
            : {
                name: '',
                description: '',
                locale: '',
                enabled: true,
                paused: false,
                priority: 0,
                rules: defaultRules(),
            };

        const syncStatusBadge = () => {
            const enabled = form.querySelector('[name="enabled"]')?.checked !== false;
            const paused = form.querySelector('[name="paused"]')?.checked === true;
            let text = labels.popupsStatusActive ?? 'Active';
            let tone = 'active';

            if (! enabled) {
                text = labels.popupsStatusDisabled ?? 'Disabled';
                tone = 'disabled';
            } else if (paused) {
                text = labels.popupsStatusPaused ?? 'Paused';
                tone = 'paused';
            }

            if (statusBadge) {
                statusBadge.textContent = text;
                statusBadge.className = `voodbuilder-gjs-popup-config__badge voodbuilder-gjs-popup-config__badge--${tone}`;
            }
        };

        try {
            mountRulesForm(body, labels, pagePathOptions);
            enhanceInspectorSelects(modal);
            bindRulesFormVisibility(form);
            fillRulesForm(form, baseline, pagePathOptions);
            syncStatusBadge();
            form.querySelector('[name="enabled"]')?.addEventListener('change', syncStatusBadge);
            form.querySelector('[name="paused"]')?.addEventListener('change', syncStatusBadge);
        } catch (error) {
            console.error('Voodbuilder: could not mount popup form.', error);
            body.replaceChildren();
            const hint = document.createElement('p');
            hint.className = 'voodbuilder-gjs-hint';
            hint.textContent = labels.popupsFormError ?? 'Could not load the popup form.';
            body.appendChild(hint);
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

        modal.querySelector('[data-popup-form-reset]')?.addEventListener('click', () => {
            fillRulesForm(form, structuredClone(baseline), pagePathOptions);
            syncStatusBadge();
        });

        panel?.addEventListener('click', (event) => {
            event.stopPropagation();
        });

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const payload = readRulesForm(form);

            if (! payload.name) {
                const tabBtn = form.querySelector('[data-popup-tab="general"]');
                tabBtn?.click();
                form.querySelector('[name="name"]')?.focus?.();

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
                <div class="voodbuilder-gjs-popup-list" data-voodbuilder-popups-list></div>
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
            row.className = 'voodbuilder-gjs-popup-card';

            const main = document.createElement('div');
            main.className = 'voodbuilder-gjs-popup-card__main';

            const title = document.createElement('div');
            title.className = 'voodbuilder-gjs-popup-card__title';
            title.textContent = popup.name ?? '';
            main.append(title, renderPopupSummary(popup, labels));

            const actions = document.createElement('div');
            actions.className = 'voodbuilder-gjs-popup-card__actions';

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
            testBtn.addEventListener('click', async () => {
                modal.hidden = true;
                previewPopup(popup, {
                    onClose: () => {
                        void openModal();
                    },
                });
            });

            const designBtn = document.createElement('a');
            designBtn.className = 'voodbuilder-gjs-btn voodbuilder-gjs-btn--primary';
            designBtn.textContent = labels.popupsOpenEditor ?? 'Design';
            designBtn.href = popup.editor_url ?? '#';
            designBtn.target = '_blank';
            designBtn.rel = 'noopener noreferrer';

            const pauseBtn = document.createElement('button');
            pauseBtn.type = 'button';
            pauseBtn.className = 'voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost';
            pauseBtn.textContent = popup.paused
                ? (labels.popupsResume ?? 'Resume')
                : (labels.popupsPause ?? 'Pause');
            pauseBtn.addEventListener('click', async () => {
                const response = await fetch(`${baseUrl}/${popup.id}`, {
                    method: 'PUT',
                    credentials: 'same-origin',
                    headers: editorApiHeaders(csrf, { json: true }),
                    body: JSON.stringify({ paused: ! popup.paused }),
                });

                if (! response.ok) {
                    await alertDialog({ message: labels.popupsSaveError ?? 'Could not save popup.', labels });

                    return;
                }

                await openModal();
            });

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

            actions.append(editBtn, testBtn, pauseBtn, designBtn, deleteBtn);
            row.append(main, actions);
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
