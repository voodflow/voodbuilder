@props([
    'hasDocSidebar' => false,
    'showReadingProgress' => false,
])

@php
    use Voodflow\Vpress\Models\VpressSettings;

    $showNotificationBell = (bool) VpressSettings::get('show_notification_bell', true);
    $stickyNav = ! $hasDocSidebar && (bool) VpressSettings::get('sticky_nav', false);
@endphp

<header @class([
    'pointer-events-none top-0 left-0 z-30 w-full',
    'sticky' => $stickyNav,
    'relative' => ! $hasDocSidebar && ! $stickyNav,
    'vp:fixed' => $hasDocSidebar,
]) role="banner">
    <div class="pointer-events-none relative h-16 whitespace-nowrap bg-vp-bg transition-colors">
        <div @class([
            'pointer-events-auto absolute top-0 left-0 z-[2] hidden h-16 w-[var(--vp-sidebar-outer-width)] vp:flex',
            'bg-vp-bg-alt' => $hasDocSidebar,
            'bg-vp-bg' => ! $hasDocSidebar,
        ])>
            <div class="ml-auto flex h-16 w-[var(--spacing-vp-sidebar)] shrink-0 items-center px-8">
                <x-vpress::nav-title />
            </div>
        </div>

        <div class="px-6 md:px-8 vp:px-0">
            <div @class([
                'pointer-events-auto relative flex h-16 items-center justify-between gap-4',
                'mx-auto max-w-[calc(var(--width-vp-layout)-4rem)]',
                'vp:mx-0 vp:max-w-none vp:pl-[var(--vp-sidebar-outer-width)] vp:pr-8',
            ])>
                <div class="hidden min-w-0 max-vp:flex max-vp:items-center">
                    <x-vpress::nav-title />
                </div>

                <div class="hidden shrink-0 items-center gap-1 vp:flex">
                    <x-vpress::menu
                        menu="main"
                        :wrapped="false"
                        link-class="inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1"
                    />
                    <x-vpress::docs-menu />
                </div>

                <div class="flex min-w-0 items-center justify-end gap-2 vp:gap-3">
                    <x-vpress::menu
                        menu="header_extra"
                        class="hidden shrink-0 items-center vp:flex"
                        link-class="inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-2 transition-colors hover:text-vp-brand-1"
                    />

                    <div class="hidden vp:block">
                        <x-vpress::search />
                    </div>

                    @auth
                        @if (config('vpress.notifications.enabled', true) && $showNotificationBell)
                            <div class="hidden vp:block">
                                <livewire:vpress.site-notification-bell wire:key="nav-bell-desktop" />
                            </div>
                        @endif
                    @endauth

                    <div class="hidden vp:block">
                        <x-vpress::nav-profile-menu />
                    </div>

                    <button
                        type="button"
                        class="hidden h-9 w-9 max-vp:inline-flex items-center justify-center rounded-full text-vp-text-2 transition-colors hover:bg-vp-gray-soft hover:text-vp-text-1"
                        data-mobile-nav-toggle
                        aria-controls="vpress-mobile-nav"
                        aria-expanded="false"
                        aria-label="{{ __('Open menu') }}"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        @unless ($hasDocSidebar || $showReadingProgress)
            <div class="pointer-events-none w-full">
                <div class="h-px w-full bg-vp-divider"></div>
            </div>
        @endunless
    </div>

    @if ($showReadingProgress)
        <div
            class="pointer-events-none relative h-[2px] w-full bg-vp-divider"
            data-vpress-progress
            aria-hidden="true"
        >
            <div
                data-reading-progress
                class="absolute top-0 left-0 h-full bg-vp-brand-1 transition-[width] duration-100 ease-out"
                style="width: 0%"
            ></div>
        </div>
    @endif

    <x-vpress::mobile-drawer :has-doc-sidebar="$hasDocSidebar" />
</header>
