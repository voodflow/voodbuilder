@extends(\Voodflow\Voodbuilder\Support\PluginLayout::appShell())

{{--
  @deprecated Companion packages own reading layouts (vdocs::layouts.voodbuilder,
  vtuts::layouts.voodbuilder). This shim keeps legacy SitePage / search hosts working
  for one release — prefer PluginLayout::resolve('page') or a companion layout.
--}}

@php
    $voodbuilderBodyClasses = trim(implode(' ', array_filter([
        ($hasSidebar ?? false) ? 'voodbuilder-has-doc-sidebar' : null,
        ($showProgress ?? false) ? 'voodbuilder-has-reading-progress' : null,
    ])));
    $hasSidebar = (bool) ($hasSidebar ?? false);
    $hasAside = (bool) ($hasAside ?? false);
    $isReadingLayout = $hasSidebar || $hasAside;
@endphp
@if ($voodbuilderBodyClasses !== '')
    @section('body_class')
        {{ $voodbuilderBodyClasses }}
    @endsection
@endif

@section('content')
    <div @class([
        'w-full',
        'vp:flex vp:items-stretch' => $hasSidebar,
    ])>
        @if ($hasSidebar)
            <aside
                class="hidden w-[var(--vp-sidebar-outer-width)] shrink-0 bg-vp-bg-alt vp:block"
                aria-label="{{ __('Sidebar') }}"
                data-voodbuilder-reading-sidebar
            >
                <div class="voodbuilder-doc-sidebar-scroll sticky top-[var(--spacing-vp-nav-total)] z-10 max-h-[calc(100vh-var(--spacing-vp-nav-total))] overflow-x-hidden overflow-y-auto overscroll-contain pb-24 pt-[calc(var(--spacing-vp-doc-offset)-var(--spacing-vp-nav-total))]">
                    <nav class="ml-auto w-[var(--spacing-vp-sidebar)] px-8 outline-0" aria-label="{{ __('Documentation') }}">
                        @yield('sidebar')
                    </nav>
                </div>
            </aside>
        @endif

        <div @class([
            'min-w-0 w-full',
            'vp:flex-1 vp:ml-[80px]' => $hasSidebar,
        ])>
            <div
                @class([
                    'w-full px-6 py-8 min-[768px]:px-8 min-[768px]:py-12',
                    'vp:pr-8 vp:pt-[var(--spacing-vp-doc-offset)] vp:pb-12' => $hasSidebar,
                    'min-[768px]:pb-16' => ! $hasSidebar,
                ])
                @if ($showProgress ?? false) data-doc-article @endif
            >
                <div
                    @class([
                        'flex max-w-full items-start gap-24 xl:gap-28',
                        'w-fit mr-auto' => $hasSidebar,
                        'mx-auto w-full max-w-[var(--width-vp-layout)]' => ! $hasSidebar && $hasAside,
                        'mx-auto w-full max-w-[var(--width-vp-content)]' => ! $hasSidebar && ! $hasAside,
                    ])
                    @if (! $hasSidebar && ! $hasAside)
                        data-voodbuilder-reading-column="content"
                    @endif
                >
                    <div @class([
                        'min-w-0',
                        'w-[min(100%,var(--width-vp-content))]' => $hasSidebar && $isReadingLayout,
                        'w-full flex-1 max-w-[min(100%,calc(var(--width-vp-layout)-var(--spacing-vp-aside)-7rem))]' => $isReadingLayout && ! $hasSidebar,
                        'w-full max-w-[min(100%,var(--width-vp-content))]' => ! $isReadingLayout,
                    ])>
                        @yield('doc')
                    </div>

                    @if ($hasAside)
                        <aside class="hidden w-[var(--spacing-vp-aside)] shrink-0 self-stretch xl:block" data-voodbuilder-reading-sidebar>
                            <div @class([
                                'sticky self-start overflow-y-auto overscroll-contain [&>nav+nav]:mt-5 [&>nav+nav]:border-t [&>nav+nav]:border-vp-divider [&>nav+nav]:pt-5',
                                'top-[var(--spacing-vp-doc-offset)] max-h-[calc(100vh-var(--spacing-vp-doc-offset)-1rem)]' => $hasSidebar,
                                'top-24 max-h-[calc(100vh-7rem)]' => ! $hasSidebar,
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
