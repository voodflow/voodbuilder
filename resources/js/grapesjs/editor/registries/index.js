/**
 * Editor contribution registries (commands, panels, data sources, conditions UI).
 *
 * @deprecated-bridge remove-by 0.2.0 — thin façade while legacy init.js still wires features.
 */

export {
    addEditorCommand,
    applyEditorCommands,
    clearEditorCommands,
    listEditorCommands,
    registerEditorCommand,
} from './commands.js';

export {
    clearEditorPanels,
    listEditorPanels,
    registerEditorPanel,
    resolveEditorPanels,
} from './panels.js';

export {
    clearEditorDataSources,
    listEditorDataSources,
    registerEditorDataSource,
    resolveEditorDataSources,
} from './data-sources.js';

export {
    clearEditorConditionTypes,
    listEditorConditionTypes,
    registerEditorConditionType,
    resolveEditorConditionTypes,
} from './conditions.js';
