import { memo, useCallback, useEffect, useRef } from 'react';
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
const START_Y = 16;
const THEME_ROW_GAP = 76;
const AREA_ROW_GAP = 24;
const THEME_NODE_HEIGHT = 54;
const CANVAS_PADDING_BOTTOM = 40;
const AREA_NODE_WIDTH = 200;
const THEME_MAP_MIN_CANVAS_HEIGHT = 520;

function estimateAreaNodeHeight(area) {
    const padding = 24;
    const titleHeight = 18;
    const badgeReserve = 22;
    let height = padding + titleHeight + badgeReserve;

    if (area.description) {
        const charsPerLine = Math.floor(AREA_NODE_WIDTH / 6.2);
        const lines = Math.max(1, Math.ceil(area.description.length / charsPerLine));
        height += lines * 12 + 2;
    }

    if (area.routes) {
        height += 14;
    }

    if (area.required_capability_label) {
        height += 16;
    }

    if (area.chrome_layout?.name || area.chrome_layout_label) {
        height += 16;
    }

    return Math.max(110, height + 10);
}

function buildAreaLayouts(areas) {
    let currentY = START_Y;
    const layouts = [];

    for (const area of areas) {
        const height = estimateAreaNodeHeight(area);

        layouts.push({
            area,
            y: currentY,
            height,
        });

        currentY += height + AREA_ROW_GAP;
    }

    const totalHeight = layouts.length > 0
        ? currentY - AREA_ROW_GAP + CANVAS_PADDING_BOTTOM
        : THEME_MAP_MIN_CANVAS_HEIGHT;

    return { layouts, totalHeight };
}

function buildThemeLayouts(themes, areaTotalHeight) {
    const themeCount = themes.length;
    const themeBlockHeight = themeCount > 0
        ? (themeCount - 1) * THEME_ROW_GAP + THEME_NODE_HEIGHT
        : 0;
    const areaContentHeight = Math.max(themeBlockHeight, areaTotalHeight - CANVAS_PADDING_BOTTOM - START_Y);
    const offsetY = START_Y + Math.max(0, (areaContentHeight - themeBlockHeight) / 2);

    return themes.map((theme, index) => ({
        theme,
        y: offsetY + index * THEME_ROW_GAP,
    }));
}

function canvasHeightFor(payload) {
    const { totalHeight } = buildAreaLayouts(payload.areas ?? []);

    return Math.max(THEME_MAP_MIN_CANVAS_HEIGHT, totalHeight);
}

function ThemeNode({ data }) {
    return (
        <div
            className="voodbuilder-tm-node voodbuilder-tm-node--theme"
            style={{ '--vp-tm-accent': data.preview }}
        >
            <div className="voodbuilder-tm-node__strip">
                {(data.strip ?? []).map((color) => (
                    <span key={color} style={{ background: color }} />
                ))}
            </div>
            <div className="voodbuilder-tm-node__body">
                <span className="voodbuilder-tm-node__title">{data.label}</span>
                {(data.capability_labels ?? []).length > 0 ? (
                    <span className="voodbuilder-tm-node__tags">
                        {(data.capability_labels ?? []).map((label) => (
                            <span key={label} className="voodbuilder-tm-node__tag">{label}</span>
                        ))}
                    </span>
                ) : null}
            </div>
            <Handle type="source" position={Position.Right} className="voodbuilder-tm-handle" />
        </div>
    );
}

