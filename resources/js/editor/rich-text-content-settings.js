/**
 * Lightweight Visual/Code rich-text editor for the Content inspector (no TipTap/Quill).
 * Few controls, native contenteditable — Bricks-like, dependency-free.
 */

import { createFormSection } from './editor-form-ui.js';
import {
    applyRichTextLinkAttrs,
    buildAnchorOpenTag,
    ensureRichTextLinkClasses,
    linkPickerDialog,
    readAnchorLinkState,
} from './link-picker-dialog.js';
import { lucideIcon } from './editor-icons.js';
import { insertHtmlInlineAtCaret, openRichTextDynamicTagPicker } from './rich-text-dynamic-tags.js';
import { findRichTextHost, keepRichTextSelection, lockRichTextChildren } from './text-elements.js';

const ALLOWED_TAGS = new Set([
    'P', 'BR', 'STRONG', 'B', 'EM', 'I', 'U', 'S', 'A',
    'UL', 'OL', 'LI', 'SPAN', 'BLOCKQUOTE', 'DIV',
]);

const ALIGN_CLASSES = ['text-left', 'text-center', 'text-right', 'text-justify'];

/**
 * @param {string} html
 * @returns {string}
 */
export function sanitizeRichTextHtml(html) {
    const template = document.createElement('template');
    template.innerHTML = String(html ?? '');

    const walk = (node) => {
        [...node.childNodes].forEach((child) => {
            if (child.nodeType === Node.TEXT_NODE) {
                return;
            }

            if (child.nodeType !== Node.ELEMENT_NODE) {
                child.remove();

                return;
            }

            const el = /** @type {HTMLElement} */ (child);
            const tag = el.tagName;

            if (! ALLOWED_TAGS.has(tag)) {
                const text = document.createTextNode(el.textContent ?? '');
                el.replaceWith(text);

                return;
            }

            let pendingAlign = '';

            [...el.attributes].forEach((attr) => {
                const name = attr.name.toLowerCase();

                if (tag === 'A' && (
                    name === 'href'
                    || name === 'target'
                    || name === 'rel'
                    || name === 'title'
                    || name === 'data-vb-link-type'
                    || name === 'data-vb-link'
                )) {
                    return;
                }

                if (
                    (tag === 'SPAN' || tag === 'A')
                    && (
                        name === 'data-voodbuilder-bind'
                        || name === 'data-voodbuilder-bind-href'
                        || name === 'data-voodbuilder-hide-when-empty'
                    )
                    && (
                        name === 'data-voodbuilder-hide-when-empty'
                        || /^[\w.-]+$/.test(String(attr.value ?? '').trim())
                    )
                ) {
                    return;
                }

                if (
                    (tag === 'SPAN' || tag === 'A')
                    && name === 'contenteditable'
                    && String(attr.value ?? '').toLowerCase() === 'false'
                ) {
                    return;
                }

                if (name === 'class') {
                    return;
                }

                if (name === 'style') {
                    const match = /text-align\s*:\s*(left|center|right|justify)/i.exec(attr.value);

                    if (match) {
                        pendingAlign = match[1].toLowerCase();
                    }

                    el.removeAttribute(attr.name);

                    return;
                }

                el.removeAttribute(attr.name);
            });

            if (
                (tag === 'SPAN' || tag === 'A')
                && (
                    el.hasAttribute('data-voodbuilder-bind')
                    || el.hasAttribute('data-voodbuilder-bind-href')
                )
                && el.getAttribute('contenteditable') !== 'false'
            ) {
                el.setAttribute('contenteditable', 'false');
            }

            if (pendingAlign) {
                const classes = String(el.getAttribute('class') ?? '')
                    .split(/\s+/)
                    .filter((token) => token && ! ALIGN_CLASSES.includes(token));
                classes.push(`text-${pendingAlign}`);
                el.setAttribute('class', classes.join(' '));
            }

            if (tag === 'A') {
                const href = el.getAttribute('href') ?? '#';
                el.setAttribute('href', href);

                if (el.getAttribute('target') === '_blank') {
                    el.setAttribute('rel', 'noopener noreferrer');
                }

                ensureRichTextLinkClasses(el);
            }

            walk(el);
        });
    };

    walk(template.content);

    return template.innerHTML.trim() || '<p></p>';
}

/**
 * @param {object} component
 * @returns {string}
 */
