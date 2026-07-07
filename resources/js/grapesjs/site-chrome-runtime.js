/**
 * Header chrome interactions (profile menu, mobile drawer, nav dropdowns) without Alpine.
 * Used on the live site and inside the GrapesJS canvas iframe.
 */

const PROFILE_PANEL_TRANSITION_MS = 150;

function closeProfileMenus(scope) {
    scope.querySelectorAll('[data-voodbuilder-profile-menu-panel]').forEach((panel) => {
        panel.classList.remove('is-open');

        window.setTimeout(() => {
            if (! panel.classList.contains('is-open')) {
                panel.hidden = true;
            }
        }, PROFILE_PANEL_TRANSITION_MS);
    });

    scope.querySelectorAll('[data-voodbuilder-profile-menu-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-expanded', 'false');
    });
}

function openProfilePanel(panel, toggle) {
    panel.hidden = false;
    panel.classList.remove('is-open');

    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            panel.classList.add('is-open');
        });
    });

    toggle.setAttribute('aria-expanded', 'true');
}

export function initProfileMenus(scope = document) {
    scope.querySelectorAll('[data-voodbuilder-profile-menu]').forEach((root) => {
        if (root.dataset.voodbuilderProfileMenuBound === 'true') {
            return;
        }

        root.dataset.voodbuilderProfileMenuBound = 'true';

        const toggle = root.querySelector('[data-voodbuilder-profile-menu-toggle]');
        const panel = root.querySelector('[data-voodbuilder-profile-menu-panel]');

        if (! toggle || ! panel) {
            return;
        }

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            const willOpen = panel.hidden || ! panel.classList.contains('is-open');
            closeProfileMenus(scope);

            if (willOpen) {
                openProfilePanel(panel, toggle);
            }
        });
    });

    if (scope.documentElement?.dataset?.voodbuilderProfileMenuDismissBound === 'true') {
        return;
    }

    scope.documentElement.dataset.voodbuilderProfileMenuDismissBound = 'true';

    scope.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('[data-voodbuilder-profile-menu]')) {
            return;
        }

        closeProfileMenus(scope);
    });

    scope.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeProfileMenus(scope);
        }
    });
}

function closeNavDropdowns(scope, exceptRoot = null) {
    scope.querySelectorAll('[data-voodbuilder-nav-dropdown]').forEach((root) => {
        if (root === exceptRoot) {
            return;
        }

        const panel = root.querySelector('[data-voodbuilder-nav-dropdown-panel]');

        if (panel) {
            panel.hidden = true;
        }

        const toggle = root.querySelector('[data-voodbuilder-nav-dropdown-toggle]');

        if (toggle) {
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}

function bindNavDropdown(root, scope) {
    if (root.dataset.voodbuilderNavDropdownBound === 'true') {
        return;
    }

    root.dataset.voodbuilderNavDropdownBound = 'true';

    const toggle = root.querySelector('[data-voodbuilder-nav-dropdown-toggle]');
    const panel = root.querySelector('[data-voodbuilder-nav-dropdown-panel]');

    if (! toggle || ! panel) {
        return;
    }

    const trigger = root.dataset.voodbuilderNavDropdownTrigger ?? 'click';

    if (trigger === 'hover') {
        root.addEventListener('mouseenter', () => {
            panel.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
        });

        root.addEventListener('mouseleave', () => {
            panel.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        });

        return;
    }

    toggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();

        const willOpen = panel.hidden;
        closeNavDropdowns(scope, willOpen ? root : null);
        panel.hidden = ! willOpen;
        toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    });
}

export function initNavDropdowns(scope = document) {
    scope.querySelectorAll('[data-voodbuilder-nav-dropdown]').forEach((root) => {
        bindNavDropdown(root, scope);
    });

    if (scope.documentElement?.dataset?.voodbuilderNavDropdownDismissBound === 'true') {
        return;
    }

    scope.documentElement.dataset.voodbuilderNavDropdownDismissBound = 'true';

    scope.addEventListener('click', (event) => {
        if (event.target instanceof Element && event.target.closest('[data-voodbuilder-nav-dropdown]')) {
            return;
        }

        closeNavDropdowns(scope);
    });

    scope.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeNavDropdowns(scope);
        }
    });
}

function setMobileNavSectionOpen(section, open) {
    const panel = section.querySelector('[data-voodbuilder-nav-mobile-panel]');
    const toggle = section.querySelector('[data-voodbuilder-nav-mobile-toggle]');

    if (! panel || ! toggle) {
        return;
    }

    panel.hidden = ! open;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.querySelector('[data-voodbuilder-nav-mobile-chevron]')?.classList.toggle('rotate-180', open);
}

export function initMobileNavSections(scope = document) {
    scope.querySelectorAll('[data-voodbuilder-nav-mobile-item]').forEach((section) => {
        if (section.dataset.voodbuilderNavMobileBound === 'true') {
            return;
        }

        section.dataset.voodbuilderNavMobileBound = 'true';

        const toggle = section.querySelector('[data-voodbuilder-nav-mobile-toggle]');
        const panel = section.querySelector('[data-voodbuilder-nav-mobile-panel]');

        if (! toggle || ! panel) {
            return;
        }

        toggle.addEventListener('click', () => {
            const willOpen = panel.hidden;
            setMobileNavSectionOpen(section, willOpen);
        });
    });
}

function setMobileNavOpen(doc, open) {
    const mobileNav = doc.querySelector('[data-mobile-nav]');
    const mobileToggle = doc.querySelector('[data-mobile-nav-toggle]');

    if (! mobileNav || ! mobileToggle) {
        return;
    }

    if (open) {
        mobileNav.hidden = false;
        mobileNav.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(() => {
            mobileNav.classList.add('is-open');
        });
    } else {
        mobileNav.classList.remove('is-open');
        mobileNav.setAttribute('aria-hidden', 'true');
        window.setTimeout(() => {
            if (! mobileNav.classList.contains('is-open')) {
                mobileNav.hidden = true;
            }
        }, 300);
    }

    mobileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    doc.body.classList.toggle('voodbuilder-mobile-nav-open', open);
}

export function initMobileNav(scope = document) {
    const mobileNav = scope.querySelector('[data-mobile-nav]');
    const mobileToggle = scope.querySelector('[data-mobile-nav-toggle]');

    if (! mobileNav || ! mobileToggle || mobileToggle.dataset.voodbuilderMobileNavBound === 'true') {
        return;
    }

    mobileToggle.dataset.voodbuilderMobileNavBound = 'true';

    mobileToggle.addEventListener('click', () => {
        setMobileNavOpen(scope, ! mobileNav.classList.contains('is-open'));
    });

    scope.addEventListener('click', (event) => {
        const target = event.target instanceof Element
            ? event.target.closest('[data-mobile-nav-close]')
            : null;

        if (target) {
            setMobileNavOpen(scope, false);
        }
    });

    scope.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && mobileNav.classList.contains('is-open')) {
            setMobileNavOpen(scope, false);
        }
    });
}

export function initSiteChrome(scope = document) {
    initProfileMenus(scope);
    initNavDropdowns(scope);
    initMobileNavSections(scope);
    initMobileNav(scope);
}