function AreaNode({ data }) {
    return (
        <div
            className="voodbuilder-tm-node voodbuilder-tm-node--area"
            style={{
                '--vp-tm-accent': data.preview,
                '--vp-tm-surface': data.surface,
            }}
        >
            <Handle type="target" position={Position.Left} className="voodbuilder-tm-handle" />
            <div className="voodbuilder-tm-node__body">
                <span className="voodbuilder-tm-node__title">{data.label}</span>
                {data.description ? (
                    <span className="voodbuilder-tm-node__meta">{data.description}</span>
                ) : null}
                {data.routes ? (
                    <span className="voodbuilder-tm-node__meta voodbuilder-tm-node__meta--routes">{data.routes}</span>
                ) : null}
                {data.required_capability_label ? (
                    <span className="voodbuilder-tm-node__tag voodbuilder-tm-node__tag--required">
                        {data.required_capability_prefix}
                        {data.required_capability_label}
                    </span>
                ) : null}
                {data.chrome_layout_label ? (
                    <span className="voodbuilder-tm-node__meta voodbuilder-tm-node__meta--layout">
                        {data.chrome_layout_prefix}
                        <strong>{data.chrome_layout_label}</strong>
                    </span>
                ) : null}
                {data.inherited ? (
                    <span className="voodbuilder-tm-node__badge">{data.inheritedLabel}</span>
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
        <g className="voodbuilder-tm-edge">
            <path
                id={id}
                className={[
                    'voodbuilder-tm-edge__path',
                    data?.inherited ? 'voodbuilder-tm-edge__path--inherited' : '',
                    selected ? 'voodbuilder-tm-edge__path--selected' : '',
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

function buildNodes(payload) {
    const themeById = Object.fromEntries((payload.themes ?? []).map((theme) => [theme.id, theme]));
    const areaThemeMap = Object.fromEntries((payload.edges ?? []).map((edge) => [edge.area_id, edge.theme_id]));
    const { layouts: areaLayouts } = buildAreaLayouts(payload.areas ?? []);
    const themeLayouts = buildThemeLayouts(payload.themes ?? [], canvasHeightFor(payload));

    const themeNodes = themeLayouts.map(({ theme, y }) => ({
        id: `theme:${theme.id}`,
        type: 'theme',
        position: { x: THEME_COLUMN_X, y },
        data: {
            ...theme,
        },
        draggable: true,
        selectable: false,
        connectable: true,
    }));

    const areaNodes = areaLayouts.map(({ area, y }) => {
        const themeId = areaThemeMap[area.id] ?? area.theme_id;
        const theme = themeById[themeId] ?? {};
        const edge = (payload.edges ?? []).find((item) => item.area_id === area.id);

        return {
            id: `area:${area.id}`,
            type: 'area',
            position: { x: AREA_COLUMN_X, y },
            data: {
                id: area.id,
                label: area.label,
                description: area.description,
                routes: area.routes,
                required_capability_label: area.required_capability_label,
                required_capability_prefix: payload.i18n?.required_capability ?? 'Requires: ',
                chrome_layout_label: area.chrome_layout?.name ?? null,
                chrome_layout_prefix: payload.i18n?.chrome_layout ?? 'Chrome layout: ',
                preview: theme.preview ?? '#64748b',
                surface: theme.surface ?? 'rgb(248 250 252)',
                inherited: edge?.inherited ?? edgeInherited(area.id, themeId, payload, payload.channel_overrides ?? {}),
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
        return payload.default_sub_theme ?? 'site';
    }

    const area = payload.areas?.find((item) => item.id === areaId);

    return area?.inherited_theme_id ?? payload.sub_theme ?? payload.default_sub_theme ?? 'site';
}

function inheritedThemeForArea(areaId, payload) {
    return resetThemeForArea(areaId, payload);
}

function edgeInherited(areaId, themeId, payload, channelOverrides) {
    const defaultSiteTheme = payload.default_sub_theme ?? 'site';

    if (areaId === 'site_pages') {
        const activeSiteTheme = payload.sub_theme ?? defaultSiteTheme;

        return themeId === defaultSiteTheme && activeSiteTheme === defaultSiteTheme;
    }

    const inheritedId = inheritedThemeForArea(areaId, payload);

    return themeId === inheritedId && !channelOverrides?.[areaId];
}

function withInheritedEdgeData(nextEdges, payload, channelOverrides) {
    const themeById = Object.fromEntries((payload.themes ?? []).map((theme) => [theme.id, theme]));

    return nextEdges.map((edge) => {
        const areaId = edge.target.replace('area:', '');
        const themeId = edge.source.replace('theme:', '');

        return {
            ...edge,
            type: 'inherited',
            selectable: true,
            focusable: true,
            deletable: true,
            data: {
                ...edge.data,
                areaId,
                themePreview: themeById[themeId]?.preview,
                inherited: edgeInherited(areaId, themeId, payload, channelOverrides),
            },
        };
    });
}

function ThemeMapCanvas({ payload, onAssignmentsChange }) {
    const payloadRef = useRef(payload);
    const channelOverridesRef = useRef(payload.channel_overrides ?? {});

    payloadRef.current = payload;
    channelOverridesRef.current = payload.channel_overrides ?? {};

    const [nodes, setNodes] = useNodesState(() => buildNodes(payload));
    const [edges, setEdges, onEdgesChange] = useEdgesState(() => buildEdges(payload));

    useEffect(() => {
        payloadRef.current = payload;
        channelOverridesRef.current = payload.channel_overrides ?? {};
        setNodes(buildNodes(payload));
        setEdges(buildEdges(payload));
    }, [payload, setEdges, setNodes]);

    const syncAssignments = useCallback(
        (nextEdges) => {
            const { subTheme, channelOverrides } = assignmentsFromEdges(nextEdges);
            onAssignmentsChange?.(subTheme, channelOverrides);
        },
        [onAssignmentsChange],
    );

    const applyEdgeState = useCallback(
        (nextEdges) => {
            const themeById = Object.fromEntries(
                (payloadRef.current.themes ?? []).map((theme) => [theme.id, theme]),
            );
            const finalizedEdges = withInheritedEdgeData(
                nextEdges,
                payloadRef.current,
                channelOverridesRef.current,
            );

            setNodes((current) =>
                current.map((node) => {
                    if (!node.id.startsWith('area:')) {
                        return node;
                    }

                    const areaId = node.id.replace('area:', '');
                    const edge = finalizedEdges.find((item) => item.target === node.id);
                    const themeId = edge?.source?.replace('theme:', '') ?? '';
                    const theme = themeById[themeId] ?? {};

                    return {
                        ...node,
                        data: {
                            ...node.data,
                            preview: theme.preview ?? '#64748b',
                            surface: theme.surface ?? 'rgb(248 250 252)',
                            inherited: edge?.data?.inherited ?? false,
                        },
                    };
                }),
            );

            setEdges(finalizedEdges);
            syncAssignments(finalizedEdges);
        },
        [setEdges, setNodes, syncAssignments],
    );

    const updateAreaVisuals = useCallback(
        (nextEdges) => {
            applyEdgeState(nextEdges);
        },
        [applyEdgeState],
    );

    const onNodesChange = useCallback(
        (changes) => {
            setNodes((current) => applyNodeChanges(changes, current));
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

            const area = payloadRef.current.areas?.find((item) => item.id === areaId);
            const allowed = area?.allowed_theme_ids ?? [];
            const theme = payloadRef.current.themes?.find((item) => item.id === themeId);

            if (allowed.length > 0 && !allowed.includes(themeId)) {
                const detail = payloadRef.current.i18n?.invalid_binding_detail
                    ?? 'This theme does not support the capability required by this area.';
                const required = area?.required_capability_label ?? '';
                const themeCaps = (theme?.capability_labels ?? []).join(', ');

                window.alert(
                    [
                        payloadRef.current.i18n?.invalid_binding
                            ?? 'That theme cannot be applied to this area.',
                        required ? `${payloadRef.current.i18n?.required_capability ?? 'Requires:'} ${required}` : '',
                        themeCaps ? `${payloadRef.current.i18n?.theme_capabilities ?? 'Theme supports:'} ${themeCaps}` : '',
                        detail,
                    ].filter(Boolean).join('\n\n'),
                );

                return;
            }

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
            for (const edge of deleted) {
                const areaId = edge.target.replace('area:', '');
                const fallbackTheme = resetThemeForArea(areaId, payloadRef.current);

                if (areaId === 'site_pages') {
                    payloadRef.current = {
                        ...payloadRef.current,
                        sub_theme: fallbackTheme,
                    };
                } else {
                    delete channelOverridesRef.current[areaId];
                }
            }

            setEdges((currentEdges) => {
                let nextEdges = currentEdges.filter(
                    (edge) => !deleted.some((item) => item.id === edge.id),
                );

                for (const edge of deleted) {
                    const areaId = edge.target.replace('area:', '');
                    const fallbackTheme = resetThemeForArea(areaId, payloadRef.current);

                    nextEdges.push({
                        id: `edge:${areaId}`,
                        source: `theme:${fallbackTheme}`,
                        target: edge.target,
                        type: 'inherited',
                        selectable: true,
                        focusable: true,
                        deletable: true,
                        data: { areaId },
                    });
                }

                const finalizedEdges = withInheritedEdgeData(
                    nextEdges,
                    payloadRef.current,
                    channelOverridesRef.current,
                );
                const themeById = Object.fromEntries(
                    (payloadRef.current.themes ?? []).map((theme) => [theme.id, theme]),
                );

                setNodes((current) =>
                    current.map((node) => {
                        if (!node.id.startsWith('area:')) {
                            return node;
                        }

                        const areaId = node.id.replace('area:', '');
                        const edge = finalizedEdges.find((item) => item.target === node.id);
                        const themeId = edge?.source?.replace('theme:', '') ?? '';
                        const theme = themeById[themeId] ?? {};

                        return {
                            ...node,
                            data: {
                                ...node.data,
                                preview: theme.preview ?? '#64748b',
                                surface: theme.surface ?? 'rgb(248 250 252)',
                                inherited: edge?.data?.inherited ?? false,
                            },
                        };
                    }),
                );

                syncAssignments(finalizedEdges);

                return finalizedEdges;
            });
        },
        [setEdges, setNodes, syncAssignments],
    );

    const isValidConnection = useCallback((connection) => {
        const areaNodeId = connection.target;
        const themeNodeId = connection.source;

        if (!areaNodeId?.startsWith('area:') || !themeNodeId?.startsWith('theme:')) {
            return false;
        }

        const areaId = areaNodeId.replace('area:', '');
        const themeId = themeNodeId.replace('theme:', '');
        const area = payloadRef.current.areas?.find((item) => item.id === areaId);
        const allowed = area?.allowed_theme_ids ?? [];

        return allowed.length === 0 || allowed.includes(themeId);
    }, []);

    const canvasHeight = canvasHeightFor(payload);

    return (
        <div className="voodbuilder-tm">
            <p className="voodbuilder-tm__hint">{payload.i18n?.hint}</p>
            <div className="voodbuilder-tm__canvas" style={{ height: canvasHeight }}>
                <ReactFlow
                    nodes={nodes}
                    edges={edges}
                    onNodesChange={onNodesChange}
                    onEdgesChange={onEdgesChange}
                    onConnect={onConnect}
                    onEdgesDelete={onEdgesDelete}
                    isValidConnection={isValidConnection}
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
            <div className="voodbuilder-tm__legend">
                <span className="voodbuilder-tm__legend-item">
                    <span className="voodbuilder-tm__legend-line" />
                    {payload.i18n?.legend_explicit}
                </span>
                <span className="voodbuilder-tm__legend-item">
                    <span className="voodbuilder-tm__legend-line voodbuilder-tm__legend-line--inherited" />
                    {payload.i18n?.legend_inherited}
                </span>
                <span className="voodbuilder-tm__legend-item voodbuilder-tm__legend-item--hint">
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
