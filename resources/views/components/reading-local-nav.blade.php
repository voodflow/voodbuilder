@props([
    'hasSidebar' => false,
    'hasOutline' => false,
])

{{--
  VitePress-style local nav for companion reading pages: "Menu" opens the left
  sidebar drawer below 960px, "On this page" drops the outline below 1280px.
--}}
@if ($hasSidebar || $hasOutline)
    <div @class([
        'vp-local-nav',
        'has-sidebar' => $hasSidebar,
        'has-outline' => $hasOutline,
    ]) data-vp-local-nav>
        @if ($hasSidebar)
            <button
                type="button"
                class="vp-local-nav__menu"
                data-vp-reading-drawer-toggle
                aria-controls="vp-reading-drawer"
                aria-expanded="false"
            >
                <svg class="vp-local-nav__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M4 6h16M4 12h10M4 18h16" />
                </svg>
                <span>{{ __('voodbuilder::reading.menu') }}</span>
            </button>
        @endif

        @if ($hasOutline)
            <details class="vp-local-nav__outline" data-vp-local-outline>
                <summary>
                    <span>{{ __('voodbuilder::reading.on_this_page') }}</span>
                    <svg class="vp-local-nav__chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 6 6 6-6 6" />
                    </svg>
                </summary>
                <div class="vp-local-nav__outline-panel">
                    <a href="#" class="vp-local-nav__top" data-vp-local-outline-top>{{ __('voodbuilder::reading.return_to_top') }}</a>
                    {{ $slot }}
                </div>
            </details>
        @endif
    </div>
@endif
