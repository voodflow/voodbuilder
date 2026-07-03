@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

    $mainMenu = (string) ($config['main_menu'] ?? 'main');
    $extraMenu = (string) ($config['extra_menu'] ?? 'header_extra');
    $showSearch = (bool) ($config['show_search'] ?? true);
    $showProfile = (bool) ($config['show_profile'] ?? true);
    $showNotifications = (bool) ($config['show_notifications'] ?? true);
    $showDocsMenu = (bool) ($config['show_docs_menu'] ?? true);
    $sticky = (bool) ($config['sticky'] ?? false);
    $showNotificationBell = (bool) VoodbuilderSettings::get('show_notification_bell', true);
@endphp

<header
    @class([
        'pointer-events-none top-0 left-0 z-30 w-full bg-vp-bg',
        'sticky' => $sticky,
        'relative' => ! $sticky,
    ])
    role="banner"
    data-voodbuilder-gjs-site-header
>
    <div class="pointer-events-none relative h-16 whitespace-nowrap">
        <div class="pointer-events-auto px-6 md:px-8">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4">
                <div class="min-w-0 shrink-0">
                    <x-voodbuilder::nav-title />
                </div>

                <div class="hidden shrink-0 items-center gap-1 md:flex">
                    <x-voodbuilder::menu
                        :menu="$mainMenu"
                        :wrapped="false"
                        link-class="inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1"
                    />
                    @if ($showDocsMenu)
                        <x-voodbuilder::docs-menu />
                    @endif
                </div>

                <div class="flex min-w-0 items-center justify-end gap-2 md:gap-3">
                    <x-voodbuilder::menu
                        :menu="$extraMenu"
                        class="hidden shrink-0 items-center md:flex"
                        link-class="inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-2 transition-colors hover:text-vp-brand-1"
                    />

                    @if ($showSearch)
                        <div class="hidden md:block">
                            <x-voodbuilder::search />
                        </div>
                    @endif

                    @auth
                        @if ($showNotifications && config('voodbuilder.notifications.enabled', true) && $showNotificationBell)
                            <div class="hidden md:block">
                                <livewire:voodbuilder.site-notification-bell wire:key="gjs-header-bell" />
                            </div>
                        @endif
                    @endauth

                    @if ($showProfile)
                        <div class="hidden md:block">
                            <x-voodbuilder::nav-profile-menu />
                        </div>
                    @endif

                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full text-vp-text-2 transition-colors hover:bg-vp-gray-soft hover:text-vp-text-1 md:hidden"
                        data-mobile-nav-toggle
                        aria-controls="voodbuilder-mobile-nav"
                        aria-expanded="false"
                        aria-label="{{ __('Open menu') }}"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="pointer-events-none w-full" data-voodbuilder-header-divider>
            <div class="h-px w-full bg-vp-divider"></div>
        </div>
    </div>

    <x-voodbuilder::mobile-drawer :has-doc-sidebar="false" :enable-notifications="$showNotifications" />

    @if ($preview ?? false)
        <div class="pointer-events-none absolute top-2 right-2 z-10">
            <span class="pointer-events-auto rounded-md border border-dashed border-vp-divider bg-vp-bg px-2 py-1 text-xs text-vp-text-2 shadow-sm">
                {{ __('voodbuilder::pro.grapesjs.blocks.site_header_preview') }}
            </span>
        </div>
    @endif
</header>
