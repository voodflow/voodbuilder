/**
 * Grapes `changeProp` traits keep their value on the model, but saved HTML only
 * carries attributes. On reload the model starts from the type defaults, so any
 * init() sync that reads `get()` first would overwrite the saved values.
 *
 * Call at the top of init(): saved attributes win over model defaults.
 */

/**
 * @param {object} component Grapes component model
 * @param {string[]} names Attribute names that mirror model props of the same name
 * @returns {boolean} true when at least one prop was updated
 */
export function hydratePropsFromAttributes(component, names) {
    const attrs = component?.getAttributes?.() ?? {};
    const updates = {};

    for (const name of names) {
        if (! Object.prototype.hasOwnProperty.call(attrs, name) || attrs[name] == null) {
            continue;
        }

        const current = component.get?.(name);
        let next = attrs[name];

        if (typeof current === 'number') {
            const numeric = Number(next);

            if (! Number.isFinite(numeric)) {
                continue;
            }

            next = numeric;
        } else {
            next = String(next);
        }

        if (current !== next) {
            updates[name] = next;
        }
    }

    if (Object.keys(updates).length === 0) {
        return false;
    }

    component.set(updates, { silent: true });

    return true;
}
