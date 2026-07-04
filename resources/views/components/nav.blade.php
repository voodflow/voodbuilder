@props([
    'hasDocSidebar' => false,
    'showReadingProgress' => false,
    'canvasPreview' => false,
    'mainNavAlign' => 'start',
    'stickyNavMode' => 'inherit',
    'inPageBlock' => false,
    'variant' => 'simple',
    'showSearch' => true,
    'showNotifications' => true,
    'showProfileMenu' => true,
])

@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

    $showNotificationBell = $showNotifications && (bool) VoodbuilderSettings::get('show_notification_bell', true);
    $desktopChromeClass = $canvasPreview ? 'md:block' : 'vp:block';
    $desktopFlexClass = $canvasPreview ? 'md:flex' : 'vp:flex';
    $mobileToggleClass = $canvasPreview ? 'inline-flex md:hidden' : 'hidden max-vp:inline-flex';
    $mainNavAlign = in_array($mainNavAlign, ['start', 'center'], true) ? $mainNavAlign : 'start';
    $mainNavCentered = $mainNavAlign === 'center';
    $stickyNavMode = in_array($stickyNavMode, ['inherit', 'sticky', 'static'], true) ? $stickyNavMode : 'inherit';
    $stickyNav = match ($stickyNavMode) {
        'sticky' => true,
        'static' => false,
        default => ! $hasDocSidebar && ($showReadingProgress || (bool) VoodbuilderSettings::get('sticky_nav', false)),
    };
    $pinNavInViewport = $stickyNav && ! $hasDocSidebar;
    $pinNavFixed = $pinNavInViewport && $inPageBlock;
    $chromeVars = compact('desktopChromeClass', 'desktopFlexClass', 'mobileToggleClass', 'showNotificationBell', 'canvasPreview', 'showSearch', 'showProfileMenu');
    $menuLinkClass = 'inline-flex h-8 items-center gap-1 rounded-md px-3 text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1';
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
    'voodbuilder-nav--variant-simple',
    'bg-vp-bg',
]) role="banner">
    <div class="pointer-events-none relative h-16 transition-colors bg-vp-bg @unless($canvasPreview) whitespace-nowrap @endunless">
        @if ($hasDocSidebar)
            <div class="pointer-events-auto absolute top-0 left-0 z-[2] hidden h-16 w-[var(--vp-sidebar-outer-width)] bg-vp-bg-alt vp:flex">
                <div class="ml-auto flex h-16 w-[var(--spacing-vp-sidebar)] shrink-0 items-center px-8">
                    <x-voodbuilder::nav-title />
                </div>
            </div>
        @endif

        <div class="px-6 md:px-8 vp:px-0">
            <div @class([
                'voodbuilder-nav__row pointer-events-auto relative flex h-16 w-full items-center gap-3 md:gap-4',
                'mx-auto max-w-[calc(var(--width-vp-layout)-4rem)]' => $hasDocSidebar,
                'vp:mx-0 vp:max-w-none vp:pl-[var(--vp-sidebar-outer-width)] vp:pr-8' => $hasDocSidebar,
                'justify-between' => $mainNavCentered,
            ])>
                @if ($mainNavCentered)
                    <div class="flex min-w-0 shrink-0 items-center">
                        <x-voodbuilder::nav-title />
                    </div>

                    <div @class(['hidden min-w-0 flex-1 items-center justify-center gap-1', $desktopFlexClass]) data-voodbuilder-desktop-nav>
                        <x-voodbuilder::menu menu="main" :wrapped="false" :link-class="$menuLinkClass" :canvas-preview="$canvasPreview" />
                        <x-voodbuilder::docs-menu />
                    </div>
                @else
                    <div class="flex min-w-0 shrink-0 items-center gap-3 md:gap-4">
                        <div class="flex shrink-0 items-center">
                            <x-voodbuilder::nav-title />
                        </div>

                        <div @class(['hidden min-w-0 items-center gap-1', $desktopFlexClass]) data-voodbuilder-desktop-nav>
                            <x-voodbuilder::menu menu="main" :wrapped="false" :link-class="$menuLinkClass" :canvas-preview="$canvasPreview" />
                            <x-voodbuilder::docs-menu />
                        </div>
                    </div>
                @endif

                <div class="ml-auto flex shrink-0 items-center gap-2">
                    @include('voodbuilder::components.partials.nav-right-chrome', array_merge($chromeVars, [
                        'hideExtraMenu' => false,
                        'hideMobileToggle' => false,
                    ]))
                </div>
            </div>
        </div>

        @unless ($hasDocSidebar)
            <div class="pointer-events-none w-full" data-voodbuilder-header-divider>
                <div class="h-px w-full bg-vp-divider"></div>
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

    @unless ($hasDocSidebar)
        <x-voodbuilder::mobile-drawer
            :has-doc-sidebar="$hasDocSidebar"
            :enable-notifications="$showNotifications"
            :canvas-preview="$canvasPreview"
        />
    @endunless
</header>
