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
    class="VPNavMobile"
    data-mobile-nav
    hidden
    aria-hidden="true"
>
    <div class="VPNavMobileOverlay" data-mobile-nav-close tabindex="-1" aria-hidden="true"></div>

    <nav
        id="vpress-mobile-nav"
        class="VPNavMobilePanel"
        aria-label="{{ __('Mobile navigation') }}"
        data-mobile-nav-panel
    >
        <div class="VPNavMobileHeader">
            <span class="VPNavMobileTitle">{{ __('Menu') }}</span>
            <button
                type="button"
                class="VPNavBarAction VPNavMobileClose"
                data-mobile-nav-close
                aria-label="{{ __('Close menu') }}"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="VPNavMobileBody">
            @if ($mainItems->isNotEmpty())
                <ul class="VPNavMobileList">
                    @foreach ($mainItems as $item)
                        <li>
                            <a
                                href="{{ $item->resolveUrl() }}"
                                @class(['VPNavMobileLink', 'active' => $item->isActive()])
                                @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                data-mobile-nav-close
                            >
                                {{ __($item->label) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($extraItems->isNotEmpty())
                <ul class="VPNavMobileList VPNavMobileList--secondary">
                    @foreach ($extraItems as $item)
                        <li>
                            <a
                                href="{{ $item->resolveUrl() }}"
                                @class(['VPNavMobileLink', 'VPNavMobileLink--secondary', 'active' => $item->isActive()])
                                @if($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif
                                data-mobile-nav-close
                            >
                                {{ __($item->label) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="VPNavMobileSection">
                <span class="VPNavMobileSectionLabel">{{ __('Preferences') }}</span>

                @if (class_exists(\Voodflow\Tutorials\Support\LocaleSwitcher::class) && \Voodflow\Tutorials\Support\LocaleSwitcher::visible())
                    <div class="VPNavMobileLanguage">
                        <x-tutorials::language-switcher />
                    </div>
                @endif

                @if ($showThemeToggle)
                    <button
                        type="button"
                        data-theme-toggle
                        class="VPNavMobileUtility"
                        aria-label="{{ __('Toggle appearance') }}"
                        aria-pressed="false"
                    >
                        <span class="VPNavMobileUtilityIcon" aria-hidden="true">
                            <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                            <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </span>
                        <span>{{ __('Toggle appearance') }}</span>
                    </button>
                @endif
            </div>

            @auth
                @if (config('vpress.notifications.enabled', true) && $showNotificationBell)
                    <div class="VPNavMobileSection VPNavMobileSection--notifications">
                        <span class="VPNavMobileSectionLabel">{{ __('vpress::notifications.panel_title') }}</span>
                        <livewire:vpress.site-notification-bell wire:key="nav-bell-mobile" />
                    </div>
                @endif

                <ul class="VPNavMobileList VPNavMobileList--account">
                    @if ($showAccountLink && config('vpress.account.enabled', true) && Route::has('vpress.account'))
                        <li>
                            <a href="{{ route('vpress.account') }}" class="VPNavMobileLink VPNavMobileLink--account" data-mobile-nav-close>
                                <span class="VPNavMobileAccountAvatar" aria-hidden="true">
                                    @if ($avatarUrl = \Voodflow\Vpress\Support\UserAvatar::url($user))
                                        <img src="{{ $avatarUrl }}" alt="">
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    @endif
                                </span>
                                <span>{{ __('vpress::account.nav') }}</span>
                            </a>
                        </li>
                    @endif

                    @if(\Voodflow\Vpress\Support\AdminAccess::userCanAccessPanel())
                        <li>
                            <a href="{{ \Voodflow\Vpress\Support\AdminAccess::panelUrl() }}" class="VPNavMobileLink VPNavMobileLink--secondary" data-mobile-nav-close>
                                {{ __('Admin') }}
                            </a>
                        </li>
                    @endif

                    <li>
                        <form method="POST" action="{{ \Voodflow\Vpress\Support\VpressUrls::logout() }}">
                            @csrf
                            <button type="submit" class="VPNavMobileLink VPNavMobileLink--secondary VPNavMobileLink--button">
                                {{ __('vpress::auth.logout') }}
                            </button>
                        </form>
                    </li>
                </ul>
            @else
                <ul class="VPNavMobileList VPNavMobileList--account">
                    <li>
                        <a href="{{ \Voodflow\Vpress\Support\VpressUrls::login() }}" class="VPNavMobileLink VPNavMobileLink--account" data-mobile-nav-close>
                            {{ __('vpress::auth.login') }}
                        </a>
                    </li>
                    @if (config('vpress.auth.registration_enabled', true))
                        <li>
                            <a href="{{ \Voodflow\Vpress\Support\VpressUrls::register() }}" class="VPNavMobileLink VPNavMobileLink--account" data-mobile-nav-close>
                                {{ __('vpress::auth.register') }}
                            </a>
                        </li>
                    @endif
                </ul>
            @endauth
        </div>
    </nav>
</div>
