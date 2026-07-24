/**
 * Inspector UI for registered block settings descriptors.
 */

import {
    ATTR,
} from '../../core/attrs.js';
import {
    isChromeLayoutContentSlotComponent,
    isChromeLayoutModeEditor,
    isChromeShellModeEditor,
} from '../../chrome-content-slot-utils.js';
import {
    isChromeShellManagedInspectorSelection,
    chromeShellManagedInspectorNotice,
} from '../../chrome-editor-guards.js';
import {
    registerBlockSettings,
    resolveSettings,
} from './registry.js';
import {
    clearActiveLayoutSettingsRoot,
    getLayoutInspectorForceRenderMs,
    getLayoutChromeBlock,
    isLayoutInspectorReady,
    resolveLayoutChromeZone,
    setActiveLayoutSettingsRoot,
} from './layout-chrome-registry.js';
import {
    ensureRootInspectable,
    findInspectableRoot,
    readBlockId,
    shouldPromoteSelectionToRoot,
} from './select.js';
import { closeAllInspectorSelects } from '../../inspector-select-ui.js';
import { createInspectorEmptyState } from '../../inspector-empty-state.js';
import { isValidGrapesComponent } from '../../core/component-model.js';
import { isCtaButtonComponent, renderCtaButtonSettings } from '../../cta-button-settings.js';
import {
    isDividerComponent,
    isIconComponent,
    isTextLinkComponent,
    renderDividerSettings,
    renderIconSettings,
    renderTextLinkSettings,
} from '../../basic-elements-settings.js';
import {
    isLayoutStructureComponent,
    renderLayoutStructureSettings,
} from '../../layout-elements-settings.js';
import { isAnimatedCounterComponent, renderAnimatedCounterSettings, syncAnimatedCounterSettingsForm } from '../../animated-counter-settings.js';
import { isImageSettingsComponent, renderImageContentSettings } from '../../image-content-settings.js';
import { ensureSmartCtaButton } from '../../grapesjs-button-link.js';

/**
 * @param {HTMLElement} mount
 * @param {object} root
 */
function syncSettingsFormValues(mount, root) {
    if (! mount || ! root?.get) {
        return;
    }

    mount.querySelectorAll('[name]').forEach((input) => {
        const name = input.getAttribute('name');

        if (! name) {
            return;
        }

        if (input instanceof HTMLInputElement && input.type === 'checkbox') {
            input.checked = root.get(name) === true;

            return;
        }

        if (input instanceof HTMLSelectElement || input instanceof HTMLInputElement) {
            const value = root.get(name) ?? root.getAttributes?.()?.[name];

            // Form-only field names (e.g. vbCountTrigger) are not model props —
            // leave the current control value alone instead of clearing it.
            if (value == null) {
                return;
            }

            const next = String(value);

            if (input.value === next) {
                return;
            }

            input.value = next;

            if (input instanceof HTMLSelectElement) {
                const label = input.closest('.voodbuilder-gjs-select-wrap')
                    ?.querySelector('.voodbuilder-gjs-select-trigger-label');
                const selected = input.options[input.selectedIndex];

                if (label && selected) {
                    label.textContent = selected.textContent?.trim() || selected.value || '-';
                }
            }
        }
    });
}

/**
 * @param {object|null|undefined} component
 * @param {object|null|undefined} root
 * @returns {boolean}
 */
function isWithinSettingsRoot(component, root) {
    if (! component || ! root || component === root) {
        return Boolean(component && root && component === root);
    }

    let current = component;

    while (current && current !== root) {
        current = current.parent?.();
    }

    return current === root;
}

/**
 * Suppresses inspector/settings re-renders and compile-css autobuild while applying block settings.
 *
 * @param {object} editor
 * @param {() => void} callback
 */
export function runWithSettingsChangeGuard(editor, callback) {
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
            // Do not schedule compile-css here: settings/content edits are not class changes.
            // Class edits still trigger page-tailwind-autobuild via component:update:classes.
        }
    }
}

/**
 * @param {object|null|undefined} descriptor
 * @returns {boolean}
 */
function isLayoutOnlyDescriptor(descriptor) {
    return Boolean(descriptor?.layoutOnly);
}

