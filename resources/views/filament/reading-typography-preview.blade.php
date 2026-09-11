@php
    use Voodflow\Voodbuilder\Support\ChromeLayoutReadingTypography;
    use Voodflow\Voodbuilder\Support\Fonts\FontStylesheets;
    use Voodflow\Voodbuilder\Support\ReadingPreviewRegistry;

    $fontId = ChromeLayoutReadingTypography::normalizeFont($readingFont ?? null);
    $sizeId = ChromeLayoutReadingTypography::normalizeSize($readingSize ?? null);
    $stack = ChromeLayoutReadingTypography::stackFor($fontId);
    $cssSize = ChromeLayoutReadingTypography::cssSizeFor($sizeId);
    $fontUrls = $fontId === ChromeLayoutReadingTypography::DEFAULT_FONT
        ? []
        : FontStylesheets::urlsFor([$fontId]);

    $preview = app(ReadingPreviewRegistry::class)->resolve((string) ($previewChannel ?? 'sample'));
    $eyebrow = $preview['eyebrow'] ?: ($preview['label'] ?? null);
@endphp

@foreach ($fontUrls as $href)
    <link rel="stylesheet" href="{{ $href }}">
@endforeach

<div
    class="overflow-hidden rounded-2xl bg-white ring-1 ring-black/5 dark:bg-gray-950 dark:ring-white/10"
    wire:key="reading-preview-{{ $fontId }}-{{ $sizeId }}-{{ $previewChannel ?? 'sample' }}"
>
    <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-4 py-2.5 dark:border-white/10">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-gray-950 dark:text-white">
                {{ __('voodbuilder::chrome_layouts.preview.heading') }}
            </p>
            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                {{ __('voodbuilder::chrome_layouts.preview.hint') }}
            </p>
        </div>
        <span class="shrink-0 rounded-md bg-gray-50 px-2 py-1 text-[11px] font-medium text-gray-600 ring-1 ring-black/5 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10">
            {{ $cssSize }} · {{ $preview['label'] }}
        </span>
    </div>

    <div class="bg-[#f6f6f7] dark:bg-gray-900">
        <div class="flex min-h-[22rem] max-h-[28rem] overflow-hidden">
            {{-- Sidebar stub (companion chrome) --}}
            <aside class="hidden w-44 shrink-0 border-r border-black/5 bg-[#f6f6f7] p-4 text-[12px] dark:border-white/10 dark:bg-gray-900 sm:block">
                <p class="mb-3 text-[11px] font-semibold tracking-wide text-gray-400 uppercase">
                    {{ $eyebrow ?? __('voodbuilder::chrome_layouts.preview.sidebar') }}
                </p>
                <ul class="space-y-2 text-gray-600 dark:text-gray-300">
                    <li class="font-medium text-[#3451b2]">{{ __('voodbuilder::chrome_layouts.preview.nav_active') }}</li>
                    <li class="opacity-70">{{ __('voodbuilder::chrome_layouts.preview.nav_item') }}</li>
                    <li class="opacity-70">{{ __('voodbuilder::chrome_layouts.preview.nav_item_alt') }}</li>
                </ul>
            </aside>

            {{-- Article column — same CSS vars as public chrome-app --}}
            <div class="min-w-0 flex-1 overflow-y-auto bg-white px-5 py-6 dark:bg-gray-950 sm:px-8">
                <article
                    class="vp-doc reading-typography-preview max-w-[min(100%,42rem)]"
                    style="--vp-font-family-doc: {{ $stack }}; --vp-font-size-doc: {{ $cssSize }}; font-family: var(--vp-font-family-doc); font-size: var(--vp-font-size-doc); line-height: 1.7; color: #3c3c43;"
                >
                    <style>
                        .reading-typography-preview h1 {
                            margin: 0 0 0.75em;
                            font-size: 1.875em;
                            font-weight: 700;
                            line-height: 1.25;
                            letter-spacing: -0.02em;
                            color: #1b1b18;
                        }
                        .reading-typography-preview h2 {
                            margin: 1.6em 0 0.6em;
                            font-size: 1.35em;
                            font-weight: 650;
                            line-height: 1.35;
                            color: #1b1b18;
                        }
                        .reading-typography-preview p { margin: 0.85em 0; }
                        .reading-typography-preview p.lead {
                            font-size: 1.05em;
                            color: #67676c;
                        }
                        .reading-typography-preview ul {
                            margin: 0.85em 0;
                            padding-left: 1.25em;
                            list-style: disc;
                        }
                        .reading-typography-preview li { margin: 0.35em 0; }
                        .reading-typography-preview code {
                            font-family: ui-monospace, Menlo, Consolas, monospace;
                            font-size: 0.9em;
                            padding: 0.1em 0.35em;
                            border-radius: 0.35rem;
                            background: #f6f6f7;
                        }
                        .reading-typography-preview pre.vp-code-block {
                            margin: 1em 0;
                            padding: 0.85em 1em;
                            overflow-x: auto;
                            border-radius: 0.65rem;
                            border: 1px solid #e2e2e3;
                            background: #f6f6f7;
                            font-size: 0.82em;
                            line-height: 1.45;
                        }
                        .reading-typography-preview pre.vp-code-block code {
                            padding: 0;
                            background: transparent;
                        }
                        .reading-typography-preview blockquote {
                            margin: 1.1em 0;
                            padding-left: 0.9em;
                            border-left: 3px solid #3451b2;
                            color: #67676c;
                        }
                        .dark .reading-typography-preview { color: #c8c8cc; }
                        .dark .reading-typography-preview h1,
                        .dark .reading-typography-preview h2 { color: #f3f3f4; }
                        .dark .reading-typography-preview p.lead,
                        .dark .reading-typography-preview blockquote { color: #a1a1a6; }
                        .dark .reading-typography-preview code,
                        .dark .reading-typography-preview pre.vp-code-block {
                            background: rgba(255,255,255,0.06);
                            border-color: rgba(255,255,255,0.1);
                        }
                    </style>
                    {!! $preview['html'] !!}
                </article>
            </div>

            {{-- TOC stub --}}
            <aside class="hidden w-36 shrink-0 border-l border-black/5 p-4 text-[12px] xl:block dark:border-white/10">
                <p class="mb-2 font-semibold text-gray-700 dark:text-gray-200">
                    {{ __('voodbuilder::chrome_layouts.preview.toc') }}
                </p>
                <ul class="space-y-2 text-gray-500 dark:text-gray-400">
                    <li class="border-l-2 border-[#3451b2] pl-2 text-[#3451b2]">{{ __('voodbuilder::chrome_layouts.preview.toc_active') }}</li>
                    <li class="border-l-2 border-transparent pl-2">{{ __('voodbuilder::chrome_layouts.preview.toc_item') }}</li>
                </ul>
            </aside>
        </div>
    </div>
</div>
