@extends(config('vpress.layouts.app', 'vpress::layouts.app'))

@php
    $vpressBodyClasses = trim(implode(' ', array_filter([
        ($hasSidebar ?? false) ? 'vpress-has-doc-sidebar' : null,
        ($showProgress ?? false) ? 'vpress-has-reading-progress' : null,
    ])));
@endphp
@if ($vpressBodyClasses !== '')
    @section('body_class')
        {{ $vpressBodyClasses }}
    @endsection
@endif

@section('content')
    @if ($hasSidebar ?? false)
        <aside
            class="fixed top-0 bottom-0 left-0 z-10 hidden w-[var(--vp-sidebar-outer-width)] overflow-x-hidden overflow-y-auto overscroll-contain bg-vp-bg-alt pb-24 vp:block vp:pt-[var(--spacing-vp-doc-offset)]"
            aria-label="{{ __('Sidebar') }}"
        >
            <nav class="ml-auto w-[var(--spacing-vp-sidebar)] px-8 outline-0" aria-label="{{ __('Documentation') }}">
                @yield('sidebar')
            </nav>
        </aside>
    @endif

    <div @class([
        'w-full',
        'vp:pl-[var(--vp-sidebar-outer-width)]' => $hasSidebar ?? false,
    ])>
        <div
            @class([
                'mx-auto w-full px-6 py-12 min-[768px]:px-8',
                'min-[768px]:py-16' => ! ($hasSidebar ?? false),
                'vp:px-8 vp:pt-[var(--spacing-vp-doc-offset)] vp:pb-12' => $hasSidebar ?? false,
            ])
            @if ($showProgress ?? false) data-doc-article @endif
        >
            <div @class([
                'mx-auto flex w-full max-w-[var(--width-vp-layout)] gap-12',
                'items-start' => ! ($hasAside ?? false),
                'items-stretch' => $hasAside ?? false,
                'justify-center' => ! ($hasAside ?? false),
            ])>
                <div @class([
                    'min-w-0 flex-1',
                    'max-w-[var(--width-vp-content)]' => $hasSidebar ?? false,
                    'max-w-[60rem]' => ! ($hasSidebar ?? false) && ! ($hasAside ?? false),
                ])>
                    @yield('doc')
                </div>

                @if ($hasAside ?? false)
                    <aside class="hidden w-[var(--spacing-vp-aside)] shrink-0 self-stretch xl:block">
                        <div @class([
                            'sticky self-start overflow-y-auto overscroll-contain text-[13px] [&>nav+nav]:mt-5 [&>nav+nav]:border-t [&>nav+nav]:border-vp-divider [&>nav+nav]:pt-5',
                            'top-[var(--spacing-vp-doc-offset)] max-h-[calc(100vh-var(--spacing-vp-doc-offset)-1rem)]' => $hasSidebar ?? false,
                            'top-24 max-h-[calc(100vh-7rem)]' => ! ($hasSidebar ?? false),
                        ])>
                            @yield('aside')
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </div>
@endsection
