/**
 * Optional public popup runtime for hosts.
 *
 * `voodbuilder` must stay agnostic when the `voodbuilder-popups` plugin is not installed.
 */
const popupRuntimeModules = import.meta.glob(
    '../../../voodbuilder-popups/resources/js/popups-runtime.js',
    { eager: true },
);

// When the plugin is missing, the glob resolves to an empty object.
const popupRuntimeModule = Object.values(popupRuntimeModules)[0];

export function initPopups() {
    popupRuntimeModule?.initPopups?.();
}

export function previewPopup(...args) {
    return popupRuntimeModule?.previewPopup?.(...args);
}
