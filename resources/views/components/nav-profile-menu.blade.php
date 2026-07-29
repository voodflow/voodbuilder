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

<div
    class="voodbuilder-nav-profile-menu relative"
    data-voodbuilder-profile-menu
    @if ($canvasPreview) data-gjs-selectable="false" @endif
>
    <button
        type="button"
        @class([
            'voodbuilder-header-icon-btn',
            'text-vp-text-2' => $canvasPreview,
        ])
        data-voodbuilder-profile-menu-toggle
        @if ($canvasPreview)
            data-gjs-type="voodbuilder-chrome-button"
            data-gjs-selectable="false"
        @endif
        aria-haspopup="menu"
        aria-expanded="false"
        aria-label="{{ __('voodbuilder::nav.menu_aria') }}"
    >
        @if (! $canvasPreview && $avatarUrl)
            <img src="{{ $avatarUrl }}" alt="" class="h-[26px] w-[26px] rounded-full object-cover">
        @else
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true" data-vb-chrome-icon="user"><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/></svg>
        @endif
    </button>

    <div
        data-voodbuilder-profile-menu-panel
        role="menu"
        hidden
        class="voodbuilder-nav-profile-menu__dropdown voodbuilder-dropdown-panel absolute top-[calc(100%+0.5rem)] right-0 z-50 min-w-48"
    >
        @if ($canvasPreview)
            <p class="px-3.5 py-2 text-xs text-vp-text-3">
                {{ __('voodbuilder::pro.grapesjs.blocks.site_nav_preview') }}
            </p>
            <div class="voodbuilder-dropdown-separator" aria-hidden="true"></div>
            <span role="menuitem" data-gjs-type="default" data-gjs-selectable="false">
                {{ __('voodbuilder::account.nav') }}
            </span>
            @if ($showThemeToggle)
                <span role="menuitem" data-gjs-type="default" data-gjs-selectable="false">
                    Light / dark
                </span>
            @endif
            <span role="menuitem" data-gjs-type="default" data-gjs-selectable="false">
                {{ __('voodbuilder::auth.login') }}
            </span>
        @else
            @auth
                @if ($accountEnabled)
                    <a
                        href="{{ route('voodbuilder.account') }}"
                        role="menuitem"
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
                    <div class="voodbuilder-dropdown-separator" aria-hidden="true"></div>
                @endif
            @endauth

            <x-voodbuilder::optional-language-switcher variant="dropdown" :labeled="true" />

            @if ($showThemeToggle)
                <x-voodbuilder::theme-toggle-button variant="menu" />
            @endif

            @auth
                @foreach (\Voodflow\Voodbuilder\Support\ProfileMenuLinkRegistry::links() as $profileMenuLink)
                    <a
                        href="{{ $profileMenuLink['url'] }}"
                        role="menuitem"
                    >
                        {{ $profileMenuLink['label'] }}
                    </a>
                    <div class="voodbuilder-dropdown-separator" aria-hidden="true"></div>
                @endforeach

                @if(\Voodflow\Voodbuilder\Support\AdminAccess::userCanAccessPanel())
                    <a
                        href="{{ \Voodflow\Voodbuilder\Support\AdminAccess::panelUrl() }}"
                        role="menuitem"
                    >
                        {{ __('Admin') }}
                    </a>
                @endif

                <form method="POST" action="{{ \Voodflow\Voodbuilder\Support\VoodbuilderUrls::logout() }}">
                    @csrf
                    <button
                        type="submit"
                        role="menuitem"
                    >
                        {{ __('voodbuilder::auth.logout') }}
                    </button>
                </form>
            @else
                <a
                    href="{{ \Voodflow\Voodbuilder\Support\VoodbuilderUrls::login() }}"
                    role="menuitem"
                >
                    {{ __('voodbuilder::auth.login') }}
                </a>
                @if (config('voodbuilder.auth.registration_enabled', true))
                    <a
                        href="{{ \Voodflow\Voodbuilder\Support\VoodbuilderUrls::register() }}"
                        role="menuitem"
                    >
                        {{ __('voodbuilder::auth.register') }}
                    </a>
                @endif
            @endauth
        @endif
    </div>
</div>
