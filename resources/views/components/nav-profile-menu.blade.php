@php
    use Voodflow\Vpress\Models\VpressSettings;
    use Voodflow\Vpress\Support\UserAvatar;

    $showThemeToggle = (bool) VpressSettings::get('show_theme_toggle', true);
    $showAccountLink = (bool) VpressSettings::get('show_account_link', true);
    $accountEnabled = $showAccountLink && config('vpress.account.enabled', true) && Route::has('vpress.account');
    $user = auth()->user();
    $avatarUrl = $user ? UserAvatar::url($user) : null;
@endphp

<div
    class="relative"
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        class="inline-flex h-9 w-9 items-center justify-center rounded-full text-vp-text-2 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
        aria-haspopup="menu"
        :aria-expanded="open"
        aria-label="{{ __('vpress::nav.menu_aria') }}"
        @click="open = ! open"
    >
        @if ($avatarUrl)
            <img src="{{ $avatarUrl }}" alt="" class="h-[26px] w-[26px] rounded-full object-cover">
        @else
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        role="menu"
        class="absolute top-[calc(100%+0.5rem)] right-0 z-50 min-w-[12rem] overflow-hidden rounded-lg border border-vp-divider bg-vp-bg-elv py-2 shadow-lg"
    >
        @auth
            @if ($accountEnabled)
                <a
                    href="{{ route('vpress.account') }}"
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
                    <span>{{ __('vpress::account.nav') }}</span>
                </a>
                <div class="my-1 h-px bg-vp-divider" aria-hidden="true"></div>
            @endif
        @endauth

        @if (class_exists(\Voodflow\Vtuts\Support\LocaleSwitcher::class) && \Voodflow\Vtuts\Support\LocaleSwitcher::visible())
            <div class="px-3 py-2">
                <div class="mb-1.5 text-[11px] font-semibold tracking-[0.08em] text-vp-text-3 uppercase">
                    {{ __('vtuts::language_switcher.label') }}
                </div>
                <x-vtuts::language-switcher variant="dropdown" />
            </div>
            <div class="my-1 h-px bg-vp-divider" aria-hidden="true"></div>
        @endif

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
            @if(\Voodflow\Vpress\Support\AdminAccess::userCanAccessPanel())
                <a
                    href="{{ \Voodflow\Vpress\Support\AdminAccess::panelUrl() }}"
                    role="menuitem"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
                >
                    {{ __('Admin') }}
                </a>
            @endif

            <form method="POST" action="{{ \Voodflow\Vpress\Support\VpressUrls::logout() }}">
                @csrf
                <button
                    type="submit"
                    role="menuitem"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
                >
                    {{ __('vpress::auth.logout') }}
                </button>
            </form>
        @else
            <a
                href="{{ \Voodflow\Vpress\Support\VpressUrls::login() }}"
                role="menuitem"
                class="flex items-center gap-2 px-3 py-2 text-sm text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
            >
                {{ __('vpress::auth.login') }}
            </a>
            @if (config('vpress.auth.registration_enabled', true))
                <a
                    href="{{ \Voodflow\Vpress\Support\VpressUrls::register() }}"
                    role="menuitem"
                    class="flex items-center gap-2 px-3 py-2 text-sm text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
                >
                    {{ __('vpress::auth.register') }}
                </a>
            @endif
        @endauth
    </div>
</div>
