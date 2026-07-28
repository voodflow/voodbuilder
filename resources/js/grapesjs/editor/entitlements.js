/**
 * Client-side entitlement helpers. Server still enforces capabilities on APIs.
 */

/**
 * @param {Record<string, boolean|string>|null|undefined} entitlements
 * @param {string} key
 * @returns {boolean}
 */
export function canEntitlement(entitlements, key) {
    if (! entitlements || typeof entitlements !== 'object') {
        return false;
    }

    return entitlements[key] === true;
}

/**
 * @param {Record<string, boolean|string>|null|undefined} entitlements
 * @param {string[]} keys
 * @returns {boolean}
 */
export function canAnyEntitlement(entitlements, keys) {
    return keys.some((key) => canEntitlement(entitlements, key));
}

/**
 * Filter UI action ids by entitlement map.
 *
 * @param {Array<{ id: string, entitlement?: string }>} actions
 * @param {Record<string, boolean|string>|null|undefined} entitlements
 * @returns {Array<{ id: string, entitlement?: string }>}
 */
export function filterActionsByEntitlement(actions, entitlements) {
    return actions.filter((action) => {
        if (! action.entitlement) {
            return true;
        }

        return canEntitlement(entitlements, action.entitlement);
    });
}
