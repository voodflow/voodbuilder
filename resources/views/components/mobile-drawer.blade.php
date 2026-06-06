@props([
    'hasDocSidebar' => false,
])

@php
    use Voodflow\Vpress\Models\VpressSettings;
    use Voodflow\Vpress\Support\Navigation;

    $mainItems = Navigation::items('main');
    $extraItems = Navigation::items('header_extra');
    $showNotificationBell = (bool) VpressSettings::get('show_notification_bell', true);
    $showThemeToggle = (bool) VpressSettings::get('show_theme_toggle', true);
    $showAccountLink = (bool) VpressSettings::get('show_account_link', true);
    $user = auth()->user();
@endphp

<div
    class="fixed inset-0 z-50 [[data-mobile-nav].is-open]:pointer-events-auto"
    data-mobile-nav
    hidden
    aria-hidden="true"
>
    <div
        class="absolute inset-0 bg-black/45 opacity-0 transition-opacity duration-300 [[data-mobile-nav].is-open_&]:opacity-100"
        data-mobile-nav-close
        tabindex="-1"
        aria-hidden="true"
    ></div>

    <nav
        id="vpress-mobile-nav"
        class="pointer-events-auto absolute top-16 bottom-0 left-0 z-10 flex w-[min(100vw-3rem,320px)] -translate-x-full flex-col border-r border-vp-divider bg-vp-bg opacity-0 shadow-xl transition-[transform,opacity] duration-300 ease-out [[data-mobile-nav].is-open_&]:translate-x-0 [[data-mobile-nav].is-open_&]:opacity-100"
        aria-label="{{ __('Mobile navigation') }}"
        data-mobile-nav-panel
    >
        <div class="flex h-14 shrink-0 items-center justify-between border-b border-vp-divider px-4">
            <span class="text-sm font-semibold text-vp-text-1">{{ __('Menu') }}</span>
            <button
                type="button"
                class="inline-flex h-9 w-9 items-center justify-center rounded-full text-vp-text-2 transition-colors hover:bg-vp-gray-soft hover:text-vp-text-1"
                data-mobile-nav-close
                aria-label="{{ __('Close menu') }}"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-4">
            @if ($mainItems->isNotEmpty())
                <ul class="space-y-1">
                    @foreach ($mainItems as $item)
                        <li>
                            <a
                                href="{{ $item->resolveUrl() }}"
                                @class([
                                    'flex items-center gap-1.5 rounded-md px-3 py-2 text-sm font-medium text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1',
                                    'text-vp-brand-1' => $item->isActive(),
                                ])
                                @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                data-mobile-nav-close
                            >
                                <span>{{ __($item->label) }}</span>
                                @if($item->isExternal())
                                    <x-vpress::external-link-icon />
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($extraItems->isNotEmpty())
                <ul class="mt-4 space-y-1 border-t border-vp-divider pt-4">
                    @foreach ($extraItems as $item)
                        <li>
                            <a
                                href="{{ $item->resolveUrl() }}"
                                @class([
                                    'flex items-center gap-1.5 rounded-md px-3 py-2 text-xs font-medium tracking-wide text-vp-text-2 uppercase transition-colors hover:text-vp-brand-1',
                                    'text-vp-brand-1' => $item->isActive(),
                                ])
                                @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                data-mobile-nav-close
                            >
                                <span>{{ __($item->label) }}</span>
                                @if($item->isExternal())
                                    <x-vpress::external-link-icon />
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-6 border-t border-vp-divider pt-4">
                <span class="mb-2 block text-[11px] font-semibold tracking-[0.08em] text-vp-text-3 uppercase">{{ __('Preferences') }}</span>

                @if (class_exists(\Voodflow\Tutorials\Support\LocaleSwitcher::class) && \Voodflow\Tutorials\Support\LocaleSwitcher::visible())
                    <div class="mb-2">
                        <x-tutorials::language-switcher />
                    </div>
                @endif

                @if ($showThemeToggle)
                    <button
                        type="button"
                        data-theme-toggle
                        class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm text-vp-text-2 transition-colors hover:bg-vp-gray-soft hover:text-vp-text-1"
                        aria-label="{{ __('Toggle appearance') }}"
                        aria-pressed="false"
                    >
                        <svg class="h-5 w-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg class="hidden h-5 w-5 dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>{{ __('Toggle appearance') }}</span>
                    </button>
                @endif
            </div>

            @auth
                @if (config('vpress.notifications.enabled', true) && $showNotificationBell)
                    <div class="mt-6 border-t border-vp-divider pt-4">
                        <span class="mb-2 block text-[11px] font-semibold tracking-[0.08em] text-vp-text-3 uppercase">{{ __('vpress::notifications.panel_title') }}</span>
                        <livewire:vpress.site-notification-bell wire:key="nav-bell-mobile" />
                    </div>
                @endif

                <ul class="mt-6 space-y-1 border-t border-vp-divider pt-4">
                    @if ($showAccountLink && config('vpress.account.enabled', true) && Route::has('vpress.account'))
                        <li>
                            <a href="{{ route('vpress.account') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium text-vp-text-1 hover:bg-vp-gray-soft" data-mobile-nav-close>
                                @if ($avatarUrl = \Voodflow\Vpress\Support\UserAvatar::url($user))
                                    <img src="{{ $avatarUrl }}" alt="" class="h-6 w-6 rounded-full object-cover">
                                @endif
                                <span>{{ __('vpress::account.nav') }}</span>
                            </a>
                        </li>
                    @endif

                    @if(\Voodflow\Vpress\Support\AdminAccess::userCanAccessPanel())
                        <li>
                            <a href="{{ \Voodflow\Vpress\Support\AdminAccess::panelUrl() }}" class="block rounded-md px-3 py-2 text-xs font-medium tracking-wide text-vp-text-2 uppercase hover:text-vp-brand-1" data-mobile-nav-close>
                                {{ __('Admin') }}
                            </a>
                        </li>
                    @endif

                    <li>
                        <form method="POST" action="{{ \Voodflow\Vpress\Support\VpressUrls::logout() }}">
                            @csrf
                            <button type="submit" class="w-full rounded-md px-3 py-2 text-left text-xs font-medium tracking-wide text-vp-text-2 uppercase hover:text-vp-brand-1">
                                {{ __('vpress::auth.logout') }}
                            </button>
                        </form>
                    </li>
                </ul>
            @else
                <ul class="mt-6 space-y-1 border-t border-vp-divider pt-4">
                    <li>
                        <a href="{{ \Voodflow\Vpress\Support\VpressUrls::login() }}" class="block rounded-md px-3 py-2 text-sm font-medium text-vp-text-1 hover:bg-vp-gray-soft" data-mobile-nav-close>
                            {{ __('vpress::auth.login') }}
                        </a>
                    </li>
                    @if (config('vpress.auth.registration_enabled', true))
                        <li>
                            <a href="{{ \Voodflow\Vpress\Support\VpressUrls::register() }}" class="block rounded-md px-3 py-2 text-sm font-medium text-vp-text-1 hover:bg-vp-gray-soft" data-mobile-nav-close>
                                {{ __('vpress::auth.register') }}
                            </a>
                        </li>
                    @endif
                </ul>
            @endauth
        </div>
    </nav>
</div>