export function readComponentHtml(component) {
    if (! component) {
        return '<p></p>';
    }

    const el = component.getEl?.();

    if (el?.innerHTML != null && String(el.innerHTML).trim() !== '') {
        return sanitizeRichTextHtml(el.innerHTML);
    }

    const children = [...(component.components?.() ?? [])];

    if (children.length > 0) {
        return sanitizeRichTextHtml(
            children.map((child) => {
                if (child.get?.('type') === 'textnode') {
                    return String(child.get('content') ?? '');
                }

                return child.toHTML?.() ?? '';
            }).join(''),
        );
    }

    return sanitizeRichTextHtml(String(component.get?.('content') ?? '<p></p>'));
}

/**
 * @param {object} component
 * @param {string} html
 * @param {object|null} [editor]
 */
export function writeComponentHtml(component, html, editor = null) {
    if (! component) {
        return;
    }

    // Always write onto the Rich Text host — never an inner paragraph.
    const host = findRichTextHost(component) ?? component;
    const safe = sanitizeRichTextHtml(html);

    const run = () => {
        // Replace children in place. Avoid keepRichTextSelection here — re-selecting
        // on every keystroke races chrome-shell component:add and can leak nodes
        // into the page-content slot (before the footer).
        host.components(safe);
        lockRichTextChildren(host);
    };

    if (! editor) {
        run();

        return;
    }

    const depth = Number(editor.__voodbuilderSettingsChangeDepth ?? 0);
    editor.__voodbuilderSettingsChangeDepth = depth + 1;
    editor.__voodbuilderSettingsChange = true;
    editor.__voodbuilderRichTextWriting = true;
    editor.__voodbuilderBulkStructureUpdate = true;

    try {
        run();
    } finally {
        const next = Number(editor.__voodbuilderSettingsChangeDepth ?? 1) - 1;
        editor.__voodbuilderSettingsChangeDepth = next;

        if (next <= 0) {
            editor.__voodbuilderSettingsChange = false;
            delete editor.__voodbuilderSettingsChangeDepth;
        }

        // Keep bulk/writing flags through pending chrome component:add rAFs.
        window.requestAnimationFrame(() => {
            window.requestAnimationFrame(() => {
                editor.__voodbuilderRichTextWriting = false;
                editor.__voodbuilderBulkStructureUpdate = false;
            });
        });
    }
}

function toolbarButton({ title, label, onClick, active = false }) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'voodbuilder-editor-rte-toolbar__btn';
    btn.title = title;
    btn.setAttribute('aria-label', title);
    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
    btn.innerHTML = label;
    btn.addEventListener('mousedown', (event) => {
        event.preventDefault();
    });
    btn.addEventListener('click', (event) => {
        event.preventDefault();
        onClick?.();
    });

    return btn;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function findContentEditableAnchor(root) {
    const selection = window.getSelection?.();
    const start = selection?.anchorNode ?? selection?.focusNode ?? null;

    if (! start) {
        return null;
    }

    let current = start.nodeType === Node.TEXT_NODE ? start.parentNode : start;

    while (current && current !== root) {
        if (String(current.nodeName ?? '').toUpperCase() === 'A') {
            return /** @type {HTMLAnchorElement} */ (current);
        }

        current = current.parentNode;
    }

    return null;
}

/**
 * @param {HTMLElement} root
 * @param {'left'|'center'|'right'} align
 */
function applyTextAlign(root, align) {
    const selection = window.getSelection?.();
    const start = selection?.anchorNode ?? null;

    if (! start) {
        return;
    }

    let node = start.nodeType === Node.TEXT_NODE ? start.parentElement : start;

    if (! (node instanceof Element) || ! root.contains(node)) {
        return;
    }

    const block = node.closest('p, div, li, h1, h2, h3, h4, h5, h6, blockquote');

    if (! block || ! root.contains(block)) {
        return;
    }

    ALIGN_CLASSES.forEach((token) => block.classList.remove(token));
    block.classList.add(`text-${align}`);
}

function toolbarSeparator() {
    const sep = document.createElement('span');
    sep.className = 'voodbuilder-editor-rte-toolbar__sep';
    sep.setAttribute('aria-hidden', 'true');

    return sep;
}

/**
 * @param {{
 *   value?: string,
 *   labels?: Record<string, string>,
 *   editor?: object|null,
 *   component?: object|null,
 *   onChange?: (html: string) => void,
 * }} args
 */
