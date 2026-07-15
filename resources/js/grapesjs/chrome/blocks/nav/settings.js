/**
 * Navbar block settings descriptor (layout editor only).
 */

import { registerBlockSettings } from '../../../blocks/settings/index.js';
import { createCheckboxField, createFormSection, createSelectField } from '../../../editor-form-ui.js';
import { configureSiteNavTraits, applySiteNavSettingChange } from './config.js';

/**
 * @param {object} editor
 */
export function registerNavSettings(editor) {
    if (editor.__voodbuilderSiteNavSettingsRegistered) {
        return;
    }

    editor.__voodbuilderSiteNavSettingsRegistered = true;

    let traitsConfiguredFor = null;

    registerBlockSettings({
        id: 'site_nav',
        layoutOnly: true,
        blockIds: ['site_nav_simple', 'site_header'],
        matchBlockId: (blockId) => typeof blockId === 'string' && blockId.startsWith('site_nav_'),
        render: ({ mount: settingsMount, root, editor: gjsEditor }) => {
            if (traitsConfiguredFor !== root) {
                configureSiteNavTraits(root, gjsEditor);
                traitsConfiguredFor = root;
            }

            const applyChange = (name, value) => {
                applySiteNavSettingChange(gjsEditor, root, name, value);
            };

            const { section, fields } = createFormSection('Navbar settings');

            fields.append(
                createSelectField({
                    label: 'Menu position',
                    name: 'vpressMainNavAlign',
                    value: root.get('vpressMainNavAlign') === 'center' ? 'center' : 'start',
                    options: [
                        { value: 'start', label: 'Left (next to logo)' },
                        { value: 'center', label: 'Center' },
                    ],
                    onChange: (value) => applyChange('vpressMainNavAlign', value),
                }),
                createSelectField({
                    label: 'Sticky',
                    name: 'vpressStickyNav',
                    value: root.get('vpressStickyNav') ?? 'inherit',
                    options: [
                        { value: 'inherit', label: 'Site default' },
                        { value: 'sticky', label: 'Sticky' },
                        { value: 'static', label: 'Scrolls with page' },
                    ],
                    onChange: (value) => applyChange('vpressStickyNav', value),
                }),
                createCheckboxField({
                    label: 'Show search',
                    name: 'vpressShowSearch',
                    checked: root.get('vpressShowSearch') === true,
                    onChange: (checked) => applyChange('vpressShowSearch', checked),
                }),
                createCheckboxField({
                    label: 'Show notifications',
                    name: 'vpressShowNotifications',
                    checked: root.get('vpressShowNotifications') === true,
                    onChange: (checked) => applyChange('vpressShowNotifications', checked),
                }),
                createCheckboxField({
                    label: 'Show account menu',
                    name: 'vpressShowProfileMenu',
                    checked: root.get('vpressShowProfileMenu') === true,
                    onChange: (checked) => applyChange('vpressShowProfileMenu', checked),
                }),
            );

            settingsMount.appendChild(section);
        },
    });
}

/** @deprecated */
export const registerSiteNavSettingsUi = registerNavSettings;
