<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support;

final class MarkdownCodeBlocks
{
    public static function normalizeFencedCodeBlocks(string $markdown): string
    {
        $lines = preg_split('/\r?\n/', $markdown) ?: [];
        $inFence = false;

        foreach ($lines as $index => $line) {
            if (! preg_match('/^```/', $line)) {
                continue;
            }

            if (! $inFence) {
                if (preg_match('/^```\s*$/', $line)) {
                    $lines[$index] = '```text';
                }

                $inFence = true;

                continue;
            }

            $inFence = false;
        }

        return implode("\n", $lines);
    }

    public static function enhance(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $html = static::wrapFencedCodeTags($html);
        $html = static::wrapShikiBlocks($html);

        return $html;
    }

    protected static function wrapFencedCodeTags(string $html): string
    {
        $html = (string) preg_replace_callback(
            '/<code class="language-([\w+#.-]+)">([\s\S]*?)<\/code>/',
            function (array $matches): string {
                if (! str_contains($matches[2], "\n")) {
                    return $matches[0];
                }

                return static::shell(
                    static::normalizeLanguage($matches[1]),
                    $matches[2],
                    'pre',
                );
            },
            $html,
        );

        return (string) preg_replace_callback(
            '/<code>([\s\S]*?)<\/code>/',
            function (array $matches): string {
                if (! str_contains($matches[1], "\n")) {
                    return $matches[0];
                }

                return static::shell('text', $matches[1], 'pre');
            },
            $html,
        );
    }

    protected static function wrapShikiBlocks(string $html): string
    {
        return (string) preg_replace_callback(
            '/<pre class="shiki[^"]*"[^>]*>[\s\S]*?<\/pre>/',
            function (array $matches): string {
                if (str_contains($matches[0], 'data-code-block')) {
                    return $matches[0];
                }

                if (! preg_match('/class="language-([\w+#.-]+)"/', $matches[0], $language)) {
                    return static::shell('code', $matches[0], 'raw');
                }

                return static::shell(
                    static::normalizeLanguage($language[1]),
                    $matches[0],
                    'raw',
                );
            },
            $html,
        );
    }

    protected static function normalizeLanguage(string $language): string
    {
        return match ($language) {
            'c++', 'cxx' => 'cpp',
            'js' => 'javascript',
            'ts' => 'typescript',
            'sh', 'zsh' => 'bash',
            default => $language,
        };
    }

    protected static function shell(string $language, string $body, string $mode): string
    {
        $label = e(strtoupper($language));

        $content = $mode === 'raw'
            ? $body
            : '<pre class="m-0 overflow-x-auto bg-transparent p-0 font-mono text-[13px] leading-relaxed"><code class="language-'.e($language).'">'.$body.'</code></pre>';

        return <<<HTML
<div class="my-4 overflow-hidden rounded-lg border border-vp-divider bg-vp-bg-alt shadow-sm" data-code-block>
    <div class="flex items-center justify-between gap-3 border-b border-vp-divider bg-vp-gray-soft/60 px-3 py-2">
        <div class="flex min-w-0 items-center gap-2">
            <span class="hidden gap-1 sm:flex" aria-hidden="true">
                <span class="h-2 w-2 rounded-full bg-[#ff5f57]"></span>
                <span class="h-2 w-2 rounded-full bg-[#febc2e]"></span>
                <span class="h-2 w-2 rounded-full bg-[#28c840]"></span>
            </span>
            <span class="truncate text-[11px] font-medium tracking-wide text-vp-text-3 uppercase">{$label}</span>
        </div>
        <button
            type="button"
            class="shrink-0 rounded-md border border-vp-divider bg-vp-bg px-2 py-1 text-[11px] font-medium text-vp-text-2 transition-colors hover:border-vp-brand-1/30 hover:text-vp-text-1"
            data-code-copy
        >
            Copy
        </button>
    </div>
    <div class="overflow-x-auto p-4 text-vp-text-1">
        {$content}
    </div>
</div>
HTML;
    }
}
