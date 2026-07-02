/**
 * Element visibility conditions UI.
 *
 * Data model:
 * - `match`: "any" (default) = OR between groups; "all" = AND between groups
 * - Each group: all conditions inside must pass (AND)
 */

import { encodeVpressConfig } from './voodbuilder-dynamic-config.js';

const ATTR = 'data-voodbuilder-conditions';

const DEFAULT_COMPARES = ['==', '!=', 'contains'];

const COMPARE_LABELS = {
    '==': 'equals',
    '!=': 'not equals',
    contains: 'contains',
    not_contains: 'not contains',
    is_not: 'not equals',
};

function decodeConditionAttribute(raw) {
    if (! raw) {
        return '';
    }

    let value = String(raw).trim();

    if (value.includes('&quot;') || value.includes('&#') || value.includes('&amp;')) {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = value;
        value = textarea.value.trim() || value;
    }

    return value;
}

function parseConditionJson(raw) {
    if (! raw) {
        return null;
    }

    for (const candidate of [decodeConditionAttribute(raw), String(raw).trim()]) {
        if (! candidate) {
            continue;
        }

        try {
            const parsed = JSON.parse(candidate);

            if (parsed && typeof parsed === 'object' && ! Array.isArray(parsed)) {
                return parsed;
            }
        } catch {
            // try next candidate
        }
    }

    return null;
}

function parseConditions(raw) {
    const decoded = parseConditionJson(raw);

    if (! decoded) {
        return { match: 'any', sets: [] };
    }

    return {
        match: decoded.match === 'all' ? 'all' : 'any',
        sets: Array.isArray(decoded.sets) ? decoded.sets : [],
    };
}

function serializeConditions(definition) {
    if (! definition?.sets?.length) {
        return '';
    }

    const json = JSON.stringify({
        match: definition.match === 'all' ? 'all' : 'any',
        sets: definition.sets,
    });

    return encodeVpressConfig(json);
}

function defaultCondition(options) {
    const first = options[0];
    const key = first?.key ?? 'user_logged_in';

    return {
        key,
        compare: first?.compares?.[0] ?? '==',
        value: defaultValueForKey(key, options),
    };
}

function optionForKey(key, options) {
    return options.find((opt) => opt.key === key) ?? {};
}

function valueMetaForKey(key, options) {
    return optionForKey(key, options).value ?? { type: 'text' };
}

function defaultValueForKey(key, options) {
    const meta = valueMetaForKey(key, options);

    if (meta.type === 'boolean') {
        return '1';
    }

    if (meta.type === 'select' && meta.choices?.length) {
        return meta.choices[0].value;
    }

    return '';
}

function compareFieldHidden(key, options) {
    const compares = comparesForKey(key, options);

    return compares.length === 0;
}

function readConditionAttribute(component) {
    const attrs = component.getAttributes?.({ noClass: true, noStyle: true }) ?? {};

    return attrs[ATTR] ?? '';
}

function syncConditionAttributeToDom(component) {
    const value = readConditionAttribute(component);
    const element = component.getView?.()?.el;

    if (! element) {
        return;
    }

    if (value) {
        element.setAttribute(ATTR, value);
    } else {
        element.removeAttribute(ATTR);
    }
}

export function syncConditionsForExport(editor) {
    editor?.getWrapper?.().find('*').forEach((component) => {
        const definition = parseConditions(readConditionAttribute(component));
        const encoded = serializeConditions(definition);

        if (! encoded) {
            component.removeAttributes(ATTR, { silent: true });
            syncConditionAttributeToDom(component);

            return;
        }

        component.addAttributes({ [ATTR]: encoded }, { silent: true });
        syncConditionAttributeToDom(component);
    });
}

