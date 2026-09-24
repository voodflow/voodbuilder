/**
 * Page-level surface styling: reuse Style panel background controls on the
 * GrapesJS wrapper. Publish remaps wrapper #id CSS to `body` so the background
 * covers the full page (including chrome).
 *
 * UX: when nothing is selected, Style targets the page automatically. A compact
 * “Page” switch appears only while editing another element (or locked chrome).
 */

export const PAGE_SURFACE_CLASS = 'voodbuilder-page-surface';
export const PAGE_SURFACE_ACTION_ATTR = 'data-voodbuilder-page-surface-action';
export const PAGE_SURFACE_FOCUS_EVENT = 'voodbuilder:page-surface-focus';

/**
 * Page-level surface styling is available on normal site pages (including chrome
 * shell). Disabled only while editing a chrome layout or a popup.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {boolean}
 */
export function isPageSurfaceMode(editor) {
    return ! editor?.__voodbuilderChromeLayoutMode
        && ! editor?.__voodbuilderPopupMode;
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {boolean}
 */
export function isPageSurfaceComponent(component, editor = null) {
    if (! component || component.isRemoved?.()) {
        return false;
    }

    if (component.get?.('type') === 'wrapper') {
        return true;
    }

    const wrapper = editor?.getWrapper?.();

    return Boolean(wrapper && component === wrapper);
}

/**
 * Style is editing the page surface (wrapper selected, nothing selected, or force).
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {boolean}
 */
export function isTargetingPageSurface(editor) {
    if (! isPageSurfaceMode(editor)) {
        return false;
    }

    const selected = editor?.getSelected?.() ?? null;

    if (selected && ! selected.isRemoved?.() && ! isPageSurfaceComponent(selected, editor)) {
        if (editor.__voodbuilderForcePageSurfaceStyle) {
            editor.__voodbuilderForcePageSurfaceStyle = false;
        }

        return false;
    }

    if (editor?.__voodbuilderForcePageSurfaceStyle) {
        return true;
    }

    if (! selected || selected.isRemoved?.()) {
        return true;
    }

    return isPageSurfaceComponent(selected, editor);
}

/**
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {import('grapesjs').Component | null}
 */
export function resolveStyleTarget(editor) {
    if (isTargetingPageSurface(editor)) {
        return editor?.getWrapper?.() ?? null;
    }

    const selected = editor?.getSelected?.() ?? null;

    if (selected && ! selected.isRemoved?.()) {
        return selected;
    }

    return null;
}

/**
 * @param {import('grapesjs').Editor} editor
 */
export function ensurePageSurfaceWrapper(editor) {
    const wrapper = editor?.getWrapper?.();

    if (! wrapper || ! isPageSurfaceMode(editor)) {
        return wrapper ?? null;
    }

    const classes = wrapper.getClasses?.() ?? [];
    const classList = Array.isArray(classes) ? classes : [...(classes.models ?? classes)].map((c) => (
        typeof c === 'string' ? c : String(c?.get?.('name') ?? c?.id ?? '')
    ));

    if (! classList.includes(PAGE_SURFACE_CLASS)) {
        wrapper.addClass?.(PAGE_SURFACE_CLASS);
    }

    // Chrome shell keeps the wrapper non-selectable (nav/footer chrome). Page
    // styles still target the wrapper via resolveStyleTarget / force flag.
    if (! editor.__voodbuilderChromeShellMode) {
        wrapper.set?.({
            droppable: true,
            selectable: true,
            highlightable: true,
            hoverable: false,
            locked: false,
        });
    }

    return wrapper;
}

/**
 * Focus Style on the page surface. Prefers selecting the wrapper; if Grapes
 * rejects that (or chrome shell locks the wrapper), falls back to a forced
 * page-style target with no selection.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {import('grapesjs').Component | null}
 */
export function selectPageSurface(editor) {
    if (! editor || ! isPageSurfaceMode(editor)) {
        return null;
    }

    const wrapper = ensurePageSurfaceWrapper(editor);

    if (! wrapper) {
        return null;
    }

    // Shell pages: wrapper is intentionally not selectable — clear selection and
    // force Style onto the page surface instead.
    if (editor.__voodbuilderChromeShellMode) {
        try {
            editor.select?.();
        } catch {
            // Ignore clear-selection failures.
        }
        editor.__voodbuilderForcePageSurfaceStyle = true;
        try {
            editor.trigger?.(PAGE_SURFACE_FOCUS_EVENT);
        } catch {
            // Optional sync hook for the Style panel.
        }

        return wrapper;
    }

    try {
        editor.select?.(wrapper, { scroll: false });
    } catch {
        // Grapes may reject selection mid-destroy.
    }

    if (isPageSurfaceComponent(editor.getSelected?.(), editor)) {
        editor.__voodbuilderForcePageSurfaceStyle = false;
    } else {
        try {
            editor.select?.();
        } catch {
            // Clear selection so resolveStyleTarget falls back to wrapper.
        }
        editor.__voodbuilderForcePageSurfaceStyle = true;
    }

    try {
        editor.trigger?.(PAGE_SURFACE_FOCUS_EVENT);
    } catch {
        // Optional sync hook for the Style panel.
    }

    return wrapper;
}

/**
 * Remap wrapper #id author rules to body so published pages keep full-bleed backgrounds.
 *
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @param {string} css
 * @returns {string}
 */
export function remapPageSurfaceCssForPublish(editor, css) {
    const raw = String(css ?? '').trim();

    if (raw === '' || ! isPageSurfaceMode(editor)) {
        return raw;
    }

    const wrapper = editor?.getWrapper?.();
    const id = String(wrapper?.getId?.() ?? '').trim();

    if (id === '') {
        return raw;
    }

    const escaped = id.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const bodyTarget = `body, body.${PAGE_SURFACE_CLASS}, .${PAGE_SURFACE_CLASS}`;

    return raw
        .replace(new RegExp(`#${escaped}(?=[\\s,{.:#[])`, 'g'), bodyTarget)
        .replace(new RegExp(`\\[data-gjs-type=["']wrapper["']\\]`, 'g'), bodyTarget);
}

/**
 * Compact Style affordance / status for page-level styles.
 *
 * - Targeting page → status chip “Pagina” (so the panel is never blank/silent).
 * - Editing another element → compact “Stile pagina” switch.
 *
 * @param {import('grapesjs').Editor} editor
 * @param {HTMLElement|null|undefined} stylePanel
 * @param {{ pageSurfaceLabel?: string, pageSurfaceHint?: string, pageSurfaceSwitch?: string }} [labels]
 */
export function ensurePageSurfaceAction(editor, stylePanel, labels = {}) {
    if (! editor || ! stylePanel || ! isPageSurfaceMode(editor)) {
        stylePanel?.querySelector(`[${PAGE_SURFACE_ACTION_ATTR}]`)?.remove();

        return null;
    }

    const targetingPage = isTargetingPageSurface(editor);
    const pageLabel = labels.pageSurfaceLabel ?? labels.classStylePage ?? 'Page';
    const switchLabel = labels.pageSurfaceSwitch ?? pageLabel;
    const hint = labels.pageSurfaceHint
        ?? 'Background and base styles for the whole page (not a single block).';

    let bar = stylePanel.querySelector(`[${PAGE_SURFACE_ACTION_ATTR}]`);

    if (! bar) {
        bar = document.createElement('div');
        bar.setAttribute(PAGE_SURFACE_ACTION_ATTR, '1');
        bar.className = 'voodbuilder-editor-page-surface-action';
        bar.innerHTML = `
            <button type="button" class="voodbuilder-editor-page-surface-action__btn" data-voodbuilder-page-surface-select>
            </button>
        `;
        stylePanel.insertBefore(bar, stylePanel.firstChild);

        bar.querySelector('[data-voodbuilder-page-surface-select]')?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (isTargetingPageSurface(editor)) {
                return;
            }

            selectPageSurface(editor);
            editor.__voodbuilderActivateInspectorTab?.('style', { userInitiated: true });
        });
    }

    const btn = bar.querySelector('[data-voodbuilder-page-surface-select]');

    if (btn) {
        btn.textContent = targetingPage ? pageLabel : switchLabel;
        btn.classList.toggle('is-active', targetingPage);
        btn.classList.toggle('is-status', targetingPage);
        btn.disabled = targetingPage;
        btn.setAttribute('aria-pressed', targetingPage ? 'true' : 'false');
        btn.title = hint;
    }

    bar.hidden = false;
    bar.dataset.voodbuilderPageSurfaceMode = targetingPage ? 'status' : 'switch';

    return bar;
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {{ pageSurfaceLabel?: string, pageSurfaceHint?: string, pageSurfaceSwitch?: string }} [labels]
 */
