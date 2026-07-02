@php
    $voodbuilderSubTheme = $voodbuilderSubTheme ?? $page->resolvedSubTheme();
@endphp

@extends($page->layoutView())

@if ($grapesJsEditor ?? false)
    @section('body_class_extra')
        voodbuilder-grapesjs-editing
    @endsection

    @push('head')
        @foreach ($grapesJsConfig['canvasStyles'] ?? [] as $canvasStyleUrl)
            <link rel="preload" href="{{ $canvasStyleUrl }}" as="style">
        @endforeach
    @endpush

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
        'VPRichPage--landing' => $page->usesFullWidthLayout(),
        'voodbuilder-grapesjs-mode' => $grapesJsEditor ?? false,
    ])>
        @if ($grapesJsEditor ?? false)
            @include('voodbuilder::partials.grapesjs-frontend-editor', [
                'grapesJsConfig' => $grapesJsConfig,
            ])
        @else
            @if ($page->usesGrapesJsBuilder() && filled($page->renderedStyles()))
                <style>{!! $page->renderedStyles() !!}</style>
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