export function registerConditionsPersistence(editor) {
    if (! editor) {
        return;
    }

    if (editor.__voodbuilderConditionsPersistenceRegistered) {
        return;
    }

    editor.__voodbuilderConditionsPersistenceRegistered = true;

    const restore = (component) => {
        const definition = parseConditions(readConditionAttribute(component));
        const encoded = serializeConditions(definition);

        if (! encoded) {
            return;
        }

        component.addAttributes({ [ATTR]: encoded }, { silent: true });
        syncConditionAttributeToDom(component);
    };

    editor.on('load', () => {
        editor.getWrapper().find('*').forEach(restore);
    });

    editor.on('component:add', restore);

    editor.on(`change:attributes:${ATTR}`, (component) => {
        syncConditionAttributeToDom(component);
    });
}

function compareLabel(compare, labels) {
    const key = compare ?? '==';

    if (labels?.[`compare_${key}`]) {
        return labels[`compare_${key}`];
    }

    return COMPARE_LABELS[key] ?? key;
}

function comparesForKey(key, options) {
    const option = optionForKey(key, options);

    if (Array.isArray(option.compares)) {
        return option.compares;
    }

    return DEFAULT_COMPARES;
}

function buildValueControl(condition, options, labels) {
    const meta = valueMetaForKey(condition.key, options);
    const wrap = document.createElement('div');
    wrap.className = 'voodbuilder-gjs-condition-value';

    let control;

    if (meta.type === 'boolean') {
        control = document.createElement('select');
        control.className = 'voodbuilder-gjs-input voodbuilder-gjs-input--inline';

        for (const choice of [
            { value: '1', label: labels.conditionsBoolYes ?? 'Yes' },
            { value: '0', label: labels.conditionsBoolNo ?? 'No' },
        ]) {
            const option = document.createElement('option');
            option.value = choice.value;
            option.textContent = choice.label;
            option.selected = String(condition.value ?? '1') === choice.value;
            control.appendChild(option);
        }
    } else if (meta.type === 'select' && meta.choices?.length) {
        control = document.createElement('select');
        control.className = 'voodbuilder-gjs-input voodbuilder-gjs-input--inline';

        for (const choice of meta.choices) {
            const option = document.createElement('option');
            option.value = choice.value;
            option.textContent = choice.label;
            option.selected = String(condition.value ?? '') === String(choice.value);
            control.appendChild(option);
        }
    } else if (meta.type === 'date') {
        control = document.createElement('input');
        control.type = 'datetime-local';
        control.className = 'voodbuilder-gjs-input voodbuilder-gjs-input--inline';
        control.value = formatDateInputValue(condition.value ?? '');
    } else {
        control = document.createElement('input');
        control.type = 'text';
        control.className = 'voodbuilder-gjs-input voodbuilder-gjs-input--inline';
        control.placeholder = labels.conditionsValuePlaceholder ?? 'Value';
        control.value = condition.value ?? '';
    }

    control.setAttribute('aria-label', labels.conditionsFieldValue ?? 'Value');
    wrap.appendChild(control);

    return { wrap, control };
}

function formatDateInputValue(value) {
    if (! value) {
        return '';
    }

    const normalized = String(value).replace(' ', 'T').slice(0, 16);

    return normalized.length >= 16 ? normalized : '';
}

function readValueFromControl(control, key, options) {
    const meta = valueMetaForKey(key, options);

    if (meta.type === 'date') {
        return control.value;
    }

    return control.value;
}

function isComponentAlive(component) {
    if (! component) {
        return false;
    }

    try {
        const el = component.getEl?.();

        return el ? el.isConnected !== false : component.parent?.() != null;
    } catch {
        return false;
    }
}

function resolveTarget(editor, lastTarget) {
    const current = editor.getSelected();

    if (isComponentAlive(current)) {
        return current;
    }

    if (isComponentAlive(lastTarget)) {
        return lastTarget;
    }

    return null;
}

