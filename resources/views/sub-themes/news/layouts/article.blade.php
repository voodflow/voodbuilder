@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    vpress-sub-theme-news vpress-has-reading-progress
@endsection

@section('content')
    <div class="vpress-news-shell" data-vpress-article>
        <div class="vpress-news-layout">
            @include('vpress::sub-themes.news.partials.sidebar-left', [
                'page' => $page,
                'sectionHome' => $sectionHome ?? null,
                'sectionPosts' => $sectionPosts ?? collect(),
            ])

            <div class="vpress-news-main">
                @yield('page')
            </div>

            @include('vpress::sub-themes.news.partials.sidebar-right', [
                'page' => $page,
                'sectionHome' => $sectionHome ?? null,
                'sectionPosts' => $sectionPosts ?? collect(),
            ])
        </div>
    </div>
@endsection
