@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('body_class')
    @yield('body_class_extra')
@endsection

@section('content')
    <div class="vpress-home-shell vpress-landing-shell vpress-full-width-shell">
        @yield('full_width')
    </div>
@endsection
