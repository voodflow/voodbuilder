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

            if (preg_match('/^`{4,}\s*$/', $line)) {
                $lines[$index] = '```';
            }

            $inFence = false;
        }

        return implode("\n", $lines);
    }

    public static function enhance(string $html, ?string $markdown = null): string
    {
        if ($html === '') {
            return $html;
        }

        $languages = $markdown !== null ? static::extractFenceLanguages($markdown) : [];
        $languageIndex = 0;

        $html = static::wrapShikiBlocks($html, $languages, $languageIndex);
        $html = static::wrapPlainCodeBlocks($html);

        return $html;
    }

    /**
     * @return list<string>
     */
    protected static function extractFenceLanguages(string $markdown): array
    {
        $languages = [];
        $inFence = false;

        foreach (preg_split('/\r?\n/', $markdown) ?: [] as $line) {
            if (! preg_match('/^```/', $line)) {
                continue;
            }

            if (! $inFence) {
                $languages[] = preg_match('/^```([^\s`]+)/', $line, $matches)
                    ? $matches[1]
                    : 'text';

                $inFence = true;

                continue;
            }

            $inFence = false;
        }

        return $languages;
    }

    protected static function wrapPlainCodeBlocks(string $html): string
    {
        $html = (string) preg_replace_callback(
            '/<pre><code class="language-([\w+#.-]+)">([\s\S]*?)<\/code><\/pre>/',
            fn (array $matches): string => static::shell(
                static::normalizeLanguage($matches[1]),
                '<pre class="m-0 whitespace-pre-wrap break-words bg-transparent p-0 font-mono text-[13px] leading-[1.35]"><code class="language-'.e(static::normalizeLanguage($matches[1])).'">'.$matches[2].'</code></pre>',
                'raw',
            ),
            $html,
        );

        return (string) preg_replace_callback(
            '/<code class="language-([\w+#.-]+)">([\s\S]*?)<\/code>/',
            function (array $matches): string {
                if (! str_contains($matches[2], "\n") || str_contains($matches[0], '<span class="line">')) {
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
    }

    /**
     * @param  list<string>  $languages
     */
    protected static function wrapShikiBlocks(string $html, array $languages, int &$languageIndex): string
    {
        return (string) preg_replace_callback(
            '/<pre class="shiki[^"]*"[^>]*>[\s\S]*?<\/pre>/',
            function (array $matches) use ($languages, &$languageIndex): string {
                if (str_contains($matches[0], 'data-code-block')) {
                    return $matches[0];
                }

                $language = static::normalizeLanguage($languages[$languageIndex] ?? 'code');
                $languageIndex++;

                return static::shell($language, $matches[0], 'raw');
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
            'sh', 'zsh', 'shell' => 'bash',
            'cmd', 'bat' => 'batch',
            default => $language,
        };
    }

    protected static function shell(string $language, string $body, string $mode): string
    {
        $label = e(strtoupper($language));

        $content = $mode === 'raw'
            ? $body
            : '<pre class="m-0 whitespace-pre-wrap break-words bg-transparent p-0 font-mono text-[13px] leading-[1.35]"><code class="language-'.e($language).'">'.$body.'</code></pre>';

        return <<<HTML
<div class="vp-code-block" data-code-block data-line-numbers>
    <div class="vp-code-block__header">
        <span class="vp-code-block__lang">{$label}</span>
        <button type="button" class="vp-code-block__copy" data-code-copy>Copy</button>
    </div>
    <div class="vp-code-block__body">
        {$content}
    </div>
</div>
HTML;
    }
}
