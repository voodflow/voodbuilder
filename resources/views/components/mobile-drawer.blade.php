@props([
    'hasDocSidebar' => false,
])

@php
    use Illuminate\Support\Facades\Route;
    use Voodflow\Vpress\Models\VpressSettings;
    use Voodflow\Vpress\Support\AdminAccess;
    use Voodflow\Vpress\Support\Navigation;
    use Voodflow\Vpress\Support\UserAvatar;
    use Voodflow\Vpress\Support\VpressUrls;

    $mainItems = Navigation::items('main');
    $extraItems = Navigation::items('header_extra');
    $showNotificationBell = (bool) VpressSettings::get('show_notification_bell', true);
    $showThemeToggle = (bool) VpressSettings::get('show_theme_toggle', true);
    $showAccountLink = (bool) VpressSettings::get('show_account_link', true);
    $searchEnabled = Route::has('vpress.search');
    $user = auth()->user();
    $avatarUrl = $user ? UserAvatar::url($user) : null;
    $brandName = VpressSettings::brandName();
    $logoMobileUrl = VpressSettings::logoMobileUrl();
    $logoUrl = VpressSettings::logoUrl();
@endphp

<div
    class="vpress-mobile-nav"
    data-mobile-nav
    hidden
    aria-hidden="true"
>
    <div
        class="vpress-mobile-nav__overlay"
        data-mobile-nav-close
        tabindex="-1"
        aria-hidden="true"
    ></div>

    <nav
        id="vpress-mobile-nav"
        class="vpress-mobile-nav__panel"
        aria-label="{{ __('Mobile navigation') }}"
        data-mobile-nav-panel
    >
        <div class="vpress-mobile-nav__header">
            <div class="vpress-mobile-nav__brand">
                <a href="{{ VpressUrls::home() }}" data-mobile-nav-close>
                    @if ($logoMobileUrl || $logoUrl)
                        <img
                            src="{{ $logoMobileUrl ?? $logoUrl }}"
                            alt=""
                            class="h-8 w-auto max-w-[140px] object-contain object-left"
                        >
                        <span class="sr-only">{{ $brandName }}</span>
                    @else
                        <span>{{ $brandName }}</span>
                    @endif
                </a>
            </div>

            <button
                type="button"
                class="vpress-mobile-nav__close"
                data-mobile-nav-close
                aria-label="{{ __('Close menu') }}"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="vpress-mobile-nav__body">
            @if ($mainItems->isNotEmpty())
                <ul class="vpress-mobile-nav__links">
                    @foreach ($mainItems as $item)
                        <li>
                            <a
                                href="{{ $item->resolveUrl() }}"
                                @class([
                                    'vpress-mobile-nav__link',
                                    'is-active' => $item->isActive(),
                                ])
                                @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                data-mobile-nav-close
                            >
                                <span>{{ __($item->label) }}</span>
                                @if ($item->isExternal())
                                    <x-vpress::external-link-icon />
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($extraItems->isNotEmpty())
                <div class="vpress-mobile-nav__section">
                    <ul class="vpress-mobile-nav__links">
                        @foreach ($extraItems as $item)
                            <li>
                                <a
                                    href="{{ $item->resolveUrl() }}"
                                    @class([
                                        'vpress-mobile-nav__link vpress-mobile-nav__link--secondary',
                                        'is-active' => $item->isActive(),
                                    ])
                                    @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                    data-mobile-nav-close
                                >
                                    <span>{{ __($item->label) }}</span>
                                    @if ($item->isExternal())
                                        <x-vpress::external-link-icon />
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="vpress-mobile-nav__footer">
            <div class="vpress-mobile-nav__toolbar">
                @if ($searchEnabled)
                    <a
                        href="{{ VpressUrls::search() }}"
                        class="vpress-mobile-nav__tool"
                        data-mobile-nav-close
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>{{ __('vpress::search.button') }}</span>
                    </a>
                @endif

                @if (class_exists(\Voodflow\Vtuts\Support\LocaleSwitcher::class) && \Voodflow\Vtuts\Support\LocaleSwitcher::visible())
                    <div class="vpress-mobile-nav__tool">
                        <x-vtuts::language-switcher />
                    </div>
                @endif

                @if ($showThemeToggle)
                    <button
                        type="button"
                        class="vpress-mobile-nav__tool"
                        data-theme-toggle
                        aria-label="{{ __('Toggle appearance') }}"
                        aria-pressed="false"
                    >
                        <svg class="h-4 w-4 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg class="hidden h-4 w-4 dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <span>{{ __('Toggle appearance') }}</span>
                    </button>
                @endif
            </div>

            @auth
                @if (config('vpress.notifications.enabled', true) && $showNotificationBell)
                    <div class="vpress-mobile-nav__section">
                        <livewire:vpress.site-notification-bell wire:key="nav-bell-mobile" />
                    </div>
                @endif

                <div class="vpress-mobile-nav__actions">
                    @if ($showAccountLink && config('vpress.account.enabled', true) && Route::has('vpress.account'))
                        <a href="{{ route('vpress.account') }}" class="vpress-mobile-nav__account" data-mobile-nav-close>
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="">
                            @endif
                            <span>{{ __('vpress::account.nav') }}</span>
                        </a>
                    @endif

                    @if (AdminAccess::userCanAccessPanel())
                        <a href="{{ AdminAccess::panelUrl() }}" class="vpress-mobile-nav__action" data-mobile-nav-close>
                            {{ __('Admin') }}
                        </a>
                    @endif

                    <form method="POST" action="{{ VpressUrls::logout() }}">
                        @csrf
                        <button type="submit" class="vpress-mobile-nav__action">
                            {{ __('vpress::auth.logout') }}
                        </button>
                    </form>
                </div>
            @else
                <div class="vpress-mobile-nav__actions">
                    <a href="{{ VpressUrls::login() }}" class="vpress-mobile-nav__action" data-mobile-nav-close>
                        {{ __('vpress::auth.login') }}
                    </a>
                    @if (config('vpress.auth.registration_enabled', true))
                        <a href="{{ VpressUrls::register() }}" class="vpress-mobile-nav__action" data-mobile-nav-close>
                            {{ __('vpress::auth.register') }}
                        </a>
                    @endif
                </div>
            @endauth
        </div>
    </nav>
</div>
