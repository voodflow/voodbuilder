@php
    $desktopChromeClass = $desktopChromeClass ?? 'vp:block';
    $desktopFlexClass = $desktopFlexClass ?? 'vp:flex';
    $mobileToggleClass = $mobileToggleClass ?? 'hidden max-vp:inline-flex';
    $showNotificationBell = (bool) ($showNotificationBell ?? true);
    $canvasPreview = (bool) ($canvasPreview ?? false);
    $showSearch = (bool) ($showSearch ?? true);
    $showProfileMenu = (bool) ($showProfileMenu ?? true);
    $hideExtraMenu = (bool) ($hideExtraMenu ?? false);
    $hideMobileToggle = (bool) ($hideMobileToggle ?? false);

    $chromeHidden = static fn (bool $visible): string => $visible ? '' : 'data-voodbuilder-chrome-hidden';
    $chromeWrapperClass = $canvasPreview ? $desktopChromeClass : 'hidden '.$desktopChromeClass;
@endphp

<div class="flex shrink-0 items-center justify-end gap-2 vp:gap-3">
    @unless ($hideExtraMenu)
        <x-voodbuilder::menu
            menu="header_extra"
            class="hidden shrink-0 items-center {{ $desktopFlexClass }}"
            link-class="inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-2 transition-colors hover:text-vp-brand-1"
            data-voodbuilder-desktop-nav
            :canvas-preview="$canvasPreview"
        />
    @endunless

    @if ($showSearch || $canvasPreview)
        <div
            @class([$chromeWrapperClass])
            data-voodbuilder-desktop-chrome
            data-voodbuilder-chrome="search"
            {!! $canvasPreview ? $chromeHidden($showSearch) : '' !!}
        >
            @if ($canvasPreview)
                <div class="flex items-center" data-voodbuilder-search data-gjs-type="default" data-gjs-selectable="false">
                    <button
                        type="button"
                        class="voodbuilder-header-icon-btn text-vp-text-2"
                        data-voodbuilder-search-open
                        data-gjs-type="voodbuilder-chrome-button"
                        data-gjs-selectable="false"
                        aria-label="{{ __('voodbuilder::search.button') }}"
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>
                </div>
            @else
                <x-voodbuilder::search :canvas-preview="false" />
            @endif
        </div>
    @endif

    @if ((config('voodbuilder.notifications.enabled', true) && $showNotificationBell) || $canvasPreview)
        <div
            @class([$chromeWrapperClass])
            data-voodbuilder-desktop-chrome
            data-voodbuilder-chrome="notifications"
            {!! $canvasPreview ? $chromeHidden($showNotificationBell) : '' !!}
        >
            @if ($canvasPreview)
                <button
                    type="button"
                    class="voodbuilder-header-icon-btn relative text-vp-text-2"
                    data-voodbuilder-notification-bell-preview
                    data-gjs-type="voodbuilder-chrome-button"
                    data-gjs-selectable="false"
                    title="{{ __('voodbuilder::pro.grapesjs.blocks.site_header_bell_preview') }}"
                    aria-label="{{ __('voodbuilder::notifications.bell_label') }}"
                >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </button>
            @else
                @auth
                    <livewire:voodbuilder.site-notification-bell wire:key="nav-bell-desktop" />
                @endauth
            @endif
        </div>
    @endif

    @if ($showProfileMenu || $canvasPreview)
        <div
            @class([$chromeWrapperClass])
            data-voodbuilder-desktop-chrome
            data-voodbuilder-chrome="profile"
            {!! $canvasPreview ? $chromeHidden($showProfileMenu) : '' !!}
        >
            <x-voodbuilder::nav-profile-menu :canvas-preview="$canvasPreview" />
        </div>
    @endif

    <button
        type="button"
        @class([
            'h-9 w-9 items-center justify-center rounded-full text-vp-text-2 transition-colors hover:bg-vp-gray-soft hover:text-vp-text-1',
            $mobileToggleClass,
        ])
        data-mobile-nav-toggle
        data-gjs-type="default"
        data-gjs-selectable="false"
        aria-controls="voodbuilder-mobile-nav"
        aria-expanded="false"
        aria-label="{{ __('Open menu') }}"
        @if ($hideMobileToggle) hidden @endif
    >
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
        </svg>
    </button>
</div>
