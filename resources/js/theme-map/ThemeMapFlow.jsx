import { memo, useCallback, useEffect, useMemo, useRef } from 'react';
import {
    ReactFlow,
    ReactFlowProvider,
    Background,
    Handle,
    Position,
    useEdgesState,
    useNodesState,
    getBezierPath,
    useReactFlow,
    applyNodeChanges,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';

const THEME_COLUMN_X = 48;
const AREA_COLUMN_X = 360;
const ROW_GAP = 96;
const START_Y = 16;
const NODE_WIDTH = 200;

const positionsStore = new Map();

function columnOffset(count, rowCount) {
    return Math.max(0, ((rowCount - count) * ROW_GAP) / 2);
}

function ThemeNode({ data }) {
    return (
        <div
            className="vpress-tm-node vpress-tm-node--theme"
            style={{ '--vp-tm-accent': data.preview }}
        >
            <div className="vpress-tm-node__strip">
                {(data.strip ?? []).map((color) => (
                    <span key={color} style={{ background: color }} />
                ))}
            </div>
            <div className="vpress-tm-node__body">
                <span className="vpress-tm-node__title">{data.label}</span>
            </div>
            <Handle type="source" position={Position.Right} className="vpress-tm-handle" />
        </div>
    );
}

function AreaNode({ data }) {
    return (
        <div
            className="vpress-tm-node vpress-tm-node--area"
            style={{
                '--vp-tm-accent': data.preview,
                '--vp-tm-surface': data.surface,
            }}
        >
            <Handle type="target" position={Position.Left} className="vpress-tm-handle" />
            <div className="vpress-tm-node__body">
                <span className="vpress-tm-node__title">{data.label}</span>
                {data.description ? (
                    <span className="vpress-tm-node__meta">{data.description}</span>
                ) : null}
                {data.inherited ? (
                    <span className="vpress-tm-node__badge">{data.inheritedLabel}</span>
                ) : null}
            </div>
        </div>
    );
}

const nodeTypes = {
    theme: ThemeNode,
    area: AreaNode,
};

function InheritedEdge({ id, sourceX, sourceY, targetX, targetY, sourcePosition, targetPosition, data, selected }) {
    const [edgePath] = getBezierPath({
        sourceX,
        sourceY,
        sourcePosition,
        targetX,
        targetY,
        targetPosition,
    });

    return (
        <g className="vpress-tm-edge">
            <path
                id={id}
                className={[
                    'vpress-tm-edge__path',
                    data?.inherited ? 'vpress-tm-edge__path--inherited' : '',
                    selected ? 'vpress-tm-edge__path--selected' : '',
                ].filter(Boolean).join(' ')}
                d={edgePath}
                fill="none"
            />
        </g>
    );
}

const edgeTypes = {
    inherited: InheritedEdge,
};

function FitViewOnce() {
    const { fitView } = useReactFlow();
    const fitted = useRef(false);

    useEffect(() => {
        if (fitted.current) {
            return;
        }

        const timer = window.setTimeout(() => {
            fitView({ padding: 0.14, duration: 0 });
            fitted.current = true;
        }, 80);

        return () => window.clearTimeout(timer);
    }, [fitView]);

    return null;
}

function buildNodes(payload, savedPositions) {
    const themeById = Object.fromEntries((payload.themes ?? []).map((theme) => [theme.id, theme]));
    const areaThemeMap = Object.fromEntries((payload.edges ?? []).map((edge) => [edge.area_id, edge.theme_id]));
    const themeCount = (payload.themes ?? []).length;
    const areaCount = (payload.areas ?? []).length;
    const rowCount = Math.max(themeCount, areaCount, 1);
    const themeOffset = columnOffset(themeCount, rowCount);
    const areaOffset = columnOffset(areaCount, rowCount);

    const themeNodes = (payload.themes ?? []).map((theme, index) => {
        const id = `theme:${theme.id}`;
        const fallback = { x: THEME_COLUMN_X, y: START_Y + themeOffset + index * ROW_GAP };
        const position = savedPositions.get(id) ?? fallback;

        return {
            id,
            type: 'theme',
            position,
            data: {
                ...theme,
            },
            draggable: true,
            selectable: false,
            connectable: true,
        };
    });

    const areaNodes = (payload.areas ?? []).map((area, index) => {
        const themeId = areaThemeMap[area.id] ?? area.theme_id;
        const theme = themeById[themeId] ?? {};
        const id = `area:${area.id}`;
        const fallback = { x: AREA_COLUMN_X, y: START_Y + areaOffset + index * ROW_GAP };
        const position = savedPositions.get(id) ?? fallback;

        return {
            id,
            type: 'area',
            position,
            data: {
                id: area.id,
                label: area.label,
                description: area.description,
                preview: theme.preview ?? '#64748b',
                surface: theme.surface ?? 'rgb(248 250 252)',
                inherited: area.source !== 'site' && area.source !== 'override',
                inheritedLabel: payload.i18n?.inherited ?? 'Inherited',
            },
            draggable: true,
            selectable: false,
            connectable: true,
        };
    });

    return [...themeNodes, ...areaNodes];
}

function buildEdges(payload) {
    return (payload.edges ?? []).map((edge) => ({
        id: `edge:${edge.area_id}`,
        source: `theme:${edge.theme_id}`,
        target: `area:${edge.area_id}`,
        type: 'inherited',
        animated: false,
        selectable: true,
        focusable: true,
        deletable: true,
        data: { inherited: edge.inherited, areaId: edge.area_id },
    }));
}

function assignmentsFromEdges(edges) {
    const subTheme =
        edges.find((edge) => edge.target === 'area:site_pages')?.source?.replace('theme:', '') ?? 'site';

    const channelOverrides = {};

    for (const edge of edges) {
        const areaId = edge.target.replace('area:', '');
        const themeId = edge.source.replace('theme:', '');

        if (areaId === 'site_pages') {
            continue;
        }

        channelOverrides[areaId] = themeId;
    }

    return { subTheme, channelOverrides };
}

function resetThemeForArea(areaId, payload) {
    if (areaId === 'site_pages') {
        return 'site';
    }

    const area = payload.areas?.find((item) => item.id === areaId);

    return area?.inherited_theme_id ?? payload.sub_theme ?? 'site';
}

function inheritedThemeForArea(areaId, payload) {
    return resetThemeForArea(areaId, payload);
}

function edgeInherited(areaId, themeId, payload, channelOverrides) {
    if (areaId === 'site_pages') {
        return false;
    }

    const inheritedId = inheritedThemeForArea(areaId, payload);

    return themeId === inheritedId && !channelOverrides?.[areaId];
}

function ThemeMapCanvas({ payload, onAssignmentsChange }) {
    const payloadRef = useRef(payload);
    const channelOverridesRef = useRef(payload.channel_overrides ?? {});
    const savedPositions = useRef(positionsStore);

    payloadRef.current = payload;
    channelOverridesRef.current = payload.channel_overrides ?? {};

    const initialNodes = useMemo(
        () => buildNodes(payload, savedPositions.current),
        // eslint-disable-next-line react-hooks/exhaustive-deps
        [],
    );
    const initialEdges = useMemo(() => buildEdges(payload), []);

    const [nodes, setNodes] = useNodesState(initialNodes);
    const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges);

    const syncAssignments = useCallback(
        (nextEdges) => {
            const { subTheme, channelOverrides } = assignmentsFromEdges(nextEdges);
            onAssignmentsChange?.(subTheme, channelOverrides);
        },
        [onAssignmentsChange],
    );

    const updateAreaVisuals = useCallback((nextEdges) => {
        const themeById = Object.fromEntries(
            (payloadRef.current.themes ?? []).map((theme) => [theme.id, theme]),
        );

        setNodes((current) =>
            current.map((node) => {
                if (!node.id.startsWith('area:')) {
                    return node;
                }

                const areaId = node.id.replace('area:', '');
                const edge = nextEdges.find((item) => item.target === node.id);
                const themeId = edge?.source?.replace('theme:', '') ?? '';
                const theme = themeById[themeId] ?? {};
                const inherited = edgeInherited(
                    areaId,
                    themeId,
                    payloadRef.current,
                    channelOverridesRef.current,
                );

                return {
                    ...node,
                    data: {
                        ...node.data,
                        preview: theme.preview ?? '#64748b',
                        surface: theme.surface ?? 'rgb(248 250 252)',
                        inherited,
                    },
                };
            }),
        );

        setEdges(
            nextEdges.map((edge) => {
                const areaId = edge.target.replace('area:', '');
                const themeId = edge.source.replace('theme:', '');

                return {
                    ...edge,
                    data: {
                        ...edge.data,
                        areaId,
                        inherited: edgeInherited(
                            areaId,
                            themeId,
                            payloadRef.current,
                            channelOverridesRef.current,
                        ),
                    },
                };
            }),
        );
    }, [setEdges, setNodes]);

    const onNodesChange = useCallback(
        (changes) => {
            setNodes((current) => {
                const next = applyNodeChanges(changes, current);

                for (const change of changes) {
                    if (change.type === 'position' && change.position && change.id) {
                        savedPositions.current.set(change.id, change.position);
                    }
                }

                return next;
            });
        },
        [setNodes],
    );

    const onConnect = useCallback(
        (connection) => {
            const areaNodeId = connection.target;
            const themeNodeId = connection.source;

            if (!areaNodeId?.startsWith('area:') || !themeNodeId?.startsWith('theme:')) {
                return;
            }

            const areaId = areaNodeId.replace('area:', '');
            const themeId = themeNodeId.replace('theme:', '');

            const nextEdges = [
                ...edges.filter((edge) => edge.target !== areaNodeId),
                {
                    id: `edge:${areaId}`,
                    source: themeNodeId,
                    target: areaNodeId,
                    type: 'inherited',
                    selectable: true,
                    focusable: true,
                    deletable: true,
                    data: {
                        areaId,
                        inherited: edgeInherited(areaId, themeId, payloadRef.current, {
                            ...channelOverridesRef.current,
                            ...(areaId !== 'site_pages' ? { [areaId]: themeId } : {}),
                        }),
                    },
                },
            ];

            if (areaId !== 'site_pages') {
                channelOverridesRef.current = {
                    ...channelOverridesRef.current,
                    [areaId]: themeId,
                };
            } else {
                payloadRef.current = { ...payloadRef.current, sub_theme: themeId };
            }

            updateAreaVisuals(nextEdges);
            syncAssignments(nextEdges);
        },
        [edges, syncAssignments, updateAreaVisuals],
    );

    const onEdgesDelete = useCallback(
        (deleted) => {
            let nextEdges = edges.filter((edge) => !deleted.some((item) => item.id === edge.id));

            for (const edge of deleted) {
                const areaId = edge.target.replace('area:', '');
                const fallbackTheme = resetThemeForArea(areaId, payloadRef.current);

                if (areaId === 'site_pages') {
                    payloadRef.current = { ...payloadRef.current, sub_theme: fallbackTheme };
                } else {
                    delete channelOverridesRef.current[areaId];
                }

                nextEdges.push({
                    id: `edge:${areaId}`,
                    source: `theme:${fallbackTheme}`,
                    target: `area:${areaId}`,
                    type: 'inherited',
                    selectable: true,
                    focusable: true,
                    deletable: true,
                    data: { areaId, inherited: true },
                });
            }

            updateAreaVisuals(nextEdges);
            syncAssignments(nextEdges);
        },
        [edges, syncAssignments, updateAreaVisuals],
    );

    const rowCount = Math.max((payload.themes ?? []).length, (payload.areas ?? []).length, 1);
    const canvasHeight = Math.max(280, rowCount * ROW_GAP + 56);

    return (
        <div className="vpress-tm">
            <p className="vpress-tm__hint">{payload.i18n?.hint}</p>
            <div className="vpress-tm__canvas" style={{ height: canvasHeight }}>
                <ReactFlow
                    nodes={nodes}
                    edges={edges}
                    onNodesChange={onNodesChange}
                    onEdgesChange={onEdgesChange}
                    onConnect={onConnect}
                    onEdgesDelete={onEdgesDelete}
                    nodeTypes={nodeTypes}
                    edgeTypes={edgeTypes}
                    nodesDraggable
                    nodesConnectable
                    elementsSelectable
                    edgesFocusable
                    deleteKeyCode={['Backspace', 'Delete']}
                    panOnDrag
                    panOnScroll={false}
                    zoomOnScroll
                    zoomOnPinch
                    minZoom={0.5}
                    maxZoom={1.6}
                    proOptions={{ hideAttribution: true }}
                    defaultEdgeOptions={{
                        type: 'inherited',
                        selectable: true,
                        focusable: true,
                        deletable: true,
                    }}
                >
                    <FitViewOnce />
                    <Background gap={18} size={1} color="rgba(148, 163, 184, 0.2)" />
                </ReactFlow>
            </div>
            <div className="vpress-tm__legend">
                <span className="vpress-tm__legend-item">
                    <span className="vpress-tm__legend-line" />
                    {payload.i18n?.legend_explicit}
                </span>
                <span className="vpress-tm__legend-item">
                    <span className="vpress-tm__legend-line vpress-tm__legend-line--inherited" />
                    {payload.i18n?.legend_inherited}
                </span>
                <span className="vpress-tm__legend-item vpress-tm__legend-item--hint">
                    {payload.i18n?.legend_controls}
                </span>
            </div>
        </div>
    );
}

function ThemeMapFlow({ payload, onAssignmentsChange }) {
    if (!payload?.themes?.length) {
        return null;
    }

    return (
        <ReactFlowProvider>
            <ThemeMapCanvas
                payload={payload}
                onAssignmentsChange={onAssignmentsChange}
            />
        </ReactFlowProvider>
    );
}

export default memo(ThemeMapFlow);
