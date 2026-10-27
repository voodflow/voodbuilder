@props([
    'canvasPreview' => false,
    'stickyNavMode' => 'inherit',
    'showSearch' => true,
    'showProfileMenu' => true,
    'uppercaseFirstLevel' => false,
    'overlayLayout' => 'split',
    'overlayImage' => null,
    'brandConfig' => [],
])

@php
    use Illuminate\Support\Facades\Route;
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;
    use Voodflow\Voodbuilder\Support\Editor\SiteNavConfig;
    use Voodflow\Voodbuilder\Support\Navigation;
    use Voodflow\Voodbuilder\Support\NavigationMenuResolver;
    use Voodflow\Voodbuilder\Support\VoodbuilderUrls;

    $mainItems = Navigation::items('main');
    $extraItems = Navigation::items('header_extra');
    $mainPlacementLabel = NavigationMenuResolver::placementLabel('main');
    $extraPlacementLabel = NavigationMenuResolver::placementLabel('header_extra');
    $brandConfig = is_array($brandConfig) ? $brandConfig : [];
    $uppercaseFirstLevel = (bool) $uppercaseFirstLevel;
    $stickyNavMode = in_array($stickyNavMode, ['inherit', 'sticky', 'static'], true) ? $stickyNavMode : 'inherit';
    $stickyNav = match ($stickyNavMode) {
        'sticky' => true,
        'static' => false,
        default => (bool) VoodbuilderSettings::get('sticky_nav', false),
    };
    $searchEnabled = $showSearch && Route::has('voodbuilder.search');
    $loginUrl = VoodbuilderUrls::login();
    $overlayId = 'vb-fl-overlay';
    $overlayLayout = in_array($overlayLayout, ['split', 'aside'], true) ? $overlayLayout : 'split';
    $overlayImage = trim((string) ($overlayImage ?: SiteNavConfig::DEFAULT_OVERLAY_IMAGE));
@endphp

<header
    @class([
        'vb-fl-nav relative z-[60] w-full bg-vp-bg text-vp-text-1',
        'sticky top-0' => $stickyNav,
        'voodbuilder-nav--canvas-preview' => $canvasPreview,
        'voodbuilder-nav-menu--uppercase-first' => $uppercaseFirstLevel,
    ])
    role="banner"
    data-vb-fl-nav
    data-voodbuilder-nav-variant="fullscreen"
    data-vb-fl-overlay-layout="{{ $overlayLayout }}"
