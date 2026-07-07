<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use DOMDocument;
use DOMElement;
use DOMXPath;

final class GrapesJsPastedComponentNormalizer
{
    /**
     * @return array{html: string, css: ?string}
     */
    public static function normalize(string $input): array
    {
        $input = trim($input);

        if ($input === '') {
            return ['html' => '', 'css' => null];
        }

        $cssParts = [];
        $html = self::extractStyleTags($input, $cssParts);
        $html = self::stripScripts($html);
        $html = self::extractDocumentBody($html);
        $html = self::stripDocumentShell($html);
        $html = GrapesJsImportedTailwindSupport::prepareHtml($html);
        $html = self::wrapMultipleRoots($html);
        $html = self::uniquifySvgIds($html);
        $html = self::stripEmbeddableMedia($html);
        $html = VoodbuilderThemeTokenMigrator::migrateHtml($html);
        $html = GrapesJsHtmlSanitizer::sanitize(trim($html));

        $importedCss = self::compileTailwindCss($html);

        if ($importedCss !== '') {
            $importedCss = VoodbuilderThemeTokenMigrator::migrateCss($importedCss);
            $cssParts[] = $importedCss;
        }

        $css = $cssParts !== [] ? trim(implode("\n\n", array_map(
            static fn (string $chunk): string => VoodbuilderThemeTokenMigrator::migrateCss($chunk),
            $cssParts,
        ))) : null;

        return [
            'html' => $html,
            'css' => $css !== '' ? $css : null,
        ];
    }

    public static function compileTailwindCss(string $html): string
    {
        return self::compileTailwindCssForScope($html, 'component');
    }

    public static function compilePageTailwindCss(string $html): string
    {
        return self::compileTailwindCssForScope($html, 'page');
    }

    private static function compileTailwindCssForScope(string $html, string $scope): string
    {
        $compiled = GrapesJsComponentTailwindCompiler::compile($html, $scope);

        if ($compiled === null) {
            $compiled = $scope === 'page'
                ? GrapesJsImportedTailwindCssBuilder::buildForPage($html)
                : GrapesJsImportedTailwindCssBuilder::build($html);
        } elseif ($scope === 'component') {
            $base = GrapesJsImportedTailwindCssBuilder::baseStyles();
            $compiled = $base !== '' ? trim($base."\n\n".$compiled) : $compiled;
        }

        return trim($compiled);
    }

    public static function mergeCss(?string $manualCss, ?string $autoCss): ?string
    {
        $manualCss = filled($manualCss) ? trim($manualCss) : '';
        $autoCss = filled($autoCss) ? trim($autoCss) : '';

        if ($manualCss === '') {
            return $autoCss !== '' ? $autoCss : null;
        }

        if ($autoCss === '' || str_contains($manualCss, $autoCss)) {
            return $manualCss;
        }

        return trim($manualCss."\n\n".$autoCss);
    }

    public static function resolvedCssForStoredHtml(
        string $html,
        ?string $storedCss,
        ?string $storedChecksum = null,
    ): string {
        $html = VoodbuilderThemeTokenMigrator::migrateHtml($html);
        $storedCss = filled($storedCss) ? trim((string) $storedCss) : '';
        $currentChecksum = self::htmlChecksum($html);

        if ($storedCss !== '' && self::storedCssIsCurrent($html, $storedCss, $storedChecksum, $currentChecksum)) {
            return self::publishedCssForStoredHtml($html, $storedCss);
        }

        $compiled = self::compileTailwindCss($html);

        if ($compiled !== '') {
            $manualCss = self::manualCssFromStoredComponentCss($storedCss);
            $css = self::mergeCss($manualCss !== '' ? $manualCss : null, $compiled);

            return trim(VoodbuilderThemeTokenMigrator::migrateComponentCss((string) $css)."\n\n".self::componentThemeTokenBridgeCss());
        }

        if ($storedCss === '') {
            return '';
        }

        return self::publishedCssForStoredHtml($html, $storedCss);
    }

