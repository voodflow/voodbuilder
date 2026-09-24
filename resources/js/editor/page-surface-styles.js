/**
 * Page-level surface styling: reuse Style panel background controls on the
 * GrapesJS wrapper when nothing is selected. Publish remaps wrapper #id CSS
 * to `body` so the background covers the full page (including chrome).
 */

export const PAGE_SURFACE_CLASS = 'voodbuilder-page-surface';

/**
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {boolean}
 */
export function isPageSurfaceMode(editor) {
    return ! editor?.__voodbuilderChromeLayoutMode
        && ! editor?.__voodbuilderChromeShellMode
        && ! editor?.__voodbuilderPopupMode;
}

/**
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {import('grapesjs').Component | null}
 */
export function resolveStyleTarget(editor) {
    const selected = editor?.getSelected?.() ?? null;

    if (selected) {
        return selected;
    }

    if (! isPageSurfaceMode(editor)) {
        return null;
    }

    return editor?.getWrapper?.() ?? null;
}

/**
 * @param {import('grapesjs').Component | null | undefined} component
 * @param {import('grapesjs').Editor | null | undefined} editor
 * @returns {boolean}
 */
export function isPageSurfaceComponent(component, editor = null) {
    if (! component) {
        return false;
    }

    if (component.get?.('type') === 'wrapper') {
        return true;
    }

    const wrapper = editor?.getWrapper?.();

    return Boolean(wrapper && component === wrapper);
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

    wrapper.set?.({
        droppable: true,
        // Selectable so Style panel + empty-canvas click can target the page.
        selectable: true,
        highlightable: true,
        hoverable: false,
    });

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
 * @param {import('grapesjs').Editor} editor
 * @param {{ pageLabel?: string }} [labels]
 */
export function registerPageSurfaceStyles(editor, labels = {}) {
    if (! editor || editor.__voodbuilderPageSurfaceRegistered) {
        return;
    }

    editor.__voodbuilderPageSurfaceRegistered = true;

    const pageLabel = labels.pageSurfaceLabel ?? labels.classStylePage ?? 'Page';

    const syncPageLabel = () => {
        const mount = document.querySelector('.gjs-sm-sectors');
        if (! mount) {
            return;
        }

        let badge = mount.querySelector('[data-voodbuilder-page-surface-badge]');
        const targetingPage = isPageSurfaceComponent(resolveStyleTarget(editor), editor)
            && ! editor.getSelected?.();

        if (! targetingPage && isPageSurfaceComponent(editor.getSelected?.(), editor)) {
            // Explicit wrapper selection counts as page too.
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
        syncPageLabel();
    };

    editor.on('load', boot);
    editor.on('component:selected', syncPageLabel);
    editor.on('component:deselected', () => {
        window.requestAnimationFrame(() => {
            ensurePageSurfaceWrapper(editor);
            syncPageLabel();
        });
    });

    // Empty canvas click → keep page styles available (deselection already happens).
    editor.on('canvas:frame:load', () => {
        window.requestAnimationFrame(boot);
    });

    boot();
}
