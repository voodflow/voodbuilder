/**
 * Reusable contextual menu for the VoodBuilder editor shell.
 */

let activeMenu = null;

function ensureMount(mount) {
    if (mount.querySelector('[data-voodbuilder-context-menu]')) {
        return mount.querySelector('[data-voodbuilder-context-menu]');
    }

    const menu = document.createElement('div');
    menu.className = 'voodbuilder-gjs-context-menu';
    menu.setAttribute('data-voodbuilder-context-menu', '');
    menu.hidden = true;
    menu.setAttribute('role', 'menu');
    mount.appendChild(menu);

    return menu;
}

function closeActiveMenu() {
    if (! activeMenu) {
        return;
    }

    activeMenu.menu.hidden = true;
    activeMenu.menu.replaceChildren();
    window.removeEventListener('pointerdown', activeMenu.onPointerDown, true);
    window.removeEventListener('keydown', activeMenu.onKeyDown, true);
    window.removeEventListener('resize', activeMenu.onClose, true);
    window.removeEventListener('scroll', activeMenu.onClose, true);
    activeMenu = null;
}

function positionMenu(menu, mount, x, y) {
    menu.hidden = false;
    menu.style.left = '0px';
    menu.style.top = '0px';

    const mountRect = mount.getBoundingClientRect();
    const menuRect = menu.getBoundingClientRect();
    const padding = 8;

    let left = x - mountRect.left;
    let top = y - mountRect.top;

    if (left + menuRect.width > mountRect.width - padding) {
        left = Math.max(padding, mountRect.width - menuRect.width - padding);
    }

    if (top + menuRect.height > mountRect.height - padding) {
        top = Math.max(padding, top - menuRect.height);
    }

    menu.style.left = `${Math.max(padding, left)}px`;
    menu.style.top = `${Math.max(padding, top)}px`;
}

/**
 * @param {object} options
 * @param {HTMLElement} options.mount - Positioning container (usually shell root)
 * @param {number} options.x - Viewport X
 * @param {number} options.y - Viewport Y
 * @param {Array<{id: string, label: string, danger?: boolean, disabled?: boolean, onSelect?: (context: *) => void}>} options.items
 * @param {*} [options.context] - Passed to onSelect handlers
 */
export function openContextMenu({ mount, x, y, items = [], context = null }) {
    if (! mount || items.length === 0) {
        return;
    }

    closeActiveMenu();

    const menu = ensureMount(mount);
    menu.replaceChildren();

    for (const item of items) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'voodbuilder-gjs-context-menu__item';
        button.setAttribute('role', 'menuitem');
        button.textContent = item.label;
        button.disabled = Boolean(item.disabled);

        if (item.danger) {
            button.classList.add('voodbuilder-gjs-context-menu__item--danger');
        }

        button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (button.disabled) {
                return;
            }

            closeActiveMenu();
            item.onSelect?.(context);
        });

        menu.appendChild(button);
    }

    const onPointerDown = (event) => {
        if (menu.contains(event.target)) {
            return;
        }

        closeActiveMenu();
    };

    const onKeyDown = (event) => {
        if (event.key === 'Escape') {
            closeActiveMenu();
        }
    };

    const onClose = () => closeActiveMenu();

    activeMenu = { menu, onPointerDown, onKeyDown, onClose };
    window.addEventListener('pointerdown', onPointerDown, true);
    window.addEventListener('keydown', onKeyDown, true);
    window.addEventListener('resize', onClose, true);
    window.addEventListener('scroll', onClose, true);

    positionMenu(menu, mount, x, y);
}

export function closeContextMenu() {
    closeActiveMenu();
}