/**
 * @param {object} editor
 * @returns {boolean}
 */
function isContentInspectorTabActive(editor) {
    return (editor.__voodbuilderInspectorActiveTab ?? 'content') === 'content';
}

/**
 * Reset cached render state so the next selection always rebuilds the form.
 *
 * @param {object|null|undefined} editor
 */
export function invalidateBlockSettingsUi(editor) {
    editor?.__voodbuilderBlockSettingsInvalidate?.();
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
function shouldAllowTraitsInLayoutMode(component) {
    let current = component;

    while (current && current.get?.('type') !== 'wrapper') {
        const attrs = current.getAttributes?.() ?? {};
        const zone = attrs[ATTR.dropZone];

        if (zone === 'nav' || zone === 'footer') {
            return false;
        }

        if (attrs[ATTR.contentSlot]) {
            return true;
        }

        current = current.parent?.();
    }

    return false;
}

/**
 * @param {object} editor
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
function shouldRenderCustomSettings(editor, component) {
    const { descriptor } = resolveSettings(component, editor);

    if (! descriptor) {
        return false;
    }

    if (
        isChromeShellModeEditor(editor)
        && ! isChromeLayoutModeEditor(editor)
        && isLayoutOnlyDescriptor(descriptor)
    ) {
        return false;
    }

    return true;
}

/**
 * @param {object|null|undefined} component
 * @returns {boolean}
 */
function componentHasContentTraits(component) {
    if (! component) {
        return false;
    }

    try {
        const traits = typeof component.getTraits === 'function'
            ? component.getTraits()
            : component.get?.('traits');

        if (! traits) {
            return false;
        }

        if (typeof traits.length === 'number') {
            return traits.length > 0;
        }

        if (Array.isArray(traits.models)) {
            return traits.models.length > 0;
        }
    } catch {
        // Ignore incomplete GrapesJS models.
    }

    return false;
}

/**
 * @param {object} editor
 */
function guardTraitManagerForBlockSettings(editor) {
    if (editor.__voodbuilderTraitManagerBlockSettingsGuarded || ! editor.TraitManager?.select) {
        return;
    }

    editor.__voodbuilderTraitManagerBlockSettingsGuarded = true;

    const originalSelect = editor.TraitManager.select.bind(editor.TraitManager);

    editor.TraitManager.select = (component, ...args) => {
        if (isChromeLayoutModeEditor(editor)) {
            if (component && shouldAllowTraitsInLayoutMode(component)) {
                return originalSelect(component, ...args);
            }

            editor.__voodbuilderBlockSettingsRender?.();

            return;
        }

        // Smart CTA buttons and animated counters own Content traits — never steal selection.
        const componentType = component?.get?.('type');

        if (componentType === 'voodbuilder-cta-button' || componentType === 'voodbuilder-animated-counter') {
            return originalSelect(component, ...args);
        }

        if (isAnimatedCounterComponent(component)) {
            editor.__voodbuilderBlockSettingsRender?.();

            return;
        }

        if (shouldRenderCustomSettings(editor, component)) {
            editor.__voodbuilderBlockSettingsRender?.();

            return;
        }

        // Empty GrapesJS traits leave a blank Content panel — show our notice instead.
        if (! componentHasContentTraits(component)) {
            editor.__voodbuilderBlockSettingsRender?.();

            return;
        }

        return originalSelect(component, ...args);
    };
}

/**
 * @param {object} editor
 * @param {HTMLElement|null} mount
 */
export function registerSettingsUi(editor, mount) {
    if (! mount || editor.__voodbuilderBlockSettingsUiRegistered) {
        return;
    }

    editor.__voodbuilderBlockSettingsUiRegistered = true;

    guardTraitManagerForBlockSettings(editor);

    let renderedRoot = null;
    let renderedRootBlockId = '';
    let renderedDescriptorId = null;
    let layoutInspectorLoadingSince = 0;

    const traitsMount = mount.closest('[data-voodbuilder-inspector="content"]')
        ?.querySelector('.voodbuilder-gjs-traits-mount');

    const invalidateRenderCache = () => {
        renderedRoot = null;
        renderedRootBlockId = '';
        renderedDescriptorId = null;
        layoutInspectorLoadingSince = 0;
        mount.hidden = true;
        mount.replaceChildren();
    };

    editor.__voodbuilderBlockSettingsInvalidate = invalidateRenderCache;

    const renderLayoutInspectorLoading = () => {
        mount.hidden = false;
        traitsMount?.classList.add('hidden');
        mount.replaceChildren();

        const hint = createInspectorEmptyState({
            message: 'Loading header and footer settings…',
        });
        mount.appendChild(hint);
    };

    const renderLayoutSlotHint = (traitsPanel) => {
        if (! traitsPanel) {
            return;
        }

        traitsPanel.classList.remove('hidden');
        traitsPanel.replaceChildren();

        const hint = createInspectorEmptyState({
            message: 'Select a block in the header or footer zone to configure its settings.',
        });
        traitsPanel.appendChild(hint);
    };

    const showTraitsFallback = () => {
        mount.hidden = true;
        mount.replaceChildren();
        traitsMount?.classList.remove('hidden');
    };

    const showNoContentSettings = (labels = {}) => {
        mount.hidden = false;
        traitsMount?.classList.add('hidden');
        traitsMount?.replaceChildren?.();
        mount.replaceChildren();
        mount.appendChild(createInspectorEmptyState({
            message: labels.contentNoSettings
                ?? 'No settings for this element. Use the Style tab to change appearance.',
        }));
    };

    const showTraitsOrNoSettings = (component, labels = {}) => {
        if (componentHasContentTraits(component)) {
            showTraitsFallback();

            return;
        }

        showNoContentSettings(labels);
    };

    const maybePromoteSelectionForHighlight = (rawSelected, root) => {
        if (
            ! root
            || ! rawSelected
            || ! shouldPromoteSelectionToRoot(rawSelected, root, editor)
            || editor.getSelected?.() === root
        ) {
            return;
        }

        window.requestAnimationFrame(() => {
            ensureRootInspectable(root);
            editor.select(root, { scroll: false });
        });
    };

    const shouldDeferLayoutInspectorRender = (rawSelected) => {
        if (! isChromeLayoutModeEditor(editor) || isLayoutInspectorReady(editor)) {
            layoutInspectorLoadingSince = 0;

            return false;
        }

        const { descriptor, root } = resolveSettings(rawSelected, editor);

        if (descriptor && root) {
            layoutInspectorLoadingSince = 0;

            return false;
        }

        if (! layoutInspectorLoadingSince) {
            layoutInspectorLoadingSince = Date.now();
        }

        return Date.now() - layoutInspectorLoadingSince < getLayoutInspectorForceRenderMs();
    };

    const showCourtesyEmptyState = (labels = {}) => {
        mount.hidden = false;
        traitsMount?.classList.add('hidden');
        traitsMount?.replaceChildren?.();
        mount.replaceChildren();
        mount.appendChild(createInspectorEmptyState({ labels }));
        renderedRoot = null;
        renderedRootBlockId = '';
        renderedDescriptorId = null;
    };

    const render = () => {
        if (editor.__voodbuilderBlockSettingsRendering) {
            return;
        }

        editor.__voodbuilderBlockSettingsRendering = true;

        try {
            const rawSelected = editor.getSelected();
            const labels = editor.__voodbuilderLabels ?? {};

            if (! rawSelected || ! isValidGrapesComponent(rawSelected)) {
                clearActiveLayoutSettingsRoot(editor);
                showCourtesyEmptyState(labels);

                return;
            }

            ensureSmartCtaButton(rawSelected, editor);

            if (isCtaButtonComponent(rawSelected) || rawSelected.get?.('type') === 'voodbuilder-cta-button') {
                closeAllInspectorSelects();
                renderCtaButtonSettings({
                    mount,
                    traitsMount,
                    component: rawSelected,
                    editor,
                    labels,
                });
                renderedRoot = null;
                renderedRootBlockId = '';
                renderedDescriptorId = null;

                return;
            }

            if (isIconComponent(rawSelected)) {
                closeAllInspectorSelects();
                renderIconSettings({
                    mount,
                    traitsMount,
                    component: rawSelected,
                    editor,
                    labels,
                });
                renderedRoot = null;
                renderedRootBlockId = '';
                renderedDescriptorId = null;

                return;
            }

            if (isLayoutStructureComponent(rawSelected)) {
                closeAllInspectorSelects();
                renderLayoutStructureSettings({
                    mount,
                    traitsMount,
                    component: rawSelected,
                    editor,
                    labels,
                });
                renderedRoot = null;
                renderedRootBlockId = '';
                renderedDescriptorId = null;

                return;
            }

            if (isTextLinkComponent(rawSelected)) {
                closeAllInspectorSelects();
                renderTextLinkSettings({
                    mount,
                    traitsMount,
                    component: rawSelected,
                    editor,
                    labels,
                });
                renderedRoot = null;
                renderedRootBlockId = '';
                renderedDescriptorId = null;

                return;
            }

            if (isDividerComponent(rawSelected)) {
                closeAllInspectorSelects();
                renderDividerSettings({
                    mount,
                    traitsMount,
                    component: rawSelected,
                    editor,
                    labels,
                });
                renderedRoot = null;
                renderedRootBlockId = '';
                renderedDescriptorId = null;

                return;
            }

            if (rawSelected.get?.('type') === 'voodbuilder-animated-counter' || isAnimatedCounterComponent(rawSelected)) {
                const counterKey = rawSelected.getId?.() ?? rawSelected.cid ?? '';

                if (
                    renderedDescriptorId === 'animated-counter'
                    && renderedRoot === rawSelected
                    && renderedRootBlockId === counterKey
                    && mount.querySelector('.voodbuilder-gjs-form')
                ) {
                    mount.hidden = false;
                    traitsMount?.classList.add('hidden');
                    syncAnimatedCounterSettingsForm(mount, rawSelected);

                    return;
                }

                closeAllInspectorSelects();
                renderAnimatedCounterSettings({
                    mount,
                    traitsMount,
                    component: rawSelected,
                    editor,
                    labels,
                });
                renderedRoot = rawSelected;
                renderedRootBlockId = counterKey;
                renderedDescriptorId = 'animated-counter';

                return;
            }

            if (isImageSettingsComponent(rawSelected)) {
                closeAllInspectorSelects();
                renderImageContentSettings({
                    mount,
                    traitsMount,
                    component: rawSelected,
                    editor,
                    labels,
                });
                renderedRoot = null;
                renderedRootBlockId = '';
                renderedDescriptorId = null;

                return;
            }

            if (isChromeShellManagedInspectorSelection(rawSelected, editor)) {
                mount.hidden = false;
                traitsMount?.classList.add('hidden');
                traitsMount?.replaceChildren?.();
                mount.replaceChildren();
                mount.appendChild(createInspectorEmptyState({
                    message: chromeShellManagedInspectorNotice(editor, labels),
                    classNameExtra: 'voodbuilder-gjs-chrome-layout-notice',
                }));
                renderedRoot = null;
                renderedRootBlockId = '';
                renderedDescriptorId = null;

                return;
            }

            if (shouldDeferLayoutInspectorRender(rawSelected)) {
                renderLayoutInspectorLoading();

                return;
            }

            if (
                isChromeLayoutModeEditor(editor)
                && rawSelected
                && isChromeLayoutContentSlotComponent(rawSelected)
            ) {
                mount.hidden = true;
                mount.replaceChildren();
                renderLayoutSlotHint(traitsMount);

                return;
            }

            const { descriptor, root } = resolveSettings(rawSelected, editor);

            if (
                isChromeShellModeEditor(editor)
                && ! isChromeLayoutModeEditor(editor)
                && isLayoutOnlyDescriptor(descriptor)
            ) {
                mount.hidden = false;
                traitsMount?.classList.add('hidden');
                traitsMount?.replaceChildren?.();
                mount.replaceChildren();
                mount.appendChild(createInspectorEmptyState({
                    message: chromeShellManagedInspectorNotice(editor, labels),
                    classNameExtra: 'voodbuilder-gjs-chrome-layout-notice',
                }));
                renderedRoot = null;
                renderedRootBlockId = '';
                renderedDescriptorId = null;

                return;
            }

            if (! descriptor || ! root || ! isValidGrapesComponent(root)) {
                const chromeZone = isChromeLayoutModeEditor(editor)
                    ? resolveLayoutChromeZone(rawSelected)
                    : null;

                if (chromeZone === 'nav' || chromeZone === 'footer') {
                    editor.__voodbuilderEnsureChromeBlockSettings?.(editor);

                    const zoneBlock = getLayoutChromeBlock(editor, chromeZone);

                    // Zone empty (nav/footer removed): courtesy empty state, not stale forms.
                    if (! zoneBlock || ! isValidGrapesComponent(zoneBlock)) {
                        editor.__voodbuilderChromeSettingsResolveRetries = 0;
                        clearActiveLayoutSettingsRoot(editor);
                        showCourtesyEmptyState(labels);

                        return;
                    }

                    const zoneDescriptor = resolveSettings(zoneBlock, editor).descriptor;

                    if (zoneDescriptor) {
                        ensureRootInspectable(zoneBlock);
                        setActiveLayoutSettingsRoot(editor, zoneBlock, chromeZone);

                        if (isContentInspectorTabActive(editor)) {
                            mount.hidden = false;
                            traitsMount?.classList.add('hidden');
                            traitsMount?.replaceChildren?.();
                            mount.replaceChildren();
                            zoneDescriptor.render({
                                mount,
                                root: zoneBlock,
                                editor,
                                traitsMount,
                            });
                            renderedRoot = zoneBlock;
                            renderedRootBlockId = readBlockId(zoneBlock);
                            renderedDescriptorId = zoneDescriptor.id;
                        }

                        return;
                    }

                    const retries = Number(editor.__voodbuilderChromeSettingsResolveRetries ?? 0);

                    if (retries < 8 && ! isLayoutInspectorReady(editor)) {
                        editor.__voodbuilderChromeSettingsResolveRetries = retries + 1;
                        mount.hidden = false;
                        traitsMount?.classList.add('hidden');
                        traitsMount?.replaceChildren?.();

                        if (! mount.querySelector('[data-voodbuilder-inspector-empty-state]')) {
                            mount.replaceChildren();
                            mount.appendChild(createInspectorEmptyState({
                                message: chromeZone === 'nav'
                                    ? 'Loading header settings…'
                                    : 'Loading footer settings…',
                            }));
                        }

                        window.setTimeout(() => {
                            editor.__voodbuilderBlockSettingsRender?.();
                        }, 120);

                        return;
                    }

                    editor.__voodbuilderChromeSettingsResolveRetries = 0;
                    clearActiveLayoutSettingsRoot(editor);
                    showCourtesyEmptyState(labels);

                    return;
                }

                showTraitsOrNoSettings(rawSelected, labels);
                renderedRoot = null;
                renderedRootBlockId = '';
                renderedDescriptorId = null;

                return;
            }

            editor.__voodbuilderChromeSettingsResolveRetries = 0;

            const rootBlockId = readBlockId(root);

            // Settings UI lives on the Content tab only.
            // Never force-activate Content here — that trapped users on this tab.
            if (! isContentInspectorTabActive(editor)) {
                return;
            }

            ensureRootInspectable(root);
            maybePromoteSelectionForHighlight(rawSelected, root);

            const existingForm = mount.querySelector('.voodbuilder-gjs-form');
            const canReuseForm = Boolean(
                existingForm
                && ! mount.hidden
                && renderedRootBlockId !== ''
                && renderedRootBlockId === rootBlockId
                && renderedDescriptorId === descriptor.id
                && renderedRoot === root
                && isValidGrapesComponent(root)
            );

            if (canReuseForm) {
                mount.hidden = false;
                traitsMount?.classList.add('hidden');
                closeAllInspectorSelects();
                syncSettingsFormValues(mount, root);

                return;
            }

            closeAllInspectorSelects();
            mount.hidden = false;
            traitsMount?.classList.add('hidden');
            traitsMount?.replaceChildren?.();
            mount.replaceChildren();

            descriptor.render({ mount, root, editor, traitsMount });

            renderedRoot = root;
            renderedRootBlockId = rootBlockId;
            renderedDescriptorId = descriptor.id;
        } finally {
            editor.__voodbuilderBlockSettingsRendering = false;
        }
    };

    const scheduleRender = () => {
        if (editor.__voodbuilderSettingsChange) {
            return;
        }

        if (editor.__voodbuilderBlockSettingsRenderScheduled) {
            return;
        }

        editor.__voodbuilderBlockSettingsRenderScheduled = true;

        window.requestAnimationFrame(() => {
            editor.__voodbuilderBlockSettingsRenderScheduled = false;
            render();
        });
    };

    const clearInspectorAfterChromeRemoval = (removed) => {
        if (! isChromeLayoutModeEditor(editor) || ! removed) {
            return;
        }

        const selected = editor.getSelected?.();
        const activeRoot = editor.__voodbuilderActiveSettingsRoot;
        const removedContains = (candidate) => {
            if (! candidate) {
                return false;
            }

            if (candidate === removed || candidate.isRemoved?.()) {
                return true;
            }

            if (! isValidGrapesComponent(candidate)) {
                return true;
            }

            let current = candidate;

            while (current && current.get?.('type') !== 'wrapper') {
                if (current === removed) {
                    return true;
                }

                current = current.parent?.();
            }

            return false;
        };

        if (
            removedContains(selected)
            || removedContains(activeRoot)
            || (renderedRoot && removedContains(renderedRoot))
        ) {
            clearActiveLayoutSettingsRoot(editor);
            invalidateRenderCache();

            try {
                editor.select?.(null);
            } catch {
                // Grapes may already have cleared selection.
            }

            scheduleRender();
            editor.trigger?.('voodbuilder:inspector-panel:refresh', {
                tabId: editor.__voodbuilderInspectorActiveTab ?? 'content',
                component: null,
            });
        }
    };

    editor.on('component:selected', scheduleRender);
    editor.on('component:deselected', render);
    editor.on('component:remove', clearInspectorAfterChromeRemoval);
    editor.on('load', scheduleRender);
    editor.on('voodbuilder:chrome-layout-ready', scheduleRender);
    editor.on('voodbuilder:layout-inspector-ready', scheduleRender);
    editor.on('voodbuilder:dynamic-blocks-refreshed', () => {
        closeAllInspectorSelects();
        invalidateRenderCache();
        scheduleRender();
    });
    editor.on('component:update', (component) => {
        if (editor.__voodbuilderSettingsChange) {
            return;
        }

        const selected = editor.getSelected();

        // Counter Content form remounted on every attr sync (bindings/preview) and
        // flickered the custom selects — keep it mounted and sync values instead.
        if (
            selected
            && renderedDescriptorId === 'animated-counter'
            && renderedRoot === selected
            && isAnimatedCounterComponent(selected)
            && mount.querySelector('.voodbuilder-gjs-form')
        ) {
            if (component === selected || isWithinSettingsRoot(component, selected)) {
                syncAnimatedCounterSettingsForm(mount, selected);
            }

            return;
        }

        const { root } = resolveSettings(selected, editor);

        if (
            root
            && renderedRoot === root
            && isWithinSettingsRoot(component, root)
            && mount.querySelector('.voodbuilder-gjs-form')
        ) {
            return;
        }

        scheduleRender();
    });

    editor.__voodbuilderBlockSettingsRender = render;
}

/** @deprecated */
export const registerBlockSettingsUi = registerSettingsUi;

/**
 * @param {object} editor
 */
export function refreshBlockSettingsUi(editor) {
    editor?.__voodbuilderBlockSettingsRender?.();
}

/**
 * @param {object} editor
 * @param {object|null|undefined} component
 * @returns {object|null}
 */
export function promoteRoot(editor, component) {
    const root = findInspectableRoot(component, editor);

    if (! root || ! shouldPromoteSelectionToRoot(component, root, editor)) {
        refreshBlockSettingsUi(editor);

        return root;
    }

    if (editor.getSelected?.() === root) {
        refreshBlockSettingsUi(editor);

        return root;
    }

    ensureRootInspectable(root);
    editor.select(root, { scroll: false });
    refreshBlockSettingsUi(editor);

    return root;
}

/** @deprecated */
export const promoteInspectableBlockSelection = promoteRoot;
