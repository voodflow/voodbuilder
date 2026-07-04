@props([
    'canvasPreview' => false,
])

@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Voodbuilder\Support\UserAvatar;

    $showThemeToggle = (bool) VoodbuilderSettings::get('show_theme_toggle', true);
    $showAccountLink = (bool) VoodbuilderSettings::get('show_account_link', true);
    $accountEnabled = $showAccountLink && config('voodbuilder.account.enabled', true) && Route::has('voodbuilder.account');
    $user = auth()->user();
    $avatarUrl = $user ? UserAvatar::url($user) : null;
@endphp

@if ($canvasPreview)
    <div
        class="voodbuilder-nav-profile-menu relative"
        data-voodbuilder-profile-menu
        data-gjs-selectable="false"
    >
        <button
            type="button"
            class="voodbuilder-header-icon-btn"
            data-voodbuilder-profile-menu-toggle
            data-gjs-type="default"
            data-gjs-selectable="false"
            aria-haspopup="menu"
            aria-expanded="false"
            aria-label="{{ __('voodbuilder::nav.menu_aria') }}"
        >
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </button>
    </div>
@else
    <div
        class="voodbuilder-nav-profile-menu relative"
        data-voodbuilder-profile-menu
    >
        <button
            type="button"
            class="voodbuilder-header-icon-btn"
            data-voodbuilder-profile-menu-toggle
            aria-haspopup="menu"
            aria-expanded="false"
            aria-label="{{ __('voodbuilder::nav.menu_aria') }}"
        >
            @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="" class="h-[26px] w-[26px] rounded-full object-cover">
            @else
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            @endif
        </button>

        <div
            data-voodbuilder-profile-menu-panel
            role="menu"
            hidden
            class="voodbuilder-nav-profile-menu__dropdown absolute top-[calc(100%+0.5rem)] right-0 z-50 min-w-48 overflow-hidden rounded-lg border border-vp-divider bg-vp-bg-elv py-2 shadow-lg"
        >
            @auth
                @if ($accountEnabled)
                    <a
                        href="{{ route('voodbuilder.account') }}"
                        role="menuitem"
                        class="flex items-center gap-2.5 px-3 py-2 text-sm font-medium text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
                    >
                        @if ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="" class="h-6 w-6 rounded-full object-cover">
                        @else
                            <svg class="h-5 w-5 text-vp-text-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        @endif
                        <span>{{ __('voodbuilder::account.nav') }}</span>
                    </a>
                    <div class="my-1 h-px bg-vp-divider" aria-hidden="true"></div>
                @endif
            @endauth

            <x-voodbuilder::optional-language-switcher variant="dropdown" :labeled="true" />

            @if ($showThemeToggle)
                <button
                    type="button"
                    role="menuitem"
                    data-theme-toggle
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
                    aria-pressed="false"
                >
                    <svg class="h-4 w-4 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    <svg class="hidden h-4 w-4 dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    <span>{{ __('Toggle appearance') }}</span>
                </button>
            @endif

            @auth
                @foreach (\Voodflow\Voodbuilder\Support\ProfileMenuLinkRegistry::links() as $profileMenuLink)
                    <a
                        href="{{ $profileMenuLink['url'] }}"
                        role="menuitem"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
                    >
                        {{ $profileMenuLink['label'] }}
                    </a>
                    <div class="my-1 h-px bg-vp-divider" aria-hidden="true"></div>
                @endforeach

                @if(\Voodflow\Voodbuilder\Support\AdminAccess::userCanAccessPanel())
                    <a
                        href="{{ \Voodflow\Voodbuilder\Support\AdminAccess::panelUrl() }}"
                        role="menuitem"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
                    >
                        {{ __('Admin') }}
                    </a>
                @endif

                <form method="POST" action="{{ \Voodflow\Voodbuilder\Support\VoodbuilderUrls::logout() }}">
                    @csrf
                    <button
                        type="submit"
                        role="menuitem"
                        class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
                    >
                        {{ __('voodbuilder::auth.logout') }}
                    </button>
                </form>
            @else
                <a
                    href="{{ \Voodflow\Voodbuilder\Support\VoodbuilderUrls::login() }}"
                    role="menuitem"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
                >
                    {{ __('voodbuilder::auth.login') }}
                </a>
                @if (config('voodbuilder.auth.registration_enabled', true))
                    <a
                        href="{{ \Voodflow\Voodbuilder\Support\VoodbuilderUrls::register() }}"
                        role="menuitem"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
                    >
                        {{ __('voodbuilder::auth.register') }}
                    </a>
                @endif
            @endauth
        </div>
    </div>
@endif
