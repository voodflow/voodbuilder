/**
 * Content-panel settings for Layout Section / Container.
 */

import {
    createFormSection,
    createSelectField,
} from './editor-form-ui.js';
import {
    LAYOUT_PRESET_ATTR,
    LAYOUT_PRESETS,
    applyContainerLayoutPreset,
    isLayoutContainer,
    isLayoutSection,
    openContainerLayoutPicker,
    resolveLayoutPresetContainer,
} from './layout-blocks.js';

const SECTION_PADDING = [
    { value: 'py-6', label: 'S' },
    { value: 'py-12', label: 'M' },
    { value: 'py-16', label: 'L' },
    { value: 'py-24', label: 'XL' },
];

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

function componentKey(component) {
    return String(component?.cid ?? component?.getId?.() ?? component?.get?.('id') ?? '');
}

export function isLayoutStructureComponent(component) {
    return isLayoutSection(component) || isLayoutContainer(component);
}

function readSectionPadding(component) {
    const classes = component.getClasses?.() ?? [];

    return SECTION_PADDING.find((item) => classes.includes(item.value))?.value ?? 'py-12';
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderLayoutStructureSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component || ! isLayoutStructureComponent(component)) {
        return false;
    }

    const key = componentKey(component);
    const existing = mount.querySelector('[data-voodbuilder-layout-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    const container = resolveLayoutPresetContainer(component);
    const isSection = isLayoutSection(component);
    const title = isSection
        ? (labels.layoutSectionSettingsTitle ?? 'Section')
        : (labels.layoutContainerSettingsTitle ?? 'Container');

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const { section, fields } = createFormSection(title);
    section.setAttribute('data-voodbuilder-layout-settings', '');
    section.setAttribute('data-component-key', key);

    if (container) {
        const currentPreset = String(container.getAttributes?.()?.[LAYOUT_PRESET_ATTR] ?? '1');

        const presetField = createSelectField({
            label: labels.layoutColumns ?? 'Columns',
            name: 'layoutPreset',
            value: currentPreset,
            options: LAYOUT_PRESETS.map((preset) => ({
                value: preset.id,
                label: preset.label,
            })),
            onChange: (value) => {
                runWithSettingsChangeGuard(editor, () => {
                    applyContainerLayoutPreset(container, value);
                    editor.select?.(isSection ? component : container);
                    editor.__voodbuilderSchedulePageCssRebuild?.(0);
                });
            },
        });

        fields.appendChild(presetField);

        const pickerBtn = document.createElement('button');
        pickerBtn.type = 'button';
        pickerBtn.className = 'voodbuilder-gjs-form-action';
        pickerBtn.textContent = labels.layoutOpenPicker ?? 'Open layout picker';
        pickerBtn.addEventListener('click', (event) => {
            event.preventDefault();
            openContainerLayoutPicker(editor, container, labels);
        });
        fields.appendChild(pickerBtn);
    }

    if (isSection) {
        let padding = readSectionPadding(component);

        const paddingField = createSelectField({
            label: labels.layoutSectionPadding ?? 'Vertical padding',
            name: 'sectionPadding',
            value: padding,
            options: SECTION_PADDING,
            onChange: (value) => {
                padding = value;
                runWithSettingsChangeGuard(editor, () => {
                    const next = [...(component.getClasses?.() ?? [])]
                        .filter((token) => ! String(token).startsWith('py-'));

                    next.push(padding);
                    component.setClass(next);
                    editor.__voodbuilderSchedulePageCssRebuild?.(0);
                });
            },
        });

        fields.appendChild(paddingField);
    }

    mount.appendChild(section);

    return true;
}
