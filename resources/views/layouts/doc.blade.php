@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@section('content')
    @isset($showProgress)
        @if($showProgress)
            <div class="VPProgress" aria-hidden="true">
                <div data-reading-progress class="VPProgressBar" style="width: 0%"></div>
            </div>
        @endif
    @endisset

    <div
        @class([
            'VPDoc',
            'has-sidebar' => $hasSidebar ?? false,
            'has-aside' => $hasAside ?? false,
        ])
        @if($showProgress ?? false) data-tutorial-article @endif
    >
        <div class="container">
            @if($hasSidebar ?? false)
                <aside class="sidebar">
                    <div class="sidebar-container">
                        @yield('sidebar')
                    </div>
                </aside>
            @endif

            <div class="content">
                @yield('doc')
            </div>

            @if($hasAside ?? false)
                <aside class="aside">
                    <div class="aside-container">
                        @yield('aside')
                    </div>
                </aside>
            @endif
        </div>
    </div>
@endsection
