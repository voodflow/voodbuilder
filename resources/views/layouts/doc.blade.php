@extends(\Voodflow\Voodbuilder\Support\PluginLayout::appShell())

@php
    $voodbuilderBodyClasses = trim(implode(' ', array_filter([
        ($hasSidebar ?? false) ? 'voodbuilder-has-doc-sidebar' : null,
        ($showProgress ?? false) ? 'voodbuilder-has-reading-progress' : null,
    ])));
@endphp
@if ($voodbuilderBodyClasses !== '')
    @section('body_class')
        {{ $voodbuilderBodyClasses }}
    @endsection
@endif

@section('content')
    <div @class([
        'w-full',
        'vp:flex vp:items-stretch' => $hasSidebar ?? false,
    ])>
        @if ($hasSidebar ?? false)
            <aside
                class="hidden w-[var(--vp-sidebar-outer-width)] shrink-0 bg-vp-bg-alt vp:block"
                aria-label="{{ __('Sidebar') }}"
            >
                <div class="sticky top-[var(--spacing-vp-nav-total)] z-10 max-h-[calc(100vh-var(--spacing-vp-nav-total))] overflow-x-hidden overflow-y-auto overscroll-contain pb-24 pt-[calc(var(--spacing-vp-doc-offset)-var(--spacing-vp-nav-total))]">
                    <nav class="ml-auto w-[var(--spacing-vp-sidebar)] px-8 outline-0" aria-label="{{ __('Documentation') }}">
                        @yield('sidebar')
                    </nav>
                </div>
            </aside>
        @endif

        <div @class([
            'min-w-0 w-full',
            'vp:flex-1' => $hasSidebar ?? false,
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
    </div>
@endsection
