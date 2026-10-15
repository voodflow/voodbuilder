<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Shared Tailwind class rules for dynamic block roots and content-width shells.
 *
 * Two questions come up on both the render path
 * ({@see EditorDynamicBlockRenderer}) and the save path
 * ({@see EditorImportedTailwindSupport}):
 *
 * 1. Is this node an author content *measure*, or is it the block's own layout?
 *    An editor-era bug stamped the content-width shell markers
 *    (`voodbuilder-editor-container`, `data-voodbuilder-role="content"`,
 *    `mx-auto w-full max-w-[80rem]`) onto the inner preview layout row of a dynamic
 *    block, so `flex … sm:justify-between` rows have to be recognized as layout, not
 *    measure, or the whole block gets wrapped in its own flex row on publish.
 * 2. Which author root classes may be forwarded onto the published component root?
 *    Template classes stay untouched; editor plumbing must never leak.
 */
final class EditorBlockClasses
{
    /**
     * Exact utility names that only ever appear on a layout node.
     *
     * @var list<string>
     */
    protected const LAYOUT_UTILITY_NAMES = ['flex', 'inline-flex', 'grid', 'inline-grid', 'grow', 'shrink'];

    /**
     * Utility prefixes that only ever appear on a layout node.
     *
     * @var list<string>
     */
    protected const LAYOUT_UTILITY_PREFIXES = [
        'flex-',
        'grid-',
        'justify-',
        'items-',
        'content-',
        'self-',
        'place-',
        'gap-',
        'space-x-',
        'space-y-',
        'order-',
        'col-',
        'row-',
        'basis-',
    ];

    /**
     * Editor plumbing that must never be forwarded onto a published block root.
     *
     * `voodbuilder-editor-section` / `voodbuilder-editor-dynamic` make the publish
     * healer (`EditorImportedTailwindSupport::healExistingEditorSections()`) treat a
     * mounted component as a section that still needs a content wrapper, and it would
     * then wrap the component's own markup.
     *
     * @var list<string>
     */
    protected const ROOT_PLUMBING_CLASSES = ['container', 'voodbuilder-pasted-component'];

    /** @var list<string> */
    protected const ROOT_PLUMBING_CLASS_PREFIXES = ['voodbuilder-editor-'];

    /**
     * True when the class list describes a layout node rather than a measure shell.
     */
    public static function hasLayoutUtilities(string $classList): bool
    {
        foreach (self::classTokens($classList) as $token) {
            if (self::isLayoutUtility($token)) {
                return true;
            }
        }

        return false;
    }

    public static function isLayoutUtility(string $token): bool
    {
        $bare = self::stripVariants($token);

        if (in_array($bare, self::LAYOUT_UTILITY_NAMES, true)) {
            return true;
        }

        foreach (self::LAYOUT_UTILITY_PREFIXES as $prefix) {
            if (str_starts_with($bare, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public static function isRootPlumbingClass(string $token): bool
    {
        if (in_array($token, self::ROOT_PLUMBING_CLASSES, true)) {
            return true;
        }

        foreach (self::ROOT_PLUMBING_CLASS_PREFIXES as $prefix) {
            if (str_starts_with($token, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Merge the author's root utilities onto the regenerated component root.
     *
     * The regenerated Blade markup owns its classes: its tokens stay in place and are
     * never dropped, so a template change is never pinned by stale saved classes. The
     * author's extra tokens (Style Manager spacing / radius / border / paint,
     * hand-written utilities) are appended, which is what makes a root edit reach the
     * published page. Editor plumbing is skipped.
     */
    public static function mergeAuthorRootClasses(string $authorClasses, string $freshClasses): string
    {
        $merged = [];
        $seen = [];

        foreach ([$freshClasses, $authorClasses] as $source) {
            foreach (self::classTokens($source) as $token) {
                if ($seen[$token] ?? false) {
                    continue;
                }

                if (self::isRootPlumbingClass($token)) {
                    continue;
                }

                $seen[$token] = true;
                $merged[] = $token;
            }
        }

        return implode(' ', $merged);
    }

    /**
     * @return list<string>
     */
    protected static function classTokens(string $classList): array
    {
        return preg_split('/\s+/', trim($classList), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Drop Tailwind variant segments: `sm:flex-row` → `flex-row`, `dark:lg:my-20` → `my-20`.
     *
     * Only `[a-z0-9_-]+:` segments are stripped, so an arbitrary value that contains a
     * colon (`bg-[url(https://…)]`) is left alone — it never looks like a layout anyway.
     */
    protected static function stripVariants(string $token): string
    {
        $bare = preg_replace('/^(?:[a-z0-9_-]+:)+/i', '', $token);

        return is_string($bare) && $bare !== '' ? $bare : $token;
    }
}
