var require=function(m){if(m==="react"||m==="react/jsx-runtime"||m==="react/jsx-dev-runtime")return window.React;if(m==="react-dom")return window.ReactDOM;if(m==="reactflow"||m==="@xyflow/react")return window.ReactFlow;throw new Error("Module "+m+" not found")};
var VoodflowNode_SiteVisitTriggerNode = (() => {
  var __create = Object.create;
  var __defProp = Object.defineProperty;
  var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
  var __getOwnPropNames = Object.getOwnPropertyNames;
  var __getProtoOf = Object.getPrototypeOf;
  var __hasOwnProp = Object.prototype.hasOwnProperty;
  var __require = /* @__PURE__ */ ((x) => typeof require !== "undefined" ? require : typeof Proxy !== "undefined" ? new Proxy(x, {
    get: (a, b) => (typeof require !== "undefined" ? require : a)[b]
  }) : x)(function(x) {
    if (typeof require !== "undefined") return require.apply(this, arguments);
    throw Error('Dynamic require of "' + x + '" is not supported');
  });
  var __export = (target, all) => {
    for (var name in all)
      __defProp(target, name, { get: all[name], enumerable: true });
  };
  var __copyProps = (to, from, except, desc) => {
    if (from && typeof from === "object" || typeof from === "function") {
      for (let key of __getOwnPropNames(from))
        if (!__hasOwnProp.call(to, key) && key !== except)
          __defProp(to, key, { get: () => from[key], enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable });
    }
    return to;
  };
  var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(
    // If the importer is in node compatibility mode or this is not an ESM
    // file that has been converted to a CommonJS file using a Babel-
    // compatible transform (i.e. "__esModule" has not been set), then set
    // "default" to the CommonJS "module.exports" for node compatibility.
    isNodeMode || !mod || !mod.__esModule ? __defProp(target, "default", { value: mod, enumerable: true }) : target,
    mod
  ));
  var __toCommonJS = (mod) => __copyProps(__defProp({}, "__esModule", { value: true }), mod);

  // ../voodbuilder/src/Nodes/SiteVisitTriggerNode/components/SiteVisitTriggerNode.jsx
  var SiteVisitTriggerNode_exports = {};
  __export(SiteVisitTriggerNode_exports, {
    default: () => SiteVisitTriggerNode
  });
  var import_react18 = __toESM(__require("react"), 1);
  var import_react19 = __require("@xyflow/react");

  // resources/js/components/nodes/NodeHeader.jsx
  var import_react2 = __toESM(__require("react"), 1);

  // resources/js/constants/uiStyles.js
  var NODE_DIVIDER = {
    /** Primary HR below NodeConfigFields (Node Title + Description). */
    AFTER_CONFIG: "border-b border-slate-100 dark:border-slate-800",
    /** Optional HR between sections inside the expanded body (e.g. collapsed summary). */
    SECTION: "border-t border-slate-100 dark:border-slate-800/50"
  };
  var TYPOGRAPHY = {
    // Labels for input fields, selects, etc.
    LABEL: {
      fontSize: "text-[10px]",
      fontWeight: "font-black",
      color: "text-slate-400",
      transform: "uppercase",
      tracking: "tracking-widest",
      padding: "pl-1",
      className: "text-[10px] font-black text-slate-400 uppercase tracking-widest pl-1"
    },
    // Placeholders
    PLACEHOLDER: {
      fontSize: "text-xs",
      fontStyle: "italic",
      className: "placeholder:italic placeholder:text-slate-400"
    },
    // Input text
    INPUT: {
      fontSize: "text-xs",
      fontWeight: "font-medium",
      className: "text-xs font-medium"
    },
    // Section headers (e.g., "AVAILABLE FIELDS", "OUTPUT FIELDS")
    SECTION_HEADER: {
      fontSize: "text-[10px]",
      fontWeight: "font-black",
      color: "text-slate-400",
      transform: "uppercase",
      tracking: "tracking-widest",
      className: "text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none"
    },
    // Helper text
    HELPER: {
      fontSize: "text-[9px]",
      fontStyle: "italic",
      color: "text-slate-400",
      className: "text-[9px] text-slate-400 italic pl-1"
    }
  };
  var INPUT_FOCUS_NEUTRAL = "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-400/35 dark:focus-visible:ring-slate-500/45 focus-visible:border-slate-400 dark:focus-visible:border-slate-500";
  var INPUT_FOCUS_BY_COLOR = {
    slate: INPUT_FOCUS_NEUTRAL,
    gray: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400/35 dark:focus-visible:ring-gray-500/45 focus-visible:border-gray-400 dark:focus-visible:border-gray-500",
    amber: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/30 dark:focus-visible:ring-amber-400/35 focus-visible:border-amber-500 dark:focus-visible:border-amber-400",
    blue: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/30 dark:focus-visible:ring-blue-400/35 focus-visible:border-blue-500 dark:focus-visible:border-blue-400",
    yellow: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-yellow-500/35 dark:focus-visible:ring-yellow-400/40 focus-visible:border-yellow-500 dark:focus-visible:border-yellow-400",
    emerald: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500/30 dark:focus-visible:ring-emerald-400/35 focus-visible:border-emerald-500 dark:focus-visible:border-emerald-400",
    purple: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500/30 dark:focus-visible:ring-purple-400/35 focus-visible:border-purple-500 dark:focus-visible:border-purple-400",
    violet: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-violet-500/30 dark:focus-visible:ring-violet-400/35 focus-visible:border-violet-500 dark:focus-visible:border-violet-400",
    cyan: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500/30 dark:focus-visible:ring-cyan-400/35 focus-visible:border-cyan-500 dark:focus-visible:border-cyan-400",
    orange: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-orange-500/30 dark:focus-visible:ring-orange-400/35 focus-visible:border-orange-500 dark:focus-visible:border-orange-400",
    rose: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500/30 dark:focus-visible:ring-rose-400/35 focus-visible:border-rose-500 dark:focus-visible:border-rose-400",
    indigo: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500/30 dark:focus-visible:ring-indigo-400/35 focus-visible:border-indigo-500 dark:focus-visible:border-indigo-400",
    teal: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500/30 dark:focus-visible:ring-teal-400/35 focus-visible:border-teal-500 dark:focus-visible:border-teal-400",
    pink: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-pink-500/30 dark:focus-visible:ring-pink-400/35 focus-visible:border-pink-500 dark:focus-visible:border-pink-400",
    green: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-green-500/30 dark:focus-visible:ring-green-400/35 focus-visible:border-green-500 dark:focus-visible:border-green-400",
    red: "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500/30 dark:focus-visible:ring-red-400/35 focus-visible:border-red-500 dark:focus-visible:border-red-400"
  };
  var inputFocusFor = (color) => INPUT_FOCUS_BY_COLOR[color] ?? INPUT_FOCUS_BY_COLOR.cyan;
  var INPUT_BASE = "w-full px-3 py-1.5 text-xs border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900/80 text-slate-900 dark:text-slate-100 transition-colors outline-none";
  var INPUT_STYLES = {
    BASE: `${INPUT_BASE} ${INPUT_FOCUS_NEUTRAL}`,
    withColor: (color) => `${INPUT_BASE} ${inputFocusFor(color)}`
  };
  var SELECT_BASE = "w-full px-3 py-1.5 text-xs border border-slate-200 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 transition-colors cursor-pointer outline-none";
  var SELECT_STYLES = {
    BASE: `${SELECT_BASE} ${INPUT_FOCUS_NEUTRAL}`,
    withColor: (color) => `${SELECT_BASE} ${inputFocusFor(color)}`
  };
  var NODE_HEADER_STYLES = {
    CONTAINER: (color) => `bg-gradient-to-r from-${color}-500 to-${color}-600 px-4 py-2.5 flex items-center justify-between rounded-t-xl group`,
    ICON_CONTAINER: "flex items-center justify-center w-8 h-8 rounded-lg bg-white/20 backdrop-blur-sm border border-white/10 shadow-inner",
    ICON: "w-5 h-5 text-white",
    TITLE_CONTAINER: "flex items-center gap-3",
    SUBTITLE: "text-[11px] font-black text-white/60 uppercase tracking-widest leading-none mb-0.5",
    TITLE: "text-sm font-bold text-white leading-none",
    ACTIONS_CONTAINER: "flex items-center gap-2",
    DIVIDER: "h-4 w-px bg-white/10 mx-1",
    EXPAND_BUTTON: "nodrag w-6 h-6 flex items-center justify-center rounded-lg hover:bg-white/10 text-white/70 hover:text-white transition-all",
    DELETE_BUTTON: "nodrag w-6 h-6 flex items-center justify-center rounded-lg hover:bg-rose-500/20 text-white/70 hover:text-rose-200 transition-all"
  };
  var COLLAPSED_NODE_STYLES = {
    CONTAINER: `p-4 bg-slate-50/50 dark:bg-slate-800/20 rounded-b-xl ${NODE_DIVIDER.SECTION} overflow-hidden`,
    DESCRIPTION: "text-slate-500 dark:text-slate-400 text-xs italic font-medium leading-relaxed",
    PLACEHOLDER: "text-slate-400 dark:text-slate-500 italic text-sm text-center",
    CONNECT_MESSAGE: "flex flex-col items-center justify-center py-4 gap-3",
    LOGO: "w-12 h-12"
  };
  var SPACING = {
    SECTION_GAP: "space-y-3",
    FIELD_GAP: "space-y-1.5",
    GRID_GAP: "gap-3"
  };

  // resources/js/components/NodeIcon.jsx
  var import_react = __toESM(__require("react"), 1);
  var NodeIcon = ({ node, className = "w-5 h-5" }) => {
    if (node.logoMissing) {
      return /* @__PURE__ */ import_react.default.createElement(HeroIcon, { name: "voodflow-logo", className });
    }
    const logoUrl = node.logoUrl || (node.display?.iconType === "image" ? node.display?.logo : null);
    if (logoUrl) {
      return /* @__PURE__ */ import_react.default.createElement(
        "img",
        {
          src: logoUrl,
          alt: node.name || "Node",
          className: `${className} object-contain`,
          onError: (e) => {
            console.warn(`Failed to load logo for ${node.name}:`, logoUrl);
            e.target.style.display = "none";
          }
        }
      );
    }
    const icon = node.icon || node.display?.icon || "heroicon-o-cog-6-tooth";
    return /* @__PURE__ */ import_react.default.createElement(HeroIcon, { name: icon, className });
  };
  var HeroIcon = ({ name, className = "w-5 h-5" }) => {
    if (name && (name.startsWith("M") || name.startsWith("<"))) {
      return /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: name }));
    }
    const icons = {
      // Categories & General
      "voodflow-logo": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" })),
      ViewGridIcon: /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" })),
      LightningBoltIcon: /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" })),
      "heroicon-o-bolt": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" })),
      "heroicon-o-cog-6-tooth": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" }), /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M15 12a3 3 0 11-6 0 3 3 0 016 0z" })),
      "heroicon-o-cube": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" })),
      "heroicon-o-link": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" })),
      // Triggers
      "heroicon-o-hand-raised": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M10.05 4.57c.18.2.43.305.688.305h.45c.258 0 .506-.105.687-.305V8.25a.75.75 0 011.5 0V7.125a.75.75 0 011.5 0V8.25a.75.75 0 011.5 0V9.375a.75.75 0 011.5 0V12a6.75 6.75 0 11-13.5 0V7.125a.75.75 0 011.5 0V8.25a.75.75 0 011.5 0V4.57z" })),
      "heroicon-o-clock": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" })),
      "heroicon-o-paper-airplane": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" })),
      // Data nodes
      "heroicon-o-funnel": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" })),
      "heroicon-o-document-arrow-down": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3 3m0 0l-3-3m3 3v-6m-1.5-9H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" })),
      "heroicon-o-tag": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581a1.125 1.125 0 001.591 0l4.456-4.456a1.125 1.125 0 000-1.591l-9.581-9.581a2.25 2.25 0 00-1.591-.659zm-1.818 5.625a1.125 1.125 0 11-2.25 0 1.125 1.125 0 012.25 0z" })),
      "heroicon-o-command-line": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M6.75 7.5l3 2.25-3 2.25m4.5 0h3m-9 8.25h13.5A2.25 2.25 0 0021 18V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v12a2.25 2.25 0 002.25 2.25z" })),
      "heroicon-o-circle-stack": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125v-3.75m16.5 0v3.75" })),
      "heroicon-o-arrow-path": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" })),
      "heroicon-o-check-badge": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" })),
      "heroicon-o-variable": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M4.745 20C2.326 14.67 2.326 9.33 4.745 4M19.255 20c2.419-5.33 2.419-10.67 0-16M7.75 7.75l8.5 8.5m0-8.5l-8.5 8.5" })),
      // Flow Control
      "heroicon-o-arrows-up-down": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" })),
      "heroicon-o-arrows-right-left": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" })),
      "heroicon-o-arrows-pointing-in": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M9 9V4.5M9 9H4.5M9 9L3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9V4.5M15 9h4.5M15 9l5.25-5.25M15 15v4.5M15 15h4.5M15 15l5.25 5.25" })),
      // Integrations & Misc
      "heroicon-o-globe-alt": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3" })),
      "heroicon-o- envelope": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91" })),
      "heroicon-o-envelope": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91" })),
      "heroicon-o-magnifying-glass": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" })),
      "heroicon-o-server": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3V3a3 3 0 013-3h13.5a3 3 0 013 3v8.25a3 3 0 01-3 3m-13.5 0a3 3 0 00-3 3v3.75a3 3 0 003 3h13.5a3 3 0 003-3v-3.75a3 3 0 00-3-3M6 4.5h.008v.008H6V4.5zm.008 2.25H6V6h.008v.008zm0 2.25H6v-.008h.008V9z" })),
      "heroicon-o-cloud-arrow-down": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M12 9.75v6.75m0 0l-3-3m3 3l3-3m-8.25 6a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" })),
      "heroicon-o-cloud-arrow-up": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" })),
      "heroicon-o-code-bracket": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M17.25 6.75L22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3l-4.5 16.5" })),
      "heroicon-o-table-cells": /* @__PURE__ */ import_react.default.createElement("svg", { className, fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" }, /* @__PURE__ */ import_react.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: 1.5, d: "M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125V5.625c0-.621.504-1.125 1.125-1.125h17.25c.621 0 1.125.504 1.125 1.125v12.75c0 .621-.504 1.125-1.125 1.125m-17.25 0V5.625m17.25 0v12.75M14.25 5.625v12.75m-4.5-12.75v12.75M3.375 10.5h17.25m-17.25 4.5h17.25" }))
    };
    return icons[name] || icons["heroicon-o-cog-6-tooth"];
  };
  var NodeIcon_default = NodeIcon;

  // resources/js/components/nodes/NodeHeader.jsx
  var NodeHeader = ({
    color,
    icon,
    nodeData = null,
    subtitle,
    title,
    badge = null,
    isExpanded = false,
    canExpand = true,
    onToggleExpand,
    onDelete
  }) => {
    const colorClasses = {
      amber: "bg-gradient-to-r from-amber-500 to-amber-600",
      blue: "bg-gradient-to-r from-blue-500 to-blue-600",
      yellow: "bg-gradient-to-r from-yellow-500 to-yellow-600",
      emerald: "bg-gradient-to-r from-emerald-500 to-emerald-600",
      purple: "bg-gradient-to-r from-purple-500 to-purple-600",
      violet: "bg-gradient-to-r from-violet-500 to-violet-600",
      slate: "bg-gradient-to-r from-slate-500 to-slate-600",
      gray: "bg-gradient-to-r from-gray-500 to-gray-600",
      orange: "bg-gradient-to-r from-orange-500 to-orange-600",
      rose: "bg-gradient-to-r from-rose-500 to-rose-600",
      indigo: "bg-gradient-to-r from-indigo-500 to-indigo-600",
      cyan: "bg-gradient-to-r from-cyan-500 to-cyan-600"
    };
    const gradientClass = colorClasses[color] || colorClasses.slate;
    return /* @__PURE__ */ import_react2.default.createElement("div", { className: `${gradientClass} px-4 py-2.5 flex items-center justify-between overflow-hidden group`, style: { borderTopLeftRadius: "10px", borderTopRightRadius: "10px" } }, /* @__PURE__ */ import_react2.default.createElement("div", { className: "flex items-center gap-3" }, /* @__PURE__ */ import_react2.default.createElement("div", { className: NODE_HEADER_STYLES.ICON_CONTAINER }, nodeData ? /* @__PURE__ */ import_react2.default.createElement(NodeIcon_default, { node: nodeData, className: NODE_HEADER_STYLES.ICON }) : /* @__PURE__ */ import_react2.default.createElement(HeroIcon, { name: icon, className: NODE_HEADER_STYLES.ICON })), /* @__PURE__ */ import_react2.default.createElement("div", null, /* @__PURE__ */ import_react2.default.createElement("div", { className: NODE_HEADER_STYLES.SUBTITLE }, subtitle), /* @__PURE__ */ import_react2.default.createElement("div", { className: NODE_HEADER_STYLES.TITLE }, title))), /* @__PURE__ */ import_react2.default.createElement("div", { className: NODE_HEADER_STYLES.ACTIONS_CONTAINER }, badge && /* @__PURE__ */ import_react2.default.createElement("div", { className: "px-2 py-0.5 rounded-full bg-white/20 backdrop-blur-md border border-white/10 text-[10px] font-black text-white uppercase tracking-tighter" }, badge), /* @__PURE__ */ import_react2.default.createElement("div", { className: NODE_HEADER_STYLES.DIVIDER }), /* @__PURE__ */ import_react2.default.createElement(
      "button",
      {
        onClick: onToggleExpand,
        className: NODE_HEADER_STYLES.EXPAND_BUTTON,
        disabled: !canExpand
      },
      /* @__PURE__ */ import_react2.default.createElement(
        "svg",
        {
          className: `w-4 h-4 transition-transform duration-300 ${isExpanded ? "rotate-180" : ""} ${!canExpand ? "opacity-50" : ""}`,
          fill: "none",
          stroke: "currentColor",
          viewBox: "0 0 24 24"
        },
        /* @__PURE__ */ import_react2.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: "2.5", d: "M19 9l-7 7-7-7" })
      )
    ), /* @__PURE__ */ import_react2.default.createElement(
      "button",
      {
        onClick: onDelete,
        className: NODE_HEADER_STYLES.DELETE_BUTTON
      },
      /* @__PURE__ */ import_react2.default.createElement("svg", { className: "w-3.5 h-3.5", fill: "none", stroke: "currentColor", viewBox: "0 0 24 24" }, /* @__PURE__ */ import_react2.default.createElement("path", { strokeLinecap: "round", strokeLinejoin: "round", strokeWidth: "2", d: "M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" }))
    )));
  };

  // resources/js/components/nodes/NodeReadOnlyBanner.jsx
  var import_react3 = __toESM(__require("react"), 1);
  function NodeReadOnlyBanner({ message }) {
    const text = message || "This node's configuration is read-only for your role.";
    return /* @__PURE__ */ import_react3.default.createElement("div", { className: "mx-4 mt-3 mb-1 text-xs text-amber-800 dark:text-amber-200 font-medium bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-900 rounded-lg px-3 py-2" }, text);
  }

  // resources/js/components/nodes/NodeConfigReadOnlyNotice.jsx
  var import_react4 = __toESM(__require("react"), 1);

  // resources/js/hooks/useNodeAccessUi.js
  function useNodeAccessUi(data = {}) {
    const workflowReadOnly = Boolean(data.workflowReadOnly);
    const access = data.nodeAccess;
    const configReadOnlyByPolicy = Boolean(access?.configReadOnly);
    const configReadOnly = workflowReadOnly || configReadOnlyByPolicy;
    const nodeDeletable = access?.deletable !== false;
    const executable = access?.executable !== false;
    const canShowDeleteButton = nodeDeletable && !workflowReadOnly;
    const readOnlyHint = data.nodeConfigReadOnlyHint ?? null;
    const nonExecutableHint = data.nodeNonExecutableHint ?? null;
    return {
      workflowReadOnly,
      configReadOnly,
      configReadOnlyByPolicy,
      nodeDeletable,
      executable,
      canShowDeleteButton,
      readOnlyHint,
      nonExecutableHint
    };
  }

  // resources/js/components/nodes/NodeConfigReadOnlyNotice.jsx
  function NodeConfigReadOnlyNotice({ data }) {
    const { configReadOnlyByPolicy, readOnlyHint } = useNodeAccessUi(data);
    if (!configReadOnlyByPolicy) {
      return null;
    }
    return /* @__PURE__ */ import_react4.default.createElement(NodeReadOnlyBanner, { message: readOnlyHint });
  }

  // resources/js/components/nodes/NodeNonExecutableNotice.jsx
  var import_react5 = __toESM(__require("react"), 1);
  function NodeNonExecutableNotice({ data }) {
    const { executable, nonExecutableHint } = useNodeAccessUi(data);
    if (executable) {
      return null;
    }
    return /* @__PURE__ */ import_react5.default.createElement(
      NodeReadOnlyBanner,
      {
        message: nonExecutableHint ?? "Questo nodo \xE8 disabilitato in esecuzione per il tuo ruolo."
      }
    );
  }

  // resources/js/components/nodes/CollapsedView.jsx
  var import_react6 = __toESM(__require("react"), 1);
  var CollapsedView = ({
    shouldShowLogo = false,
    hasConfiguration = false,
    description = "",
    summary = null,
    VoodflowLogo,
    noPadding = false,
    isTrigger = false
  }) => {
    if (summary) {
      if (typeof summary !== "string") {
        if (noPadding) {
          return /* @__PURE__ */ import_react6.default.createElement("div", { className: `bg-slate-50/50 dark:bg-slate-800/20 rounded-b-xl ${NODE_DIVIDER.SECTION}` }, summary);
        }
        return /* @__PURE__ */ import_react6.default.createElement("div", { className: `bg-slate-50/50 dark:bg-slate-800/20 rounded-b-xl ${NODE_DIVIDER.SECTION} p-4` }, summary);
      }
      return /* @__PURE__ */ import_react6.default.createElement("div", { className: COLLAPSED_NODE_STYLES.CONTAINER }, /* @__PURE__ */ import_react6.default.createElement("div", { className: "text-center space-y-1" }, /* @__PURE__ */ import_react6.default.createElement("div", { className: COLLAPSED_NODE_STYLES.DESCRIPTION }, summary)));
    }
    if (shouldShowLogo) {
      return /* @__PURE__ */ import_react6.default.createElement("div", { className: COLLAPSED_NODE_STYLES.CONTAINER }, /* @__PURE__ */ import_react6.default.createElement("div", { className: COLLAPSED_NODE_STYLES.CONNECT_MESSAGE }, VoodflowLogo && /* @__PURE__ */ import_react6.default.createElement(VoodflowLogo, { className: COLLAPSED_NODE_STYLES.LOGO }), /* @__PURE__ */ import_react6.default.createElement("div", { className: "text-slate-500 dark:text-slate-400 font-medium text-sm" }, isTrigger ? "Configure" : "Connect data")));
    }
    if (hasConfiguration && description) {
      return /* @__PURE__ */ import_react6.default.createElement("div", { className: COLLAPSED_NODE_STYLES.CONTAINER }, /* @__PURE__ */ import_react6.default.createElement("div", { className: COLLAPSED_NODE_STYLES.DESCRIPTION }, description));
    }
    return /* @__PURE__ */ import_react6.default.createElement("div", { className: COLLAPSED_NODE_STYLES.CONTAINER }, /* @__PURE__ */ import_react6.default.createElement("div", { className: COLLAPSED_NODE_STYLES.PLACEHOLDER }, "Click to configure"));
  };

  // resources/js/components/nodes/BaseNodeContainer.jsx
  var import_react7 = __toESM(__require("react"), 1);

  // resources/js/constants/editorConfig.js
  var NODE_CONFIG_DEBOUNCE_MS = 1200;
  var EXPANDED_NODE_Z_INDEX = 1e3;

  // resources/js/components/nodes/BaseNodeContainer.jsx
  var BaseNodeContainer = ({
    color = "cyan",
    selected = false,
    isExpanded = false,
    minWidth = "320px",
    maxWidth = "450px",
    style = {},
    className = "",
    children
  }) => {
    const collapsedWidth = "350px";
    const finalMinWidth = isExpanded ? minWidth : collapsedWidth;
    const finalMaxWidth = isExpanded ? maxWidth : collapsedWidth;
    const colorMap = {
      amber: {
        selected: "border-amber-500 shadow-amber-500/20",
        hover: "hover:border-amber-400 dark:hover:border-amber-600"
      },
      blue: {
        selected: "border-blue-500 shadow-blue-500/20",
        hover: "hover:border-blue-400 dark:hover:border-blue-600"
      },
      yellow: {
        selected: "border-yellow-500 shadow-yellow-500/20",
        hover: "hover:border-yellow-400 dark:hover:border-yellow-600"
      },
      emerald: {
        selected: "border-emerald-500 shadow-emerald-500/20",
        hover: "hover:border-emerald-400 dark:hover:border-emerald-600"
      },
      purple: {
        selected: "border-purple-500 shadow-purple-500/20",
        hover: "hover:border-purple-400 dark:hover:border-purple-600"
      },
      violet: {
        selected: "border-violet-500 shadow-violet-500/20",
        hover: "hover:border-violet-400 dark:hover:border-violet-600"
      },
      slate: {
        selected: "border-slate-500 shadow-slate-500/20",
        hover: "hover:border-slate-400 dark:hover:border-slate-600"
      },
      gray: {
        selected: "border-gray-500 shadow-gray-500/20",
        hover: "hover:border-gray-400 dark:hover:border-gray-600"
      },
      cyan: {
        selected: "border-cyan-500 shadow-cyan-500/20",
        hover: "hover:border-cyan-400 dark:hover:border-cyan-600"
      },
      orange: {
        selected: "border-orange-500 shadow-orange-500/20",
        hover: "hover:border-orange-400 dark:hover:border-orange-600"
      },
      rose: {
        selected: "border-rose-500 shadow-rose-500/20",
        hover: "hover:border-rose-400 dark:hover:border-rose-600"
      },
      indigo: {
        selected: "border-indigo-500 shadow-indigo-500/20",
        hover: "hover:border-indigo-400 dark:hover:border-indigo-600"
      }
    };
    const colorClasses = colorMap[color] || colorMap.cyan;
    const containerClasses = `
        relative
        bg-white dark:bg-slate-900
        border-2
        rounded-xl
        shadow-lg
        transition-all duration-200
        group
        ${selected ? colorClasses.selected : `border-slate-200 dark:border-slate-700 ${colorClasses.hover}`}
        ${className}
    `.trim().replace(/\s+/g, " ");
    const containerStyle = {
      zIndex: isExpanded ? EXPANDED_NODE_Z_INDEX : "auto",
      minWidth: finalMinWidth,
      maxWidth: finalMaxWidth,
      ...style
    };
    return /* @__PURE__ */ import_react7.default.createElement("div", { className: containerClasses, style: containerStyle }, children);
  };

  // resources/js/components/nodes/StandardHandle.jsx
  var import_react8 = __toESM(__require("react"), 1);
  var import_react9 = __require("@xyflow/react");
  var StandardHandle = ({
    type = "target",
    position = import_react9.Position.Left,
    color = "slate-500",
    id = null,
    top = "50%",
    isConnected = null,
    // null = always visible, true/false = control opacity
    style = {},
    className = ""
  }) => {
    const colorMap = {
      "amber": "#f59e0b",
      "amber-500": "#f59e0b",
      "blue": "#3b82f6",
      "blue-500": "#3b82f6",
      "emerald": "#10b981",
      "emerald-500": "#10b981",
      "yellow": "#eab308",
      "yellow-500": "#eab308",
      "red": "#ef4444",
      "red-500": "#ef4444",
      "orange": "#f97316",
      "orange-500": "#f97316",
      "rose": "#f43f5e",
      "rose-500": "#f43f5e",
      "purple": "#a855f7",
      "purple-500": "#a855f7",
      "violet": "#8b5cf6",
      "violet-500": "#8b5cf6",
      "indigo": "#6366f1",
      "indigo-500": "#6366f1",
      "cyan": "#06b6d4",
      "cyan-500": "#06b6d4",
      "gray": "#6b7280",
      "gray-500": "#6b7280",
      "slate": "#64748b",
      "slate-500": "#64748b"
    };
    let colorValue = colorMap[color] || colorMap["slate-500"];
    if (color.startsWith("bg-")) {
      const colorKey = color.replace("bg-", "");
      colorValue = colorMap[colorKey] || colorMap["slate-500"];
    }
    let opacityClass = "";
    if (isConnected !== null) {
      opacityClass = isConnected ? "opacity-100" : "opacity-70 hover:opacity-100";
    }
    const handleStyle = {
      top,
      backgroundColor: colorValue,
      borderColor: "white",
      borderWidth: "2px",
      borderStyle: "solid",
      ...style
    };
    const handleClassName = `!w-6 !h-6 !border-2 !border-white ${opacityClass} ${className}`.trim();
    return /* @__PURE__ */ import_react8.default.createElement(
      import_react9.Handle,
      {
        id,
        type,
        position,
        className: handleClassName,
        style: handleStyle
      }
    );
  };
  var StandardHandle_default = StandardHandle;

  // resources/js/components/nodes/FormFields.jsx
  var import_react10 = __toESM(__require("react"), 1);
  var FieldLabel = ({ children, required = false }) => /* @__PURE__ */ import_react10.default.createElement("label", { className: TYPOGRAPHY.LABEL.className }, children, required && /* @__PURE__ */ import_react10.default.createElement("span", { className: "text-rose-500 ml-0.5" }, "*"));
  var TextInput = ({
    value,
    onChange,
    placeholder = "",
    color = "slate",
    className = "",
    ...props
  }) => {
    const baseClass = color === "slate" ? INPUT_STYLES.BASE : INPUT_STYLES.withColor(color);
    return /* @__PURE__ */ import_react10.default.createElement(
      "input",
      {
        type: "text",
        value,
        onChange,
        placeholder,
        className: `${baseClass} ${className}`,
        ...props
      }
    );
  };
  var NodeConfigFields = ({
    label,
    description,
    onLabelChange,
    onDescriptionChange,
    labelPlaceholder = "Node name",
    descriptionPlaceholder = "Description (optional)",
    color = "slate",
    readOnly = false
  }) => {
    return /* @__PURE__ */ import_react10.default.createElement("div", { className: `p-4 space-y-4 ${NODE_DIVIDER.AFTER_CONFIG}` }, /* @__PURE__ */ import_react10.default.createElement("div", { className: "grid grid-cols-2 gap-3" }, /* @__PURE__ */ import_react10.default.createElement("div", { className: SPACING.FIELD_GAP }, /* @__PURE__ */ import_react10.default.createElement(FieldLabel, null, "Node Title"), /* @__PURE__ */ import_react10.default.createElement(
      TextInput,
      {
        value: label,
        onChange: onLabelChange,
        placeholder: labelPlaceholder,
        color,
        readOnly,
        disabled: readOnly
      }
    )), /* @__PURE__ */ import_react10.default.createElement("div", { className: SPACING.FIELD_GAP }, /* @__PURE__ */ import_react10.default.createElement(FieldLabel, null, "Description"), /* @__PURE__ */ import_react10.default.createElement(
      TextInput,
      {
        value: description,
        onChange: onDescriptionChange,
        placeholder: descriptionPlaceholder,
        color,
        className: "placeholder:italic",
        readOnly,
        disabled: readOnly
      }
    ))));
  };

  // resources/js/components/nodes/SmartConditionRow.jsx
  var import_react15 = __toESM(__require("react"), 1);

  // resources/js/components/nodes/HelpTooltip.jsx
  var import_react11 = __toESM(__require("react"), 1);

  // resources/js/components/nodes/RepeaterComponents.jsx
  var import_react12 = __toESM(__require("react"), 1);

  // resources/js/hooks/useUpstreamFieldValues.js
  var import_react13 = __require("react");
  var import_react14 = __require("@xyflow/react");

  // resources/js/constants/dataTypes.js
  var DATE_PARTS = {
    YEAR: "year",
    MONTH: "month",
    DAY: "day",
    HOUR: "hour",
    MINUTE: "minute"
  };
  var DATE_PART_LABELS = {
    [DATE_PARTS.YEAR]: "Year",
    [DATE_PARTS.MONTH]: "Month",
    [DATE_PARTS.DAY]: "Day",
    [DATE_PARTS.HOUR]: "Hour",
    [DATE_PARTS.MINUTE]: "Minute"
  };
  var TIME_UNITS = {
    MINUTES: "minutes",
    HOURS: "hours",
    DAYS: "days",
    WEEKS: "weeks",
    MONTHS: "months",
    YEARS: "years"
  };
  var TIME_UNIT_LABELS = {
    [TIME_UNITS.MINUTES]: "Minutes",
    [TIME_UNITS.HOURS]: "Hours",
    [TIME_UNITS.DAYS]: "Days",
    [TIME_UNITS.WEEKS]: "Weeks",
    [TIME_UNITS.MONTHS]: "Months",
    [TIME_UNITS.YEARS]: "Years"
  };

  // resources/js/components/nodes/AvailableFieldsSection.jsx
  var import_react16 = __toESM(__require("react"), 1);

  // resources/js/hooks/useStandardNodeBehavior.js
  var import_react17 = __require("react");
  var useStandardNodeBehavior = (nodeId, nodeData, getEdges, setNodes, isTrigger = false) => {
    const [isExpanded, setIsExpanded] = (0, import_react17.useState)(
      nodeData.isExpanded ?? (nodeData.isNew ?? true)
    );
    const [isConnected, setIsConnected] = (0, import_react17.useState)(() => {
      const edges = getEdges();
      return edges.some((edge) => edge.target === nodeId);
    });
    const [isOutputConnected, setIsOutputConnected] = (0, import_react17.useState)(() => {
      const edges = getEdges();
      return edges.some((edge) => edge.source === nodeId);
    });
    const wasConnected = (0, import_react17.useRef)(isConnected);
    const getEdgesRef = (0, import_react17.useRef)(getEdges);
    (0, import_react17.useLayoutEffect)(() => {
      getEdgesRef.current = getEdges;
    });
    (0, import_react17.useEffect)(() => {
      const updateConnectionStates = () => {
        const currentEdges = getEdgesRef.current();
        const currentlyConnected = currentEdges.some((edge) => edge.target === nodeId);
        const currentlyOutputConnected = currentEdges.some((edge) => edge.source === nodeId);
        setIsConnected(currentlyConnected);
        setIsOutputConnected(currentlyOutputConnected);
      };
      updateConnectionStates();
      window.addEventListener("voodflow:edges-changed", updateConnectionStates);
      return () => window.removeEventListener("voodflow:edges-changed", updateConnectionStates);
    }, [nodeId]);
    (0, import_react17.useEffect)(() => {
      if (!wasConnected.current && isConnected && !isExpanded) {
        setIsExpanded(true);
        const workflowReadOnly = Boolean(nodeData.workflowReadOnly) || typeof window !== "undefined" && window.__voodflowWorkflowReadOnly;
        if (!workflowReadOnly && nodeData.livewireId && window.Livewire) {
          const component = window.Livewire.find(nodeData.livewireId);
          if (component) {
            component.call("updateNodeConfig", {
              nodeId,
              isExpanded: true
            });
          }
        }
        setNodes((nds) => nds.map((node) => {
          if (node.id === nodeId) {
            return {
              ...node,
              data: {
                ...node.data,
                isExpanded: true,
                isNew: false
              }
            };
          }
          return node;
        }));
      }
      wasConnected.current = isConnected;
    }, [isConnected, isExpanded, nodeId, nodeData.livewireId, setNodes]);
    const toggleExpansion = () => {
      const newExpandedState = !isExpanded;
      setIsExpanded(newExpandedState);
      const workflowReadOnly = Boolean(nodeData.workflowReadOnly) || typeof window !== "undefined" && window.__voodflowWorkflowReadOnly;
      if (!workflowReadOnly && nodeData.livewireId && window.Livewire) {
        const component = window.Livewire.find(nodeData.livewireId);
        if (component) {
          component.call("updateNodeConfig", {
            nodeId,
            isExpanded: newExpandedState
          });
        }
      }
      setNodes((nds) => nds.map((node) => {
        if (node.id === nodeId) {
          return {
            ...node,
            data: {
              ...node.data,
              isExpanded: newExpandedState,
              isNew: false
            }
          };
        }
        return node;
      }));
    };
    const hasConfiguration = isTrigger ? Boolean(
      nodeData.description || nodeData.config && nodeData.config.schedule_type || nodeData.eventClass
    ) : Boolean(
      nodeData.description || nodeData.label !== nodeData.defaultLabel
    );
    const shouldShowLogo = isTrigger ? !hasConfiguration : !isConnected;
    return {
      isExpanded,
      toggleExpansion,
      isConnected,
      isOutputConnected,
      shouldShowLogo,
      hasConfiguration,
      isTrigger
    };
  };

  // ../voodbuilder/src/Nodes/SiteVisitTriggerNode/components/SiteVisitTriggerNode.jsx
  var MATCH_BADGES = {
    any: "ANY",
    page: "PAGE",
    menu_item: "MENU",
    path: "PATH"
  };
  function SiteVisitTriggerNode({ id, data, selected }) {
    const defaultLabel = "Site Visit";
    const nodeColor = data.color || "sky";
    const { configReadOnly, canShowDeleteButton } = useNodeAccessUi(data);
    const { ConfirmModal, DynamicFormRenderer } = window.VoodflowCommon || {};
    const { setNodes, getEdges } = (0, import_react19.useReactFlow)();
    const {
      isExpanded,
      toggleExpansion,
      isOutputConnected,
      shouldShowLogo,
      hasConfiguration
    } = useStandardNodeBehavior(id, { ...data, defaultLabel }, getEdges, setNodes, true);
    const schema = (0, import_react18.useMemo)(
      () => Array.isArray(data.definition?.ui_schema) ? data.definition.ui_schema : [],
      [data.definition]
    );
    const schemaFieldNames = (0, import_react18.useMemo)(
      () => schema.map((field) => field.name).filter(Boolean),
      [schema]
    );
    const [showDeleteModal, setShowDeleteModal] = (0, import_react18.useState)(false);
    const [label, setLabel] = (0, import_react18.useState)(data.label || defaultLabel);
    const [description, setDescription] = (0, import_react18.useState)(data.description || "");
    const [fieldValues, setFieldValues] = (0, import_react18.useState)(() => {
      const initial = {};
      schemaFieldNames.forEach((name) => {
        initial[name] = data[name] ?? "";
      });
      return initial;
    });
    (0, import_react18.useEffect)(() => {
      setFieldValues((prev) => {
        const next = { ...prev };
        schemaFieldNames.forEach((name) => {
          if (data[name] !== void 0) {
            next[name] = data[name];
          } else if (next[name] === void 0) {
            next[name] = "";
          }
        });
        return next;
      });
    }, [schemaFieldNames.join("|"), ...schemaFieldNames.map((name) => data[name])]);
    (0, import_react18.useEffect)(() => {
      if (configReadOnly) {
        return;
      }
      const payload = {
        label,
        description,
        ...fieldValues
      };
      const hasChanged = label !== (data.label || defaultLabel) || description !== (data.description || "") || schemaFieldNames.some((name) => String(fieldValues[name] ?? "") !== String(data[name] ?? ""));
      if (!hasChanged) {
        return;
      }
      const timeoutId = setTimeout(() => {
        window.Livewire?.find(data.livewireId)?.call("updateNodeConfig", {
          nodeId: id,
          ...payload
        });
        setNodes((nds) => nds.map((node) => {
          if (node.id !== id) {
            return node;
          }
          return {
            ...node,
            data: {
              ...node.data,
              ...payload
            }
          };
        }));
      }, NODE_CONFIG_DEBOUNCE_MS);
      return () => clearTimeout(timeoutId);
    }, [
      configReadOnly,
      label,
      description,
      fieldValues,
      schemaFieldNames.join("|"),
      data.label,
      data.description,
      data.livewireId,
      id,
      setNodes,
      defaultLabel
    ]);
    const handleDelete = () => {
      if (!canShowDeleteButton) {
        return;
      }
      window.Livewire?.find(data.livewireId)?.call("deleteNode", id);
    };
    const matchType = String(fieldValues.match_type || data.match_type || "page");
    const badge = MATCH_BADGES[matchType] || matchType.toUpperCase().slice(0, 8);
    return /* @__PURE__ */ import_react18.default.createElement(import_react18.default.Fragment, null, canShowDeleteButton && ConfirmModal && /* @__PURE__ */ import_react18.default.createElement(
      ConfirmModal,
      {
        isOpen: showDeleteModal,
        title: "Delete Node",
        message: `Remove "${label}"?`,
        onConfirm: handleDelete,
        onCancel: () => setShowDeleteModal(false)
      }
    ), /* @__PURE__ */ import_react18.default.createElement(
      BaseNodeContainer,
      {
        color: nodeColor,
        selected,
        isExpanded,
        minWidth: "340px",
        maxWidth: "460px"
      },
      /* @__PURE__ */ import_react18.default.createElement(
        NodeHeader,
        {
          nodeData: data,
          color: nodeColor,
          title: label,
          subtitle: "SITE VISIT",
          badge,
          isExpanded,
          canExpand: true,
          onToggleExpand: toggleExpansion,
          onDelete: canShowDeleteButton ? () => setShowDeleteModal(true) : void 0
        }
      ),
      isExpanded ? /* @__PURE__ */ import_react18.default.createElement("div", { className: "nodrag" }, /* @__PURE__ */ import_react18.default.createElement(NodeConfigReadOnlyNotice, { data }), /* @__PURE__ */ import_react18.default.createElement(NodeNonExecutableNotice, { data }), /* @__PURE__ */ import_react18.default.createElement(
        NodeConfigFields,
        {
          label,
          description,
          onLabelChange: (e) => setLabel(e.target.value),
          onDescriptionChange: (e) => setDescription(e.target.value),
          color: nodeColor,
          readOnly: configReadOnly
        }
      ), /* @__PURE__ */ import_react18.default.createElement("div", { className: "p-4 space-y-3" }, /* @__PURE__ */ import_react18.default.createElement("div", { className: "text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400" }, "When a visitor lands"), schema.length === 0 ? /* @__PURE__ */ import_react18.default.createElement("div", { className: "text-xs text-slate-400 italic dark:text-slate-500" }, "No configuration defined.") : DynamicFormRenderer ? /* @__PURE__ */ import_react18.default.createElement("div", { className: "rounded-lg ring-1 ring-black/5 bg-slate-50 dark:bg-slate-900/50 p-3" }, /* @__PURE__ */ import_react18.default.createElement(
        DynamicFormRenderer,
        {
          schema,
          values: fieldValues,
          onChange: (fieldName, value) => {
            if (configReadOnly) {
              return;
            }
            setFieldValues((prev) => ({
              ...prev,
              [fieldName]: value
            }));
          },
          availableFields: data.filterFieldsMap || data.availableFields || {}
        }
      )) : /* @__PURE__ */ import_react18.default.createElement("div", { className: "text-xs text-rose-500" }, "DynamicFormRenderer unavailable \u2014 rebuild Voodflow assets."), /* @__PURE__ */ import_react18.default.createElement("p", { className: "text-[11px] leading-relaxed text-slate-500 dark:text-slate-400" }, "Payload includes ", /* @__PURE__ */ import_react18.default.createElement("code", { className: "text-[10px]" }, "visitor_key"), " for Trigger Popup (", /* @__PURE__ */ import_react18.default.createElement("code", { className: "text-[10px]" }, "{{visitor_key}}"), ")."))) : /* @__PURE__ */ import_react18.default.createElement(
        CollapsedView,
        {
          label,
          description: description || "Visitor lands on matched page / menu / path",
          hasConfiguration,
          shouldShowLogo,
          color: nodeColor
        }
      ),
      /* @__PURE__ */ import_react18.default.createElement(
        StandardHandle_default,
        {
          type: "source",
          position: import_react19.Position.Right,
          id: "output",
          color: nodeColor,
          connected: isOutputConnected
        }
      )
    ));
  }
  return __toCommonJS(SiteVisitTriggerNode_exports);
})();
//# sourceMappingURL=site-visit-trigger-node.js.map
