/**
 * Global CSS classes manager for the GrapesJS style inspector.
 */

import { alertDialog } from './editor-dialog.js';

export function registerGlobalClassesUi(editor, options = {}) {
    const {
        globalClassesUrl,
        csrf,
        labels = {},
        mount,
    } = options;

    if (! mount || ! globalClassesUrl) {
        return;
    }

    let catalog = [];

    mount.innerHTML = `
        <section class="voodbuilder-gjs-global-classes">
            <div class="voodbuilder-gjs-global-classes__list" data-voodbuilder-global-classes-list></div>
            <form class="voodbuilder-gjs-form-stack voodbuilder-gjs-global-classes-form" data-voodbuilder-global-classes-form>
                <label class="voodbuilder-gjs-form-field">
                    <span class="voodbuilder-gjs-form-field__label">${labels.globalClassesName ?? 'Class name'}</span>
                    <input type="text" name="name" class="voodbuilder-gjs-input" placeholder="my-class" pattern="[a-z][a-z0-9_-]*" required />
                </label>
                <label class="voodbuilder-gjs-form-field">
                    <span class="voodbuilder-gjs-form-field__label">${labels.globalClassesLabel ?? 'Label'}</span>
                    <input type="text" name="label" class="voodbuilder-gjs-input" placeholder="${labels.globalClassesLabel ?? 'Label'}" required />
                </label>
                <label class="voodbuilder-gjs-form-field">
                    <span class="voodbuilder-gjs-form-field__label">CSS</span>
                    <textarea name="css" class="voodbuilder-gjs-input voodbuilder-gjs-input--textarea" rows="4" placeholder=".my-class { color: inherit; }" required></textarea>
                </label>
                <button type="submit" class="voodbuilder-gjs-btn voodbuilder-gjs-btn--primary voodbuilder-gjs-btn--block">
                    ${labels.globalClassesSave ?? 'Save global class'}
                </button>
            </form>
        </section>
    `;

    const listEl = mount.querySelector('[data-voodbuilder-global-classes-list]');
    const form = mount.querySelector('[data-voodbuilder-global-classes-form]');

    const injectCss = (cssText) => {
        if (! cssText?.trim()) {
            return;
        }

        editor.Css.addRules(cssText);
    };

    const loadCatalog = async () => {
        try {
            const response = await fetch(globalClassesUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });

            if (! response.ok) {
                return;
            }

            const payload = await response.json();
            catalog = payload.classes ?? [];

            for (const item of catalog) {
                injectCss(item.css);
            }

            renderList();
        } catch {
            listEl.innerHTML = `<p class="voodbuilder-gjs-hint">${labels.globalClassesLoadError ?? 'Could not load global classes.'}</p>`;
        }
    };

    const renderList = () => {
        listEl.replaceChildren();

        if (catalog.length === 0) {
            listEl.innerHTML = `<p class="voodbuilder-gjs-hint">${labels.globalClassesEmpty ?? 'No global classes yet.'}</p>`;

            return;
        }

        const wrap = document.createElement('div');
        wrap.className = 'voodbuilder-gjs-global-classes__chips';

        for (const item of catalog) {
            const row = document.createElement('button');
            row.type = 'button';
            row.className = 'voodbuilder-gjs-global-class-chip';
            row.textContent = `.${item.name}`;
            row.title = item.label;
            row.addEventListener('click', () => {
                const selected = editor.getSelected();

                if (! selected) {
                    return;
                }

                const classes = [...(selected.getClasses?.() ?? [])];

                if (! classes.includes(item.name)) {
                    selected.addClass(item.name);
                }
            });
            wrap.appendChild(row);
        }

        listEl.appendChild(wrap);
    };

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();

        const data = new FormData(form);
        const name = String(data.get('name') ?? '').trim();
        const label = String(data.get('label') ?? '').trim();
        let css = String(data.get('css') ?? '').trim();

        if (! css.includes(`.${name}`)) {
            css = `.${name} { ${css.replace(/^\{|\}$/g, '').trim()} }`;
        }

        const response = await fetch(globalClassesUrl.replace(/\/$/, ''), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ name, label, css }),
        });

        if (! response.ok) {
            await alertDialog({
                message: labels.globalClassesSaveError ?? 'Could not save global class.',
                labels,
            });

            return;
        }

        const payload = await response.json();
        catalog.push(payload.class);
        injectCss(payload.class?.css);
        renderList();
        form.reset();
    });

    void loadCatalog();
}
