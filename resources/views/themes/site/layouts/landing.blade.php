@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    vpress-sub-theme-events @yield('body_class_extra')
@endsection

@section('content')
    <div class="vpress-site-shell">
        <div class="vpress-landing-shell">
            @yield('landing')
        </div>
    </div>
@endsection
