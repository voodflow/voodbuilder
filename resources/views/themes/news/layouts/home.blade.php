@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    vpress-sub-theme-news vpress-has-reading-progress
@endsection

@section('content')
    <div class="vpress-news-shell" data-vpress-article>
        <div class="vpress-news-content">
            <div class="vpress-news-content-inner">
                @yield('home')
            </div>
        </div>
    </div>
@endsection
