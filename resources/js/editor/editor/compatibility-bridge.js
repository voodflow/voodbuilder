/**
 * Legacy compatibility bridge for editor registries.
 *
 * Existing modules may keep calling Editor APIs directly; this bridge records
 * contributions for introspection and future entitlement filtering.
 *
 * @deprecated remove-by 0.2.0
 */

export * from './registries/index.js';

/**
 * @param {object} editor
 * @param {object} [context]
 */
export function bootEditorRegistries(editor, context = {}) {
    if (! editor) {
        return;
    }

    // applyEditorCommands is imported lazily to avoid cycles with init.js.
    import('./registries/commands.js').then(({ applyEditorCommands }) => {
        applyEditorCommands(editor, context);
    });
}
