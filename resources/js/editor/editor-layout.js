/**
 * Voodbuilder Editor shell — 3-column builder layout (blocks | canvas | inspector).
 * Uses only public Editor APIs: appendTo, Panels, BlockManager container.
 */

import { STYLE_MANAGER_SECTORS } from './editor-chrome.js';
import { registerBlockPins } from './block-pins.js';
import { lucideIcon, tablerIcon } from './editor-icons.js';
import { animatedMarkMarkup } from './editor-build-status.js';
import { setupStyleInspectorSectors } from './inspector-collapsible-sector.js';
import { registerInspectorSelectUi } from './inspector-select-ui.js';
import { registerInspectorColorFix } from './inspector-color-fix.js';
import { applyBlocksLibraryUi, collapseAllBlockCategories, collapseLibraryCategories, readBlocksSearchQuery } from './blocks-library-sync.js';
import { resolveBlockSettingsTarget, promoteRoot, refreshBlockSettingsUi, findInspectableRoot, ensureRootInspectable, readBlockId } from './blocks/settings/index.js';
import {
    inspectorSelectionNotice,
} from './chrome-editor-guards.js';
import {
    createInspectorEmptyState,
} from './inspector-empty-state.js';
import {
    ensurePageSurfaceAction,
    isPageSurfaceComponent,
    isPageSurfaceMode,
    selectPageSurface,
} from './page-surface-styles.js';
import { canEntitlement } from './editor/entitlements.js';
import { findRichTextHost, isRichTextComponent } from './text-elements.js';

const INSPECTOR_TABS = ['content', 'style', 'dynamic', 'conditions', 'layers'];
const POPUP_INSPECTOR_TABS = ['content', 'style', 'dynamic', 'layers', 'popup'];

const LIBRARY_TAB_ICONS = {
    blocks: 'wall',
    components: 'components',
    templates: 'template',
};

const INSPECTOR_TAB_ICONS = {
    content: 'pencil',
    style: 'color-swatch',
    dynamic: 'database',
    conditions: 'directions',
    layers: 'layers',
    popup: 'settings',
};

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Resolve topbar context under the brand mark: "Page · Home" / "Layout · Docs" / "Popup · …".
 *
 * @param {Record<string, unknown>} options
 * @param {Record<string, string>} labels
 * @returns {{ kind: string, name: string } | null}
 */
export function resolveEditingContext(options = {}, labels = {}) {
    if (options.popupMode) {
        const name = String(options.popupName ?? '').trim();

        return {
            kind: labels.editingContextPopup ?? 'Popup',
            name: name !== '' ? name : (labels.editingContextUntitled ?? 'Untitled'),
        };
    }

    if (options.chromeLayoutMode) {
        const name = String(options.chromeLayoutName ?? '').trim();

        return {
            kind: labels.editingContextLayout ?? 'Layout',
            name: name !== '' ? name : (labels.editingContextUntitled ?? 'Untitled'),
        };
    }

    const name = String(options.pageTitle ?? options.pageName ?? '').trim();

    if (name === '' && ! options.pageId) {
        return null;
    }

    return {
        kind: labels.editingContextPage ?? 'Page',
        name: name !== '' ? name : (labels.editingContextUntitled ?? 'Untitled'),
    };
}

function editingContextMarkup(context) {
    if (! context?.kind && ! context?.name) {
        return '';
    }

    const kind = String(context.kind ?? '').trim();
    const name = String(context.name ?? '').trim();
    const title = name !== '' ? name : kind;
    const hover = kind !== '' && name !== '' ? `${kind} · ${name}` : title;

    if (title === '') {
        return '';
    }

    return `
        <div class="voodbuilder-editor-topbar__editing-context" title="${escapeHtml(hover)}">
            <span class="voodbuilder-editor-topbar__editing-name">${escapeHtml(title)}</span>
            ${kind !== '' && name !== '' ? `<span class="voodbuilder-editor-topbar__editing-kind sr-only">${escapeHtml(kind)}</span>` : ''}
        </div>
    `;
}

/**
 * @param {Record<string, unknown>|null|undefined} summary
 * @param {Record<string, string>} labels
 * @returns {string}
 */
function editionChromeMarkup(summary, labels = {}) {
    if (! summary || typeof summary !== 'object') {
        return '';
    }

    const badge = String(summary.badge ?? summary.label ?? '').trim();

    if (badge === '') {
        return '';
    }

    const infoLabel = labels.editionInfo ?? 'Edition details';

    return `
        <div class="voodbuilder-editor-topbar__edition" data-voodbuilder-edition-chrome>
            <span class="voodbuilder-editor-topbar__edition-badge">${escapeHtml(badge)}</span>
            <button
                type="button"
                class="voodbuilder-editor-topbar__edition-info"
                data-voodbuilder-edition-info
                aria-haspopup="dialog"
                aria-expanded="false"
                title="${escapeHtml(infoLabel)}"
                aria-label="${escapeHtml(infoLabel)}"
            >${lucideIcon('info', 14)}</button>
        </div>
    `;
}

/**
 * @param {string|null|undefined} iso
 * @returns {string}
 */
function formatEditionExpiry(iso) {
    const raw = String(iso ?? '').trim();

    if (raw === '') {
        return '';
    }

    const date = new Date(raw);

    if (Number.isNaN(date.getTime())) {
        return raw;
    }

    try {
        return date.toLocaleDateString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return raw;
    }
}

/**
 * @param {string} group
 * @param {Record<string, string>} labels
 * @param {Record<string, unknown>} summary
 * @returns {string}
 */
function editionPackageGroupLabel(group, labels = {}, summary = {}) {
    switch (group) {
        case 'core':
            return labels.editionInfoGroupCore ?? 'Core';
        case 'edition': {
            const title = String(summary.edition_packages_title ?? '').trim();

            if (title !== '') {
                return title;
            }

            return labels.editionInfoGroupEdition ?? 'Edition';
        }
        case 'extra':
            return labels.editionInfoGroupExtra ?? 'Extra core & licences';
        default:
            return group;
    }
}

