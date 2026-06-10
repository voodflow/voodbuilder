@php
    $vpressSubTheme = $page->resolvedSubTheme();
@endphp

@extends($page->layoutView())

@section($page->contentSection())
    @if ($page->isSectionArticle() && $sectionHome)
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

    <div class="VPRichPage">
        {!! $page->renderedContent() !!}
    </div>
@endsection