>
    {{-- Full-bleed bar: background spans the viewport; only the row padding insets content. --}}
    <div class="vb-fl-nav__bar flex w-full items-center justify-between gap-4 px-5 py-4 md:px-8 lg:px-10">
        <div class="min-w-0 shrink-0" data-voodbuilder-chrome="brand">
            <x-voodbuilder::nav-title :config="$brandConfig" :preview="$canvasPreview" />
        </div>

        <div class="flex items-center gap-2">
            @if ($showProfileMenu || $canvasPreview)
                <div data-voodbuilder-chrome="profile">
                    @if ($canvasPreview)
                        <button
                            type="button"
                            class="voodbuilder-header-icon-btn"
                            data-gjs-type="voodbuilder-chrome-button"
                            data-gjs-selectable="false"
                            aria-label="{{ __('voodbuilder::account.nav') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"/></svg>
                        </button>
                    @else
                        <x-voodbuilder::nav-profile-menu :canvas-preview="false" />
                    @endif
                </div>
            @endif

            @if ($searchEnabled || $canvasPreview)
                <div data-voodbuilder-chrome="search">
                    @if ($canvasPreview)
                        <button
                            type="button"
                            class="voodbuilder-header-icon-btn"
                            data-gjs-type="voodbuilder-chrome-button"
                            data-gjs-selectable="false"
                            aria-label="{{ __('voodbuilder::search.button') }}"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                        </button>
                    @else
                        <x-voodbuilder::search :canvas-preview="false" />
                    @endif
                </div>
            @endif

            <button
                type="button"
                class="voodbuilder-header-icon-btn vb-fl-nav__toggle"
                data-vb-fl-open
                data-gjs-type="voodbuilder-chrome-button"
                data-gjs-selectable="false"
                aria-expanded="false"
                aria-controls="{{ $overlayId }}"
                aria-label="{{ __('Open menu') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
            </button>
        </div>
    </div>

    <div
        id="{{ $overlayId }}"
        @class([
            'vb-fl-nav__overlay',
            'vb-fl-nav__overlay--split' => $overlayLayout === 'split',
            'vb-fl-nav__overlay--aside' => $overlayLayout === 'aside',
        ])
        data-vb-fl-overlay
        data-vb-fl-overlay-layout="{{ $overlayLayout }}"
        hidden
        aria-hidden="true"
    >
        {{-- Explicit floating close — always visible over image + menu. --}}
        <button
            type="button"
            class="vb-fl-nav__close"
            data-vb-fl-close
            data-gjs-type="voodbuilder-chrome-button"
            data-gjs-selectable="false"
            aria-label="{{ __('Close menu') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6L6 18"/></svg>
            <span class="vb-fl-nav__close-label">{{ __('Close') }}</span>
        </button>

        <div class="vb-fl-nav__panel">
            <aside class="vb-fl-nav__visual" aria-hidden="true">
                <div
                    class="vb-fl-nav__visual-bg"
                    style="background-image:url('{{ e($overlayImage) }}');"
                ></div>
                <div class="vb-fl-nav__visual-shade"></div>
                <div class="vb-fl-nav__visual-copy">
                    <div class="vb-fl-nav__visual-brand">
                        <x-voodbuilder::nav-title :config="$brandConfig" :preview="$canvasPreview" />
                    </div>
                    <p class="vb-fl-nav__visual-tagline">
                        {{ VoodbuilderSettings::get('site_tagline') ?: __('voodbuilder::pro.editor.blocks.site_nav_fullscreen_tagline') }}
                    </p>
                </div>
            </aside>

            <div class="vb-fl-nav__menu">
                <div class="vb-fl-nav__columns">
                    <nav class="vb-fl-nav__column" aria-label="{{ $mainPlacementLabel }}">
                        <p class="vb-fl-nav__column-label">{{ $mainPlacementLabel }}</p>
                        @if ($mainItems->isNotEmpty())
                            <ul class="vb-fl-nav__links">
                                @foreach ($mainItems as $item)
                                    <x-voodbuilder::menu-nav-item
                                        :item="$item"
                                        :mobile="true"
                                        :canvas-preview="$canvasPreview"
                                    />
                                @endforeach
                            </ul>
                        @else
                            <p class="vb-fl-nav__placeholder" data-vb-fl-menu-placeholder="main">
                                {{ __('voodbuilder::pro.editor.blocks.site_nav_menu_placeholder', ['menu' => $mainPlacementLabel]) }}
                            </p>
                        @endif
                    </nav>

                    <nav class="vb-fl-nav__column vb-fl-nav__column--extra" aria-label="{{ $extraPlacementLabel }}">
                        <p class="vb-fl-nav__column-label">{{ $extraPlacementLabel }}</p>
                        @if ($extraItems->isNotEmpty())
                            <ul class="vb-fl-nav__links">
                                @foreach ($extraItems as $item)
                                    <x-voodbuilder::menu-nav-item
                                        :item="$item"
                                        :mobile="true"
                                        :canvas-preview="$canvasPreview"
                                    />
                                @endforeach
                            </ul>
                        @else
                            <p class="vb-fl-nav__placeholder" data-vb-fl-menu-placeholder="header_extra">
                                {{ __('voodbuilder::pro.editor.blocks.site_nav_menu_placeholder', ['menu' => $extraPlacementLabel]) }}
                            </p>
                        @endif
                    </nav>
                </div>

                @guest
                    @if (! $canvasPreview)
                        <div class="vb-fl-nav__auth">
                            <a href="{{ $loginUrl }}" class="vb-fl-nav__auth-link" data-vb-fl-close>
                                {{ __('voodbuilder::auth.login') }}
                            </a>
                        </div>
                    @endif
                @endguest
            </div>
        </div>
    </div>
</header>
