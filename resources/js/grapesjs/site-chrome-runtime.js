/**
 * Header chrome interactions (profile menu, mobile drawer) without Alpine.
 * Used on the live site and inside the GrapesJS canvas iframe.
 */

function closeProfileMenus(scope) {
    scope.querySelectorAll('[data-voodbuilder-profile-menu-panel]').forEach((panel) => {
        panel.hidden = true;
    });

    scope.querySelectorAll('[data-voodbuilder-profile-menu-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-expanded', 'false');
    });
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

            const willOpen = panel.hidden;
            closeProfileMenus(scope);

            if (willOpen) {
                panel.hidden = false;
                toggle.setAttribute('aria-expanded', 'true');
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
    initMobileNav(scope);
}
