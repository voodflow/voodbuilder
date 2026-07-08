/**
 * CodeMirror 6 fields for HTML/CSS editing in GrapesJS modals.
 */

import { defaultKeymap, history, historyKeymap, indentWithTab } from '@codemirror/commands';
import { css as cssLanguage } from '@codemirror/lang-css';
import { html as htmlLanguage } from '@codemirror/lang-html';
import {
    bracketMatching,
    defaultHighlightStyle,
    foldGutter,
    indentOnInput,
    syntaxHighlighting,
} from '@codemirror/language';
import { Compartment, EditorState, RangeSetBuilder } from '@codemirror/state';
import {
    Decoration,
    EditorView,
    ViewPlugin,
    drawSelection,
    dropCursor,
    highlightActiveLine,
    highlightActiveLineGutter,
    highlightSpecialChars,
    keymap,
    lineNumbers,
    rectangularSelection,
} from '@codemirror/view';
import beautify from 'js-beautify';

const activeEditors = new Set();
const wrapCompartment = new Compartment();
const reviewCompartment = new Compartment();

function editorSurfaceIsDark() {
    return document.documentElement.classList.contains('dark')
        || Boolean(document.querySelector('.voodbuilder-gjs-root.dark, .voodbuilder-gjs-root .dark'));
}