export function registerPageSurfaceStyles(editor, labels = {}) {
    if (! editor || editor.__voodbuilderPageSurfaceRegistered) {
        return;
    }

    editor.__voodbuilderPageSurfaceRegistered = true;

    const pageLabel = labels.pageSurfaceLabel ?? labels.classStylePage ?? 'Page';

    const syncUi = () => {
        ensurePageSurfaceWrapper(editor);

        const stylePanel = document.querySelector('[data-voodbuilder-inspector="style"]');
        ensurePageSurfaceAction(editor, stylePanel, labels);

        const mount = document.querySelector('.gjs-sm-sectors')
            ?? document.querySelector('[data-voodbuilder-inspector="style"] .voodbuilder-editor-styles-mount');

        if (! mount) {
            return;
        }

        let badge = mount.querySelector('[data-voodbuilder-page-surface-badge]');
        const targetingPage = isTargetingPageSurface(editor);

        if (targetingPage) {
            if (! badge) {
                badge = document.createElement('div');
                badge.dataset.voodbuilderPageSurfaceBadge = '1';
                badge.className = 'voodbuilder-editor-page-surface-badge';
                mount.prepend(badge);
            }
            badge.hidden = false;
            badge.textContent = pageLabel;

            return;
        }

        if (badge) {
            badge.hidden = true;
        }
    };

    const boot = () => {
        ensurePageSurfaceWrapper(editor);
        syncUi();
    };

    editor.on('load', boot);
    editor.on('component:selected', () => {
        if (editor.__voodbuilderForcePageSurfaceStyle) {
            const selected = editor.getSelected?.();

            if (selected && ! isPageSurfaceComponent(selected, editor)) {
                editor.__voodbuilderForcePageSurfaceStyle = false;
            }
        }

        syncUi();
    });
    editor.on('component:deselected', () => {
        window.requestAnimationFrame(syncUi);
    });
    editor.on(PAGE_SURFACE_FOCUS_EVENT, syncUi);
    editor.on('canvas:frame:load', () => {
        window.requestAnimationFrame(boot);
    });

    boot();
}
