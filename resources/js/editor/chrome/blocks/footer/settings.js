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
    createTextField,
    createTextareaField,
} from '../../../editor-form-ui.js';
import { isFooterBlock } from '../../ids.js';
import {
    configureSiteFooterTraits,
    applySiteFooterSettingChange,
    footerBlockHasMenu,
    footerBlockHasColumns,
    footerBlockHasTextColumns,
    footerBlockHasNewsletter,
    footerBlockMenuColumnCount,
    footerContactIconForText,
    footerContactItemsJson,
    normalizeFooterContactItems,
    splitFooterLines,
    FOOTER_CONTACT_ITEM_LIMIT,
    FOOTER_CONTACT_ITEMS_PROP,
    FOOTER_VERTICAL_DIVIDERS_PROP,
    FOOTER_CONTACT_TITLE_PROP,
    FOOTER_CONTACT_LINES_PROP,
    FOOTER_DETAILS_TITLE_PROP,
    FOOTER_DETAILS_LINES_PROP,
    footerColumnLabel,
    footerSettingLabel,
    FOOTER_SOCIAL_ALIGN_PROP,
    FOOTER_TAGLINE_PROP,
    FOOTER_COPYRIGHT_PROP,
    FOOTER_DEFAULT_COPYRIGHT,
    normalizeFooterSocialAlign,
} from './config.js';
import {
    ensureTablerCatalog,
    queryTablerIcons,
    resolveTablerIconName,
    tablerIconSvg,
} from '../../../tabler-icons-catalog.js';

/**
 * Compact Tabler picker used by each repeatable contact row.
 *
 * @param {{
 *   value: string,
 *   label: (key: string, fallback: string) => string,
 *   onChange: (icon: string) => void,
 * }} opts
 * @returns {HTMLElement}
 */
function createFooterContactIconPicker({ value, label, onChange }) {
    const wrap = document.createElement('div');
    wrap.className = 'voodbuilder-editor-contact-icon-picker';

    const { field: searchField, input: searchInput } = createTextField({
        label: label('footerContactIconSearch', 'Search icon'),
        name: 'footerContactIconSearch',
        value: '',
        placeholder: label('footerContactIconSearchPlaceholder', 'Type an icon name…'),
    });

    const grid = document.createElement('div');
    grid.className = 'voodbuilder-editor-icon-picker__grid voodbuilder-editor-contact-icon-picker__grid';

    let query = '';
    let activeIcon = resolveTablerIconName(value);

    const render = () => {
        const { names } = queryTablerIcons({ query, limit: 48 });

        grid.replaceChildren(...names.map((name) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'voodbuilder-editor-icon-picker__item';
            button.title = name;
            button.setAttribute('aria-label', name);
            button.setAttribute('aria-pressed', name === activeIcon ? 'true' : 'false');

            if (name === activeIcon) {
                button.classList.add('is-active');
            }

            button.innerHTML = tablerIconSvg(name, {
                sizeClass: 'h-4 w-4',
                className: 'vb-icon__glyph',
            });

            button.addEventListener('click', () => {
                activeIcon = name;
                onChange(name);
            });

            return button;
        }));
    };

    searchInput.addEventListener('input', () => {
        query = searchInput.value.trim().toLowerCase();
        render();
    });

    render();
    void ensureTablerCatalog().then(render).catch(() => {});

    wrap.append(searchField, grid);

    return wrap;
}

/**
 * Repeatable contacts column: one row per icon + text entry.
 *
 * @param {{
 *   root: object,
 *   applyChange: (name: string, value: unknown) => void,
 *   label: (key: string, fallback: string) => string,
 * }} opts
 * @returns {HTMLElement}
 */
