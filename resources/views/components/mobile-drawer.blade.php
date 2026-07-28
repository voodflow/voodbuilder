@props([
    'hasDocSidebar' => false,
    'enableNotifications' => true,
    'canvasPreview' => false,
    'brandConfig' => [],
])

@php
    use Illuminate\Support\Facades\Route;
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Voodbuilder\Support\AdminAccess;
    use Voodflow\Voodbuilder\Support\Navigation;
    use Voodflow\Voodbuilder\Support\UserAvatar;
    use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

    $mainItems = Navigation::items('main');
    $extraItems = Navigation::items('header_extra');
    $docSections = class_exists(\Voodflow\Vdocs\Support\DocNavigation::class)
        && \Voodflow\Vdocs\Support\DocNavigation::shouldAutoInject()
        && Route::has('vdocs.index')
        ? \Voodflow\Vdocs\Support\DocNavigation::sections()
        : collect();
    $docsNavActive = class_exists(\Voodflow\Vdocs\Support\DocNavigation::class)
        && \Voodflow\Vdocs\Support\DocNavigation::shouldAutoInject()
        && \Voodflow\Vdocs\Support\DocNavigation::isActive();
    $showNotificationBell = $enableNotifications && (bool) VoodbuilderSettings::get('show_notification_bell', true);
    $showThemeToggle = (bool) VoodbuilderSettings::get('show_theme_toggle', true);
    $showAccountLink = (bool) VoodbuilderSettings::get('show_account_link', true);
    $searchEnabled = Route::has('voodbuilder.search');
    $user = auth()->user();
    $avatarUrl = $user ? UserAvatar::url($user) : null;
    $brandConfig = is_array($brandConfig) ? $brandConfig : [];
    $cookieConsent = function_exists('cookie_consent_settings') ? cookie_consent_settings() : null;
@endphp

<div
    class="voodbuilder-mobile-nav"
    data-mobile-nav
    data-gjs-selectable="false"
    data-gjs-editable="false"
    data-gjs-droppable="false"
    data-gjs-removable="false"
    data-gjs-copyable="false"
    hidden
    aria-hidden="true"
