/**
 * Navbar block settings descriptor (layout editor only).
 */

import { registerBlockSettings } from '../../../blocks/settings/index.js';
import { findLayoutChromeZoneBlockRoot } from '../../../blocks/settings/select.js';
import {
    appendChromeLogoFields,
    createCheckboxField,
    createFormSection,
    createFormTabs,
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
            const tabKey = `site_nav:${root.cid ?? root.getId?.() ?? 'nav'}`;
            const rememberedTab = gjsEditor.__voodbuilderBlockSettingsTab?.[tabKey];
            const tabDefs = [
                { id: 'layout', label: label('navTabLayout', 'Layout') },
                { id: 'brand', label: label('navTabBrand', 'Brand') },
            ];
            const activeId = tabDefs.some((tab) => tab.id === rememberedTab)
                ? rememberedTab
                : 'layout';

            const { section, fields } = createFormSection(label('navSettingsTitle', 'Navbar settings'));
            const { root: tabsRoot, panels } = createFormTabs(tabDefs, {
                activeId,
                onActiveChange: (id) => {
                    if (! gjsEditor.__voodbuilderBlockSettingsTab) {
                        gjsEditor.__voodbuilderBlockSettingsTab = {};
                    }

                    gjsEditor.__voodbuilderBlockSettingsTab[tabKey] = id;
                },
            });

            panels.layout.append(
                createSelectField({
                    label: label('navMenuPosition', 'Menu position'),
                    name: 'voodbuilderMainNavAlign',
                    value: root.get('voodbuilderMainNavAlign') === 'center' ? 'center' : 'start',
                    options: [
                        { value: 'start', label: label('navMenuLeft', 'Left (next to logo)') },
                        { value: 'center', label: label('navMenuCenter', 'Center') },
                    ],
                    onChange: (value) => applyChange('voodbuilderMainNavAlign', value),
                }),
                createSelectField({
                    label: label('navSticky', 'Sticky'),
                    name: 'voodbuilderStickyNav',
                    value: root.get('voodbuilderStickyNav') ?? 'inherit',
                    options: [
                        { value: 'inherit', label: label('navStickyInherit', 'Site default') },
                        { value: 'sticky', label: label('navStickyOn', 'Sticky') },
                        { value: 'static', label: label('navStickyOff', 'Scrolls with page') },
                    ],
                    onChange: (value) => applyChange('voodbuilderStickyNav', value),
                }),
                createCheckboxField({
                    label: label('navShowSearch', 'Show search'),
                    name: 'voodbuilderShowSearch',
                    checked: root.get('voodbuilderShowSearch') === true,
                    onChange: (checked) => applyChange('voodbuilderShowSearch', checked),
                }),
                createCheckboxField({
                    label: label('navShowNotifications', 'Show notifications'),
                    name: 'voodbuilderShowNotifications',
                    checked: root.get('voodbuilderShowNotifications') === true,
                    onChange: (checked) => applyChange('voodbuilderShowNotifications', checked),
                }),
                createCheckboxField({
                    label: label('navShowProfile', 'Show account menu'),
                    name: 'voodbuilderShowProfileMenu',
                    checked: root.get('voodbuilderShowProfileMenu') === true,
                    onChange: (checked) => applyChange('voodbuilderShowProfileMenu', checked),
                }),
            );

            panels.brand.append(
                createCheckboxField({
                    label: label('navShowLogo', 'Show logo'),
                    name: 'voodbuilderShowLogo',
                    checked: root.get('voodbuilderShowLogo') !== false,
                    onChange: (checked) => applyChange('voodbuilderShowLogo', checked),
                }),
                createCheckboxField({
                    label: label('navShowSiteName', 'Show site name'),
                    name: 'voodbuilderShowSiteName',
                    checked: root.get('voodbuilderShowSiteName') !== false,
                    onChange: (checked) => applyChange('voodbuilderShowSiteName', checked),
                }),
            );

            appendChromeLogoFields({
                fields: panels.brand,
                root,
                editor: gjsEditor,
                applyChange,
                labelFn: label,
            });

            fields.appendChild(tabsRoot);
            settingsMount.appendChild(section);
        },
    });
}

/** @deprecated */
export const registerSiteNavSettingsUi = registerNavSettings;