export function createLightRichTextEditor({ value = '<p></p>', labels = {}, editor = null, component = null, onChange } = {}) {
    const root = document.createElement('div');
    root.className = 'voodbuilder-editor-rte';
    root.setAttribute('data-voodbuilder-light-rte', '');

    let mode = 'visual';
    let html = sanitizeRichTextHtml(value);

    const modeRow = document.createElement('div');
    modeRow.className = 'voodbuilder-editor-rte__modes';

    const visualBtn = document.createElement('button');
    visualBtn.type = 'button';
    visualBtn.className = 'voodbuilder-editor-rte__mode is-active';
    visualBtn.textContent = labels.richTextModeVisual ?? 'Visual';

    const codeBtn = document.createElement('button');
    codeBtn.type = 'button';
    codeBtn.className = 'voodbuilder-editor-rte__mode';
    codeBtn.textContent = labels.richTextModeCode ?? 'Code';

    modeRow.append(visualBtn, codeBtn);

    // Both surfaces edit the same field, so they carry the same name. Without it the
    // visual surface announces itself as an unnamed text box, and the code surface as an
    // unnamed multiline field.
    const fieldName = labels.richTextSettingsTitle ?? 'Rich text';

    const toolbar = document.createElement('div');
    toolbar.className = 'voodbuilder-editor-rte-toolbar';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', fieldName);

    const surface = document.createElement('div');
    surface.className = 'voodbuilder-editor-rte__surface';

    const visual = document.createElement('div');
    visual.className = 'voodbuilder-editor-rte__visual';
    visual.contentEditable = 'true';
    visual.spellcheck = true;
    visual.setAttribute('role', 'textbox');
    visual.setAttribute('aria-multiline', 'true');
    visual.setAttribute('aria-label', fieldName);
    visual.innerHTML = html;

    const code = document.createElement('textarea');
    code.className = 'voodbuilder-editor-rte__code';
    code.hidden = true;
    code.setAttribute('aria-label', fieldName);
    code.value = html;
    code.spellcheck = false;

    const emit = () => {
        html = mode === 'visual'
            ? sanitizeRichTextHtml(visual.innerHTML)
            : sanitizeRichTextHtml(code.value);
        onChange?.(html);
    };

    const syncFromVisual = () => {
        html = sanitizeRichTextHtml(visual.innerHTML);
        code.value = html;
        onChange?.(html);
    };

    const syncFromCode = () => {
        html = sanitizeRichTextHtml(code.value);
        visual.innerHTML = html;
        onChange?.(html);
    };

    const exec = (command, valueArg = null) => {
        visual.focus();

        const selection = window.getSelection?.();
        if (! selection || selection.rangeCount === 0) {
            syncFromVisual();
            return;
        }

        const range = selection.getRangeAt(0);

        if (! range || ! visual.contains(range.commonAncestorContainer)) {
            syncFromVisual();
            return;
        }

        const unwrapAnchor = () => {
            const anchorNode = selection.anchorNode ?? null;
            const anchorEl = anchorNode instanceof Element
                ? anchorNode.closest?.('a')
                : anchorNode?.parentElement?.closest?.('a');

            if (! anchorEl) {
                return;
            }

            const parent = anchorEl.parentNode;

            if (! parent) {
                return;
            }

            while (anchorEl.firstChild) {
                parent.insertBefore(anchorEl.firstChild, anchorEl);
            }

            anchorEl.remove();
        };

        const wrapRange = (tagName) => {
            const wrapper = document.createElement(tagName);

            if (range.collapsed) {
                // Insert an empty wrapper so the caret stays usable.
                wrapper.appendChild(document.createTextNode(''));
                range.insertNode(wrapper);

                const caret = document.createRange();
                const textNode = wrapper.firstChild;

                if (textNode) {
                    caret.setStart(textNode, 0);
                    caret.collapse(true);
                } else {
                    caret.selectNodeContents(wrapper);
                    caret.collapse(false);
                }

                selection.removeAllRanges?.();
                selection.addRange?.(caret);

                return;
            }

            const contents = range.extractContents();
            wrapper.appendChild(contents);
            range.insertNode(wrapper);

            const caret = document.createRange();
            caret.selectNodeContents(wrapper);
            caret.collapse(false);

            selection.removeAllRanges?.();
            selection.addRange?.(caret);
        };

        const insertList = (listTag) => {
            const contents = range.extractContents();
            const list = document.createElement(listTag);
            const li = document.createElement('li');

            li.appendChild(contents);
            list.appendChild(li);
            range.insertNode(list);

            const caret = document.createRange();
            caret.setStartAfter(list);
            caret.collapse(true);

            selection.removeAllRanges?.();
            selection.addRange?.(caret);
        };

        switch (command) {
            case 'bold':
                wrapRange('strong');
                break;
            case 'italic':
                wrapRange('em');
                break;
            case 'underline':
                wrapRange('u');
                break;
            case 'unlink':
                unwrapAnchor();
                break;
            case 'insertUnorderedList':
                insertList('ul');
                break;
            case 'insertOrderedList':
                insertList('ol');
                break;
            default:
                // No-op: we intentionally avoid deprecated document.execCommand.
                break;
        }

        syncFromVisual();
    };

    const setMode = (next) => {
        if (next === mode) {
            return;
        }

        if (mode === 'visual') {
            html = sanitizeRichTextHtml(visual.innerHTML);
            code.value = html;
        } else {
            html = sanitizeRichTextHtml(code.value);
            visual.innerHTML = html;
        }

        mode = next;
        const isVisual = mode === 'visual';
        visual.hidden = ! isVisual;
        code.hidden = isVisual;
        toolbar.hidden = ! isVisual;
        visualBtn.classList.toggle('is-active', isVisual);
        codeBtn.classList.toggle('is-active', ! isVisual);
    };

    visualBtn.addEventListener('click', () => setMode('visual'));
    codeBtn.addEventListener('click', () => setMode('code'));

    toolbar.append(
        toolbarButton({
            title: labels.richTextBold ?? 'Bold',
            label: '<strong>B</strong>',
            onClick: () => exec('bold'),
        }),
        toolbarButton({
            title: labels.richTextItalic ?? 'Italic',
            label: '<em>I</em>',
            onClick: () => exec('italic'),
        }),
        toolbarButton({
            title: labels.richTextUnderline ?? 'Underline',
            label: '<span style="text-decoration:underline">U</span>',
            onClick: () => exec('underline'),
        }),
        toolbarButton({
            title: labels.richTextLink ?? 'Link',
            label: lucideIcon('link', 14),
            onClick: async () => {
                const selection = window.getSelection?.();
                const text = String(selection?.toString?.() ?? '');

                if (! text && ! findContentEditableAnchor(visual)) {
                    return;
                }

                const anchor = findContentEditableAnchor(visual);
                const linkState = readAnchorLinkState(anchor);
                const snapshotRange = selection?.rangeCount
                    ? selection.getRangeAt(0).cloneRange()
                    : null;

                const result = await linkPickerDialog({
                    editor,
                    labels,
                    title: labels.rteLinkPromptTitle ?? 'Link',
                    message: labels.rteLinkPromptMessage
                        ?? 'Choose how this text should link (same options as buttons).',
                    defaultLinkType: linkState.linkType,
                    defaultHref: linkState.href || 'https://',
                    defaultLinkRef: linkState.linkRef,
                    defaultTarget: linkState.target,
                    allowRemove: linkState.isLink,
                });

                if (result === null) {
                    return;
                }

                visual.focus();

                if (snapshotRange) {
                    try {
                        const sel = window.getSelection?.();
                        sel?.removeAllRanges?.();
                        sel?.addRange?.(snapshotRange);
                    } catch {
                        // Selection may be lost after the modal — fall back to createLink/unlink.
                    }
                }

                if (result.remove) {
                    exec('unlink');

                    return;
                }

                if (anchor && linkState.isLink) {
                    applyRichTextLinkAttrs(anchor, result);
                    syncFromVisual();

                    return;
                }

                const open = buildAnchorOpenTag(result);
                insertHtmlInlineAtCaret(visual, `${open}${escapeHtml(text)}</a>`);
                syncFromVisual();
            },
        }),
    );

    const dynamicBtn = toolbarButton({
        title: labels.richTextDynamicData ?? labels.makeDynamic ?? 'Dynamic data',
        label: lucideIcon('zap', 14),
        onClick: () => {
            const selection = window.getSelection?.();
            const snapshotRange = selection?.rangeCount
                ? selection.getRangeAt(0).cloneRange()
                : null;

            openRichTextDynamicTagPicker({
                editor,
                component,
                anchorEl: dynamicBtn,
                labels,
                onInsert: (tagHtml) => {
                    visual.focus();

                    if (snapshotRange) {
                        try {
                            const sel = window.getSelection?.();
                            sel?.removeAllRanges?.();
                            sel?.addRange?.(snapshotRange);
                        } catch {
                            // Selection may be gone after the menu closes.
                        }
                    }

                    insertHtmlInlineAtCaret(visual, tagHtml);
                    syncFromVisual();
                },
            });
        },
    });
    dynamicBtn.setAttribute('data-vb-rte-dynamic', '');

    toolbar.append(
        dynamicBtn,
        toolbarSeparator(),
        toolbarButton({
            title: labels.richTextAlignLeft ?? 'Align left',
            label: lucideIcon('align-left', 14),
            onClick: () => {
                visual.focus();
                applyTextAlign(visual, 'left');
                syncFromVisual();
            },
        }),
        toolbarButton({
            title: labels.richTextAlignCenter ?? 'Align center',
            label: lucideIcon('align-center', 14),
            onClick: () => {
                visual.focus();
                applyTextAlign(visual, 'center');
                syncFromVisual();
            },
        }),
        toolbarButton({
            title: labels.richTextAlignRight ?? 'Align right',
            label: lucideIcon('align-right', 14),
            onClick: () => {
                visual.focus();
                applyTextAlign(visual, 'right');
                syncFromVisual();
            },
        }),
        toolbarSeparator(),
        toolbarButton({
            title: labels.richTextBulletList ?? 'Bullet list',
            label: lucideIcon('list', 14),
            onClick: () => exec('insertUnorderedList'),
        }),
        toolbarButton({
            title: labels.richTextNumberList ?? 'Numbered list',
            label: lucideIcon('list-ordered', 14),
            onClick: () => exec('insertOrderedList'),
        }),
    );

    visual.addEventListener('input', syncFromVisual);
    visual.addEventListener('blur', syncFromVisual);
    visual.addEventListener('paste', (event) => {
        event.preventDefault();
        const pasted = event.clipboardData?.getData('text/html')
            || event.clipboardData?.getData('text/plain')
            || '';
        const safe = sanitizeRichTextHtml(
            pasted.includes('<') ? pasted : pasted.replace(/\n/g, '<br>'),
        );
        insertHtmlInlineAtCaret(visual, safe);
        syncFromVisual();
    });

    code.addEventListener('change', syncFromCode);
    code.addEventListener('blur', syncFromCode);

    surface.append(visual, code);
    root.append(modeRow, toolbar, surface);

    return {
        root,
        getHtml: () => (mode === 'visual'
            ? sanitizeRichTextHtml(visual.innerHTML)
            : sanitizeRichTextHtml(code.value)),
        setHtml: (next) => {
            html = sanitizeRichTextHtml(next);
            visual.innerHTML = html;
            code.value = html;
        },
        destroy: () => {
            root.remove();
        },
    };
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderRichTextSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component) {
        return false;
    }

    const key = String(component.cid ?? component.getId?.() ?? '');
    const existing = mount.querySelector('[data-voodbuilder-rich-text-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;
        lockRichTextChildren(component);
        keepRichTextSelection(editor, component);

        return true;
    }

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const { section, fields } = createFormSection(labels.richTextSettingsTitle ?? 'Rich text');
    section.setAttribute('data-voodbuilder-rich-text-settings', '');
    section.setAttribute('data-component-key', key);

    const editorUi = createLightRichTextEditor({
        value: readComponentHtml(component),
        labels,
        editor,
        component,
        onChange: (html) => {
            writeComponentHtml(component, html, editor);
        },
    });

    fields.appendChild(editorUi.root);
    mount.appendChild(section);
    lockRichTextChildren(component);
    keepRichTextSelection(editor, component);

    return true;
}

/**
 * @param {{ mount: HTMLElement, traitsMount?: HTMLElement|null, component: object, editor: object, labels?: object }} args
 */
export function renderBasicTextSettings({ mount, traitsMount = null, component, editor, labels = {} }) {
    if (! mount || ! component) {
        return false;
    }

    const key = String(component.cid ?? component.getId?.() ?? '');
    const existing = mount.querySelector('[data-voodbuilder-basic-text-settings]');

    if (existing && existing.getAttribute('data-component-key') === key) {
        traitsMount?.classList.add('hidden');
        mount.hidden = false;

        return true;
    }

    traitsMount?.classList.add('hidden');
    traitsMount?.replaceChildren?.();
    mount.hidden = false;
    mount.replaceChildren();

    const { section, fields } = createFormSection(labels.basicTextSettingsTitle ?? 'Basic text');
    section.setAttribute('data-voodbuilder-basic-text-settings', '');
    section.setAttribute('data-component-key', key);

    const hint = document.createElement('p');
    hint.className = 'voodbuilder-editor-form-hint';
    hint.textContent = labels.basicTextHint
        ?? 'Plain text — edit directly on the canvas. No formatting toolbar.';

    fields.appendChild(hint);
    mount.appendChild(section);

    return true;
}