>
    <div
        class="voodbuilder-mobile-nav__overlay"
        data-mobile-nav-close
        tabindex="-1"
        aria-hidden="true"
    ></div>

    <nav
        id="voodbuilder-mobile-nav"
        class="voodbuilder-mobile-nav__panel"
        aria-label="{{ __('Mobile navigation') }}"
        data-mobile-nav-panel
    >
        <div class="voodbuilder-mobile-nav__header">
            <div class="voodbuilder-mobile-nav__brand">
                <x-voodbuilder::nav-title
                    :config="$brandConfig"
                    link-class="inline-flex items-center gap-2 text-base font-semibold text-vp-text-1"
                    desktop-logo-class="h-8 w-auto max-w-[140px] object-contain object-left"
                    mobile-logo-class="h-8 w-auto max-w-[140px] object-contain object-left"
                    data-mobile-nav-close
                />
            </div>

            <button
                type="button"
                class="voodbuilder-mobile-nav__close"
                data-mobile-nav-close
                aria-label="{{ __('Close menu') }}"
            >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="voodbuilder-mobile-nav__body">
            @if ($mainItems->isNotEmpty())
                <ul class="voodbuilder-mobile-nav__links">
                    @foreach ($mainItems as $item)
                        <x-voodbuilder::menu-nav-item :item="$item" :mobile="true" />
                    @endforeach
                </ul>
            @endif

            @if ($extraItems->isNotEmpty())
                <div @class(['voodbuilder-mobile-nav__section' => $mainItems->isNotEmpty()])>
                    <ul class="voodbuilder-mobile-nav__links">
                        @foreach ($extraItems as $item)
                            <x-voodbuilder::menu-nav-item :item="$item" :mobile="true" />
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($docSections->isNotEmpty())
                <div @class(['voodbuilder-mobile-nav__section' => $mainItems->isNotEmpty() || $extraItems->isNotEmpty()])>
                    <ul class="voodbuilder-mobile-nav__links">
                        <x-voodbuilder::mobile-docs-nav :sections="$docSections" :active="$docsNavActive" />
                    </ul>
                </div>
            @endif
        </div>

        <div class="voodbuilder-mobile-nav__footer">
            <div class="voodbuilder-mobile-nav__toolbar">
                @if ($searchEnabled)
                    <a
                        href="{{ VoodbuilderUrls::search() }}"
                        class="voodbuilder-mobile-nav__tool"
                        data-mobile-nav-close
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <span>{{ __('voodbuilder::search.button') }}</span>
                    </a>
                @endif

                <x-voodbuilder::optional-language-switcher variant="mobile-tool" />

                @if ($showThemeToggle)
                    <x-voodbuilder::theme-toggle-button variant="mobile" />
                @endif
            </div>

            @auth
                @if (config('voodbuilder.notifications.enabled', true) && $showNotificationBell)
                    <div class="voodbuilder-mobile-nav__section">
                        @if ($canvasPreview)
                            <button
                                type="button"
                                class="voodbuilder-mobile-nav__tool"
                                disabled
                                aria-label="{{ __('voodbuilder::notifications.bell_label') }}"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                <span>{{ __('voodbuilder::notifications.bell_label') }}</span>
                            </button>
                        @else
                            <livewire:voodbuilder.site-notification-bell wire:key="nav-bell-mobile" />
                        @endif
                    </div>
                @endif

                <div class="voodbuilder-mobile-nav__actions">
                    @if ($canvasPreview)
                        <span class="voodbuilder-mobile-nav__action">{{ __('voodbuilder::account.nav') }}</span>
                    @else
                        @if ($showAccountLink && config('voodbuilder.account.enabled', true) && Route::has('voodbuilder.account'))
                            <a href="{{ route('voodbuilder.account') }}" class="voodbuilder-mobile-nav__account" data-mobile-nav-close>
                                @if ($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="">
                                @endif
                                <span>{{ __('voodbuilder::account.nav') }}</span>
                            </a>
                        @endif

                        @foreach (\Voodflow\Voodbuilder\Support\ProfileMenuLinkRegistry::links() as $profileMenuLink)
                            <a href="{{ $profileMenuLink['url'] }}" class="voodbuilder-mobile-nav__action" data-mobile-nav-close>
                                {{ $profileMenuLink['label'] }}
                            </a>
                        @endforeach

                        @if (AdminAccess::userCanAccessPanel())
                            <a href="{{ AdminAccess::panelUrl() }}" class="voodbuilder-mobile-nav__action" data-mobile-nav-close>
                                {{ __('Admin') }}
                            </a>
                        @endif

                        <form method="POST" action="{{ VoodbuilderUrls::logout() }}">
                            @csrf
                            <button type="submit" class="voodbuilder-mobile-nav__action">
                                {{ __('voodbuilder::auth.logout') }}
                            </button>
                        </form>
                    @endif
                </div>
            @else
                <div class="voodbuilder-mobile-nav__actions">
                    <a href="{{ VoodbuilderUrls::login() }}" class="voodbuilder-mobile-nav__action" data-mobile-nav-close>
                        {{ __('voodbuilder::auth.login') }}
                    </a>
                    @if (config('voodbuilder.auth.registration_enabled', true))
                        <a href="{{ VoodbuilderUrls::register() }}" class="voodbuilder-mobile-nav__action" data-mobile-nav-close>
                            {{ __('voodbuilder::auth.register') }}
                        </a>
                    @endif
                </div>
            @endauth

            @if (! $canvasPreview && $cookieConsent && filled($cookieConsent->content_href))
                <div class="voodbuilder-mobile-nav__legal">
                    <a
                        href="{{ $cookieConsent->content_href }}"
                        class="voodbuilder-mobile-nav__cookie-link"
                        @if (filled($cookieConsent->content_target)) target="{{ $cookieConsent->content_target }}" @endif
                        data-mobile-nav-close
                    >
                        {{ $cookieConsent->content_policy }}
                    </a>
                    <span class="voodbuilder-mobile-nav__legal-separator" aria-hidden="true">·</span>
                    <button type="button" class="voodbuilder-mobile-nav__cookie-link" data-cookie-preferences>
                        {{ __('voodbuilder::nav.cookie_settings') }}
                    </button>
                </div>
            @endif
        </div>
    </nav>
</div>
