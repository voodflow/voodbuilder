/**
 * Customize GrapesJS inline RTE (toolbar when selecting text).
 *
 * Default "Link" wraps the selection in `<a href="">` then tries to select it
 * so you can edit href in Traits — but our inspector promotes selection to the
 * block root, so the URL field never appears. Use the same link picker as buttons.
 */

import {
    applyRichTextLinkAttrs,
    buildAnchorOpenTag,
    linkPickerDialog,
    readAnchorLinkState,
} from './link-picker-dialog.js';
import { lucideIcon } from './editor-icons.js';
import { configurePlainTextRte } from './text-elements.js';

const SELECT_ME_ATTR = 'data-selectme';

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
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

    const anchor = findSelectionAnchor(rte);
    const linkState = readAnchorLinkState(anchor);

    return {
        text: String(rte.selection?.() ?? ''),
        range: selection.getRangeAt(0).cloneRange(),
        anchor,
        ...linkState,
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

            void linkPickerDialog({
                editor,
                labels,
                title: labels.rteLinkPromptTitle ?? 'Link',
                message: labels.rteLinkPromptMessage
                    ?? 'Choose how this text should link (same options as buttons).',
                defaultLinkType: snapshot.linkType,
                defaultHref: snapshot.href || 'https://',
                defaultLinkRef: snapshot.linkRef,
                defaultTarget: snapshot.target,
                allowRemove: snapshot.isLink,
            }).then((result) => {
                if (result === null) {
                    return;
                }

                restoreSelection(rte, snapshot);

                if (result.remove) {
                    if (snapshot.isLink) {
                        rte.exec('unlink');
                    }

                    return;
                }

                if (snapshot.isLink) {
                    applyRichTextLinkAttrs(findSelectionAnchor(rte) ?? snapshot.anchor, result);

                    return;
                }

                rte.insertHTML(
                    `${buildAnchorOpenTag(result, SELECT_ME_ATTR)}${escapeHtml(snapshot.text)}</a>`,
                    { select: true },
                );
            });
        },
    });

    configurePlainTextRte(editor);
}
