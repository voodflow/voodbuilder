/**
 * Footer block settings descriptor (layout editor only).
 */

import { registerBlockSettings } from '../../../blocks/settings/index.js';
import { createCheckboxField, createFormSection } from '../../../editor-form-ui.js';
import { isFooterBlock } from '../../ids.js';
import {
    configureSiteFooterTraits,
    applySiteFooterSettingChange,
    footerBlockHasMenu,
    footerBlockHasColumns,
    footerBlockHasNewsletter,
    footerColumnLabel,
    footerSettingLabel,
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
        render: ({ mount: settingsMount, root, editor: gjsEditor }) => {
            if (traitsConfiguredFor !== root) {
                configureSiteFooterTraits(root, gjsEditor);
                traitsConfiguredFor = root;
            }

            const blockId = root.getAttributes()['data-voodbuilder-block'];
            const applyChange = (name, value) => {
                applySiteFooterSettingChange(gjsEditor, root, name, value);
            };

            const { section, fields } = createFormSection('Footer settings');

            fields.append(
                createCheckboxField({
                    label: footerSettingLabel(gjsEditor, 'footerShowLogo', 'Show logo'),
                    name: 'vpressShowBrand',
                    checked: root.get('vpressShowBrand') === true,
                    onChange: (checked) => applyChange('vpressShowBrand', checked),
                }),
                createCheckboxField({
                    label: footerSettingLabel(gjsEditor, 'footerShowTagline', 'Show tagline'),
                    name: 'vpressShowTagline',
                    checked: root.get('vpressShowTagline') === true,
                    onChange: (checked) => applyChange('vpressShowTagline', checked),
                }),
            );

            if (footerBlockHasMenu(blockId)) {
                fields.append(
                    createCheckboxField({
                        label: footerSettingLabel(gjsEditor, 'footerShowMenu', 'Show footer menu'),
                        name: 'vpressShowFooterMenu',
                        checked: root.get('vpressShowFooterMenu') === true,
                        onChange: (checked) => applyChange('vpressShowFooterMenu', checked),
                    }),
                );
            }

            fields.append(
                createCheckboxField({
                    label: footerSettingLabel(gjsEditor, 'footerShowSocial', 'Show social icons'),
                    name: 'vpressShowSocial',
                    checked: root.get('vpressShowSocial') === true,
                    onChange: (checked) => applyChange('vpressShowSocial', checked),
                }),
            );

            if (footerBlockHasColumns(blockId)) {
                for (let index = 1; index <= 4; index++) {
                    fields.append(
                        createCheckboxField({
                            label: footerColumnLabel(index, gjsEditor),
                            name: `vpressShowFooterCol${index}`,
                            checked: root.get(`vpressShowFooterCol${index}`) === true,
                            onChange: (checked) => applyChange(`vpressShowFooterCol${index}`, checked),
                        }),
                    );
                }

                fields.append(
                    createCheckboxField({
                        label: footerSettingLabel(gjsEditor, 'footerColumnsRedistribute', 'Redistribute visible columns'),
                        name: 'vpressFooterColumnsRedistribute',
                        checked: root.get('vpressFooterColumnsRedistribute') === true,
                        onChange: (checked) => applyChange('vpressFooterColumnsRedistribute', checked),
                    }),
                );
            }

            if (footerBlockHasNewsletter(blockId)) {
                fields.append(
                    createCheckboxField({
                        label: footerSettingLabel(gjsEditor, 'footerShowNewsletter', 'Show newsletter'),
                        name: 'vpressShowNewsletter',
                        checked: root.get('vpressShowNewsletter') === true,
                        onChange: (checked) => applyChange('vpressShowNewsletter', checked),
                    }),
                );
            }

            fields.append(
                createCheckboxField({
                    label: footerSettingLabel(gjsEditor, 'footerShowCopyright', 'Show copyright'),
                    name: 'vpressShowCopyright',
                    checked: root.get('vpressShowCopyright') === true,
                    onChange: (checked) => applyChange('vpressShowCopyright', checked),
                }),
            );

            settingsMount.appendChild(section);
        },
    });
}

/** @deprecated */
export const registerSiteFooterSettingsUi = registerFooterSettings;
