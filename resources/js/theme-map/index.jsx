import { createRoot } from 'react-dom/client';
import ThemeMapFlow from './ThemeMapFlow';
import './theme-map.css';

const roots = new WeakMap();

function readPayloadElement() {
    return document.getElementById('voodbuilder-theme-map-payload');
}

function parsePayload(raw) {
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

function currentPayload() {
    const payloadElement = readPayloadElement();
    const fromScript = payloadElement?.textContent?.trim();

    if (fromScript) {
        return parsePayload(fromScript);
    }

    const root = document.getElementById('voodbuilder-theme-map-root');

    return parsePayload(root?.dataset?.payload);
}

function writePayload(payload) {
    const payloadElement = readPayloadElement();

    if (payloadElement) {
        payloadElement.textContent = JSON.stringify(payload);

        return;
    }

    const root = document.getElementById('voodbuilder-theme-map-root');

    if (root) {
        root.dataset.payload = JSON.stringify(payload);
    }
}

function renderThemeMap(root, payload) {
    if (!root || !payload?.themes?.length) {
        return false;
    }

    let reactRoot = roots.get(root);

    if (!reactRoot) {
        reactRoot = createRoot(root);
        roots.set(root, reactRoot);
    }

    const wireId = root.closest('[wire\\:id]')?.getAttribute('wire:id') ?? null;

    reactRoot.render(
        <ThemeMapFlow
            key={(payload.themes ?? []).map((theme) => theme.id).join('|')}
            payload={payload}
            onAssignmentsChange={(subTheme, channelOverrides) => {
                if (!wireId || !window.Livewire) {
                    return;
                }

                const component = window.Livewire.find(wireId);

                if (component) {
                    component.call('applyAssignments', subTheme, channelOverrides);
                }
            }}
        />,
    );

    return true;
}

export function mountVoodbuilderThemeMap() {
    const root = document.getElementById('voodbuilder-theme-map-root');

    if (!root) {
        return false;
    }

    const payload = currentPayload();

    return renderThemeMap(root, payload);
}

function scheduleMount() {
    if (mountVoodbuilderThemeMap()) {
        return;
    }

    window.requestAnimationFrame(() => {
        mountVoodbuilderThemeMap();
    });
}

function registerLivewireHooks() {
    if (typeof window.Livewire === 'undefined' || window.__voodbuilderThemeMapHooksRegistered) {
        return;
    }

    window.__voodbuilderThemeMapHooksRegistered = true;

    window.Livewire.on('voodbuilder-theme-map-refresh', ({ payload }) => {
        const root = document.getElementById('voodbuilder-theme-map-root');

        if (!root || !payload) {
            return;
        }

        writePayload(payload);
        renderThemeMap(root, payload);
    });
}

window.voodbuilderMountThemeMap = mountVoodbuilderThemeMap;

function boot() {
    registerLivewireHooks();
    scheduleMount();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

document.addEventListener('livewire:navigated', boot);
document.addEventListener('livewire:initialized', boot);
