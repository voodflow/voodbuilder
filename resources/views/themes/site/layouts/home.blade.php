@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    vpress-sub-theme-events @yield('body_class_extra')
@endsection

@section('content')
    <div class="vpress-site-shell vpress-full-width-shell">
        <div class="vpress-home-shell vpress-landing-shell">
            @yield('full_width')
        </div>
    </div>
@endsection
