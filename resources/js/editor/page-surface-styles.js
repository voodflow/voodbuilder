/**
 * Page-level surface styling: reuse Style panel background controls on the
 * GrapesJS wrapper. Publish remaps wrapper #id CSS to `body` so the background
 * covers the full page (including chrome).
 *
 * UX: a persistent “Page” control in the Style inspector — full canvases almost
 * never have an empty click target, so deselect-to-edit is not enough.
 */

export const PAGE_SURFACE_CLASS = 'voodbuilder-page-surface';
export const PAGE_SURFACE_ACTION_ATTR = 'data-voodbuilder-page-surface-action';

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

    if (selected && ! selected.isRemoved?.()) {
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
        selectable: true,
        highlightable: true,
        hoverable: false,
    });

    return wrapper;
}

/**
 * Select the page root so Style → Background edits the full page.
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

    if (editor.getSelected?.() === wrapper) {
        return wrapper;
    }

    try {
        editor.select?.(wrapper);
    } catch {
        // Grapes may reject selection mid-destroy.
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
 * Persistent control in the Style inspector (survives empty-state / chrome notices).
 *
 * @param {import('grapesjs').Editor} editor
 * @param {HTMLElement|null|undefined} stylePanel
 * @param {{ pageLabel?: string, pageSurfaceHint?: string }} [labels]
 */
export function ensurePageSurfaceAction(editor, stylePanel, labels = {}) {
    if (! editor || ! stylePanel || ! isPageSurfaceMode(editor)) {
        return null;
    }

    let bar = stylePanel.querySelector(`[${PAGE_SURFACE_ACTION_ATTR}]`);

    if (! bar) {
        bar = document.createElement('div');
        bar.setAttribute(PAGE_SURFACE_ACTION_ATTR, '1');
        bar.className = 'voodbuilder-editor-page-surface-action';
        bar.innerHTML = `
            <button type="button" class="voodbuilder-editor-page-surface-action__btn" data-voodbuilder-page-surface-select>
            </button>
            <p class="voodbuilder-editor-page-surface-action__hint" data-voodbuilder-page-surface-hint></p>
        `;
        stylePanel.insertBefore(bar, stylePanel.firstChild);

        bar.querySelector('[data-voodbuilder-page-surface-select]')?.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            selectPageSurface(editor);
            editor.__voodbuilderActivateInspectorTab?.('style', { userInitiated: true });
        });
    }

    const pageLabel = labels.pageSurfaceLabel ?? labels.classStylePage ?? 'Page';
    const hint = labels.pageSurfaceHint
        ?? 'Background and base styles for the whole page (not a single block).';
    const btn = bar.querySelector('[data-voodbuilder-page-surface-select]');
    const hintEl = bar.querySelector('[data-voodbuilder-page-surface-hint]');

    if (btn) {
        btn.textContent = pageLabel;
        const active = isPageSurfaceComponent(editor.getSelected?.(), editor);
        btn.classList.toggle('is-active', active);
        btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        btn.title = hint;
    }

    if (hintEl) {
        hintEl.textContent = hint;
        hintEl.hidden = isPageSurfaceComponent(editor.getSelected?.(), editor);
    }

    bar.hidden = false;

    return bar;
}

/**
 * @param {import('grapesjs').Editor} editor
 * @param {{ pageLabel?: string, pageSurfaceHint?: string }} [labels]
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

        const mount = document.querySelector('.gjs-sm-sectors');

        if (! mount) {
            return;
        }

        let badge = mount.querySelector('[data-voodbuilder-page-surface-badge]');
        const targetingPage = isPageSurfaceComponent(editor.getSelected?.(), editor);

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
    editor.on('component:selected', syncUi);
    editor.on('component:deselected', () => {
        window.requestAnimationFrame(syncUi);
    });
    editor.on('canvas:frame:load', () => {
        window.requestAnimationFrame(boot);
    });

    boot();
}
