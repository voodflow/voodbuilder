@php
    use Voodflow\Voodbuilder\Support\ChromeLayoutContentWidth;

    $voodbuilderSubTheme = $voodbuilderSubTheme ?? $page->resolvedSubTheme();
    $pageContentWidth = ChromeLayoutContentWidth::resolve($voodbuilderChromeLayout ?? null, $page);
@endphp

@extends($page->layoutView())

@if ($grapesJsEditor ?? false)
    @section('body_class_extra')
        voodbuilder-grapesjs-editing
    @endsection

    @if ($grapesJsEditor ?? false)
    @push('head')
        <style id="voodbuilder-grapesjs-host-chrome-critical">{!! \Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsHostChrome::criticalHideCss() !!}</style>
    @endpush
    @endif

    @push('scripts-before-livewire')
        <style>
            .voodbuilder-grapesjs-mode .voodbuilder-landing-shell,
            .voodbuilder-grapesjs-mode .voodbuilder-site-shell,
            .voodbuilder-grapesjs-mode .voodbuilder-polito-content {
                max-width: none;
                padding: 0;
            }

            .voodbuilder-grapesjs-mode .VPRichPage--landing {
                width: 100%;
                max-width: none;
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
            || $page->usesGrapesJsBuilder(),
        'voodbuilder-grapesjs-mode' => $grapesJsEditor ?? false,
    ])>
        @if ($grapesJsEditor ?? false)
            @include('voodbuilder::partials.grapesjs-frontend-editor', [
                'grapesJsConfig' => $grapesJsConfig,
            ])
        @else
            @foreach (\Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsCanvas::publishedStyleUrls() as $publishedStyleUrl)
                <link rel="stylesheet" href="{{ $publishedStyleUrl }}">
            @endforeach

            @php($grapesJsStyles = $page->usesGrapesJsBuilder() ? $page->renderedStyles() : null)
            @if (filled($grapesJsStyles))
                <style>{!! $grapesJsStyles !!}</style>
            @endif

            {!! $page->renderedContent() !!}

            @if (filled($page->renderedScripts()))
                <script>{!! $page->renderedScripts() !!}</script>
            @endif
        @endif
    </div>
@endsection

@if (($canEditGrapesJs ?? false) && ! ($grapesJsEditor ?? false))
    @push('overlays')
        @include('voodbuilder::partials.grapesjs-edit-launch')
    @endpush
@endif
