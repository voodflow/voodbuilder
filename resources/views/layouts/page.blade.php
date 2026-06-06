@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('content')
    <div class="mx-auto w-full max-w-[var(--width-vp-layout)] px-6 py-12 md:px-8 md:py-24">
        @yield('page')
    </div>
@endsection