/**
 * @param {boolean|null|undefined} value
 * @param {Record<string, string>} labels
 * @returns {string}
 */
function editionPackageBoolLabel(value, labels = {}) {
    if (value === true) {
        return labels.editionInfoPkgYes ?? 'Yes';
    }

    if (value === false) {
        return labels.editionInfoPkgNo ?? 'No';
    }

    return labels.editionInfoPkgNa ?? '—';
}

/**
 * @param {Record<string, unknown>} summary
 * @param {Record<string, string>} labels
 * @returns {string}
 */
function buildEditionModalBody(summary, labels = {}) {
    const configured = summary.licence_configured === true;
    const active = summary.active === true;
    let status = labels.editionInfoStatusUnconfigured ?? 'No licence key';

    if (configured) {
        status = active
            ? (labels.editionInfoStatusActive ?? 'Active')
            : (labels.editionInfoStatusInactive ?? 'Inactive');
    }

    const expiresRaw = String(summary.expires_at ?? '').trim();
    const expires = expiresRaw !== ''
        ? formatEditionExpiry(expiresRaw)
        : (labels.editionInfoExpiresNever ?? 'No expiry date');
    const packageLabel = String(summary.package_status_label ?? summary.package_version ?? '').trim();
    const packageStatus = String(summary.package_status ?? 'unknown').trim();
    const packageStatusAttr = packageStatus === 'current'
        ? 'active'
        : (packageStatus === 'update' || packageStatus === 'ahead' ? 'warn' : '');
    const docsUrl = String(summary.docs_url ?? 'https://docs.voodflow.com').trim();
    const message = String(summary.message ?? '').trim();
    const edition = String(summary.label ?? summary.badge ?? '').trim();
    const packages = Array.isArray(summary.packages) ? summary.packages : [];

    const metaRows = [
        edition !== ''
            ? `<div class="voodbuilder-editor-edition-modal__row"><dt>${escapeHtml(labels.editionInfoEdition ?? 'Edition')}</dt><dd>${escapeHtml(edition)}</dd></div>`
            : '',
        `<div class="voodbuilder-editor-edition-modal__row"><dt>${escapeHtml(labels.editionInfoStatus ?? 'Licence status')}</dt><dd data-status="${configured && active ? 'active' : 'warn'}">${escapeHtml(status)}</dd></div>`,
        `<div class="voodbuilder-editor-edition-modal__row"><dt>${escapeHtml(labels.editionInfoExpires ?? 'Expires')}</dt><dd>${escapeHtml(expires)}</dd></div>`,
        packageLabel !== ''
            ? `<div class="voodbuilder-editor-edition-modal__row"><dt>${escapeHtml(labels.editionInfoPackage ?? 'Core package')}</dt><dd${packageStatusAttr !== '' ? ` data-status="${packageStatusAttr}"` : ''}>${escapeHtml(String(summary.package_version ?? ''))}${packageLabel !== String(summary.package_version ?? '') ? ` · ${escapeHtml(packageLabel)}` : ''}</dd></div>`
            : '',
        message !== ''
            ? `<div class="voodbuilder-editor-edition-modal__row voodbuilder-editor-edition-modal__row--full"><dt>${escapeHtml(labels.editionInfoMessage ?? 'Note')}</dt><dd>${escapeHtml(message)}</dd></div>`
            : '',
    ].filter(Boolean).join('');

    /** @type {Map<string, Array<Record<string, unknown>>>} */
    const byGroup = new Map();

    packages.forEach((pkg) => {
        if (! pkg || typeof pkg !== 'object') {
            return;
        }

        const group = String(pkg.group ?? 'extra');

        if (! byGroup.has(group)) {
            byGroup.set(group, []);
        }

        byGroup.get(group).push(pkg);
    });

    const groupOrder = ['core', 'edition', 'extra'];
    const packageSections = groupOrder
        .filter((group) => byGroup.has(group) && byGroup.get(group).length > 0)
        .map((group) => {
            const rows = byGroup.get(group).map((pkg) => {
                const activeAttr = pkg.active === true
                    ? 'active'
                    : (pkg.active === false ? 'warn' : '');
                const registeredAttr = pkg.registered === true
                    ? 'active'
                    : (pkg.registered === false ? 'warn' : '');
                const version = pkg.installed === false
                    ? (labels.editionInfoPkgMissing ?? 'Not installed')
                    : (pkg.version ?? labels.editionInfoPkgNa ?? '—');

                return `
                    <tr>
                        <th scope="row">
                            <span class="voodbuilder-editor-edition-modal__pkg-name">${escapeHtml(pkg.name ?? pkg.id ?? '')}</span>
                            <span class="voodbuilder-editor-edition-modal__pkg-composer">${escapeHtml(pkg.composer ?? '')}</span>
                        </th>
                        <td data-status="${activeAttr}">${escapeHtml(editionPackageBoolLabel(pkg.active, labels))}</td>
                        <td data-status="${registeredAttr}">${escapeHtml(editionPackageBoolLabel(pkg.registered, labels))}</td>
                        <td>${escapeHtml(version)}</td>
                    </tr>
                `;
            }).join('');

            return `
                <section class="voodbuilder-editor-edition-modal__group">
                    <h3 class="voodbuilder-editor-edition-modal__group-title">${escapeHtml(editionPackageGroupLabel(group, labels, summary))}</h3>
                    <div class="voodbuilder-editor-edition-modal__table-wrap">
                        <table class="voodbuilder-editor-edition-modal__table">
                            <colgroup>
                                <col class="voodbuilder-editor-edition-modal__col-pkg">
                                <col class="voodbuilder-editor-edition-modal__col-active">
                                <col class="voodbuilder-editor-edition-modal__col-registered">
                                <col class="voodbuilder-editor-edition-modal__col-version">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th scope="col">${escapeHtml(labels.editionInfoPkgName ?? 'Package')}</th>
                                    <th scope="col">${escapeHtml(labels.editionInfoPkgActiveCol ?? 'Active')}</th>
                                    <th scope="col">${escapeHtml(labels.editionInfoPkgRegisteredCol ?? 'Registered')}</th>
                                    <th scope="col">${escapeHtml(labels.editionInfoPkgVersion ?? 'Version')}</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                </section>
            `;
        }).join('');

    return `
        <dl class="voodbuilder-editor-edition-modal__meta">${metaRows}</dl>
        ${packageSections !== '' ? `
            <div class="voodbuilder-editor-edition-modal__packages">
                ${packageSections}
            </div>
        ` : ''}
        ${docsUrl !== '' ? `
            <a
                class="voodbuilder-editor-edition-modal__docs"
                href="${escapeHtml(docsUrl)}"
                target="_blank"
                rel="noopener noreferrer"
            >${lucideIcon('external-link', 13)}<span>${escapeHtml(labels.editionInfoDocs ?? 'Documentation')}</span></a>
        ` : ''}
    `;
}

