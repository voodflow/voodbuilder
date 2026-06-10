@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    vpress-sub-theme-news
@endsection

@section('content')
    <div class="vpress-news-shell">
        <div class="vpress-news-content">
            <div class="vpress-news-content-inner">
                @yield('home')
            </div>
        </div>
    </div>
@endsection