    /**
     * CSS for public page render — never invokes Tailwind compilation.
     */
    public static function publishedCssForStoredHtml(string $html, ?string $storedCss): string
    {
        $storedCss = filled($storedCss) ? trim((string) $storedCss) : '';

        if ($storedCss === '') {
            return '';
        }

        return trim(VoodbuilderThemeTokenMigrator::migrateComponentCss($storedCss)."\n\n".self::componentThemeTokenBridgeCss());
    }

    /**
     * CSS for page-level GrapesJS styles (non-component utilities).
     */
    public static function resolvePublishedPageCss(string $html, ?string $storedCss): string
    {
        $storedCss = filled($storedCss) ? trim((string) $storedCss) : '';
        $pageHtml = GrapesJsComponentPageHtml::htmlForPageTailwindCompile($html);
        $needsRecompile = $storedCss === ''
            || self::storedCssIsCorrupted($storedCss)
            || self::pageCssIncludesTailwindPreflight($storedCss)
            || self::pageCssMissingThemeVariables($storedCss)
            || ($pageHtml !== '' && self::htmlHasTailwindUtilitiesMissingFromCss($pageHtml, $storedCss));

        if (! $needsRecompile) {
            return GrapesJsCssSanitizer::sanitize(
                VoodbuilderThemeTokenMigrator::migratePublishedPageCss(
                    self::stripTailwindPreflightFromPageCss($storedCss),
                ),
            );
        }

        return self::compileAndMergePublishedPageCss(
            $pageHtml,
            self::manualPageCssFromStoredCss($storedCss),
        );
    }

    /**
     * Save path: always recompile Tailwind from current page HTML and keep only GrapesJS
     * composer / custom rules from the submitted CSS — never stale utility bundles from getCss().
     */
    public static function resolvePublishedPageCssForSave(string $html, ?string $storedCss): string
    {
        $pageHtml = GrapesJsComponentPageHtml::htmlForPageTailwindCompile($html);

        return self::compileAndMergePublishedPageCss(
            $pageHtml,
            self::manualPageCssFromStoredCss($storedCss),
        );
    }

    private static function compileAndMergePublishedPageCss(string $pageHtml, ?string $manualCss): string
    {
        $manualCss = filled($manualCss) ? trim((string) $manualCss) : '';

        if ($pageHtml === '') {
            return $manualCss !== ''
                ? GrapesJsCssSanitizer::sanitize(VoodbuilderThemeTokenMigrator::migratePublishedPageCss($manualCss))
                : '';
        }

        $compiled = self::compilePageTailwindCss($pageHtml);

        if ($compiled === '') {
            return $manualCss !== ''
                ? GrapesJsCssSanitizer::sanitize(VoodbuilderThemeTokenMigrator::migratePublishedPageCss($manualCss))
                : '';
        }

        $merged = self::mergeCss($manualCss !== '' ? $manualCss : null, $compiled);

        return GrapesJsCssSanitizer::sanitize(
            VoodbuilderThemeTokenMigrator::migratePublishedPageCss(
                self::stripTailwindPreflightFromPageCss((string) $merged),
            ),
        );
    }

    public static function pageCssIncludesTailwindPreflight(string $css): bool
    {
        $css = trim($css);

        if ($css === '') {
            return false;
        }

        return (str_contains($css, 'border-radius: 0') && str_contains($css, '::file-selector-button'))
            || (str_contains($css, 'box-sizing: border-box') && preg_match('/^\*[^{]*\{[^}]*box-sizing\s*:\s*border-box/m', $css) === 1)
            || (str_contains($css, 'html, :host') && str_contains($css, '-webkit-text-size-adjust'))
            || (str_contains($css, 'tab-size: 4') && str_contains($css, 'font-family: var(--default-font-family'));
    }

    /**
     * JIT page CSS references --spacing and other theme tokens stripped with @layer theme.
     */
    public static function pageCssMissingThemeVariables(string $css): bool
    {
        $css = trim($css);

        if ($css === '' || ! str_contains($css, 'var(--spacing)')) {
            return false;
        }

        return ! preg_match('/:root[^{]*\{[^}]*--spacing\s*:/s', $css);
    }

