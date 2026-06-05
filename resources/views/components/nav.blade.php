@php
    use Voodflow\Vpress\Models\VpressSettings;

    $siteTitle = VpressSettings::siteTitle();
    $brandName = VpressSettings::brandName();
    $logoUrl = VpressSettings::logoUrl();
    $showBrandName = (bool) VpressSettings::get('show_site_title', true);
    $showNotificationBell = (bool) VpressSettings::get('show_notification_bell', true);
    $showThemeToggle = (bool) VpressSettings::get('show_theme_toggle', true);
    $showAccountLink = (bool) VpressSettings::get('show_account_link', true);
    $user = auth()->user();
@endphp

<header class="VPNav" role="banner">
    <div class="VPNavBar">
        <div class="wrap">
            <div class="VPNavBarStart">
                <a href="{{ route('home') }}" class="VPNavBarTitle" aria-label="{{ $siteTitle }}">
                    @if ($logoUrl)
                        <img src="{{ $logoUrl }}" alt="" class="VPNavBarLogo">
                    @endif
                    @if ($showBrandName)
                        <span class="VPNavBarTitleText">{{ $brandName }}</span>
                    @endif
                </a>

                <x-vpress::menu menu="main" class="VPNavBarMenu VPNavBarMenu--desktop" />
            </div>

            <div class="VPNavBarActions VPNavBarActions--desktop">
                <x-vpress::menu
                    menu="header_extra"
                    link-class="VPNavBarMenuLink VPNavBarMenuLink--extra"
                    :extra="true"
                    :wrapped="false"
                />

                @if(\Voodflow\Vpress\Support\Navigation::items('header_extra')->isNotEmpty())
                    <span class="VPNavBarDivider" aria-hidden="true"></span>
                @endif

                @if (class_exists(\Voodflow\Tutorials\Support\LocaleSwitcher::class))
                    <x-tutorials::language-switcher />
                @endif

                @if ($showThemeToggle)
                    <button
                        type="button"
                        data-theme-toggle
                        class="VPNavBarAction"
                        aria-label="{{ __('Toggle appearance') }}"
                        aria-pressed="false"
                    >
                        <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </button>
                @endif

                @auth
                    @if (config('vpress.notifications.enabled', true) && $showNotificationBell)
                        <livewire:vpress.site-notification-bell wire:key="nav-bell-desktop" />
                    @endif

                    @if ($showAccountLink && config('vpress.account.enabled', true) && Route::has('vpress.account'))
                        <a
                            href="{{ route('vpress.account') }}"
                            class="VPNavBarAction VPNavBarAccount"
                            aria-label="{{ __('vpress::account.nav') }}"
                        >
                            @if ($user?->getFilamentAvatarUrl())
                                <img
                                    src="{{ $user->getFilamentAvatarUrl() }}"
                                    alt=""
                                    class="VPNavBarAvatar"
                                >
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            @endif
                        </a>
                    @endif

                    @if(\Voodflow\Vpress\Support\AdminAccess::userCanAccessPanel())
                        <a href="{{ \Voodflow\Vpress\Support\AdminAccess::panelUrl() }}" class="VPNavBarMenuLink VPNavBarMenuLink--extra">{{ __('Admin') }}</a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="VPNavBarMenuLink VPNavBarMenuLink--extra">{{ __('Log out') }}</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="VPNavBarMenuLink VPNavBarMenuLink--extra">{{ __('Log in') }}</a>
                @endauth
            </div>

            <button
                type="button"
                class="VPNavBarAction VPNavBarHamburger"
                data-mobile-nav-toggle
                data-label-open="{{ __('Open menu') }}"
                data-label-close="{{ __('Close menu') }}"
                aria-controls="vpress-mobile-nav"
                aria-expanded="false"
                aria-label="{{ __('Open menu') }}"
            >
                <svg class="VPNavBarHamburgerIcon VPNavBarHamburgerIcon--open w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
                <svg class="VPNavBarHamburgerIcon VPNavBarHamburgerIcon--close w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </div>

    <x-vpress::mobile-drawer />
</header>
