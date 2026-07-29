/**
 * Editor-canvas show/hide for [data-voodbuilder-chrome] nodes.
 *
 * Published HTML uses Tailwind `hidden`. The Editor canvas must use
 * `data-voodbuilder-chrome-hidden` only, so settings toggles can re-show
 * elements after save/reload without a full block re-render.
 *
 * Always strip `hidden` when updating visibility so any leaked published
 * class (or older preview HTML) does not stick after the user re-enables
 * a chrome slot — works for current and future chrome kinds.
 *
 * @param {Element | null | undefined} node
 * @param {boolean} visible
 */
export function setChromeVisible(node, visible) {
    if (! node) {
        return;
    }

    node.classList.remove('hidden');

    if (visible) {
        node.removeAttribute('data-voodbuilder-chrome-hidden');
    } else {
        node.setAttribute('data-voodbuilder-chrome-hidden', '');
    }
}
