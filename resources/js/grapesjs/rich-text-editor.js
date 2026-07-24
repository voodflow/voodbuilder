/**
 * Customize GrapesJS inline RTE (toolbar when selecting text).
 *
 * Default "Link" wraps the selection in `<a href="">` then tries to select it
 * so you can edit href in Traits — but our inspector promotes selection to the
 * block root, so the URL field never appears. Prompt for the URL instead.
 */

import { promptDialog } from './editor-dialog.js';
import { lucideIcon } from './editor-icons.js';

const SELECT_ME_ATTR = 'data-selectme';

function escapeAttr(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;');
}

function selectionIsInsideTag(rte, tagName = 'A') {
    const selection = rte?.selection?.();

    if (! selection) {
        return false;
    }

    const tag = String(tagName).toUpperCase();
    const parents = [selection.anchorNode, selection.focusNode]
        .filter(Boolean)
        .map((node) => (node.nodeType === Node.TEXT_NODE ? node.parentNode : node));

    return parents.some((node) => {
        let current = node;

        while (current && current !== rte.el) {
            if (String(current.nodeName ?? '').toUpperCase() === tag) {
                return true;
            }

            current = current.parentNode;
        }

        return false;
    });
}

function findSelectionAnchor(rte) {
    const selection = rte?.selection?.();
    const start = selection?.anchorNode ?? selection?.focusNode ?? null;

    if (! start) {
        return null;
    }

    let current = start.nodeType === Node.TEXT_NODE ? start.parentNode : start;

    while (current && current !== rte.el) {
        if (String(current.nodeName ?? '').toUpperCase() === 'A') {
            return current;
        }

        current = current.parentNode;
    }

    return null;
}

function captureSelectionSnapshot(rte) {
    const doc = rte?.doc;
    const selection = doc?.getSelection?.();

    if (! selection || selection.rangeCount === 0) {
        return null;
    }

    return {
        text: String(rte.selection?.() ?? ''),
        range: selection.getRangeAt(0).cloneRange(),
        href: findSelectionAnchor(rte)?.getAttribute?.('href') ?? '',
        isLink: selectionIsInsideTag(rte, 'A'),
    };
}

function restoreSelection(rte, snapshot) {
    if (! snapshot?.range || ! rte?.doc) {
        return false;
    }

    const selection = rte.doc.getSelection?.();

    if (! selection) {
        return false;
    }

    try {
        selection.removeAllRanges();
        selection.addRange(snapshot.range);
        rte.el?.focus?.();

        return true;
    } catch {
        return false;
    }
}

/**
 * @param {object} editor
 * @param {Record<string, string>} [labels]
 */
export function configureRichTextEditor(editor, labels = {}) {
    if (! editor?.RichTextEditor || editor.__voodbuilderRteConfigured) {
        return;
    }

    editor.__voodbuilderRteConfigured = true;

    const wrapTitle = labels.rteWrapTitle
        ?? 'Wrap for styles — wraps selection in a <span> so you can style only that part';
    const linkTitle = labels.rteLinkTitle ?? 'Link';
    const linkPromptTitle = labels.rteLinkPromptTitle ?? 'Link URL';
    const linkPromptMessage = labels.rteLinkPromptMessage
        ?? 'Enter the URL for the selected text. Leave empty to remove the link.';
    const linkPlaceholder = labels.rteLinkPlaceholder ?? 'https:// or /page';

    // GrapesJS RTE requires `icon` as a string or DOM Node; missing icon → appendChild crash
    // and aborts editor boot before Layout/Basic blocks register.
    editor.RichTextEditor.add('wrap', {
        icon: lucideIcon('box-select', 14),
        attributes: { title: wrapTitle },
        result: (rte) => {
            const selected = String(rte.selection?.() ?? '');

            if (selected === '') {
                return;
            }

            rte.insertHTML(`<span>${selected}</span>`);
        },
    });

    editor.RichTextEditor.add('link', {
        icon: lucideIcon('link', 14),
        attributes: {
            title: linkTitle,
        },
        result: (rte) => {
            const snapshot = captureSelectionSnapshot(rte);

            if (! snapshot || snapshot.text === '') {
                return;
            }

            void promptDialog({
                title: linkPromptTitle,
                message: linkPromptMessage,
                defaultValue: snapshot.href || 'https://',
                placeholder: linkPlaceholder,
                labels,
            }).then((url) => {
                if (url === null) {
                    return;
                }

                restoreSelection(rte, snapshot);

                const trimmed = String(url).trim();

                if (trimmed === '') {
                    if (snapshot.isLink) {
                        rte.exec('unlink');
                    }

                    return;
                }

                if (snapshot.isLink) {
                    const anchor = findSelectionAnchor(rte);

                    if (anchor) {
                        anchor.setAttribute('href', trimmed);
                    }

                    return;
                }

                rte.insertHTML(
                    `<a href="${escapeAttr(trimmed)}" ${SELECT_ME_ATTR}>${snapshot.text}</a>`,
                    { select: true },
                );
            });
        },
    });
}