function escapeRegExp(value) {
    return String(value ?? '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function buildEditorTheme(dark) {
    return EditorView.theme({
        '&': {
            color: dark ? '#e2e8f0' : '#1e293b',
            backgroundColor: dark ? '#0f172a' : '#f8fafc',
        },
        '.cm-content': {
            caretColor: dark ? '#a5b4fc' : '#4f46e5',
            padding: '0.5rem 0',
        },
        '.cm-scroller': {
            fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
            lineHeight: '1.5',
            overflowX: 'auto',
        },
        '.cm-gutters': {
            backgroundColor: dark ? '#111827' : '#f1f5f9',
            color: dark ? '#64748b' : '#94a3b8',
            borderRight: `1px solid ${dark ? '#1f2937' : '#e2e8f0'}`,
        },
        '&.cm-focused .cm-activeLine': {
            backgroundColor: dark ? 'rgba(99, 102, 241, 0.12)' : 'rgba(99, 102, 241, 0.08)',
        },
        '&.cm-focused .cm-activeLineGutter': {
            backgroundColor: dark ? 'rgba(99, 102, 241, 0.16)' : 'rgba(99, 102, 241, 0.12)',
        },
        '.cm-selectionBackground, ::selection': {
            backgroundColor: dark ? 'rgba(99, 102, 241, 0.35)' : 'rgba(99, 102, 241, 0.22)',
        },
        '.voodbuilder-cm-review-class': {
            backgroundColor: dark ? 'rgba(245, 158, 11, 0.28)' : 'rgba(245, 158, 11, 0.22)',
            borderRadius: '0.15rem',
            outline: dark ? '1px solid rgba(245, 158, 11, 0.45)' : '1px solid rgba(217, 119, 6, 0.4)',
        },
    }, { dark });
}

function buildReviewClassHighlighter(classNames) {
    const names = [...new Set(classNames.filter(Boolean))];

    if (names.length === 0) {
        return [];
    }

    const mark = Decoration.mark({ class: 'voodbuilder-cm-review-class' });

    return ViewPlugin.fromClass(class {
        decorations = Decoration.none;

        constructor(view) {
            this.decorations = this.build(view);
        }

        update(update) {
            if (update.docChanged || update.viewportChanged) {
                this.decorations = this.build(update.view);
            }
        }

        build(view) {
            const builder = new RangeSetBuilder();
            const text = view.state.doc.toString();
            const ranges = [];

            for (const className of names) {
                const pattern = new RegExp(`\\b${escapeRegExp(className)}\\b`, 'g');
                let match = pattern.exec(text);

                while (match) {
                    ranges.push({ from: match.index, to: match.index + match[0].length });
                    match = pattern.exec(text);
                }
            }

            ranges.sort((left, right) => left.from - right.from || left.to - right.to);

            let cursor = 0;

            for (const range of ranges) {
                if (range.from < cursor) {
                    continue;
                }

                builder.add(range.from, range.to, mark);
                cursor = range.to;
            }

            return builder.finish();
        }
    }, { decorations: (plugin) => plugin.decorations });
}

export function formatCodeForEditor(value, language = 'html') {
    const trimmed = String(value ?? '').trim();

    if (trimmed === '') {
        return '';
    }

    try {
        if (language === 'css') {
            return beautify.css(trimmed, {
                indent_size: 2,
            });
        }

        return beautify.html(trimmed, {
            indent_size: 2,
            wrap_line_length: 0,
            preserve_newlines: false,
            indent_inner_html: true,
        });
    } catch {
        return trimmed;
    }
}

export function createCodeEditorField({
    mount,
    value = '',
    language = 'html',
    minHeight = '12rem',
    lineWrapping = false,
    onChange = null,
}) {
    if (! mount) {
        return null;
    }

    const dark = editorSurfaceIsDark();
    const languageExtension = language === 'css' ? cssLanguage() : htmlLanguage();

    const updateListener = EditorView.updateListener.of((update) => {
        if (update.docChanged) {
            onChange?.(update.state.doc.toString());
        }
    });

    const state = EditorState.create({
        doc: value,
        extensions: [
            lineNumbers(),
            highlightActiveLineGutter(),
            highlightSpecialChars(),
            history(),
            foldGutter(),
            drawSelection(),
            dropCursor(),
            EditorState.allowMultipleSelections.of(true),
            indentOnInput(),
            syntaxHighlighting(defaultHighlightStyle, { fallback: true }),
            bracketMatching(),
            keymap.of([
                indentWithTab,
                ...defaultKeymap,
                ...historyKeymap,
            ]),
            rectangularSelection(),
            highlightActiveLine(),
            languageExtension,
            buildEditorTheme(dark),
            wrapCompartment.of(lineWrapping ? EditorView.lineWrapping : []),
            reviewCompartment.of([]),
            EditorView.theme({
                '&': {
                    minHeight,
                    border: `1px solid ${dark ? '#1f2937' : '#e2e8f0'}`,
                    borderRadius: '0.5rem',
                    overflow: 'hidden',
                },
                '.cm-scroller': {
                    minHeight,
                },
            }),
            updateListener,
        ],
    });

    const view = new EditorView({
        state,
        parent: mount,
    });

    const field = {
        view,
        lineWrapping,
        getValue() {
            return view.state.doc.toString();
        },
        setValue(nextValue) {
            const current = view.state.doc.toString();

            if (current === nextValue) {
                return;
            }

            view.dispatch({
                changes: {
                    from: 0,
                    to: current.length,
                    insert: nextValue,
                },
            });
        },
        setLineWrapping(enabled) {
            field.lineWrapping = Boolean(enabled);
            view.dispatch({
                effects: wrapCompartment.reconfigure(field.lineWrapping ? EditorView.lineWrapping : []),
            });
        },
        toggleLineWrapping() {
            field.setLineWrapping(! field.lineWrapping);
        },
        setReviewClasses(classNames) {
            view.dispatch({
                effects: reviewCompartment.reconfigure(buildReviewClassHighlighter(classNames)),
            });
        },
        scrollToClass(className) {
            const text = view.state.doc.toString();
            const pattern = new RegExp(`\\b${escapeRegExp(className)}\\b`);
            const match = pattern.exec(text);

            if (! match) {
                return false;
            }

            const from = match.index;
            const to = from + match[0].length;

            view.dispatch({
                selection: { anchor: from, head: to },
                effects: EditorView.scrollIntoView(from, { y: 'center' }),
            });
            view.focus();

            return true;
        },
        focus() {
            view.focus();
        },
        destroy() {
            activeEditors.delete(field);
            view.destroy();
        },
    };

    activeEditors.add(field);

    return field;
}

export function destroyCodeEditorFields() {
    for (const field of activeEditors) {
        field.destroy();
    }

    activeEditors.clear();
}