export function registerConditionsUi(editor, options = {}) {
    const { mount, labels = {}, conditionOptions = [] } = options;

    if (! mount) {
        return;
    }

    if (editor.__voodbuilderConditionsUiRegistered) {
        return;
    }

    editor.__voodbuilderConditionsUiRegistered = true;

    mount.addEventListener('mousedown', (event) => {
        event.stopPropagation();
    });

    const applyDefinition = (component, definition) => {
        const serialized = serializeConditions(definition);

        if (serialized) {
            component.addAttributes({ [ATTR]: serialized });
        } else {
            component.removeAttributes(ATTR);
        }

        syncConditionAttributeToDom(component);
        editor.trigger('update');
    };

    let lastTarget = null;
    let feedbackTimer = null;

    const showFeedback = (message) => {
        const feedback = mount.querySelector('[data-voodbuilder-conditions-feedback]');

        if (! feedback) {
            return;
        }

        feedback.textContent = message;
        feedback.hidden = false;
        window.clearTimeout(feedbackTimer);
        feedbackTimer = window.setTimeout(() => {
            feedback.hidden = true;
        }, 3200);
    };

    const workingTarget = () => resolveTarget(editor, lastTarget);

    mount.innerHTML = `
        <div class="voodbuilder-gjs-conditions">
            <p class="voodbuilder-gjs-panel-subtitle">${labels.conditionsTitle ?? 'Visibility'}</p>
            <p class="voodbuilder-gjs-hint">${labels.conditionsHint ?? ''}</p>
            <div class="voodbuilder-gjs-conditions-match" hidden>
                <span class="voodbuilder-gjs-field-label">${labels.conditionsMatchLabel ?? 'Show when'}</span>
                <div class="voodbuilder-gjs-segmented" role="group" aria-label="${labels.conditionsMatchLabel ?? 'Show when'}" data-voodbuilder-conditions-match>
                    <button type="button" class="voodbuilder-gjs-segmented__btn" data-match="any" aria-pressed="true">
                        ${labels.conditionsMatchAnyShort ?? 'OR'}
                    </button>
                    <button type="button" class="voodbuilder-gjs-segmented__btn" data-match="all" aria-pressed="false">
                        ${labels.conditionsMatchAllShort ?? 'AND'}
                    </button>
                </div>
                <span class="voodbuilder-gjs-segmented__hint">${labels.conditionsMatchBetweenGroups ?? 'between groups'}</span>
            </div>
            <div data-voodbuilder-conditions-sets class="voodbuilder-gjs-conditions-sets"></div>
            <p class="voodbuilder-gjs-conditions-feedback" data-voodbuilder-conditions-feedback hidden></p>
            <div class="voodbuilder-gjs-actions voodbuilder-gjs-conditions-actions">
                <button type="button" class="voodbuilder-gjs-btn" data-voodbuilder-conditions-add-set>
                    ${labels.conditionsAddSet ?? 'Add group'}
                </button>
                <button type="button" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost" data-voodbuilder-conditions-clear>
                    ${labels.conditionsClear ?? 'Clear all'}
                </button>
            </div>
        </div>
    `;

    const setsMount = mount.querySelector('[data-voodbuilder-conditions-sets]');
    const matchWrap = mount.querySelector('.voodbuilder-gjs-conditions-match');
    const matchControl = mount.querySelector('[data-voodbuilder-conditions-match]');

    const syncMatchControl = (match) => {
        matchControl?.querySelectorAll('[data-match]').forEach((button) => {
            const active = button.dataset.match === match;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };

    const render = () => {
        const component = workingTarget();

        if (! component) {
            matchWrap.hidden = true;
            setsMount.innerHTML = `<p class="voodbuilder-gjs-hint voodbuilder-gjs-conditions-select-hint">${labels.selectComponent ?? 'Select an element on the canvas first.'}</p>`;

            return;
        }

        const definition = parseConditions(readConditionAttribute(component));
        const hasSets = definition.sets.length > 0;

        matchWrap.hidden = ! hasSets;
        syncMatchControl(definition.match);

        setsMount.replaceChildren();

        if (! hasSets) {
            setsMount.innerHTML = `
                <div class="voodbuilder-gjs-conditions-empty">
                    <p class="voodbuilder-gjs-hint">${labels.conditionsEmpty ?? 'No visibility rules. The element is always shown.'}</p>
                </div>
            `;

            return;
        }

        definition.sets.forEach((set, setIndex) => {
            if (setIndex > 0) {
                const divider = document.createElement('div');
                divider.className = 'voodbuilder-gjs-conditions-operator';
                divider.textContent = definition.match === 'all'
                    ? (labels.conditionsOperatorAnd ?? 'AND')
                    : (labels.conditionsOperatorOr ?? 'OR');
                setsMount.appendChild(divider);
            }

            setsMount.appendChild(buildSetBlock(
                definition,
                setIndex,
                set,
                conditionOptions,
                labels,
                render,
                component,
                applyDefinition,
            ));
        });
    };

    matchControl?.addEventListener('click', (event) => {
        const button = event.target.closest('[data-match]');

        if (! button) {
            return;
        }

        const component = workingTarget();

        if (! component) {
            return;
        }

        const def = parseConditions(readConditionAttribute(component));
        def.match = button.dataset.match === 'all' ? 'all' : 'any';
        applyDefinition(component, def);
        editor.select(component);
        render();
    });

    mount.querySelector('[data-voodbuilder-conditions-add-set]')?.addEventListener('click', () => {
        const component = workingTarget();

        if (! component) {
            showFeedback(labels.conditionsSelectFirst ?? labels.selectComponent ?? 'Select an element on the canvas first.');

            return;
        }

        editor.select(component);

        const def = parseConditions(readConditionAttribute(component));
        def.sets.push({ conditions: [defaultCondition(conditionOptions)] });
        applyDefinition(component, def);
        render();
    });

    mount.querySelector('[data-voodbuilder-conditions-clear]')?.addEventListener('click', () => {
        const component = workingTarget();

        if (! component) {
            showFeedback(labels.conditionsSelectFirst ?? labels.selectComponent ?? 'Select an element on the canvas first.');

            return;
        }

        editor.select(component);
        applyDefinition(component, { match: 'any', sets: [] });
        render();
    });

    editor.on('component:selected', (component) => {
        lastTarget = component;
        render();
    });

    editor.on('component:deselected', () => {
        render();
    });

    editor.on('voodbuilder:inspector-panel:refresh', ({ tabId }) => {
        if (tabId === 'conditions') {
            render();
        }
    });

    render();
}

function buildSetBlock(definition, setIndex, set, options, labels, render, component, applyDefinition) {
    const setEl = document.createElement('div');
    setEl.className = 'voodbuilder-gjs-condition-card';

    const header = document.createElement('div');
    header.className = 'voodbuilder-gjs-condition-card__header';

    const title = document.createElement('span');
    title.className = 'voodbuilder-gjs-condition-card__title';
    title.textContent = `${labels.conditionsGroup ?? 'Group'} ${setIndex + 1}`;

    const removeSet = document.createElement('button');
    removeSet.type = 'button';
    removeSet.className = 'voodbuilder-gjs-icon-btn';
    removeSet.title = labels.conditionsRemoveSet ?? 'Remove group';
    removeSet.setAttribute('aria-label', labels.conditionsRemoveSet ?? 'Remove group');
    removeSet.textContent = '×';
    removeSet.addEventListener('click', () => {
        const def = parseConditions(readConditionAttribute(component));
        def.sets.splice(setIndex, 1);
        applyDefinition(component, def);
        render();
    });

    header.append(title, removeSet);
    setEl.appendChild(header);

    const body = document.createElement('div');
    body.className = 'voodbuilder-gjs-condition-card__body';

    const conditions = set.conditions ?? [];

    conditions.forEach((condition, condIndex) => {
        if (condIndex > 0) {
            const andBadge = document.createElement('div');
            andBadge.className = 'voodbuilder-gjs-conditions-join';
            andBadge.textContent = labels.conditionsOperatorAnd ?? 'AND';
            body.appendChild(andBadge);
        }

        body.appendChild(buildConditionRow(
            setIndex,
            condIndex,
            condition,
            options,
            labels,
            render,
            component,
            conditions.length,
            applyDefinition,
        ));
    });

    const addCond = document.createElement('button');
    addCond.type = 'button';
    addCond.className = 'voodbuilder-gjs-btn voodbuilder-gjs-btn--ghost voodbuilder-gjs-btn--block';
    addCond.textContent = labels.conditionsAddCondition ?? 'Add condition';
    addCond.addEventListener('click', () => {
        const def = parseConditions(readConditionAttribute(component));
        def.sets[setIndex].conditions.push(defaultCondition(options));
        applyDefinition(component, def);
        render();
    });
    body.appendChild(addCond);
    setEl.appendChild(body);

    return setEl;
}

function buildConditionRow(setIndex, condIndex, condition, options, labels, render, component, conditionCount, applyDefinition) {
    const row = document.createElement('div');
    row.className = 'voodbuilder-gjs-condition-item';

    const fields = document.createElement('div');
    fields.className = 'voodbuilder-gjs-condition-item__fields';

    const keySelect = document.createElement('select');
    keySelect.className = 'voodbuilder-gjs-input voodbuilder-gjs-input--inline';
    keySelect.setAttribute('aria-label', labels.conditionsFieldKey ?? 'Condition');

    for (const opt of options) {
        const option = document.createElement('option');
        option.value = opt.key;
        option.textContent = opt.label;
        option.selected = opt.key === condition.key;
        keySelect.appendChild(option);
    }

    const compares = comparesForKey(condition.key, options);

    const compareSelect = document.createElement('select');
    compareSelect.className = 'voodbuilder-gjs-input voodbuilder-gjs-input--inline';
    compareSelect.setAttribute('aria-label', labels.conditionsFieldCompare ?? 'Operator');

    const refreshCompareOptions = (key, selected) => {
        compareSelect.replaceChildren();
        const available = comparesForKey(key, options);

        if (available.length === 0) {
            compareSelect.hidden = true;

            return;
        }

        compareSelect.hidden = false;

        for (const cmp of available) {
            const option = document.createElement('option');
            option.value = cmp;
            option.textContent = compareLabel(cmp, labels);
            option.selected = cmp === (selected ?? '==');
            compareSelect.appendChild(option);
        }
    };

    refreshCompareOptions(condition.key, condition.compare);

    const valueSlot = document.createElement('div');
    valueSlot.className = 'voodbuilder-gjs-condition-item__value';
    const { wrap: valueWrap, control: valueControl } = buildValueControl(condition, options, labels);
    valueSlot.appendChild(valueWrap);

    const syncCompareVisibility = (key) => {
        compareSelect.hidden = compareFieldHidden(key, options);
    };

    syncCompareVisibility(condition.key);

    const sync = () => {
        const def = parseConditions(readConditionAttribute(component));
        def.sets[setIndex].conditions[condIndex] = {
            key: keySelect.value,
            compare: compareSelect.hidden ? '==' : compareSelect.value,
            value: readValueFromControl(valueControl, keySelect.value, options),
        };
        applyDefinition(component, def);
    };

    keySelect.addEventListener('change', () => {
        const def = parseConditions(readConditionAttribute(component));
        const nextKey = keySelect.value;
        def.sets[setIndex].conditions[condIndex] = {
            key: nextKey,
            compare: comparesForKey(nextKey, options)[0] ?? '==',
            value: defaultValueForKey(nextKey, options),
        };
        applyDefinition(component, def);
        render();
    });

    compareSelect.addEventListener('change', sync);
    valueControl.addEventListener('input', sync);
    valueControl.addEventListener('change', sync);

    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'voodbuilder-gjs-icon-btn';
    remove.title = labels.conditionsRemoveCondition ?? 'Remove condition';
    remove.setAttribute('aria-label', labels.conditionsRemoveCondition ?? 'Remove condition');
    remove.textContent = '×';
    remove.addEventListener('click', () => {
        const def = parseConditions(readConditionAttribute(component));

        if (conditionCount <= 1) {
            def.sets.splice(setIndex, 1);
        } else {
            def.sets[setIndex].conditions.splice(condIndex, 1);
        }

        applyDefinition(component, def);
        render();
    });

    const metaRow = document.createElement('div');
    metaRow.className = 'voodbuilder-gjs-condition-item__meta';

    if (! compareSelect.hidden) {
        metaRow.appendChild(compareSelect);
    }

    metaRow.appendChild(valueSlot);

    fields.append(keySelect, metaRow);
    row.append(fields, remove);

    return row;
}
