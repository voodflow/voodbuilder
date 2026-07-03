/**
 * Collapsible inspector sectors — same look as GrapesJS Style Manager sectors.
 */

export function mountCollapsibleInspectorSector(mount, { title, open = true } = {}) {
    if (! mount || mount.dataset.voodbuilderSectorReady === '1') {
        return mount?.closest('.voodbuilder-gjs-inspector-sector') ?? null;
    }

    const parent = mount.parentElement;

    if (! parent) {
        return null;
    }

    const sector = document.createElement('div');
    sector.className = `voodbuilder-gjs-inspector-sector${open ? ' is-open' : ''}`;

    const toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'voodbuilder-gjs-inspector-sector__title';
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.innerHTML = `<span class="voodbuilder-gjs-inspector-sector__caret" aria-hidden="true"></span><span class="voodbuilder-gjs-inspector-sector__label">${title ?? ''}</span>`;

    const body = document.createElement('div');
    body.className = 'voodbuilder-gjs-inspector-sector__body';

    sector.append(toggle, body);
    parent.replaceChild(sector, mount);
    body.appendChild(mount);

    toggle.addEventListener('click', () => {
        const nextOpen = ! sector.classList.contains('is-open');
        sector.classList.toggle('is-open', nextOpen);
        toggle.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
    });

    mount.dataset.voodbuilderSectorReady = '1';

    return sector;
}

export function setupStyleInspectorSectors(mounts, labels = {}) {
    if (mounts?.selectors) {
        mountCollapsibleInspectorSector(mounts.selectors, {
            title: labels.styleClassesTitle ?? 'Classes',
            open: true,
        });
    }

    if (mounts?.globalClasses) {
        mountCollapsibleInspectorSector(mounts.globalClasses, {
            title: labels.globalClassesTitle ?? 'Global classes',
            open: false,
        });
    }
}
