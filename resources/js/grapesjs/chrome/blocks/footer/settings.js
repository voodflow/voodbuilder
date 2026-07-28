/**
 * Footer block settings descriptor (layout editor only).
 */

import { registerBlockSettings } from '../../../blocks/settings/index.js';
import { findLayoutChromeZoneBlockRoot } from '../../../blocks/settings/select.js';
import {
    appendChromeLogoFields,
    createCheckboxField,
    createCheckboxGrid,
    createFormSection,
    createFormTabs,
    createSelectField,
} from '../../../editor-form-ui.js';
import { isFooterBlock } from '../../ids.js';
import {
    configureSiteFooterTraits,
    applySiteFooterSettingChange,
    footerBlockHasMenu,
    footerBlockHasColumns,
    footerBlockHasNewsletter,
    footerColumnLabel,
    footerSettingLabel,
    FOOTER_SOCIAL_ALIGN_PROP,
    normalizeFooterSocialAlign,
} from './config.js';

/**
 * @param {object} editor
 */
export function registerFooterSettings(editor) {
    if (editor.__voodbuilderSiteFooterSettingsRegistered) {
        return;
    }

    editor.__voodbuilderSiteFooterSettingsRegistered = true;

    let traitsConfiguredFor = null;

    registerBlockSettings({
        id: 'site_footer',
        layoutOnly: true,
        matchBlockId: (blockId) => isFooterBlock(blockId),
        findRoot: findLayoutChromeZoneBlockRoot,
        render: ({ mount: settingsMount, root, editor: gjsEditor }) => {
            if (traitsConfiguredFor !== root) {
                configureSiteFooterTraits(root, gjsEditor);
                traitsConfiguredFor = root;
            }

            const blockId = root.getAttributes()['data-voodbuilder-block'];
            const applyChange = (name, value) => {
                applySiteFooterSettingChange(gjsEditor, root, name, value);
            };

            const label = (key, fallback) => footerSettingLabel(gjsEditor, key, fallback);
            const hasColumns = footerBlockHasColumns(blockId);
            const hasLayoutOptions = footerBlockHasMenu(blockId) || footerBlockHasNewsletter(blockId);
            const tabKey = `site_footer:${root.cid ?? root.getId?.() ?? 'footer'}`;
            const rememberedTab = gjsEditor.__voodbuilderBlockSettingsTab?.[tabKey];

            const { section, fields } = createFormSection(label('footerSettingsTitle', 'Footer settings'));

            /** @type {{ id: string, label: string }[]} */
            const tabDefs = [];

            if (hasLayoutOptions) {
                tabDefs.push({ id: 'layout', label: label('footerTabLayout', 'Layout') });
            }

            tabDefs.push({ id: 'brand', label: label('footerTabBrand', 'Brand') });

            if (hasColumns) {
                tabDefs.push({ id: 'columns', label: label('footerTabColumns', 'Columns') });
            }

            const defaultTab = hasLayoutOptions ? 'layout' : 'brand';
            const activeId = tabDefs.some((tab) => tab.id === rememberedTab)
                ? rememberedTab
                : defaultTab;

            const { root: tabsRoot, panels } = createFormTabs(tabDefs, {
                activeId,
                onActiveChange: (id) => {
                    if (! gjsEditor.__voodbuilderBlockSettingsTab) {
                        gjsEditor.__voodbuilderBlockSettingsTab = {};
                    }

                    gjsEditor.__voodbuilderBlockSettingsTab[tabKey] = id;
                },
            });

            if (hasLayoutOptions && panels.layout) {
                if (footerBlockHasMenu(blockId)) {
                    panels.layout.append(
                        createCheckboxField({
                            label: label('footerShowMenu', 'Show footer menu'),
                            name: 'vpressShowFooterMenu',
                            checked: root.get('vpressShowFooterMenu') === true,
                            onChange: (checked) => applyChange('vpressShowFooterMenu', checked),
                        }),
                    );
                }

                if (footerBlockHasNewsletter(blockId)) {
                    panels.layout.append(
                        createCheckboxField({
                            label: label('footerShowNewsletter', 'Show newsletter'),
                            name: 'vpressShowNewsletter',
                            checked: root.get('vpressShowNewsletter') !== false,
                            onChange: (checked) => applyChange('vpressShowNewsletter', checked),
                        }),
                    );
                }

                panels.layout.append(
                    createSelectField({
                        label: label('footerSocialAlign', 'Social icons alignment'),
                        name: FOOTER_SOCIAL_ALIGN_PROP,
                        value: normalizeFooterSocialAlign(root.get(FOOTER_SOCIAL_ALIGN_PROP)),
                        options: [
                            { value: 'left', label: label('footerSocialAlignLeft', 'Left') },
                            { value: 'center', label: label('footerSocialAlignCenter', 'Center') },
                            { value: 'right', label: label('footerSocialAlignRight', 'Right') },
                        ],
                        onChange: (value) => applyChange(FOOTER_SOCIAL_ALIGN_PROP, value),
                    }),
                );
            }

            panels.brand.append(
                createCheckboxGrid([
                    createCheckboxField({
                        label: label('footerShowLogo', 'Show logo'),
                        name: 'vpressShowBrand',
                        checked: root.get('vpressShowBrand') !== false,
                        onChange: (checked) => applyChange('vpressShowBrand', checked),
                    }),
                    createCheckboxField({
                        label: label('footerShowSiteName', 'Show site name'),
                        name: 'vpressShowSiteName',
                        checked: root.get('vpressShowSiteName') !== false,
                        onChange: (checked) => applyChange('vpressShowSiteName', checked),
                    }),
                    createCheckboxField({
                        label: label('footerShowTagline', 'Show tagline'),
                        name: 'vpressShowTagline',
                        checked: root.get('vpressShowTagline') !== false,
                        onChange: (checked) => applyChange('vpressShowTagline', checked),
                    }),
                    createCheckboxField({
                        label: label('footerShowCopyright', 'Show copyright'),
                        name: 'vpressShowCopyright',
                        checked: root.get('vpressShowCopyright') !== false,
                        onChange: (checked) => applyChange('vpressShowCopyright', checked),
                    }),
                    createCheckboxField({
                        label: label('footerShowSocial', 'Show social icons'),
                        name: 'vpressShowSocial',
                        checked: root.get('vpressShowSocial') !== false,
                        onChange: (checked) => applyChange('vpressShowSocial', checked),
                    }),
                ]),
            );

            if (! hasLayoutOptions) {
                panels.brand.append(
                    createSelectField({
                        label: label('footerSocialAlign', 'Social icons alignment'),
                        name: FOOTER_SOCIAL_ALIGN_PROP,
                        value: normalizeFooterSocialAlign(root.get(FOOTER_SOCIAL_ALIGN_PROP)),
                        options: [
                            { value: 'left', label: label('footerSocialAlignLeft', 'Left') },
                            { value: 'center', label: label('footerSocialAlignCenter', 'Center') },
                            { value: 'right', label: label('footerSocialAlignRight', 'Right') },
                        ],
                        onChange: (value) => applyChange(FOOTER_SOCIAL_ALIGN_PROP, value),
                    }),
                );
            }

            appendChromeLogoFields({
                fields: panels.brand,
                root,
                editor: gjsEditor,
                applyChange,
                labelFn: label,
            });

            if (hasColumns && panels.columns) {
                for (let index = 1; index <= 4; index++) {
                    panels.columns.append(
                        createCheckboxField({
                            label: footerColumnLabel(index, gjsEditor),
                            name: `vpressShowFooterCol${index}`,
                            checked: root.get(`vpressShowFooterCol${index}`) === true,
                            onChange: (checked) => applyChange(`vpressShowFooterCol${index}`, checked),
                        }),
                    );
                }

                panels.columns.append(
                    createCheckboxField({
                        label: label('footerColumnsRedistribute', 'Redistribute visible columns'),
                        name: 'vpressFooterColumnsRedistribute',
                        checked: root.get('vpressFooterColumnsRedistribute') === true,
                        onChange: (checked) => applyChange('vpressFooterColumnsRedistribute', checked),
                    }),
                );
            }

            fields.appendChild(tabsRoot);
            settingsMount.appendChild(section);
        },
    });
}

/** @deprecated */
export const registerSiteFooterSettingsUi = registerFooterSettings;
