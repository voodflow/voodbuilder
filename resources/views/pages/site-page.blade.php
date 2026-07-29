@php
    use Voodflow\Voodbuilder\Support\ChromeLayoutContentWidth;

    $voodbuilderSubTheme = $voodbuilderSubTheme ?? $page->resolvedSubTheme();
    $pageContentWidth = ChromeLayoutContentWidth::resolve($voodbuilderChromeLayout ?? null, $page);
@endphp

@extends($page->layoutView())

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
        // Landing section styles stay available; width is controlled by layout data-voodbuilder-page-width.
        'VPRichPage--landing' => ($voodbuilderChromeLayout ?? null) !== null
            || ChromeLayoutContentWidth::isFull($pageContentWidth)
            || $page->usesEditorBuilder(),
        'voodbuilder-editor-mode' => $editorEditor ?? false,
    ])>
        @if ($editorEditor ?? false)
            @include('voodbuilder::partials.editor-frontend-editor', [
                'editorConfig' => $editorConfig,
            ])
        @else
            @foreach (\Voodflow\Voodbuilder\Support\Editor\EditorCanvas::publishedStyleUrls() as $publishedStyleUrl)
                <link rel="stylesheet" href="{{ $publishedStyleUrl }}">
            @endforeach

            @php($editorStyles = $page->usesEditorBuilder() ? $page->renderedStyles() : null)
            @if (filled($editorStyles))
                <style>{!! $editorStyles !!}</style>
            @endif

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
