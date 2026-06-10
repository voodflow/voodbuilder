@php
    $vpressSubTheme = $page->resolvedSubTheme();
    $posts = $sectionPosts ?? collect();
@endphp

@extends($page->layoutView())

@section('section_index')
    @if ($vpressSubTheme === 'news')
        <header class="vpress-news-desk-header">
            <p class="vpress-news-desk-kicker">{{ __('vpress::demo.news.desk_kicker') }}</p>
            <h1 class="vpress-news-desk-title">{{ $page->title }}</h1>
            <p class="vpress-news-desk-intro">{{ $page->displayExcerpt() }}</p>
        </header>

        @include('vpress::sub-themes.news.partials.story-grid', ['posts' => $posts])
    @else
        <header class="vpress-blog-index-header">
            <h1 class="vpress-blog-index-title">{{ $page->title }}</h1>
            <p class="vpress-blog-index-intro">{{ $page->displayExcerpt() }}</p>
        </header>

        @include('vpress::sub-themes.blog.partials.post-list', ['posts' => $posts])
    @endif
@endsection
