@php
    use Voodflow\Voodbuilder\Support\ChromeLayoutContentWidth;
    use Voodflow\Voodbuilder\Support\Fonts\FontStylesheets;
    use Voodflow\Voodbuilder\Voodbuilder;

    $voodbuilderSubTheme = $voodbuilderSubTheme ?? $page->resolvedSubTheme();
    $pageContentWidth = ChromeLayoutContentWidth::resolve($voodbuilderChromeLayout ?? null, $page);

    $pageFontIds = $page->builder_payload['fonts'] ?? null;

    if (! is_array($pageFontIds) || $pageFontIds === []) {
        $pageFontIds = Voodbuilder::fonts()->detectUsedIds(
            (string) ($page->builder_payload['css'] ?? '')."\n".(string) ($page->builder_payload['html'] ?? ''),
        );
    }

    $pageFontUrls = ! ($editorEditor ?? false) && $page->usesEditorBuilder()
        ? FontStylesheets::urlsFor($pageFontIds)
        : [];
    $pageFontFileUrls = $pageFontUrls !== []
        ? FontStylesheets::fontFileUrlsFor($pageFontIds)
        : [];
@endphp

@extends($page->layoutView())

{{-- Early stack (before @vite): preload woff2 + font CSS so first paint uses the right face. --}}
@if ($pageFontUrls !== [])
    @push('fonts')
        @foreach ($pageFontFileUrls as $pageFontFileUrl)
            <link rel="preload" href="{{ $pageFontFileUrl }}" as="font" type="font/woff2" crossorigin>
        @endforeach
        @foreach ($pageFontUrls as $pageFontUrl)
            <link rel="stylesheet" href="{{ $pageFontUrl }}">
        @endforeach
    @endpush
@endif

@php($editorStyles = ! ($editorEditor ?? false) && $page->usesEditorBuilder() ? $page->renderedStyles() : null)
@if (filled($editorStyles))
    @push('head')
        <style id="voodbuilder-page-css">{!! $editorStyles !!}</style>
    @endpush
@endif

@if ($editorEditor ?? false)
    @section('body_class_extra')
        voodbuilder-editor-editing
    @endsection

    @if ($editorEditor ?? false)
    @push('head')
        <style id="voodbuilder-editor-host-chrome-critical">{!! \Voodflow\Voodbuilder\Support\Editor\EditorHostChrome::criticalHideCss() !!}</style>
    @endpush
    @endif

    @push('scripts-before-livewire')
        <style>
            body.voodbuilder-editor-editing :is(main, .voodbuilder-landing-shell, .voodbuilder-site-shell, .voodbuilder-site-content, .voodbuilder-polito-content, .VPRichPage, .VPRichPage--landing) {
                max-width: none !important;
                width: 100% !important;
                margin-inline: 0 !important;
                padding-inline: 0;
            }
        </style>
    @endpush
@elseif (! empty($pageGate))
    @section('body_class_extra')
        voodbuilder-page-gated
    @endsection
@endif

@section($page->contentSection())
    @if ($page->isSectionArticle() && ($sectionHome ?? null))
        <nav class="voodbuilder-section-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ $sectionHome->getUrl() }}" class="voodbuilder-section-breadcrumb-link">
                {{ $sectionHome->title }}
            </a>
            <span class="voodbuilder-section-breadcrumb-sep" aria-hidden="true">/</span>
            <span class="voodbuilder-section-breadcrumb-current">{{ $page->title }}</span>
        </nav>
    @endif

    @if ($page->isSectionArticle())
        <header class="voodbuilder-article-header">
            <time class="voodbuilder-article-date" datetime="{{ $page->published_at?->toDateString() }}">
                {{ $page->published_at?->translatedFormat('M j, Y') }}
            </time>
        </header>
    @endif

    <div @class([
        'VPRichPage',
        // Landing full-bleed only for real page content — gate stays contained (80rem).
        'VPRichPage--landing' => empty($pageGate) && (
            ($voodbuilderChromeLayout ?? null) !== null
            || ChromeLayoutContentWidth::isFull($pageContentWidth)
            || $page->usesEditorBuilder()
        ),
        'VPRichPage--gate' => ! empty($pageGate),
        'voodbuilder-editor-mode' => $editorEditor ?? false,
    ])>
        @if ($editorEditor ?? false)
            @include('voodbuilder::partials.editor-frontend-editor', [
                'editorConfig' => $editorConfig,
            ])
        @elseif (! empty($pageGate))
            @include('voodbuilder::partials.page-gate')
        @else
            @foreach (\Voodflow\Voodbuilder\Support\Editor\EditorCanvas::publishedStyleUrls() as $publishedStyleUrl)
                <link rel="stylesheet" href="{{ $publishedStyleUrl }}">
            @endforeach

            {!! $page->renderedContent() !!}

            @if (filled($page->renderedScripts()))
                <script>{!! $page->renderedScripts() !!}</script>
            @endif
        @endif
    </div>
@endsection

@if (($canEditEditor ?? false) && ! ($editorEditor ?? false))
    @push('overlays')
        @include('voodbuilder::partials.editor-edit-launch')
    @endpush
@endif