function createFooterContactItemsField({ root, applyChange, label }) {
    const stored = normalizeFooterContactItems(root.get(FOOTER_CONTACT_ITEMS_PROP));

    /** @type {{ icon: string, text: string }[]} */
    let items = stored.length > 0
        ? stored
        : splitFooterLines(root.get(FOOTER_CONTACT_LINES_PROP)).map((text) => ({
            icon: footerContactIconForText(text),
            text,
        }));

    let openPicker = -1;

    const field = document.createElement('div');
    field.className = 'voodbuilder-editor-form-field';
    field.setAttribute('data-voodbuilder-footer-contact-items', '');

    const labelEl = document.createElement('div');
    labelEl.className = 'voodbuilder-editor-form-label';
    labelEl.textContent = label('footerContactItems', 'Contacts (icon + text)');

    const list = document.createElement('div');
    list.className = 'voodbuilder-editor-contact-items';

    const hint = document.createElement('p');
    hint.className = 'voodbuilder-editor-form-hint';
    hint.textContent = label(
        'footerContactItemsHelp',
        'Emails, phone numbers and URLs become links automatically. Street addresses stay text.',
    );

    const addButton = document.createElement('button');
    addButton.type = 'button';
    addButton.className = 'voodbuilder-editor-form-action';
    addButton.textContent = label('footerAddContactItem', 'Add contact item');
    addButton.addEventListener('click', () => {
        if (items.length >= FOOTER_CONTACT_ITEM_LIMIT) {
            return;
        }

        items.push({ icon: 'point', text: '' });
        openPicker = items.length - 1;
        commit();
        render();
    });

    const commit = () => {
        applyChange(FOOTER_CONTACT_ITEMS_PROP, footerContactItemsJson(items));
    };

    const move = (index, offset) => {
        const target = index + offset;

        if (target < 0 || target >= items.length) {
            return;
        }

        [items[index], items[target]] = [items[target], items[index]];
        openPicker = -1;
        commit();
        render();
    };

    const row = (item, index) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'voodbuilder-editor-contact-item';

        const line = document.createElement('div');
        line.className = 'voodbuilder-editor-contact-item__line';

        const iconButton = document.createElement('button');
        iconButton.type = 'button';
        iconButton.className = 'voodbuilder-editor-contact-item__icon';
        iconButton.title = label('footerContactIcon', 'Icon');
        iconButton.setAttribute('aria-label', label('footerContactIcon', 'Icon'));
        iconButton.setAttribute('aria-expanded', openPicker === index ? 'true' : 'false');
        iconButton.innerHTML = tablerIconSvg(item.icon, {
            sizeClass: 'h-4 w-4',
            className: 'vb-icon__glyph',
        });
        iconButton.addEventListener('click', () => {
            openPicker = openPicker === index ? -1 : index;
            render();
        });

        const textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.className = 'voodbuilder-editor-input voodbuilder-editor-contact-item__text';
        textInput.value = item.text;
        textInput.placeholder = label('footerContactItemText', 'Email, phone or address');
        textInput.addEventListener('input', () => {
            items[index] = { ...items[index], text: textInput.value };
        });
        textInput.addEventListener('change', () => {
            items[index] = { ...items[index], text: textInput.value.trim() };
            commit();
        });

        line.append(iconButton, textInput);

        for (const [offset, glyph, titleKey, titleFallback] of [
            [-1, '↑', 'footerContactItemMoveUp', 'Move up'],
            [1, '↓', 'footerContactItemMoveDown', 'Move down'],
        ]) {
            const moveButton = document.createElement('button');
            moveButton.type = 'button';
            moveButton.className = 'voodbuilder-editor-contact-item__action';
            moveButton.textContent = glyph;
            moveButton.title = label(titleKey, titleFallback);
            moveButton.setAttribute('aria-label', label(titleKey, titleFallback));
            moveButton.disabled = offset === -1 ? index === 0 : index === items.length - 1;
            moveButton.addEventListener('click', () => move(index, offset));
            line.append(moveButton);
        }

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'voodbuilder-editor-contact-item__action voodbuilder-editor-contact-item__remove';
        removeButton.textContent = '×';
        removeButton.title = label('footerContactItemRemove', 'Remove');
        removeButton.setAttribute('aria-label', label('footerContactItemRemove', 'Remove'));
        removeButton.addEventListener('click', () => {
            items = items.filter((_, itemIndex) => itemIndex !== index);
            openPicker = -1;
            commit();
            render();
        });
        line.append(removeButton);

        wrapper.append(line);

        if (openPicker === index) {
            wrapper.append(createFooterContactIconPicker({
                value: item.icon,
                label,
                onChange: (icon) => {
                    items[index] = { ...items[index], icon };
                    openPicker = -1;
                    commit();
                    render();
                },
            }));
        }

        return wrapper;
    };

    const render = () => {
        list.replaceChildren(...items.map(row));

        addButton.disabled = items.length >= FOOTER_CONTACT_ITEM_LIMIT;
        list.classList.toggle('is-empty', items.length === 0);
    };

    render();
    field.append(labelEl, list, addButton, hint);

    return field;
}

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
                            name: 'voodbuilderShowFooterMenu',
                            checked: root.get('voodbuilderShowFooterMenu') === true,
                            onChange: (checked) => applyChange('voodbuilderShowFooterMenu', checked),
                        }),
                    );
                }

                if (footerBlockHasNewsletter(blockId)) {
                    panels.layout.append(
                        createCheckboxField({
                            label: label('footerShowNewsletter', 'Show newsletter'),
                            name: 'voodbuilderShowNewsletter',
                            checked: root.get('voodbuilderShowNewsletter') !== false,
                            onChange: (checked) => applyChange('voodbuilderShowNewsletter', checked),
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
                        label: label('footerShowLogoDesktop', 'Show logo (desktop)'),
                        name: 'voodbuilderShowLogoDesktop',
                        checked: root.get('voodbuilderShowLogoDesktop') !== false,
                        onChange: (checked) => applyChange('voodbuilderShowLogoDesktop', checked),
                    }),
                    createCheckboxField({
                        label: label('footerShowLogoMobile', 'Show logo (mobile)'),
                        name: 'voodbuilderShowLogoMobile',
                        checked: root.get('voodbuilderShowLogoMobile') !== false,
                        onChange: (checked) => applyChange('voodbuilderShowLogoMobile', checked),
                    }),
                    createCheckboxField({
                        label: label('footerShowSiteNameDesktop', 'Show site name (desktop)'),
                        name: 'voodbuilderShowSiteNameDesktop',
                        checked: root.get('voodbuilderShowSiteNameDesktop') !== false,
                        onChange: (checked) => applyChange('voodbuilderShowSiteNameDesktop', checked),
                    }),
                    createCheckboxField({
                        label: label('footerShowSiteNameMobile', 'Show site name (mobile)'),
                        name: 'voodbuilderShowSiteNameMobile',
                        checked: root.get('voodbuilderShowSiteNameMobile') !== false,
                        onChange: (checked) => applyChange('voodbuilderShowSiteNameMobile', checked),
                    }),
                    createCheckboxField({
                        label: label('footerShowTagline', 'Show tagline'),
                        name: 'voodbuilderShowTagline',
                        checked: root.get('voodbuilderShowTagline') !== false,
                        onChange: (checked) => applyChange('voodbuilderShowTagline', checked),
                    }),
                    createCheckboxField({
                        label: label('footerShowCopyright', 'Show copyright'),
                        name: 'voodbuilderShowCopyright',
                        checked: root.get('voodbuilderShowCopyright') !== false,
                        onChange: (checked) => applyChange('voodbuilderShowCopyright', checked),
                    }),
                    createCheckboxField({
                        label: label('footerShowSocial', 'Show social icons'),
                        name: 'voodbuilderShowSocial',
                        checked: root.get('voodbuilderShowSocial') !== false,
                        onChange: (checked) => applyChange('voodbuilderShowSocial', checked),
                    }),
                ]),
            );

            const { field: taglineField, input: taglineInput } = createTextareaField({
                label: label('footerTagline', 'Tagline'),
                name: FOOTER_TAGLINE_PROP,
                value: String(root.get(FOOTER_TAGLINE_PROP) ?? ''),
                placeholder: label('footerTaglinePlaceholder', 'A Visual CMS for Laravel & Filament'),
                rows: 2,
            });
            taglineInput.addEventListener('input', () => applyChange(FOOTER_TAGLINE_PROP, taglineInput.value));
            taglineInput.addEventListener('change', () => applyChange(FOOTER_TAGLINE_PROP, taglineInput.value));
            panels.brand.append(taglineField);

            const { field: copyrightField, input: copyrightInput } = createTextField({
                label: label('footerCopyright', 'Copyright'),
                name: FOOTER_COPYRIGHT_PROP,
                value: String(root.get(FOOTER_COPYRIGHT_PROP) ?? ''),
                placeholder: label('footerCopyrightPlaceholder', FOOTER_DEFAULT_COPYRIGHT),
            });
            copyrightInput.addEventListener('input', () => applyChange(FOOTER_COPYRIGHT_PROP, copyrightInput.value));
            copyrightInput.addEventListener('change', () => applyChange(FOOTER_COPYRIGHT_PROP, copyrightInput.value));
            panels.brand.append(copyrightField);

            const tagsHelp = document.createElement('p');
            tagsHelp.className = 'voodbuilder-editor-form-hint';
            tagsHelp.textContent = label(
                'footerCopyTagsHelp',
                'You can use text tags such as {current_year} and {brand_name}. Open “Available text tags” in the toolbar to copy them — they resolve on the published site and in this preview.',
            );
            panels.brand.append(tagsHelp);

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
                const menuColumnCount = footerBlockMenuColumnCount(blockId);

                for (let index = 1; index <= menuColumnCount; index++) {
                    panels.columns.append(
                        createCheckboxField({
                            label: footerColumnLabel(index, gjsEditor),
                            name: `voodbuilderShowFooterCol${index}`,
                            checked: root.get(`voodbuilderShowFooterCol${index}`) === true,
                            onChange: (checked) => applyChange(`voodbuilderShowFooterCol${index}`, checked),
                        }),
                    );
                }

                panels.columns.append(
                    createCheckboxField({
                        label: label('footerColumnsRedistribute', 'Redistribute visible columns'),
                        name: 'voodbuilderFooterColumnsRedistribute',
                        checked: root.get('voodbuilderFooterColumnsRedistribute') === true,
                        onChange: (checked) => applyChange('voodbuilderFooterColumnsRedistribute', checked),
                    }),
                );

                if (footerBlockHasTextColumns(blockId)) {
                    panels.columns.append(
                        createCheckboxField({
                            label: label('footerVerticalDividers', 'Vertical dividers (brand | columns | copyright)'),
                            name: FOOTER_VERTICAL_DIVIDERS_PROP,
                            checked: root.get(FOOTER_VERTICAL_DIVIDERS_PROP) === true,
                            onChange: (checked) => applyChange(FOOTER_VERTICAL_DIVIDERS_PROP, checked),
                        }),
                    );

                    const dividersHelp = document.createElement('p');
                    dividersHelp.className = 'voodbuilder-editor-form-hint';
                    dividersHelp.textContent = label(
                        'footerVerticalDividersHelp',
                        'Splits the footer into brand | link columns | copyright, with a rule between each block. The details column moves under the copyright.',
                    );
                    panels.columns.append(dividersHelp);
                }

                if (footerBlockHasTextColumns(blockId)) {
                    const { field: contactTitleField, input: contactTitleInput } = createTextField({
                        label: label('footerContactTitle', 'Contacts column title'),
                        name: FOOTER_CONTACT_TITLE_PROP,
                        value: String(root.get(FOOTER_CONTACT_TITLE_PROP) ?? ''),
                    });
                    contactTitleInput.addEventListener('input', () => applyChange(FOOTER_CONTACT_TITLE_PROP, contactTitleInput.value));
                    contactTitleInput.addEventListener('change', () => applyChange(FOOTER_CONTACT_TITLE_PROP, contactTitleInput.value));
                    panels.columns.append(contactTitleField);

                    panels.columns.append(createFooterContactItemsField({
                        root,
                        applyChange,
                        label,
                    }));

                    const { field: detailsTitleField, input: detailsTitleInput } = createTextField({
                        label: label('footerDetailsTitle', 'Details column title'),
                        name: FOOTER_DETAILS_TITLE_PROP,
                        value: String(root.get(FOOTER_DETAILS_TITLE_PROP) ?? ''),
                    });
                    detailsTitleInput.addEventListener('input', () => applyChange(FOOTER_DETAILS_TITLE_PROP, detailsTitleInput.value));
                    detailsTitleInput.addEventListener('change', () => applyChange(FOOTER_DETAILS_TITLE_PROP, detailsTitleInput.value));
                    panels.columns.append(detailsTitleField);

                    const { field: detailsLinesField, input: detailsLinesInput } = createTextareaField({
                        label: label('footerDetailsLines', 'Details (one line per item)'),
                        name: FOOTER_DETAILS_LINES_PROP,
                        value: String(root.get(FOOTER_DETAILS_LINES_PROP) ?? ''),
                        rows: 4,
                    });
                    detailsLinesInput.addEventListener('input', () => applyChange(FOOTER_DETAILS_LINES_PROP, detailsLinesInput.value));
                    detailsLinesInput.addEventListener('change', () => applyChange(FOOTER_DETAILS_LINES_PROP, detailsLinesInput.value));
                    panels.columns.append(detailsLinesField);

                    const detailsHelp = document.createElement('p');
                    detailsHelp.className = 'voodbuilder-editor-form-hint';
                    detailsHelp.textContent = label(
                        'footerDetailsLinesHelp',
                        'Organisations, departments, legal notes: one line per item.',
                    );
                    panels.columns.append(detailsHelp);
                }
            }

            fields.appendChild(tabsRoot);
            settingsMount.appendChild(section);
        },
    });
}

/** @deprecated */
export const registerSiteFooterSettingsUi = registerFooterSettings;
