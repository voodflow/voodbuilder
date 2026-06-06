@props([
    'hasDocSidebar' => false,
])

@php
    use Voodflow\Vpress\Models\VpressSettings;

    $showNotificationBell = (bool) VpressSettings::get('show_notification_bell', true);
    $showAccountLink = (bool) VpressSettings::get('show_account_link', true);
    $stickyNav = ! $hasDocSidebar && (bool) VpressSettings::get('sticky_nav', false);
    $user = auth()->user();
@endphp

<header @class([
    'pointer-events-none top-0 left-0 z-30 w-full',
    'sticky' => $stickyNav,
    'relative' => ! $hasDocSidebar && ! $stickyNav,
    'vp:fixed' => $hasDocSidebar,
]) role="banner">
    <div class="pointer-events-none relative h-16 whitespace-nowrap bg-vp-bg transition-colors">
        @if ($hasDocSidebar)
            <div class="pointer-events-auto absolute top-0 left-0 z-[2] hidden h-16 w-[var(--vp-sidebar-outer-width)] bg-vp-bg-alt vp:flex">
                <div class="ml-auto flex h-16 w-[var(--spacing-vp-sidebar)] shrink-0 items-center px-8">
                    <x-vpress::nav-title />
                </div>
            </div>
        @endif

        <div @class([
            'px-6 md:px-8',
            'vp:px-0' => $hasDocSidebar,
        ])>
            <div @class([
                'pointer-events-auto relative flex h-16 items-center justify-between gap-4',
                'mx-auto max-w-[calc(var(--width-vp-layout)-4rem)]' => ! $hasDocSidebar,
                'vp:pl-[var(--vp-sidebar-outer-width)] vp:pr-8' => $hasDocSidebar,
            ])>
                @unless ($hasDocSidebar)
                    <div class="flex min-w-0 items-center gap-6">
                        <x-vpress::nav-title />
                        <x-vpress::menu
                            menu="main"
                            class="hidden items-center md:flex"
                            link-class="inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1"
                        />
                    </div>
                @else
                    <x-vpress::menu
                        menu="main"
                        class="hidden shrink-0 items-center md:flex"
                        link-class="inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1"
                    />
                @endunless

                <div class="flex min-w-0 items-center justify-end gap-2">
                    <x-vpress::search />

                    <x-vpress::nav-more-menu />

                    @auth
                        @if (config('vpress.notifications.enabled', true) && $showNotificationBell)
                            <livewire:vpress.site-notification-bell wire:key="nav-bell-desktop" />
                        @endif

                        @if ($showAccountLink && config('vpress.account.enabled', true) && Route::has('vpress.account'))
                            <a
                                href="{{ route('vpress.account') }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-md text-vp-text-2 transition-colors hover:text-vp-brand-1"
                                aria-label="{{ __('vpress::account.nav') }}"
                            >
                                @if ($avatarUrl = \Voodflow\Vpress\Support\UserAvatar::url($user))
                                    <img src="{{ $avatarUrl }}" alt="" class="h-[22px] w-[22px] rounded-full object-cover">
                                @else
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                @endif
                            </a>
                        @endif
                    @endauth

                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md text-vp-text-2 transition-colors hover:text-vp-text-1 vp:hidden"
                        data-mobile-nav-toggle
                        data-label-open="{{ __('Open menu') }}"
                        data-label-close="{{ __('Close menu') }}"
                        aria-controls="vpress-mobile-nav"
                        aria-expanded="false"
                        aria-label="{{ __('Open menu') }}"
                    >
                        <svg class="h-6 w-6 [[data-mobile-nav].is-open_&]:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg class="hidden h-6 w-6 [[data-mobile-nav].is-open_&]:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        @unless ($hasDocSidebar)
            <div class="pointer-events-none w-full">
                <div class="h-px w-full bg-vp-divider"></div>
            </div>
        @endunless
    </div>

    @if ($hasDocSidebar)
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
