/**
 * Tailwind-style custom selects for GrapesJS inspector (traits + style manager).
 * Uses public DOM hooks only — no patches to node_modules/grapesjs.
 */

const CHEVRON_SVG = '<svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>';

function closeOpenSelects(exceptWrap = null) {
    document.querySelectorAll('.voodbuilder-gjs-select-wrap.is-open').forEach((wrap) => {
        if (wrap === exceptWrap) {
            return;
        }

        wrap.classList.remove('is-open');
        wrap.querySelector('.voodbuilder-gjs-select-list')?.setAttribute('hidden', '');
        wrap.querySelector('.voodbuilder-gjs-select-trigger')?.setAttribute('aria-expanded', 'false');
    });
}

function syncCustomSelect(wrap) {
    const select = wrap.querySelector('select');
    const trigger = wrap.querySelector('.voodbuilder-gjs-select-trigger');
    const list = wrap.querySelector('.voodbuilder-gjs-select-list');

    if (! select || ! trigger || ! list) {
        return;
    }

    const selected = select.options[select.selectedIndex];
    trigger.textContent = selected?.textContent?.trim() || '';

    list.querySelectorAll('.voodbuilder-gjs-select-option').forEach((option) => {
        const active = option.dataset.value === select.value;
        option.classList.toggle('is-selected', active);
        option.setAttribute('aria-selected', active ? 'true' : 'false');
    });
}

function buildOptionList(select, list, wrap) {
    list.replaceChildren();

    for (const option of select.options) {
        const item = document.createElement('li');
        item.className = 'voodbuilder-gjs-select-option';
        item.role = 'option';
        item.dataset.value = option.value;
        item.textContent = option.textContent?.trim() || option.value;
        item.tabIndex = -1;

        item.addEventListener('mousedown', (event) => {
            event.preventDefault();
        });

        item.addEventListener('click', () => {
            if (select.value !== option.value) {
                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }

            syncCustomSelect(wrap);
            closeOpenSelects();
        });

        list.appendChild(item);
    }

    syncCustomSelect(wrap);
}

function enhanceSelect(select) {
    if (!(select instanceof HTMLSelectElement) || select.dataset.vbInspectorSelect === '1') {
        return;
    }

    if (select.closest('.voodbuilder-gjs-select-wrap')) {
        return;
    }

    select.dataset.vbInspectorSelect = '1';
    select.classList.add('voodbuilder-gjs-select', 'voodbuilder-gjs-select--native');

    const wrap = document.createElement('div');
    wrap.className = 'voodbuilder-gjs-select-wrap';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'voodbuilder-gjs-select-trigger';
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const chevron = document.createElement('span');
    chevron.className = 'voodbuilder-gjs-select-chevron';
    chevron.innerHTML = CHEVRON_SVG;

    const list = document.createElement('ul');
    list.className = 'voodbuilder-gjs-select-list';
    list.role = 'listbox';
    list.hidden = true;

    select.parentNode?.insertBefore(wrap, select);
    wrap.append(trigger, select, chevron, list);

    trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        const willOpen = ! wrap.classList.contains('is-open');
        closeOpenSelects(willOpen ? wrap : null);

        if (willOpen) {
            wrap.classList.add('is-open');
            list.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            list.querySelector('.voodbuilder-gjs-select-option.is-selected')?.scrollIntoView?.({ block: 'nearest' });
        } else {
            wrap.classList.remove('is-open');
            list.hidden = true;
            trigger.setAttribute('aria-expanded', 'false');
        }
    });

    trigger.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            if (! wrap.classList.contains('is-open')) {
                trigger.click();
            }
        }

        if (event.key === 'Escape') {
            closeOpenSelects();
        }
    });

    select.addEventListener('change', () => syncCustomSelect(wrap));

    buildOptionList(select, list, wrap);
}

export function enhanceInspectorSelects(root = document) {
    if (! root) {
        return;
    }

    const scope = root instanceof Document ? root : root;

    scope.querySelectorAll?.('.voodbuilder-gjs-root select:not([data-vb-inspector-select])').forEach((select) => {
        if (select.closest('.voodbuilder-gjs-bindings-modal, .voodbuilder-code-editor-modal')) {
            return;
        }

        enhanceSelect(select);
    });
}

export function registerInspectorSelectUi(editor, mounts = {}) {
    if (editor.__voodbuilderInspectorSelectUiRegistered) {
        return;
    }

    editor.__voodbuilderInspectorSelectUiRegistered = true;

    const roots = [
        mounts.traits,
        mounts.styles,
        mounts.selectors,
        mounts.conditions,
        mounts.dynamic,
        mounts.siteChromeSettings,
    ].filter(Boolean);

    const refresh = () => {
        window.requestAnimationFrame(() => {
            for (const root of roots) {
                enhanceInspectorSelects(root);
            }
        });
    };

    editor.on('component:selected', refresh);
    editor.on('trait:select', refresh);
    editor.on('load', refresh);

    document.addEventListener('click', () => closeOpenSelects());

    for (const root of roots) {
        if (! root || root.__vbSelectObserver) {
            continue;
        }

        const observer = new MutationObserver(refresh);
        observer.observe(root, { childList: true, subtree: true });
        root.__vbSelectObserver = observer;
    }

    refresh();
}