/**
 * @param {HTMLElement|null|undefined} shellRoot
 * @param {Record<string, unknown>|null|undefined} summary
 * @param {Record<string, string>} labels
 */
export function wireEditionInfoChrome(shellRoot, summary, labels = {}) {
    const chrome = shellRoot?.querySelector?.('[data-voodbuilder-edition-chrome]');
    const button = chrome?.querySelector?.('[data-voodbuilder-edition-info]');

    if (! chrome || ! button || ! summary) {
        return;
    }

    /** @type {HTMLElement|null} */
    let modal = null;

    const close = () => {
        if (! modal) {
            return;
        }

        modal.hidden = true;
        modal.remove();
        modal = null;
        button.setAttribute('aria-expanded', 'false');
        chrome.classList.remove('is-edition-open');
        document.removeEventListener('keydown', onKeyDown, true);
    };

    const onKeyDown = (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
        }
    };

    const open = () => {
        if (modal) {
            close();
        }

        const title = labels.editionInfo ?? 'Edition details';
        const closeLabel = labels.editionInfoClose ?? labels.dialogCancel ?? 'Close';

        modal = document.createElement('div');
        modal.className = 'voodbuilder-editor-modal voodbuilder-editor-edition-modal';
        modal.setAttribute('role', 'presentation');
        modal.innerHTML = `
            <div class="voodbuilder-editor-modal__backdrop" data-voodbuilder-edition-close></div>
            <div
                class="voodbuilder-editor-modal__panel voodbuilder-editor-edition-modal__panel"
                role="dialog"
                aria-modal="true"
                aria-labelledby="voodbuilder-edition-modal-title"
            >
                <header class="voodbuilder-editor-modal__head">
                    <h2 class="voodbuilder-editor-modal__title" id="voodbuilder-edition-modal-title">${escapeHtml(title)}</h2>
                    <button type="button" class="voodbuilder-editor-modal__close" data-voodbuilder-edition-close aria-label="${escapeHtml(closeLabel)}">×</button>
                </header>
                <div class="voodbuilder-editor-modal__body voodbuilder-editor-edition-modal__body">
                    ${buildEditionModalBody(summary, labels)}
                </div>
                <footer class="voodbuilder-editor-edition-modal__foot">
                    <button type="button" class="voodbuilder-editor-btn" data-voodbuilder-edition-close>${escapeHtml(closeLabel)}</button>
                </footer>
            </div>
        `;

        document.body.appendChild(modal);
        modal.hidden = false;
        button.setAttribute('aria-expanded', 'true');
        chrome.classList.add('is-edition-open');
        document.addEventListener('keydown', onKeyDown, true);

        modal.querySelectorAll('[data-voodbuilder-edition-close]').forEach((el) => {
            el.addEventListener('click', (event) => {
                event.preventDefault();
                close();
            });
        });
    };

    button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (modal) {
            close();
        } else {
            open();
        }
    });
}

/**
 * Place a companion control in the topbar actions group.
 *
 * Callers used to anchor on the save-state readout, which put them to the left of it.
 * The readout now leads the group, so controls belong between it and Exit: everything
 * that acts on the page stays adjacent to Save.
 *
 * @param {HTMLElement|null} actionsMount  `.voodbuilder-editor-topbar__actions`
 * @param {HTMLElement} control
 */
export function mountTopbarAction(actionsMount, control) {
    if (! actionsMount || ! control) {
        return;
    }

    const exitLink = actionsMount.querySelector('[data-voodbuilder-editor-exit]')
        // Older shells had no marker on the exit link.
        ?? actionsMount.querySelector('.voodbuilder-editor-topbar__btn--ghost[href]');

    if (exitLink) {
        actionsMount.insertBefore(control, exitLink);

        return;
    }

    actionsMount.appendChild(control);
}

