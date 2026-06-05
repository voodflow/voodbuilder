@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@if ($hasSidebar ?? false)
    @section('body_class')
        vpress-has-doc-sidebar
    @endsection
@endif

@section('content')
    @if ($showProgress ?? false)
        <div class="VPProgress" aria-hidden="true">
            <div data-reading-progress class="VPProgressBar" style="width: 0%"></div>
        </div>
    @endif

    @if ($hasSidebar ?? false)
        <aside class="VPSidebar" aria-label="{{ __('Sidebar') }}">
            <div class="VPSidebarNav">
                @yield('sidebar')
            </div>
        </aside>
    @endif

    <div @class(['VPContent', 'has-sidebar' => $hasSidebar ?? false])>
        <div
            @class([
                'VPDoc',
                'has-sidebar' => $hasSidebar ?? false,
                'has-aside' => $hasAside ?? false,
            ])
            @if ($showProgress ?? false) data-doc-article @endif
        >
            <div class="container">
                <div class="content">
                    @yield('doc')
                </div>

                @if ($hasAside ?? false)
                    <aside class="aside">
                        <div class="aside-container">
                            @yield('aside')
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </div>
@endsection
