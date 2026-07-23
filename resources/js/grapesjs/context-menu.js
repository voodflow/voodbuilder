/**
 * Reusable contextual menu for the VoodBuilder editor shell.
 * Supports flat items, separators, and one-level flyout submenus.
 */

let activeMenu = null;
let menuRoot = null;

function resolveMenuRoot() {
    if (menuRoot?.isConnected) {
        return menuRoot;
    }

    menuRoot = document.body;

    return menuRoot;
}

function ensureMenu() {
    const mount = resolveMenuRoot();
    let menu = mount.querySelector('[data-voodbuilder-context-menu]');

    if (menu) {
        return menu;
    }

    menu = document.createElement('div');
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

function positionMenu(menu, x, y) {
    menu.hidden = false;
    menu.style.left = '0px';
    menu.style.top = '0px';

    const menuRect = menu.getBoundingClientRect();
    const padding = 8;
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;

    let left = x;
    let top = y;

    if (left + menuRect.width > viewportWidth - padding) {
        left = Math.max(padding, viewportWidth - menuRect.width - padding);
    }

    if (top + menuRect.height > viewportHeight - padding) {
        top = Math.max(padding, top - menuRect.height);
    }

    menu.style.left = `${Math.max(padding, left)}px`;
    menu.style.top = `${Math.max(padding, top)}px`;
}

function positionSubmenu(parentItem, submenu) {
    submenu.hidden = false;
    submenu.style.left = '100%';
    submenu.style.right = 'auto';
    submenu.style.top = '0px';

    const parentRect = parentItem.getBoundingClientRect();
    const submenuRect = submenu.getBoundingClientRect();
    const padding = 8;

    if (parentRect.right + submenuRect.width > window.innerWidth - padding) {
        submenu.style.left = 'auto';
        submenu.style.right = '100%';
    }

    const overflowBottom = parentRect.top + submenuRect.height - (window.innerHeight - padding);

    if (overflowBottom > 0) {
        submenu.style.top = `${Math.max(-parentRect.top + padding, -overflowBottom)}px`;
    }
}

function createMenuItemButton(item, context) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'voodbuilder-gjs-context-menu__item';
    button.setAttribute('role', 'menuitem');
    button.disabled = Boolean(item.disabled);

    if (item.danger) {
        button.classList.add('voodbuilder-gjs-context-menu__item--danger');
    }

    if (Array.isArray(item.children) && item.children.length > 0) {
        button.classList.add('voodbuilder-gjs-context-menu__item--submenu');
        button.setAttribute('aria-haspopup', 'menu');
        button.setAttribute('aria-expanded', 'false');

        const label = document.createElement('span');
        label.textContent = item.label;
        const chevron = document.createElement('span');
        chevron.className = 'voodbuilder-gjs-context-menu__chevron';
        chevron.setAttribute('aria-hidden', 'true');
        chevron.textContent = '›';
        button.append(label, chevron);
    } else {
        button.textContent = item.label;
    }

    button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (button.disabled || (Array.isArray(item.children) && item.children.length > 0)) {
            return;
        }

        closeActiveMenu();
        item.onSelect?.(context);
    });

    return button;
}

function createSeparator() {
    const separator = document.createElement('div');
    separator.className = 'voodbuilder-gjs-context-menu__separator';
    separator.setAttribute('role', 'separator');

    return separator;
}

function createSubmenu(item, context) {
    const wrap = document.createElement('div');
    wrap.className = 'voodbuilder-gjs-context-menu__submenu-wrap';

    const trigger = createMenuItemButton(item, context);
    const submenu = document.createElement('div');
    submenu.className = 'voodbuilder-gjs-context-menu voodbuilder-gjs-context-menu--submenu';
    submenu.setAttribute('role', 'menu');
    submenu.hidden = true;

    for (const child of item.children) {
        if (child.type === 'separator') {
            submenu.appendChild(createSeparator());
            continue;
        }

        submenu.appendChild(createMenuItemButton(child, context));
    }

    const open = () => {
        const root = wrap.closest('[data-voodbuilder-context-menu]');

        root?.querySelectorAll?.('.voodbuilder-gjs-context-menu--submenu').forEach((node) => {
            if (node !== submenu) {
                node.hidden = true;
                node.previousElementSibling?.setAttribute?.('aria-expanded', 'false');
            }
        });
        positionSubmenu(wrap, submenu);
        trigger.setAttribute('aria-expanded', 'true');
    };

    const close = () => {
        submenu.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
    };

    wrap.addEventListener('pointerenter', open);
    wrap.addEventListener('pointerleave', close);
    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        if (submenu.hidden) {
            open();
        } else {
            close();
        }
    });

    wrap.append(trigger, submenu);

    return wrap;
}

/**
 * @param {object} options
 * @param {number} options.x - Viewport X
 * @param {number} options.y - Viewport Y
 * @param {Array<object>} options.items
 * @param {*} [options.context] - Passed to onSelect handlers
 */
export function openContextMenu({ x, y, items = [], context = null }) {
    if (items.length === 0) {
        return;
    }

    closeActiveMenu();

    const menu = ensureMenu();
    menu.replaceChildren();

    for (const item of items) {
        if (item.type === 'separator') {
            menu.appendChild(createSeparator());
            continue;
        }

        if (Array.isArray(item.children) && item.children.length > 0) {
            menu.appendChild(createSubmenu(item, context));
            continue;
        }

        menu.appendChild(createMenuItemButton(item, context));
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

    positionMenu(menu, x, y);
}

export function closeContextMenu() {
    closeActiveMenu();
}