export function buildEditorShell(container, labels = {}, meta = {}) {
    const hideTemplates = Boolean(meta.hideTemplates);
    const popupMode = Boolean(meta.popupMode);
    const brand = String(meta.brand ?? 'VoodBuilder').trim() || 'VoodBuilder';
    const editingContext = meta.editingContext
        ?? (meta.editingBadgeTitle
            ? { kind: meta.editingBadgeTitle, name: '' }
            : null);
    const editionSummary = meta.editionSummary && typeof meta.editionSummary === 'object'
        ? meta.editionSummary
        : null;
    const inspectorTabs = popupMode
        ? POPUP_INSPECTOR_TABS
        : INSPECTOR_TABS;
    const defaultInspectorTab = popupMode ? 'popup' : 'content';

    container.classList.add('voodbuilder-editor-root');
    container.innerHTML = `
        <div class="voodbuilder-editor-shell" data-voodbuilder-device="desktop"${popupMode ? ' data-voodbuilder-popup-mode="true"' : ''}>
            <header class="voodbuilder-editor-topbar">
                <div class="voodbuilder-editor-topbar__brand-wrap">
                    <div class="voodbuilder-editor-topbar__brand" aria-label="${escapeHtml(brand)}">
                        <span class="voodbuilder-editor-topbar__brand-mark">${animatedMarkMarkup(32)}</span>
                        <span class="voodbuilder-editor-topbar__brand-sr">${escapeHtml(brand)}</span>
                    </div>
                    <div class="voodbuilder-editor-topbar__brand-meta">
                        ${editingContextMarkup(editingContext)}
                        ${editionChromeMarkup(editionSummary, labels)}
                    </div>
                </div>
                <div class="voodbuilder-editor-topbar__tools"></div>
                <div class="voodbuilder-editor-topbar__actions">
                    <span
                        class="voodbuilder-editor-topbar__status"
                        data-voodbuilder-editor-saved
                        aria-live="polite"
                        hidden
                    ></span>
                    <a
                        href="${escapeHtml(meta.exitUrl ?? '#')}"
                        class="voodbuilder-editor-topbar__btn voodbuilder-editor-topbar__btn--ghost"
                        data-voodbuilder-editor-exit
                    >${escapeHtml(labels.exitEditor ?? 'Exit editor')}</a>
                    <button
                        type="button"
                        class="voodbuilder-editor-topbar__btn voodbuilder-editor-topbar__btn--primary"
                        data-voodbuilder-editor-save
                    >
                        <span data-voodbuilder-editor-save-label>${escapeHtml(labels.save ?? 'Save')}</span>
                    </button>
                </div>
            </header>
            <div class="voodbuilder-editor-shell__workspace">
                <aside class="voodbuilder-editor-shell__left" aria-label="${escapeHtml(labels.panelLibrary ?? labels.panelBlocks ?? 'Library')}">
                    <div class="voodbuilder-editor-library-tabs" role="tablist">
                        <button type="button" class="voodbuilder-editor-library-tab voodbuilder-editor-library-tab--active" data-voodbuilder-library="blocks" role="tab" aria-selected="true" title="${escapeHtml(labels.tabElements ?? 'Elements')}" aria-label="${escapeHtml(labels.tabElements ?? 'Elements')}">
                            ${tablerIcon(LIBRARY_TAB_ICONS.blocks, 17)}
                        </button>
                        <button type="button" class="voodbuilder-editor-library-tab" data-voodbuilder-library="components" role="tab" aria-selected="false" title="${escapeHtml(labels.tabComponents ?? 'Components')}" aria-label="${escapeHtml(labels.tabComponents ?? 'Components')}">
                            ${tablerIcon(LIBRARY_TAB_ICONS.components, 17)}
                        </button>
                        ${hideTemplates ? '' : `
                        <button type="button" class="voodbuilder-editor-library-tab" data-voodbuilder-library="templates" role="tab" aria-selected="false" title="${escapeHtml(labels.tabTemplates ?? 'Templates')}" aria-label="${escapeHtml(labels.tabTemplates ?? 'Templates')}">
                            ${tablerIcon(LIBRARY_TAB_ICONS.templates, 17)}
                        </button>
                        `}
                    </div>
                    <label class="voodbuilder-editor-blocks-search-wrap">
                        <span class="voodbuilder-editor-blocks-search-icon">${lucideIcon('search', 16)}</span>
                        <input
                            type="search"
                            class="voodbuilder-editor-blocks-search"
                            placeholder="${escapeHtml(labels.blockSearch ?? 'Search elements…')}"
                            autocomplete="off"
                            aria-label="${escapeHtml(labels.blockSearch ?? 'Search elements')}"
                        />
                    </label>
                    <div class="voodbuilder-editor-library-panels">
                        <div class="voodbuilder-editor-library-panel voodbuilder-editor-library-panel--active" data-voodbuilder-library-panel="blocks">
                            <div class="voodbuilder-editor-blocks-mount"></div>
                        </div>
                        <div class="voodbuilder-editor-library-panel" data-voodbuilder-library-panel="components" hidden>
                            <div class="voodbuilder-editor-components-mount"></div>
                        </div>
                        ${hideTemplates ? '' : `
                        <div class="voodbuilder-editor-library-panel" data-voodbuilder-library-panel="templates" hidden>
                            <div class="voodbuilder-editor-templates-mount"></div>
                        </div>
                        `}
                    </div>
                </aside>
                <div class="voodbuilder-editor-shell__center">
                    <div class="voodbuilder-editor-canvas-mount"></div>
                </div>
                <aside class="voodbuilder-editor-shell__right" aria-label="${escapeHtml(labels.panelInspector ?? 'Inspector')}">
                    <div class="voodbuilder-editor-inspector-tabs" role="tablist"></div>
                    <div class="voodbuilder-editor-inspector-panels">
                        <div class="voodbuilder-editor-inspector-panel${defaultInspectorTab === 'content' ? ' voodbuilder-editor-inspector-panel--active' : ''}" data-voodbuilder-inspector="content"${defaultInspectorTab === 'content' ? '' : ' hidden'}>
                            <div class="voodbuilder-editor-traits-mount"></div>
                            <div class="voodbuilder-editor-site-chrome-settings-mount" hidden></div>
                            <div class="voodbuilder-editor-component-props-mount"></div>
                        </div>
                        <div class="voodbuilder-editor-inspector-panel" data-voodbuilder-inspector="style" hidden>
                            <div class="voodbuilder-editor-selectors-mount"></div>
                            <div class="voodbuilder-editor-styles-mount"></div>
                        </div>
                        <div class="voodbuilder-editor-inspector-panel" data-voodbuilder-inspector="dynamic" hidden>
                            <div class="voodbuilder-editor-dynamic-mount"></div>
                        </div>
                        ${popupMode ? '' : `
                        <div class="voodbuilder-editor-inspector-panel" data-voodbuilder-inspector="conditions" hidden>
                            <div class="voodbuilder-editor-conditions-mount"></div>
                        </div>
                        `}
                        <div class="voodbuilder-editor-inspector-panel" data-voodbuilder-inspector="layers" hidden>
                            <div class="voodbuilder-editor-layers-mount"></div>
                        </div>
                        ${popupMode ? `
                        <div class="voodbuilder-editor-inspector-panel${defaultInspectorTab === 'popup' ? ' voodbuilder-editor-inspector-panel--active' : ''}" data-voodbuilder-inspector="popup"${defaultInspectorTab === 'popup' ? '' : ' hidden'}>
                            <div class="voodbuilder-editor-popup-settings-mount"></div>
                        </div>
                        ` : ''}
                    </div>
                </aside>
            </div>
        </div>
    `;

    const tabLabels = {
        content: labels.tabContent ?? 'Content',
        style: labels.tabStyle ?? 'Style',
        dynamic: labels.tabDynamic ?? 'Dynamic',
        conditions: labels.tabConditions ?? 'Conditions',
        layers: labels.tabLayers ?? 'Layers',
        popup: labels.tabPopup ?? labels.popupsConfiguratorTitle ?? 'Settings',
    };

    const tablist = container.querySelector('.voodbuilder-editor-inspector-tabs');

    for (const tabId of inspectorTabs) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'voodbuilder-editor-inspector-tab';
        button.dataset.voodbuilderTab = tabId;
        button.setAttribute('role', 'tab');
        button.setAttribute('aria-selected', tabId === defaultInspectorTab ? 'true' : 'false');
        button.setAttribute('aria-label', tabLabels[tabId]);
        button.title = tabLabels[tabId];
        if (tabId === defaultInspectorTab) {
            button.classList.add('voodbuilder-editor-inspector-tab--active');
        }
        button.innerHTML = tabId === 'style' || tabId === 'conditions'
            ? tablerIcon(INSPECTOR_TAB_ICONS[tabId] ?? 'box-select', 17)
            : lucideIcon(INSPECTOR_TAB_ICONS[tabId] ?? 'box-select', 17);
        tablist.appendChild(button);
    }

    const shell = container.querySelector('.voodbuilder-editor-shell');

    return {
        shell,
        mounts: {
            canvas: container.querySelector('.voodbuilder-editor-canvas-mount'),
            canvasToolbar: container.querySelector('.voodbuilder-editor-topbar__tools'),
            blocks: container.querySelector('.voodbuilder-editor-blocks-mount'),
            components: container.querySelector('.voodbuilder-editor-components-mount'),
            templates: container.querySelector('.voodbuilder-editor-templates-mount'),
            componentProps: container.querySelector('.voodbuilder-editor-component-props-mount'),
            libraryTabs: container.querySelector('.voodbuilder-editor-library-tabs'),
            libraryPanels: container.querySelector('.voodbuilder-editor-library-panels'),
            layers: container.querySelector('.voodbuilder-editor-layers-mount'),
            traits: container.querySelector('.voodbuilder-editor-traits-mount'),
            popupSettings: container.querySelector('.voodbuilder-editor-popup-settings-mount'),
            siteChromeSettings: container.querySelector('.voodbuilder-editor-site-chrome-settings-mount'),
            selectors: container.querySelector('.voodbuilder-editor-selectors-mount'),
            styles: container.querySelector('.voodbuilder-editor-styles-mount'),
            dynamic: container.querySelector('.voodbuilder-editor-dynamic-mount'),
            conditions: container.querySelector('.voodbuilder-editor-conditions-mount'),
            search: container.querySelector('.voodbuilder-editor-blocks-search'),
            tablist,
            panels: container.querySelector('.voodbuilder-editor-inspector-panels'),
            saveButton: container.querySelector('[data-voodbuilder-editor-save]'),
            saveLabel: container.querySelector('[data-voodbuilder-editor-save-label]'),
            savedIndicator: container.querySelector('[data-voodbuilder-editor-saved]'),
        },
    };
}

