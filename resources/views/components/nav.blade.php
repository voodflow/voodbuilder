@props([
    'hasDocSidebar' => false,
    'showReadingProgress' => false,
    'canvasPreview' => false,
    'mainNavAlign' => 'start',
    'stickyNavMode' => 'inherit',
    'inPageBlock' => false,
    'variant' => 'simple',
    'tone' => 'light',
])

@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

    $showNotificationBell = (bool) VoodbuilderSettings::get('show_notification_bell', true);
    $desktopChromeClass = $canvasPreview ? 'md:block' : 'vp:block';
    $desktopFlexClass = $canvasPreview ? 'md:flex' : 'vp:flex';
    $mobileToggleClass = $canvasPreview ? 'inline-flex md:hidden' : 'hidden max-vp:inline-flex';
    $variants = ['simple', 'simple_dark', 'with_search', 'with_search_dark', 'with_action', 'with_action_dark', 'centered_links', 'menu_left'];
    $variant = in_array($variant, $variants, true) ? $variant : 'simple';
    $tone = in_array($tone, ['light', 'dark'], true) ? $tone : (str_contains($variant, '_dark') ? 'dark' : 'light');
    $mainNavAlign = in_array($mainNavAlign, ['start', 'center'], true) ? $mainNavAlign : 'start';
    if ($variant === 'centered_links') {
        $mainNavAlign = 'center';
    }
    $mainNavCentered = $mainNavAlign === 'center';
    $stickyNavMode = in_array($stickyNavMode, ['inherit', 'sticky', 'static'], true) ? $stickyNavMode : 'inherit';
    $stickyNav = match ($stickyNavMode) {
        'sticky' => true,
        'static' => false,
        default => ! $hasDocSidebar && ($showReadingProgress || (bool) VoodbuilderSettings::get('sticky_nav', false)),
    };
    $pinNavInViewport = $stickyNav && ! $hasDocSidebar;
    $pinNavFixed = $pinNavInViewport && $inPageBlock;
    $chromeVars = compact('desktopChromeClass', 'desktopFlexClass', 'mobileToggleClass', 'showNotificationBell', 'canvasPreview');
    $menuLinkClass = $tone === 'dark'
        ? 'inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-white/90 transition-colors hover:text-white'
        : 'inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1';
    $showInlineSearch = in_array($variant, ['with_search', 'with_search_dark', 'centered_links'], true);
    $showQuickAction = in_array($variant, ['with_action', 'with_action_dark'], true);
    $menuButtonLeft = $variant === 'menu_left';
@endphp