    public static function stripTailwindPreflightFromPageCss(string $css): string
    {
        $css = trim($css);

        if ($css === '' || ! self::pageCssIncludesTailwindPreflight($css)) {
            return $css;
        }

        $patterns = [
            '/\*[^{]*\{[^}]*box-sizing\s*:\s*border-box[^}]*\}\s*/s',
            '/html,\s*:host[^{]*\{[^}]*\}\s*/s',
            '/:root[^{]*\{[^}]*--font-sans[^}]*\}\s*/s',
            '/:host[^{]*\{[^}]*--font-sans[^}]*\}\s*/s',
            '/(?:^|[\n}])(?:html|body|hr|abbr|h[1-6]|a|b|strong|code|kbd|samp|pre|small|sub|sup|table|button|input|select|optgroup|textarea)[^{]*\{[^}]*\}\s*/ms',
            '/(?:button|input|select|optgroup|textarea|,|\s)+[^{]*\{[^}]*border-radius\s*:\s*0[^}]*\}\s*/s',
            '/button[^{]*\{[^}]*margin-inline-end[^}]*\}\s*/s',
            '/@layer\s+properties\s*;\s*/',
            '/@layer\s+theme,\s*base,\s*components,\s*utilities\s*;\s*/',
            '/\*,\s*::before,\s*::after,\s*::backdrop[^{]*\{[^}]*--tw-[^}]*\}\s*/s',
        ];

        foreach ($patterns as $pattern) {
            $css = preg_replace($pattern, '', $css) ?? $css;
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", $css) ?? $css);
    }

    /**
     * Non-Tailwind rules from GrapesJS getCss(): #id composer styles and custom class rules.
     * Strips utility bundles and @media blocks (regenerated on compile).
     */
    public static function manualPageCssFromStoredCss(?string $storedCss): string
    {
        $storedCss = trim((string) ($storedCss ?? ''));

        if ($storedCss === '') {
            return '';
        }

        $withoutMedia = preg_replace('/@media[^{]*\{(?:[^{}]++|\{(?:[^{}]++|\{[^{}]*\})*\})*\}/s', '', $storedCss) ?? $storedCss;

        if (! preg_match_all('/(?:^|[\n}])([^{}\n@]+)\{([^{}]*)\}/s', $withoutMedia, $matches, PREG_SET_ORDER)) {
            return self::grapesComposerRulesFromStoredCss($storedCss);
        }

        $kept = [];

        foreach ($matches as $match) {
            $selectors = trim($match[1]);
            $body = trim($match[2]);

            if ($selectors === '' || str_contains($selectors, '.voodbuilder-pasted-component')) {
                continue;
            }

            if ($body === '' || trim(preg_replace('/\s+/', '', $body) ?? '') === '') {
                continue;
            }

            if (self::shouldPreserveManualPageCssRule($selectors, $body)) {
                $kept[] = $selectors.' {'.$body.'}';
            }
        }

        return trim(implode("\n", $kept));
    }

    private static function shouldPreserveManualPageCssRule(string $selectors, string $body = ''): bool
    {
        if (trim($body) === '') {
            return false;
        }

        foreach (array_map('trim', explode(',', $selectors)) as $selector) {
            if ($selector === '') {
                continue;
            }

            if (str_contains($selector, '#')) {
                return true;
            }

            if (! str_contains($selector, '.')) {
                continue;
            }

            if (! preg_match_all('/\.((?:\\.|[^\s.#:[>+~,])+)/', $selector, $classMatches)) {
                continue;
            }

            foreach ($classMatches[1] as $className) {
                $className = str_replace('\\', '', ltrim($className, '!'));

                if (! self::isTailwindUtilityClassName($className)) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function isTailwindUtilityClassName(string $className): bool
    {
        $utilityPattern = '/^(?:[a-z][a-z0-9_-]*:)*-?(?:flex|grid|inline-flex|inline|block|hidden|contents|table|flow-root|list-item|absolute|relative|fixed|sticky|static|container|mx-|my-|mt-|mb-|ml-|mr-|w-|h-|min-w-|max-w-|min-h-|max-h-|size-|gap-|p-|px-|py-|pt-|pb-|pl-|pr-|m-|text-|bg-|rounded|shadow|aspect-|col-|row-|items-|justify-|self-|order-|space-|divide-|border-opacity|border-|ring-|outline-|opacity-|z-|top-|bottom-|left-|right-|inset-|object-|overflow-|truncate|whitespace-|leading-|font-|tracking-|underline|decoration-|backdrop-|transition|duration-|ease-|scale-|rotate-|translate-|skew-|origin-|fill-|stroke-|sr-only|not-sr-only|pointer-events-|select-|cursor-|align-|place-|content-|grow|shrink|basis-|from-|to-|via-|bg-vp-|text-vp-|antialiased|subpixel-antialiased|italic|not-italic|visible|invisible|collapse|isolate|box-|break-|hyphens-|list-|columns-|float-|clear-|overscroll-|scroll-|snap-|touch-|will-change-|accent-|caret-|field-sizing-)/i';

        return preg_match($utilityPattern, $className) === 1;
    }

    /**
     * GrapesJS Style Manager rules (#element-id) — excludes Tailwind utility bundles from getCss().
     */
    public static function grapesComposerRulesFromStoredCss(?string $storedCss): string
    {
        $storedCss = trim((string) ($storedCss ?? ''));

        if ($storedCss === '') {
            return '';
        }

        if (! preg_match_all('/#[^{]+\{[^{}]*\}/s', $storedCss, $matches)) {
            return '';
        }

        return trim(implode("\n", $matches[0]));
    }

    /**
     * Compile Tailwind utilities for markup saved inside a component instance on a page.
     * Instance HTML is not theme-migrated (same as public render inside components).
     */
    public static function compileCssForComponentInstanceHtml(string $html): ?string
    {
        $html = trim($html);

        if ($html === '') {
            return null;
        }

        $compiled = self::compileTailwindCss($html);

        if ($compiled === '') {
            return null;
        }

        return trim(VoodbuilderThemeTokenMigrator::migrateComponentCss($compiled));
    }

    public static function cssForDatabaseStorage(?string $css): ?string
    {
        $css = filled($css) ? trim((string) $css) : '';

        if ($css === '') {
            return null;
        }

        $css = str_replace(self::componentThemeTokenBridgeCss(), '', $css);

        return trim($css) !== '' ? trim($css) : null;
    }

    public static function cssForExport(?string $css, string $html = ''): ?string
    {
        $css = self::cssForDatabaseStorage($css);

        if ($css === null || $css === '') {
            return null;
        }

        $css = self::stripComponentRuntimeBaseStyles($css, $html);
        $css = trim(VoodbuilderThemeTokenMigrator::migrateComponentCss($css));

        return $css !== '' ? $css : null;
    }

    public static function stripComponentRuntimeBaseStyles(string $css, string $html): string
    {
        $needsHeaderLayout = preg_match('/<header\b[^>]*\babsolute\b/i', $html) === 1;
        $needsDialog = str_contains(strtolower($html), '<dialog');

        foreach (explode("\n", GrapesJsImportedTailwindCssBuilder::baseStyles()) as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (! $needsDialog && str_contains($line, 'dialog:not([open])')) {
                $css = str_replace($line, '', $css);

                continue;
            }

            if (! $needsHeaderLayout && (str_contains($line, 'header.absolute') || str_contains($line, 'min-height: 42rem'))) {
                $css = str_replace($line, '', $css);

                continue;
            }

            if (str_contains($line, '--color-primary:')) {
                $css = str_replace($line, '', $css);
            }
        }

        $css = preg_replace("/\n{3,}/", "\n\n", $css) ?? $css;

        return trim($css);
    }

    public static function persistCssFromCatalogResolution(
        array $resolved,
        ?string $incomingCss,
        ?string $normalizedAutoCss = null,
    ): ?string {
        $manualCss = filled($incomingCss)
            ? self::manualCssFromStoredComponentCss(trim((string) $incomingCss))
            : '';

        if (filled($resolved['cssToPersist'] ?? null)) {
            return self::mergeCss(
                $manualCss !== '' ? $manualCss : null,
                (string) $resolved['cssToPersist'],
            );
        }

        if (filled($incomingCss)) {
            return self::cssForDatabaseStorage((string) $incomingCss);
        }

        return filled($normalizedAutoCss) ? trim((string) $normalizedAutoCss) : null;
    }

    public static function htmlChecksum(string $html): string
    {
        return hash('sha256', VoodbuilderThemeTokenMigrator::migrateHtml($html));
    }

    public static function storedCssIsCurrent(
        string $html,
        string $storedCss,
        ?string $storedChecksum,
        ?string $currentChecksum = null,
    ): bool {
        if ($storedCss === '') {
            return false;
        }

        $currentChecksum ??= self::htmlChecksum($html);

        if ($storedChecksum !== null && $storedChecksum !== '') {
            return hash_equals($storedChecksum, $currentChecksum);
        }

        return ! self::storedCssRequiresRecompile($html, $storedCss);
    }

    public static function storedCssIsCorrupted(string $storedCss): bool
    {
        return (bool) preg_match('/\bvar\(\s*(?:\}|;)/', $storedCss);
    }

    public static function storedCssRequiresRecompile(string $html, string $storedCss): bool
    {
        if (self::storedCssIsCorrupted($storedCss)) {
            return true;
        }

        if (self::htmlReferencesLegacyBrandUtilities($html) || self::cssReferencesLegacyBrandUtilities($storedCss)) {
            return true;
        }

        if (self::cssReferencesLegacyPaletteVariables($storedCss)) {
            return true;
        }

        if (self::htmlReferencesPrelineSemanticUtilities($html) && ! self::cssIncludesPrelineSemanticUtilities($storedCss)) {
            return true;
        }

        return self::htmlHasTailwindUtilitiesMissingFromCss($html, $storedCss);
    }

    public static function htmlHasTailwindUtilitiesMissingFromCss(string $html, string $storedCss): bool
    {
        if (! preg_match_all('/\bclass=(["\'])([^"\']+)\1/i', $html, $matches)) {
            return false;
        }

        $utilityPattern = '/^(?:[a-z][a-z0-9_-]*:)*-?(?:flex|grid|inline-flex|inline|block|hidden|contents|table|flow-root|list-item|absolute|relative|fixed|sticky|static|container|mx-|my-|mt-|mb-|ml-|mr-|w-|h-|min-w-|max-w-|min-h-|max-h-|size-|gap-|p-|px-|py-|pt-|pb-|pl-|pr-|m-|text-|bg-|rounded|shadow|aspect-|col-|row-|items-|justify-|self-|order-|space-|divide-|border-opacity|border-|ring-|outline-|opacity-|z-|top-|bottom-|left-|right-|inset-|object-|overflow-|truncate|whitespace-|leading-|font-|tracking-|underline|decoration-|backdrop-|transition|duration-|ease-|scale-|rotate-|translate-|skew-|origin-|fill-|stroke-|sr-only|not-sr-only|pointer-events-|select-|cursor-|align-|place-|content-|grow|shrink|basis-|from-|to-|via-|bg-vp-|text-vp-|antialiased|subpixel-antialiased|italic|not-italic|visible|invisible|collapse|isolate|box-|break-|hyphens-|list-|columns-|float-|clear-|overscroll-|scroll-|snap-|touch-|will-change-|accent-|caret-|field-sizing-)/i';

        foreach ($matches[2] as $classAttribute) {
            foreach (preg_split('/\s+/', trim($classAttribute)) ?: [] as $className) {
                $className = ltrim($className, '!');

                if ($className === '' || ! preg_match($utilityPattern, $className)) {
                    continue;
                }

                $escaped = preg_quote($className, '/');

                if (preg_match('/\.'.$escaped.'(?:\b|[\[:])/', $storedCss) !== 1) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * CSS for catalog listing: uses stored CSS when present; compiles Tailwind only for
     * components that have no stored CSS yet (new imports / legacy rows).
     *
     * @return array{css: string, cssToPersist: ?string, htmlChecksumToPersist: ?string}
     */
    public static function resolveCatalogCss(string $html, ?string $storedCss, ?string $storedChecksum = null): array
    {
        $html = VoodbuilderThemeTokenMigrator::migrateHtml($html);
        $storedCss = filled($storedCss) ? trim((string) $storedCss) : '';

        if ($storedCss !== '' && self::storedCssIsCurrent($html, $storedCss, $storedChecksum)) {
            return [
                'css' => self::publishedCssForStoredHtml($html, $storedCss),
                'cssToPersist' => null,
                'htmlChecksumToPersist' => null,
            ];
        }

        $compiled = self::compileTailwindCss($html);
        $cssToPersist = $compiled !== '' ? trim(VoodbuilderThemeTokenMigrator::migrateComponentCss($compiled)) : null;
        $css = $cssToPersist !== null && $cssToPersist !== ''
            ? trim($cssToPersist."\n\n".self::componentThemeTokenBridgeCss())
            : self::resolvedCssForStoredHtml($html, null, null);

        return [
            'css' => $css,
            'cssToPersist' => $cssToPersist,
            'htmlChecksumToPersist' => $cssToPersist !== null && $cssToPersist !== ''
                ? self::htmlChecksum($html)
                : null,
        ];
    }

    public static function catalogCssForStoredComponent(string $html, ?string $storedCss): string
    {
        return self::resolveCatalogCss($html, $storedCss)['css'];
    }

    public static function manualCssFromStoredComponentCss(string $storedCss): string
    {
        $storedCss = trim($storedCss);

        if ($storedCss === '') {
            return '';
        }

        if (! str_contains($storedCss, '.voodbuilder-pasted-component')) {
            return $storedCss;
        }

        $chunks = preg_split('/\n(?=\.voodbuilder-pasted-component\b)/', $storedCss) ?: [];
        $manual = [];

        foreach ($chunks as $chunk) {
            $trimmed = trim($chunk);

            if ($trimmed === '' || preg_match('/^\.voodbuilder-pasted-component\b/', $trimmed) === 1) {
                continue;
            }

            $manual[] = $trimmed;
        }

        return trim(implode("\n\n", $manual));
    }

    public static function componentThemeTokenBridgeCss(): string
    {
        return <<<'CSS'
.voodbuilder-gjs-component-instance .voodbuilder-pasted-component,
.voodbuilder-component-rendered .voodbuilder-pasted-component,
.VPRichPage .voodbuilder-pasted-component,
.voodbuilder-pasted-component {
    --color-vp-brand-1: inherit;
    --color-vp-brand-2: inherit;
    --color-vp-brand-3: inherit;
    --color-vp-text-1: inherit;
    --color-vp-text-2: inherit;
    --color-vp-text-3: inherit;
    --color-vp-bg: inherit;
    --color-vp-bg-alt: inherit;
    --color-vp-bg-elv: inherit;
    --color-vp-divider: inherit;
    --color-vp-gray-soft: inherit;
    --color-primary: var(--color-vp-brand-2);
    --color-primary-hover: var(--color-vp-brand-1);
    --color-primary-focus: var(--color-vp-brand-1);
    --color-primary-line: var(--color-vp-brand-2);
    --color-primary-foreground: #ffffff;
    --color-foreground: var(--color-vp-text-1);
    --color-layer: var(--color-vp-bg-elv);
    --color-layer-hover: var(--color-vp-bg-alt);
    --color-layer-focus: var(--color-vp-bg-alt);
    --color-layer-line: var(--color-vp-divider);
    --color-layer-foreground: var(--color-vp-text-1);
    --color-surface-1: var(--color-vp-bg-alt);
    --color-surface: var(--color-vp-bg-alt);
    --color-plain: var(--color-vp-bg-elv);
    --color-inverse: #ffffff;
    --color-foreground-inverse: #ffffff;
    --color-muted-hover: var(--color-vp-bg-alt);
    --color-muted-focus: var(--color-vp-bg-alt);
    --color-travia-transparent: transparent;
    --color-white: #ffffff;
    --color-neutral-900: #171717;
    color: var(--color-vp-text-1);
}
:where(.voodbuilder-gjs-component-instance, .voodbuilder-component-rendered, .VPRichPage) :where(.voodbuilder-pasted-component) .text-white {
    color: var(--color-white);
}
:where(.voodbuilder-gjs-component-instance, .voodbuilder-component-rendered, .VPRichPage) :where(.voodbuilder-pasted-component) .text-primary {
    color: var(--color-primary);
}
:where(.voodbuilder-gjs-component-instance, .voodbuilder-component-rendered, .VPRichPage) :where(.voodbuilder-pasted-component) .text-foreground {
    color: var(--color-foreground);
}
:where(.voodbuilder-gjs-component-instance, .voodbuilder-component-rendered, .VPRichPage) :where(.voodbuilder-pasted-component) .text-foreground-inverse {
    color: var(--color-foreground-inverse);
}
:where(.voodbuilder-gjs-component-instance, .voodbuilder-component-rendered, .VPRichPage) :where(.voodbuilder-pasted-component) .text-inverse {
    color: var(--color-inverse);
}
.dark :where(.voodbuilder-gjs-component-instance, .voodbuilder-component-rendered, .VPRichPage) :where(.voodbuilder-pasted-component) .dark\:text-neutral-900 {
    color: var(--color-neutral-900);
}
:where(.voodbuilder-gjs-component-instance, .voodbuilder-component-rendered, .VPRichPage) :where(.voodbuilder-pasted-component) .bg-card {
    background-color: var(--color-vp-bg-elv);
}
:where(.voodbuilder-gjs-component-instance, .voodbuilder-component-rendered, .VPRichPage) :where(.voodbuilder-pasted-component) .bg-layer {
    background-color: var(--color-vp-bg-elv);
}
:where(.voodbuilder-gjs-component-instance, .voodbuilder-component-rendered, .VPRichPage) :where(.voodbuilder-pasted-component) .bg-surface {
    background-color: var(--color-vp-bg-alt);
}
:where(.voodbuilder-gjs-component-instance, .voodbuilder-component-rendered, .VPRichPage) :where(.voodbuilder-pasted-component) .bg-surface-1 {
    background-color: var(--color-vp-bg-alt);
}
CSS;
    }

    public static function cssReferencesLegacyBrandUtilities(string $css): bool
    {
        return (bool) preg_match(
            '/\.(?:[a-z0-9_-]+:)*-?(?:bg|text|border|ring|outline|from|to|via)-(?:indigo|yellow|red|purple|violet|pink|blue|green)-\d+/i',
            $css,
        );
    }

    public static function htmlReferencesLegacyBrandUtilities(string $html): bool
    {
        return (bool) preg_match(
            '/\b(?:hover:|focus:|focus-visible:|active:|group-hover:)?(?:bg|text|border|ring|outline|from|to|via)-(?:indigo|yellow|red|purple|violet|pink|blue|green)-\d+/i',
            $html,
        );
    }

    public static function cssReferencesLegacyPaletteVariables(string $css): bool
    {
        return (bool) preg_match(
            '/--color-(?:indigo|yellow|red|purple|violet|pink|blue|green)-/i',
            $css,
        );
    }

    public static function htmlReferencesPrelineSemanticUtilities(string $html): bool
    {
        return (bool) preg_match(
            '/\b(?:hover:|focus:|focus-visible:)?(?:bg|text|border|from|to|via)-(?:primary|foreground|layer|surface-1|surface|plain|inverse|foreground-inverse|muted-hover|muted-focus)(?:-(?:hover|focus|line|foreground))?\b/i',
            $html,
        );
    }

    public static function cssIncludesPrelineSemanticUtilities(string $css): bool
    {
        return str_contains($css, '.text-primary')
            || str_contains($css, '.bg-primary')
            || str_contains($css, '.text-foreground');
    }

    /**
     * @param  list<string>  $cssParts
     */
    protected static function extractStyleTags(string $html, array &$cssParts): string
    {
        $result = preg_replace_callback(
            '/<style\b[^>]*>(.*?)<\/style>/is',
            static function (array $matches) use (&$cssParts): string {
                $css = trim($matches[1]);

                if ($css !== '') {
                    $cssParts[] = $css;
                }

                return '';
            },
            $html,
        );

        return is_string($result) ? $result : $html;
    }

    protected static function stripScripts(string $html): string
    {
        $result = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $html);

        return is_string($result) ? $result : $html;
    }

    protected static function extractDocumentBody(string $html): string
    {
        if (preg_match('/<body\b[^>]*>(.*)<\/body>/is', $html, $matches) === 1) {
            return trim($matches[1]);
        }

        return $html;
    }

    protected static function stripDocumentShell(string $html): string
    {
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html) ?? $html;
        $html = preg_replace('/<\/?html[^>]*>/i', '', $html) ?? $html;
        $html = preg_replace('/<head\b[^>]*>.*?<\/head>/is', '', $html) ?? $html;

        return trim($html);
    }

    protected static function wrapMultipleRoots(string $html): string
    {
        if ($html === '' || preg_match('/^<div[^>]*class="[^"]*voodbuilder-pasted-component/i', $html) === 1) {
            return $html;
        }

        if (preg_match_all('/<(section|nav|header|footer|main|article)\b/i', $html, $matches) > 1) {
            return '<div class="voodbuilder-pasted-component">'.$html.'</div>';
        }

        return $html;
    }

    protected static function uniquifySvgIds(string $html): string
    {
        if (! str_contains($html, 'id=') && ! str_contains($html, 'url(#')) {
            return $html;
        }

        $suffix = bin2hex(random_bytes(4));
        $idMap = [];

        $html = preg_replace_callback(
            '/\bid=(["\'])([^"\']+)\1/i',
            static function (array $matches) use (&$idMap, $suffix): string {
                $quote = $matches[1];
                $original = $matches[2];
                $mapped = $idMap[$original] ?? ($original.'-vb-'.$suffix);
                $idMap[$original] = $mapped;

                return 'id='.$quote.$mapped.$quote;
            },
            $html,
        ) ?? $html;

        foreach ($idMap as $original => $mapped) {
            $html = str_replace('url(#'.$original.')', 'url(#'.$mapped.')', $html);
            $html = str_replace('href="#'.$original.'"', 'href="#'.$mapped.'"', $html);
        }

        return $html;
    }

    protected static function stripEmbeddableMedia(string $html): string
    {
        if (! preg_match('/<(?:video|iframe)\b|data-gjs-type=(["\'])video\1/i', $html)) {
            return $html;
        }

        $document = self::loadDocument($html);
        $xpath = new DOMXPath($document);
        $nodesToReplace = [];

        foreach ($document->getElementsByTagName('video') as $element) {
            if ($element instanceof DOMElement) {
                $nodesToReplace[] = $element;
            }
        }

        foreach ($document->getElementsByTagName('iframe') as $element) {
            if ($element instanceof DOMElement && self::isVideoEmbedSrc($element->getAttribute('src'))) {
                $nodesToReplace[] = $element;
            }
        }

        $xpathResult = $xpath->query('//*[@data-gjs-type="video"]');

        if ($xpathResult !== false) {
            foreach ($xpathResult as $element) {
                if ($element instanceof DOMElement) {
                    $nodesToReplace[] = $element;
                }
            }
        }

        $seen = [];

        foreach ($nodesToReplace as $element) {
            $hash = spl_object_hash($element);

            if (isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;
            self::replaceWithMediaSlot($document, $element);
        }

        return self::extractBodyHtml($document) ?? $html;
    }

    protected static function isVideoEmbedSrc(string $src): bool
    {
        $src = trim($src);

        if ($src === '') {
            return false;
        }

        return (bool) preg_match('/(?:youtube(?:-nocookie)?\.com|youtu\.be|vimeo\.com|player\.vimeo\.com)/i', $src);
    }

    protected static function replaceWithMediaSlot(DOMDocument $document, DOMElement $element): void
    {
        $placeholder = $document->createElement('div');
        $placeholder->setAttribute('class', 'voodbuilder-component-library-media-slot');
        $placeholder->setAttribute('aria-hidden', 'true');
        $element->parentNode?->replaceChild($placeholder, $element);
    }

    protected static function loadDocument(string $html): DOMDocument
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);

        $document->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$html.'</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $document;
    }

    protected static function extractBodyHtml(DOMDocument $document): ?string
    {
        $body = $document->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return null;
        }

        $html = '';

        foreach ($body->childNodes as $child) {
            $html .= $document->saveHTML($child);
        }

        return trim($html);
    }
}