export function editorLayoutInitOptions(mounts) {
    return {
        container: mounts.canvas,
        showDevices: false,
        blockManager: {
            appendTo: mounts.blocks,
        },
        layerManager: {
            appendTo: mounts.layers,
        },
        traitManager: {
            appendTo: mounts.traits,
        },
        selectorManager: {
            componentFirst: true,
            states: [
                { name: 'hover', label: 'Hover' },
                { name: 'active', label: 'Active' },
                { name: 'focus', label: 'Focus' },
            ],
        },
        styleManager: {
            appendTo: mounts.styles,
            sectors: STYLE_MANAGER_SECTORS,
        },
    };
}

function setupLibraryTabs(mounts, labels = {}, editor = null) {
    const { libraryTabs, libraryPanels, search } = mounts;

    if (! libraryTabs || ! libraryPanels) {
        return;
    }

    let activeLibrary = 'blocks';

    const placeholders = {
        blocks: labels.blockSearch ?? 'Search elements…',
        components: labels.componentSearch ?? 'Search components…',
        templates: labels.templateSearch ?? 'Search templates…',
    };

    const activateLibrary = (libraryId) => {
        activeLibrary = libraryId;

        if (editor) {
            editor.__voodbuilderActiveLibrary = libraryId;
            editor.__voodbuilderRelocateLibrary?.(libraryId);
            editor.trigger?.('voodbuilder:active-library-changed', { libraryId });
        }

        libraryTabs.closest('.voodbuilder-editor-shell')
            ?.setAttribute('data-voodbuilder-active-library', libraryId);

        libraryTabs.querySelectorAll('[data-voodbuilder-library]').forEach((button) => {
            const active = button.dataset.voodbuilderLibrary === libraryId;
            button.classList.toggle('voodbuilder-editor-library-tab--active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        libraryPanels.querySelectorAll('[data-voodbuilder-library-panel]').forEach((panel) => {
            const active = panel.dataset.voodbuilderLibraryPanel === libraryId;
            panel.classList.toggle('voodbuilder-editor-library-panel--active', active);
            panel.hidden = ! active;
        });

        if (search) {
            search.placeholder = placeholders[libraryId] ?? placeholders.blocks;
            search.value = '';
            search.dispatchEvent(new Event('input', { bubbles: true }));
        }
    };

    libraryTabs.addEventListener('click', (event) => {
        const button = event.target.closest('[data-voodbuilder-library]');

        if (! button?.dataset.voodbuilderLibrary) {
            return;
        }

        activateLibrary(button.dataset.voodbuilderLibrary);
    });

    activateLibrary('blocks');
    mounts.blocks?.setAttribute('data-voodbuilder-blocks-library', 'blocks');
}

function setupBlockSearch(editor, searchInput) {
    if (! searchInput) {
        return;
    }

    const filter = () => {
        editor.__voodbuilderBlocksSearchQuery = searchInput.value.trim();
        applyBlocksLibraryUi(editor, editor.__voodbuilderBlocksSearchQuery);
    };

    searchInput.addEventListener('input', filter);
    editor.on('block:add', () => {
        window.requestAnimationFrame(filter);
    });
    editor.on('block:remove', filter);
    editor.on('load', () => {
        window.requestAnimationFrame(filter);
    });
}

export function collapseBlockCategories(editor) {
    collapseAllBlockCategories(editor);
}

export function refreshBlocksLibraryUi(editor) {
    const libraryId = editor.__voodbuilderActiveLibrary ?? 'blocks';

    editor.__voodbuilderRelocateLibrary?.(libraryId);
    collapseLibraryCategories(editor, libraryId);
    applyBlocksLibraryUi(editor, readBlocksSearchQuery());
}

function syncInspectorManagers(editor, tabId, { refreshInspectorPanels = false } = {}) {
    let component = editor.getSelected();

    if (tabId === 'content' && component) {
        promoteRoot(editor, component);
        component = editor.getSelected();
        refreshBlockSettingsUi(editor);

        let { descriptor } = resolveBlockSettingsTarget(component, editor);

        if (! descriptor && editor.__voodbuilderChromeLayoutMode) {
            const root = findInspectableRoot(component, editor);

            if (root) {
                ensureRootInspectable(root);
                ({ descriptor } = resolveBlockSettingsTarget(root, editor));
            }
        }

        if (! descriptor) {
            const layoutTarget = editor.__voodbuilderChromeLayoutMode
                ? resolveBlockSettingsTarget(component, editor)
                : { descriptor: null };

            if (
                ! layoutTarget.descriptor?.layoutOnly
                && ! editor.__voodbuilderChromeLayoutMode
            ) {
                editor.TraitManager.select(component);
            }
        }
    } else if (tabId !== 'content' && editor.__voodbuilderChromeLayoutMode) {
        editor.__voodbuilderBlockSettingsRender?.();
    }

    if (tabId === 'style') {
        syncChromeLayoutStylePanel(editor, editor.__voodbuilderShellMounts);
    }

    if (tabId === 'layers') {
        window.requestAnimationFrame(() => {
            editor.trigger('voodbuilder:layers-panel:show');
        });
    }

    if (refreshInspectorPanels && (tabId === 'dynamic' || tabId === 'conditions')) {
        window.requestAnimationFrame(() => {
            editor.trigger('voodbuilder:inspector-panel:refresh', {
                tabId,
                component: editor.getSelected(),
            });
        });
    }
}

function setInspectorSidebarWidth(inspectorAside, tabId) {
    if (! inspectorAside) {
        return;
    }

    inspectorAside.setAttribute('data-voodbuilder-inspector-tab', tabId);
}

function setupInspectorTabs(mounts, editor) {
    const { tablist, panels } = mounts;
    const inspectorAside = panels?.closest('.voodbuilder-editor-shell__right') ?? null;
    const defaultTab = editor.__voodbuilderPopupMode ? 'popup' : 'content';
    let activeTab = defaultTab;

    if (! tablist || ! panels) {
        return;
    }

    const activateTab = (tabId, { userInitiated = false } = {}) => {
        activeTab = tabId;
        editor.__voodbuilderInspectorActiveTab = tabId;

        if (userInitiated) {
            editor.__voodbuilderInspectorTabUserChoice = true;
        }

        tablist.querySelectorAll('.voodbuilder-editor-inspector-tab').forEach((button) => {
            const active = button.dataset.voodbuilderTab === tabId;
            button.classList.toggle('voodbuilder-editor-inspector-tab--active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        panels.querySelectorAll('[data-voodbuilder-inspector]').forEach((panel) => {
            const active = panel.dataset.voodbuilderInspector === tabId;

            panel.classList.toggle('voodbuilder-editor-inspector-panel--active', active);
            panel.toggleAttribute('hidden', ! active);
            panel.setAttribute('aria-hidden', active ? 'false' : 'true');
        });

        if (inspectorAside) {
            setInspectorSidebarWidth(inspectorAside, tabId);
        }

        editor.trigger?.('voodbuilder:inspector-tab', tabId);

        window.requestAnimationFrame(() => syncInspectorManagers(editor, tabId, {
            refreshInspectorPanels: tabId === 'dynamic' || tabId === 'conditions',
        }));
    };

    editor.__voodbuilderActivateInspectorTab = activateTab;

    tablist.addEventListener('click', (event) => {
        const button = event.target.closest('.voodbuilder-editor-inspector-tab');

        if (! button?.dataset.voodbuilderTab) {
            return;
        }

        // Always go through the editor hook so listeners (e.g. Reading preview) can wrap it.
        editor.__voodbuilderActivateInspectorTab?.(button.dataset.voodbuilderTab, { userInitiated: true });
    });

    editor.on('component:selected', (component) => {
        if (component) {
            const { descriptor, root } = resolveBlockSettingsTarget(component, editor);
            const richHost = findRichTextHost(component) ?? (isRichTextComponent(component) ? component : null);

            if (descriptor && root) {
                // Prefer block id over object identity so a post-refresh re-select
                // still opens Content once, without locking the user on that tab.
                const rootKey = readBlockId(root) || `cid:${component.cid ?? ''}`;
                const lastKey = editor.__voodbuilderLayoutSettingsAutoTabKey ?? null;

                editor.__voodbuilderLayoutSettingsAutoTabRoot = root;

                if (lastKey !== rootKey) {
                    editor.__voodbuilderLayoutSettingsAutoTabKey = rootKey;
                    editor.__voodbuilderInspectorTabUserChoice = false;
                    activateTab('content');
                }
            } else if (richHost) {
                // Rich Text formats in the Content light RTE — open that tab on first select.
                const richKey = `rich:${richHost.cid ?? richHost.getId?.() ?? ''}`;
                const lastKey = editor.__voodbuilderLayoutSettingsAutoTabKey ?? null;

                editor.__voodbuilderLayoutSettingsAutoTabRoot = richHost;

                if (lastKey !== richKey) {
                    editor.__voodbuilderLayoutSettingsAutoTabKey = richKey;
                    editor.__voodbuilderInspectorTabUserChoice = false;
                    activateTab('content');
                }
            } else {
                editor.__voodbuilderLayoutSettingsAutoTabRoot = null;
                editor.__voodbuilderLayoutSettingsAutoTabKey = null;
            }

            syncInspectorManagers(editor, activeTab);

            // Prefer block settings over bind/conditions tabs when a descriptor matches.
            if (! descriptor && ! richHost) {
                const hasBindingSources = (editor.__voodbuilderBindingsCatalog?.groups ?? []).length > 0;

                if (hasBindingSources && component.getAttributes?.()['data-voodbuilder-bind']) {
                    activateTab('dynamic');
                }

                if (
                    ! editor.__voodbuilderPopupMode
                    && component.getAttributes?.()['data-voodbuilder-conditions']
                ) {
                    activateTab('conditions');
                }
            }

            return;
        }

        syncInspectorManagers(editor, activeTab);
    });

    editor.on('voodbuilder:dynamic-blocks-refreshed', () => {
        // Allow the next selection to re-open Content once after refresh.
        editor.__voodbuilderLayoutSettingsAutoTabKey = null;
        editor.__voodbuilderLayoutSettingsAutoTabRoot = null;

        if (activeTab === 'content') {
            refreshBlockSettingsUi(editor);
        }
    });

    editor.on('component:deselected', () => {
        syncInspectorManagers(editor, activeTab);
    });

    activateTab(defaultTab);
}

function mountSelectorManagerPanel(editor, mount) {
    if (! mount) {
        return;
    }

    mount.replaceChildren();

    const panel = editor.SelectorManager.render();

    if (panel) {
        mount.appendChild(panel);
    }
}

function getClassManagerPanels(root) {
    if (! root) {
        return [];
    }

    return [...root.querySelectorAll('.clm-tags, .gjs-clm')];
}

function dedupeSelectorManagerPanels(selectorsMount, stylesMount) {
    const stylePanel = selectorsMount?.closest('[data-voodbuilder-inspector="style"]')
        ?? stylesMount?.closest('[data-voodbuilder-inspector="style"]');

    if (stylesMount) {
        getClassManagerPanels(stylesMount).forEach((panel) => panel.remove());
    }

    const scope = stylePanel ?? selectorsMount;

    if (! scope) {
        return;
    }

    const classPanels = getClassManagerPanels(scope);

    for (let index = 1; index < classPanels.length; index += 1) {
        classPanels[index].remove();
    }
}

function observeSelectorManagerDedupe(mounts) {
    const targets = [mounts.selectors, mounts.styles].filter(Boolean);

    if (targets.length === 0) {
        return;
    }

    let dedupeTimer = null;
    let deduping = false;
    const observers = [];

    const dedupe = () => {
        if (deduping) {
            return;
        }

        deduping = true;

        for (const { observer } of observers) {
            observer.disconnect();
        }

        try {
            dedupeSelectorManagerPanels(mounts.selectors, mounts.styles);
        } finally {
            window.queueMicrotask(() => {
                deduping = false;

                for (const { observer, target } of observers) {
                    observer.observe(target, { childList: true, subtree: true });
                }
            });
        }
    };

    const scheduleDedupe = () => {
        if (deduping) {
            return;
        }

        window.clearTimeout(dedupeTimer);
        dedupeTimer = window.setTimeout(dedupe, 80);
    };

    for (const target of targets) {
        const observer = new MutationObserver(() => {
            scheduleDedupe();
        });

        observer.observe(target, { childList: true, subtree: true });
        observers.push({ observer, target });
    }

    dedupe();
}

function syncChromeLayoutStylePanel(editor, mounts) {
    if (! mounts?.styles) {
        return;
    }

    const panel = mounts.styles.closest('[data-voodbuilder-inspector="style"]');

    if (! panel) {
        return;
    }

    const labels = editor.__voodbuilderLabels ?? {};
    ensurePageSurfaceAction(editor, panel, labels);

    let selected = editor.getSelected?.();

    // Full canvases rarely leave an empty click target — when nothing is selected,
    // Style targets the page surface so Background still works.
    if (
        isPageSurfaceMode(editor)
        && (! selected || selected.isRemoved?.())
    ) {
        selected = selectPageSurface(editor) ?? selected;
    }

    // Locked chrome (nav/footer) selection: keep the layout notice, but Page button
    // above still lets authors style the full-page background without hunting gaps.
    const noticeText = isPageSurfaceComponent(selected, editor)
        ? null
        : inspectorSelectionNotice(selected, editor, labels);
    const hideControls = noticeText !== null;
    const styleMounts = [mounts.selectors, mounts.styles].filter(Boolean);

    styleMounts.forEach((el) => {
        el.hidden = hideControls;
        const sector = el.closest?.('.voodbuilder-editor-inspector-sector');

        if (sector) {
            sector.hidden = hideControls;
        }
    });

    let notice = panel.querySelector('[data-voodbuilder-chrome-layout-notice]');

    if (! hideControls) {
        if (notice) {
            notice.hidden = true;
        }

        ensurePageSurfaceAction(editor, panel, labels);

        return;
    }

    if (! notice) {
        notice = createInspectorEmptyState({ labels });
        notice.dataset.voodbuilderChromeLayoutNotice = '1';
        // Keep the Page action bar first when present.
        const pageAction = panel.querySelector('[data-voodbuilder-page-surface-action]');
        if (pageAction?.nextSibling) {
            panel.insertBefore(notice, pageAction.nextSibling);
        } else {
            panel.insertBefore(notice, panel.firstChild);
        }
    }

    notice.hidden = false;
    notice.className = 'voodbuilder-editor-inspector-empty-state voodbuilder-editor-chrome-layout-notice';
    notice.textContent = noticeText;
    ensurePageSurfaceAction(editor, panel, labels);
}

function setupStyleInspector(editor, mounts) {
    const dedupe = () => dedupeSelectorManagerPanels(mounts.selectors, mounts.styles);

    editor.__voodbuilderShellMounts = mounts;

    editor.on('load', () => {
        mountSelectorManagerPanel(editor, mounts.selectors);
        observeSelectorManagerDedupe(mounts);
        dedupe();
        syncChromeLayoutStylePanel(editor, mounts);
    });
    editor.on('component:selected', () => {
        window.requestAnimationFrame(() => {
            dedupe();
            window.requestAnimationFrame(dedupe);
            syncChromeLayoutStylePanel(editor, mounts);
        });
    });
    editor.on('component:deselected', () => {
        syncChromeLayoutStylePanel(editor, mounts);
    });
    editor.on('voodbuilder:inspector-tab', (tabId) => {
        if (tabId === 'style') {
            syncChromeLayoutStylePanel(editor, mounts);
        }
    });
    editor.on('component:remove', () => {
        window.requestAnimationFrame(() => {
            syncChromeLayoutStylePanel(editor, mounts);
        });
    });
    editor.on('selector:add', dedupe);
    editor.on('selector:remove', dedupe);
    editor.on('selector:update', dedupe);
}

function trimDefaultPanelButtons(editor) {
    const removeIds = [
        'export-template',
        'open-sm',
        'open-tm',
        'open-layers',
        'open-blocks',
        'fullscreen',
        'preview',
        'sw-visibility',
    ];

    for (const panelId of ['options', 'views', 'commands']) {
        for (const buttonId of removeIds) {
            editor.Panels.removeButton(panelId, buttonId);
        }
    }

    for (const panelId of ['views', 'commands', 'options']) {
        if (editor.Panels.getPanel(panelId)) {
            editor.Panels.removePanel(panelId);
        }
    }
}

/**
 * Soft commercial gate: keep Community Elements visible, show companion upsell above the catalog.
 *
 * @param {object|null|undefined} shell
 * @param {object} [labels]
 * @param {import('grapesjs').Editor|null|undefined} editor
 * @returns {void}
 */
function mountElementsLibraryUpsell(shell, labels = {}, editor = null) {
    const entitlements = editor?.__voodbuilderEntitlements ?? {};

    // Elements plugin active → SOURCE UI owns the library (no upsell).
    // Pro capability alone is not enough: without the companion, keep the soft upsell
    // above the limited local Elements accordion (same pattern as other companions).
    if (canEntitlement(entitlements, 'elementsLibrary')) {
        return;
    }

    // Library modal already owns SOURCE (Elements and/or Templates companion).
    // Do not re-add the upsell after bootEditorPlugins mounted the dock button —
    // that race left both Library + upsell visible with a Templates-only empty modal.
    if (
        editor?.__voodbuilderElementsLibraryMounted
        || document.querySelector('[data-voodbuilder-elements-library-btn]')
        || document.querySelector('.vb-elements-library-btn-row')
        || (Array.isArray(editor?.__voodbuilderElementsCatalogs)
            && editor.__voodbuilderElementsCatalogs.length > 0)
    ) {
        return;
    }

    const blocksMount = shell?.mounts?.blocks ?? null;
    const panel = blocksMount?.closest('[data-voodbuilder-library-panel="blocks"]')
        ?? blocksMount?.parentElement
        ?? null;

    if (! panel || panel.querySelector('[data-voodbuilder-elements-upsell]')) {
        return;
    }

    const upsell = createInspectorEmptyState({
        classNameExtra: 'voodbuilder-editor-elements-library__locked',
        title: labels.elementsPluginRequiredTitle ?? 'Voodbuilder Elements',
        message: labels.elementsPluginRequiredBody
            ?? 'Thousands of elements — copy and paste from toolkits like Tailwind Plus, create new elements with code, and expand your library. Requires the Voodbuilder Elements plugin.',
        labels,
        linkUrl: labels.marketingUrl ?? null,
        linkLabel: labels.learnMore ?? 'Learn more',
    });
    upsell.setAttribute('data-voodbuilder-elements-upsell', '1');

    if (blocksMount?.parentElement === panel) {
        panel.insertBefore(upsell, blocksMount);
    } else {
        panel.prepend(upsell);
    }
}

export function configureEditorLayout(editor, shell, labels = {}) {
    trimDefaultPanelButtons(editor);
    setupStyleInspectorSectors(shell.mounts, labels);
    setupLibraryTabs(shell.mounts, labels, editor);
    mountElementsLibraryUpsell(shell, labels, editor);
    setupBlockSearch(editor, shell.mounts.search);
    registerBlockPins(editor, {
        blocksMount: shell.mounts.blocks,
        labels,
    });
    setupStyleInspector(editor, shell.mounts);
    setupInspectorTabs(shell.mounts, editor);
    registerInspectorSelectUi(editor, shell.mounts);
    registerInspectorColorFix(editor, shell.mounts);

    editor.on('style:change', () => {
        editor.Canvas.getFrameEl()?.contentWindow?.dispatchEvent(new Event('resize'));
    });

    editor.on('component:styleUpdate', () => {
        editor.trigger('change:canvasOffset');
    });

    editor.on('load', () => {
        collapseBlockCategories(editor);
    });
}
