/**
 * Navbar block settings descriptor (layout editor only).
 */

import { registerBlockSettings } from '../../../blocks/settings/index.js';
import { findLayoutChromeZoneBlockRoot } from '../../../blocks/settings/select.js';
import {
    appendChromeLogoFields,
    createCheckboxField,
    createFormSection,
    createSelectField,
} from '../../../editor-form-ui.js';
import { configureSiteNavTraits, applySiteNavSettingChange, navSettingLabel } from './config.js';

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
        findRoot: findLayoutChromeZoneBlockRoot,
        render: ({ mount: settingsMount, root, editor: gjsEditor }) => {
            if (traitsConfiguredFor !== root) {
                configureSiteNavTraits(root, gjsEditor);
                traitsConfiguredFor = root;
            }

            const applyChange = (name, value) => {
                applySiteNavSettingChange(gjsEditor, root, name, value);
            };

            const label = (key, fallback) => navSettingLabel(gjsEditor, key, fallback);

            const { section, fields } = createFormSection(label('navSettingsTitle', 'Navbar settings'));

            fields.append(
                createCheckboxField({
                    label: label('navShowLogo', 'Show logo'),
                    name: 'vpressShowLogo',
                    checked: root.get('vpressShowLogo') !== false,
                    onChange: (checked) => applyChange('vpressShowLogo', checked),
                }),
                createCheckboxField({
                    label: label('navShowSiteName', 'Show site name'),
                    name: 'vpressShowSiteName',
                    checked: root.get('vpressShowSiteName') !== false,
                    onChange: (checked) => applyChange('vpressShowSiteName', checked),
                }),
            );

            appendChromeLogoFields({
                fields,
                root,
                editor: gjsEditor,
                applyChange,
                labelFn: label,
            });

            fields.append(
                createSelectField({
                    label: label('navMenuPosition', 'Menu position'),
                    name: 'vpressMainNavAlign',
                    value: root.get('vpressMainNavAlign') === 'center' ? 'center' : 'start',
                    options: [
                        { value: 'start', label: label('navMenuLeft', 'Left (next to logo)') },
                        { value: 'center', label: label('navMenuCenter', 'Center') },
                    ],
                    onChange: (value) => applyChange('vpressMainNavAlign', value),
                }),
                createSelectField({
                    label: label('navSticky', 'Sticky'),
                    name: 'vpressStickyNav',
                    value: root.get('vpressStickyNav') ?? 'inherit',
                    options: [
                        { value: 'inherit', label: label('navStickyInherit', 'Site default') },
                        { value: 'sticky', label: label('navStickyOn', 'Sticky') },
                        { value: 'static', label: label('navStickyOff', 'Scrolls with page') },
                    ],
                    onChange: (value) => applyChange('vpressStickyNav', value),
                }),
                createCheckboxField({
                    label: label('navShowSearch', 'Show search'),
                    name: 'vpressShowSearch',
                    checked: root.get('vpressShowSearch') === true,
                    onChange: (checked) => applyChange('vpressShowSearch', checked),
                }),
                createCheckboxField({
                    label: label('navShowNotifications', 'Show notifications'),
                    name: 'vpressShowNotifications',
                    checked: root.get('vpressShowNotifications') === true,
                    onChange: (checked) => applyChange('vpressShowNotifications', checked),
                }),
                createCheckboxField({
                    label: label('navShowProfile', 'Show account menu'),
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
