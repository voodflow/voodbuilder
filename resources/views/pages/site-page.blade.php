@php
    $vpressSubTheme = $page->resolvedSubTheme();
@endphp

@extends($page->layoutView())

@if ($grapesJsEditor ?? false)
    @section('body_class_extra')
        vpress-grapesjs-editing
    @endsection

    @push('scripts-before-livewire')
        <style>
            .vpress-grapesjs-mode .vpress-landing-shell,
            .vpress-grapesjs-mode .vpress-events-shell {
                max-width: none;
                padding: 0;
            }

            .vpress-grapesjs-mode .VPRichPage--landing {
                width: 100%;
                max-width: none;
            }
        </style>
    @endpush
@endif

@section($page->contentSection())
    @if ($page->isSectionArticle() && ($sectionHome ?? null))
        <nav class="vpress-section-breadcrumb" aria-label="{{ __('Breadcrumb') }}">
            <a href="{{ $sectionHome->getUrl() }}" class="vpress-section-breadcrumb-link">
                {{ $sectionHome->title }}
            </a>
            <span class="vpress-section-breadcrumb-sep" aria-hidden="true">/</span>
            <span class="vpress-section-breadcrumb-current">{{ $page->title }}</span>
        </nav>
    @endif

    @if ($page->isSectionArticle())
        <header class="vpress-article-header">
            <time class="vpress-article-date" datetime="{{ $page->published_at?->toDateString() }}">
                {{ $page->published_at?->translatedFormat('M j, Y') }}
            </time>
        </header>
    @endif

    <div @class([
        'VPRichPage',
        'VPRichPage--landing' => $page->usesLandingCanvas() || $vpressSubTheme === 'events',
        'vpress-grapesjs-mode' => $grapesJsEditor ?? false,
    ])>
        @if ($grapesJsEditor ?? false)
            @include('vpress::partials.grapesjs-frontend-editor', [
                'grapesJsConfig' => $grapesJsConfig,
            ])
        @else
            @if ($page->usesGrapesJsBuilder())
                @if (filled($page->renderedStyles()))
                    <style>{!! $page->renderedStyles() !!}</style>
                @endif
            @endif

            {!! $page->renderedContent() !!}
        @endif
    </div>
@endsection

@if (($canEditGrapesJs ?? false) && ! ($grapesJsEditor ?? false))
    @push('overlays')
        @include('vpress::partials.grapesjs-edit-launch')
    @endpush
@endif
