/**
 * Content-panel settings for Animated counter (and counters inside Animated stats).
 */

import {
    createFormSection,
    createSelectField,
    createTextField,
} from './editor-form-ui.js';
import {
    applyCounterConfig,
    isAnimatedCounterComponent,
    readCounterConfig,
} from './grapesjs-animated-blocks.js';

function runWithSettingsChangeGuard(editor, callback) {
    if (! editor || typeof callback !== 'function') {
        return;
    }

    const depth = Number(editor.__voodbuilderSettingsChangeDepth ?? 0);
    editor.__voodbuilderSettingsChangeDepth = depth + 1;
    editor.__voodbuilderSettingsChange = true;

    try {
        callback();
    } finally {
        const nextDepth = Number(editor.__voodbuilderSettingsChangeDepth ?? 1) - 1;
        editor.__voodbuilderSettingsChangeDepth = nextDepth;

        if (nextDepth <= 0) {
            editor.__voodbuilderSettingsChange = false;
            delete editor.__voodbuilderSettingsChangeDepth;
        }
    }
}

/**
 * @param {object} component
 * @param {Partial<ReturnType<typeof readCounterConfig>>} patch
 * @param {object|null|undefined} editor
 */
function patchCounter(component, patch, editor) {
    runWithSettingsChangeGuard(editor, () => applyCounterConfig(component, patch));
}

/**
 * @param {HTMLElement} fields
 * @param {{ label: string, name: string, value: string, type?: string, min?: number, max?: number, onCommit: (value: string) => void }} args
 */
function appendNumberField(fields, {
    label,
    name,
    value,
    type = 'number',
    min,
    max,
    onCommit,
}) {
    const { field, input } = createTextField({
        label,
        name,
        type,
        value,
        min,
        max,
    });

    const commit = () => onCommit(input.value);

    input.addEventListener('change', commit);
    input.addEventListener('blur', commit);
    fields.appendChild(field);
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderAnimatedCounterSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component || ! isAnimatedCounterComponent(component)) {
        return false;
    }

    const config = readCounterConfig(component);

    mount.hidden = false;
    mount.replaceChildren();
    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();

    const { section, fields } = createFormSection(labels.counterSettingsTitle ?? 'Counter settings');

    appendNumberField(fields, {
        label: 'From',
        name: 'vbCountFrom',
        value: String(config.from),
        onCommit: (value) => patchCounter(component, { from: Number(value) || 0 }, editor),
    });

    appendNumberField(fields, {
        label: 'To',
        name: 'vbCountTo',
        value: String(config.to),
        onCommit: (value) => patchCounter(component, { to: Number(value) || 0 }, editor),
    });

    appendNumberField(fields, {
        label: 'Duration (ms)',
        name: 'vbCountDuration',
        value: String(config.duration),
        min: 200,
        max: 8000,
        onCommit: (value) => patchCounter(component, {
            duration: Math.max(200, Math.min(8000, Number(value) || 1600)),
        }, editor),
    });

    appendNumberField(fields, {
        label: 'Delay (ms)',
        name: 'vbCountDelay',
        value: String(config.delay),
        min: 0,
        max: 5000,
        onCommit: (value) => patchCounter(component, {
            delay: Math.max(0, Math.min(5000, Number(value) || 0)),
        }, editor),
    });

    fields.appendChild(createSelectField({
        label: 'Start when',
        name: 'vbCountTrigger',
        value: config.trigger,
        options: [
            { value: 'always', label: 'Always' },
            { value: 'visible', label: 'On visible' },
            { value: 'hover', label: 'On hover' },
            { value: 'click', label: 'On click' },
        ],
        onChange: (value) => patchCounter(component, { trigger: value }, editor),
    }));

    fields.appendChild(createSelectField({
        label: 'Easing',
        name: 'vbCountEasing',
        value: config.easing,
        options: [
            { value: 'ease-out', label: 'Ease out' },
            { value: 'linear', label: 'Linear' },
            { value: 'ease-in-out', label: 'Ease in-out' },
        ],
        onChange: (value) => patchCounter(component, { easing: value }, editor),
    }));

    appendNumberField(fields, {
        label: 'Decimals',
        name: 'vbCountDecimals',
        value: String(config.decimals),
        min: 0,
        max: 2,
        onCommit: (value) => patchCounter(component, {
            decimals: Math.max(0, Math.min(2, Number.parseInt(value, 10) || 0)),
        }, editor),
    });

    const { field: prefixField, input: prefixInput } = createTextField({
        label: 'Prefix',
        name: 'vbCountPrefix',
        value: config.prefix,
    });
    prefixInput.addEventListener('change', () => patchCounter(component, { prefix: prefixInput.value }, editor));
    fields.appendChild(prefixField);

    const { field: suffixField, input: suffixInput } = createTextField({
        label: 'Suffix',
        name: 'vbCountSuffix',
        value: config.suffix,
    });
    suffixInput.addEventListener('change', () => patchCounter(component, { suffix: suffixInput.value }, editor));
    fields.appendChild(suffixField);

    fields.appendChild(createSelectField({
        label: 'Values source',
        name: 'vbCountSource',
        value: config.source,
        options: [
            { value: 'static', label: 'Static' },
            { value: 'dynamic', label: 'Dynamic (bindings / text)' },
        ],
        onChange: (value) => patchCounter(component, { source: value }, editor),
    }));

    mount.appendChild(section);

    return true;
}

/**
 * Update counter settings controls in place (avoids remount flicker).
 *
 * @param {HTMLElement} mount
 * @param {object} component
 */
export function syncAnimatedCounterSettingsForm(mount, component) {
    if (! mount || ! isAnimatedCounterComponent(component)) {
        return;
    }

    const config = readCounterConfig(component);
    const values = {
        vbCountFrom: String(config.from),
        vbCountTo: String(config.to),
        vbCountDuration: String(config.duration),
        vbCountDelay: String(config.delay),
        vbCountTrigger: String(config.trigger),
        vbCountEasing: String(config.easing),
        vbCountDecimals: String(config.decimals),
        vbCountPrefix: String(config.prefix ?? ''),
        vbCountSuffix: String(config.suffix ?? ''),
        vbCountSource: String(config.source),
    };

    Object.entries(values).forEach(([name, value]) => {
        const input = mount.querySelector(`[name="${CSS.escape(name)}"]`);

        if (! (input instanceof HTMLInputElement || input instanceof HTMLSelectElement)) {
            return;
        }

        if (input.value === value) {
            return;
        }

        input.value = value;

        if (input instanceof HTMLSelectElement) {
            const label = input.closest('.voodbuilder-gjs-select-wrap')
                ?.querySelector('.voodbuilder-gjs-select-trigger-label');
            const selected = input.options[input.selectedIndex];

            if (label && selected) {
                label.textContent = selected.textContent?.trim() || selected.value || '-';
            }
        }
    });
}

export { isAnimatedCounterComponent };
