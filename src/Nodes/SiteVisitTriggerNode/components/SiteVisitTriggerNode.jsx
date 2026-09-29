import React, { useEffect, useMemo, useState } from 'react';
import { Position, useReactFlow } from '@xyflow/react';
import {
    BaseNodeContainer,
    NodeHeader,
    NodeConfigReadOnlyNotice,
    NodeNonExecutableNotice,
    CollapsedView,
    NodeConfigFields,
    StandardHandle,
    NODE_CONFIG_DEBOUNCE_MS,
    useStandardNodeBehavior,
    useNodeAccessUi,
} from '../../../../../voodflow/resources/js/components/nodes';

const MATCH_BADGES = {
    any: 'ANY',
    page: 'PAGE',
    menu_item: 'MENU',
    path: 'PATH',
};

export default function SiteVisitTriggerNode({ id, data, selected }) {
    const defaultLabel = 'Site Visit';
    const nodeColor = data.color || 'sky';
    const { configReadOnly, canShowDeleteButton } = useNodeAccessUi(data);
    const { ConfirmModal, DynamicFormRenderer } = window.VoodflowCommon || {};
    const { setNodes, getEdges } = useReactFlow();

    const {
        isExpanded,
        toggleExpansion,
        isOutputConnected,
        shouldShowLogo,
        hasConfiguration,
    } = useStandardNodeBehavior(id, { ...data, defaultLabel }, getEdges, setNodes, true);

    const schema = useMemo(
        () => (Array.isArray(data.definition?.ui_schema) ? data.definition.ui_schema : []),
        [data.definition],
    );

    const schemaFieldNames = useMemo(
        () => schema.map((field) => field.name).filter(Boolean),
        [schema],
    );

    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [label, setLabel] = useState(data.label || defaultLabel);
    const [description, setDescription] = useState(data.description || '');
    const [fieldValues, setFieldValues] = useState(() => {
        const initial = {};
        schemaFieldNames.forEach((name) => {
            initial[name] = data[name] ?? '';
        });
        return initial;
    });

    useEffect(() => {
        setFieldValues((prev) => {
            const next = { ...prev };
            schemaFieldNames.forEach((name) => {
                if (data[name] !== undefined) {
                    next[name] = data[name];
                } else if (next[name] === undefined) {
                    next[name] = '';
                }
            });
            return next;
        });
    }, [schemaFieldNames.join('|'), ...schemaFieldNames.map((name) => data[name])]);

    useEffect(() => {
        if (configReadOnly) {
            return;
        }

        const payload = {
            label,
            description,
            ...fieldValues,
        };

        const hasChanged =
            label !== (data.label || defaultLabel)
            || description !== (data.description || '')
            || schemaFieldNames.some((name) => String(fieldValues[name] ?? '') !== String(data[name] ?? ''));

        if (! hasChanged) {
            return;
        }

        const timeoutId = setTimeout(() => {
            window.Livewire?.find(data.livewireId)?.call('updateNodeConfig', {
                nodeId: id,
                ...payload,
            });

            setNodes((nds) => nds.map((node) => {
                if (node.id !== id) {
                    return node;
                }

                return {
                    ...node,
                    data: {
                        ...node.data,
                        ...payload,
                    },
                };
            }));
        }, NODE_CONFIG_DEBOUNCE_MS);

        return () => clearTimeout(timeoutId);
    }, [
        configReadOnly,
        label,
        description,
        fieldValues,
        schemaFieldNames.join('|'),
        data.label,
        data.description,
        data.livewireId,
        id,
        setNodes,
        defaultLabel,
    ]);

    const handleDelete = () => {
        if (! canShowDeleteButton) {
            return;
        }
        window.Livewire?.find(data.livewireId)?.call('deleteNode', id);
    };

    const matchType = String(fieldValues.match_type || data.match_type || 'page');
    const badge = MATCH_BADGES[matchType] || matchType.toUpperCase().slice(0, 8);

    return (
        <>
            {canShowDeleteButton && ConfirmModal && (
                <ConfirmModal
                    isOpen={showDeleteModal}
                    title="Delete Node"
                    message={`Remove "${label}"?`}
                    onConfirm={handleDelete}
                    onCancel={() => setShowDeleteModal(false)}
                />
            )}

            <BaseNodeContainer
                color={nodeColor}
                selected={selected}
                isExpanded={isExpanded}
                minWidth="340px"
                maxWidth="460px"
            >
                <NodeHeader
                    nodeData={data}
                    color={nodeColor}
                    title={label}
                    subtitle="SITE VISIT"
                    badge={badge}
                    isExpanded={isExpanded}
                    canExpand
                    onToggleExpand={toggleExpansion}
                    onDelete={canShowDeleteButton ? () => setShowDeleteModal(true) : undefined}
                />

                {isExpanded ? (
                    <div className="nodrag">
                        <NodeConfigReadOnlyNotice data={data} />
                        <NodeNonExecutableNotice data={data} />
                        <NodeConfigFields
                            label={label}
                            description={description}
                            onLabelChange={(e) => setLabel(e.target.value)}
                            onDescriptionChange={(e) => setDescription(e.target.value)}
                            color={nodeColor}
                            readOnly={configReadOnly}
                        />

                        <div className="p-4 space-y-3">
                            <div className="text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                When a visitor lands
                            </div>

                            {schema.length === 0 ? (
                                <div className="text-xs text-slate-400 italic dark:text-slate-500">
                                    No configuration defined.
                                </div>
                            ) : DynamicFormRenderer ? (
                                <div className="rounded-lg ring-1 ring-black/5 bg-slate-50 dark:bg-slate-900/50 p-3">
                                    <DynamicFormRenderer
                                        schema={schema}
                                        values={fieldValues}
                                        onChange={(fieldName, value) => {
                                            if (configReadOnly) {
                                                return;
                                            }
                                            setFieldValues((prev) => ({
                                                ...prev,
                                                [fieldName]: value,
                                            }));
                                        }}
                                        availableFields={data.filterFieldsMap || data.availableFields || {}}
                                    />
                                </div>
                            ) : (
                                <div className="text-xs text-rose-500">
                                    DynamicFormRenderer unavailable — rebuild Voodflow assets.
                                </div>
                            )}

                            <p className="text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">
                                Payload includes <code className="text-[10px]">visitor_key</code> for Trigger Popup
                                (<code className="text-[10px]">{'{{visitor_key}}'}</code>).
                            </p>
                        </div>
                    </div>
                ) : (
                    <CollapsedView
                        label={label}
                        description={description || 'Visitor lands on matched page / menu / path'}
                        hasConfiguration={hasConfiguration}
                        shouldShowLogo={shouldShowLogo}
                        color={nodeColor}
                    />
                )}

                <StandardHandle
                    type="source"
                    position={Position.Right}
                    id="output"
                    color={nodeColor}
                    connected={isOutputConnected}
                />
            </BaseNodeContainer>
        </>
    );
}
