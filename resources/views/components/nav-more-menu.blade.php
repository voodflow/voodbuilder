@php
    use Voodflow\Vpress\Models\VpressSettings;

    $showThemeToggle = (bool) VpressSettings::get('show_theme_toggle', true);
    $showAccountLink = (bool) VpressSettings::get('show_account_link', true);
    $user = auth()->user();
@endphp

<div
    class="relative hidden md:block"
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        class="inline-flex h-9 w-9 items-center justify-center rounded-md text-vp-text-2 transition-colors hover:text-vp-text-1"
        aria-haspopup="menu"
        :aria-expanded="open"
        aria-label="{{ __('More options') }}"
        @click="open = ! open"
    >
        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="12" cy="5" r="1.75"/>
            <circle cx="12" cy="12" r="1.75"/>
            <circle cx="12" cy="19" r="1.75"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        role="menu"
        class="absolute top-[calc(100%+0.5rem)] right-0 z-50 min-w-[12rem] overflow-hidden rounded-lg border border-vp-divider bg-vp-bg-elv py-2 shadow-lg"
    >
        @if(\Voodflow\Vpress\Support\Navigation::items('header_extra')->isNotEmpty())
            <div class="px-2 pb-1">
                <x-vpress::menu
                    menu="header_extra"
                    class="flex flex-col"
                    link-class="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
                    :extra="true"
                    :wrapped="false"
                />
            </div>
            <div class="my-1 h-px bg-vp-divider" aria-hidden="true"></div>
        @endif

        @if (class_exists(\Voodflow\Tutorials\Support\LocaleSwitcher::class))
            <div class="px-3 py-2">
                <div class="mb-1.5 text-[11px] font-semibold tracking-[0.08em] text-vp-text-3 uppercase">
                    {{ __('tutorials::language_switcher.label') }}
                </div>
                <x-tutorials::language-switcher variant="dropdown" />
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