<header @class([
    'pointer-events-none top-0 left-0 z-30 w-full',
    'fixed' => $pinNavFixed,
    'sticky self-start' => $pinNavInViewport && ! $pinNavFixed,
    'relative' => ! $hasDocSidebar && ! $pinNavInViewport,
    'vp:fixed' => $hasDocSidebar,
    'voodbuilder-nav--canvas-preview' => $canvasPreview,
    'voodbuilder-nav-align-start' => ! $mainNavCentered,
    'voodbuilder-nav-align-center' => $mainNavCentered,
    'voodbuilder-nav--variant-'.$variant,
    'voodbuilder-nav--tone-dark' => $tone === 'dark',
    'bg-vp-bg' => $tone !== 'dark',
    'bg-gray-900 text-white' => $tone === 'dark',
]) role="banner">
    <div @class([
        'pointer-events-none relative h-16 whitespace-nowrap transition-colors',
        'bg-vp-bg' => $tone !== 'dark',
        'bg-gray-900' => $tone === 'dark',
    ])>
        <div @class([
            'pointer-events-auto absolute top-0 left-0 z-[2] hidden h-16 w-[var(--vp-sidebar-outer-width)] vp:flex',
            'bg-vp-bg-alt' => $hasDocSidebar && $tone !== 'dark',
            'bg-vp-bg' => $hasDocSidebar && $tone === 'dark',
            'bg-gray-900' => $hasDocSidebar && $tone === 'dark',
        ])>
            <div class="ml-auto flex h-16 w-[var(--spacing-vp-sidebar)] shrink-0 items-center px-8">
                <x-voodbuilder::nav-title />
            </div>
        </div>

        <div class="px-6 md:px-8 vp:px-0">
            <div @class([
                'voodbuilder-nav__row pointer-events-auto relative flex h-16 items-center gap-3 md:gap-4',
                'mx-auto max-w-[calc(var(--width-vp-layout)-4rem)]',
                'vp:mx-0 vp:max-w-none vp:pl-[var(--vp-sidebar-outer-width)] vp:pr-8',
                'justify-between' => $mainNavCentered,
            ])>
                @if ($menuButtonLeft)
                    <button
                        type="button"
                        @class([
                            'inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md transition-colors',
                            'text-vp-text-2 hover:bg-vp-gray-soft hover:text-vp-text-1' => $tone !== 'dark',
                            'text-white/80 hover:bg-white/10 hover:text-white' => $tone === 'dark',
                            $mobileToggleClass,
                        ])
                        data-mobile-nav-toggle
                        aria-controls="voodbuilder-mobile-nav"
                        aria-expanded="false"
                        aria-label="{{ __('Open menu') }}"
                    >
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                @endif

                @if ($mainNavCentered)
                    <div class="flex min-w-0 shrink-0 items-center">
                        <x-voodbuilder::nav-title />
                    </div>

                    <div @class(['hidden min-w-0 flex-1 items-center justify-center gap-1', $desktopFlexClass])>
                        <x-voodbuilder::menu menu="main" :wrapped="false" :link-class="$menuLinkClass" />
                        <x-voodbuilder::docs-menu />
                    </div>
                @else
                    <div class="flex min-w-0 shrink-0 items-center gap-3 md:gap-4">
                        <div class="flex shrink-0 items-center">
                            <x-voodbuilder::nav-title />
                        </div>

                        <div @class(['hidden min-w-0 items-center gap-1', $desktopFlexClass])>
                            <x-voodbuilder::menu menu="main" :wrapped="false" :link-class="$menuLinkClass" />
                            <x-voodbuilder::docs-menu />
                        </div>
                    </div>
                @endif

                @if ($showInlineSearch)
                    <div @class(['hidden min-w-0 flex-1 justify-center px-4', $desktopFlexClass])>
                        <div class="w-full max-w-md">
                            <x-voodbuilder::search />
                        </div>
                    </div>
                @endif

                <div class="ml-auto flex shrink-0 items-center gap-2">
                    @if ($showQuickAction)
                        <x-voodbuilder::menu
                            menu="header_extra"
                            class="hidden shrink-0 items-center {{ $desktopFlexClass }}"
                            :link-class="$menuLinkClass.' font-semibold'"
                        />
                    @endif

                    @include('voodbuilder::components.partials.nav-right-chrome', array_merge($chromeVars, [
                        'hideSearch' => $showInlineSearch,
                        'hideExtraMenu' => $showQuickAction,
                        'hideMobileToggle' => $menuButtonLeft,
                    ]))
                </div>
            </div>
        </div>

        @unless ($hasDocSidebar)
            <div class="pointer-events-none w-full" data-voodbuilder-header-divider>
                <div @class([
                    'h-px w-full',
                    'bg-vp-divider' => $tone !== 'dark',
                    'bg-white/10' => $tone === 'dark',
                ])></div>
            </div>
        @endunless
    </div>

    @if ($showReadingProgress)
        <div
            class="pointer-events-none relative w-full bg-vp-divider"
            data-voodbuilder-progress
            aria-hidden="true"
        >
            <div
                data-reading-progress
                class="absolute top-0 left-0 h-full bg-vp-brand-1 transition-[width] duration-100 ease-out"
                style="width: 0%"
            ></div>
        </div>
    @endif

    <x-voodbuilder::mobile-drawer :has-doc-sidebar="$hasDocSidebar" />
</header>
