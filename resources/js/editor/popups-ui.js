/**
 * Optional popup UI for hosts.
 *
 * `voodbuilder` must stay agnostic when the `voodbuilder-popups` plugin is not installed yet:
 * build should succeed and popups should simply be disabled.
 */

const popupUiModules = import.meta.glob(
    '../../../../voodbuilder-popups/resources/js/editor/popups-ui.js',
    { eager: true },
);

// When the plugin is missing, the glob resolves to an empty object.
const popupUiModule = Object.values(popupUiModules)[0];

/**
 * @param {object} editor
 * @param {object} [options]
 */
export function registerPopupsUi(editor, options) {
    popupUiModule?.registerPopupsUi?.(editor, options);
}
