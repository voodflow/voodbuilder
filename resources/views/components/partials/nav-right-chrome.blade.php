@php
    $desktopChromeClass = $desktopChromeClass ?? 'vp:block';
    $desktopFlexClass = $desktopFlexClass ?? 'vp:flex';
    $mobileToggleClass = $mobileToggleClass ?? 'hidden max-vp:inline-flex';
    $showNotificationBell = (bool) ($showNotificationBell ?? true);
    $canvasPreview = (bool) ($canvasPreview ?? false);
    $hideSearch = (bool) ($hideSearch ?? false);
    $hideExtraMenu = (bool) ($hideExtraMenu ?? false);
    $hideMobileToggle = (bool) ($hideMobileToggle ?? false);
@endphp

<div class="flex shrink-0 items-center justify-end gap-2 vp:gap-3">
    @unless ($hideExtraMenu)
        <x-voodbuilder::menu
            menu="header_extra"
            class="hidden shrink-0 items-center {{ $desktopFlexClass }}"
            link-class="inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-2 transition-colors hover:text-vp-brand-1"
        />
    @endunless

    @unless ($hideSearch)
        <div @class(['hidden', $desktopChromeClass])>
            <x-voodbuilder::search />
        </div>
    @endunless

    @auth
        @if (config('voodbuilder.notifications.enabled', true) && $showNotificationBell)
            <div @class(['hidden', $desktopChromeClass])>
                @if ($canvasPreview)
                    <button
                        type="button"
                        class="voodbuilder-header-icon-btn relative"
                        data-voodbuilder-notification-bell-preview
                        title="{{ __('voodbuilder::pro.grapesjs.blocks.site_header_bell_preview') }}"
                        aria-label="{{ __('voodbuilder::notifications.bell_label') }}"
                    >
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </button>
                @else
                    <livewire:voodbuilder.site-notification-bell wire:key="nav-bell-desktop" />
                @endif
            </div>
        @endif
    @endauth

    <div @class(['hidden', $desktopChromeClass])>
        <x-voodbuilder::nav-profile-menu />
    </div>

    <button
        type="button"
        @class([
            'h-9 w-9 items-center justify-center rounded-full text-vp-text-2 transition-colors hover:bg-vp-gray-soft hover:text-vp-text-1',
            $mobileToggleClass,
        ])
        data-mobile-nav-toggle
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
