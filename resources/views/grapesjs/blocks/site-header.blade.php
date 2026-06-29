@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

    $mainMenu = (string) ($config['main_menu'] ?? 'main');
    $extraMenu = (string) ($config['extra_menu'] ?? 'header_extra');
    $brandName = VoodbuilderSettings::brandName();
@endphp

<header class="border-b border-vp-divider bg-vp-bg" role="banner" data-voodbuilder-gjs-site-header>
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-4 px-6">
        <a href="{{ url('/') }}" class="truncate text-sm font-semibold text-vp-text-1">
            {{ $brandName }}
        </a>

        <nav class="hidden items-center gap-1 md:flex" aria-label="{{ __('Main navigation') }}">
            <x-voodbuilder::menu
                :menu="$mainMenu"
                :wrapped="false"
                link-class="inline-flex h-8 items-center rounded-md px-3 text-sm font-medium text-vp-text-1 transition-colors hover:text-vp-brand-1"
            />
        </nav>

        <div class="hidden items-center gap-1 md:flex">
            <x-voodbuilder::menu
                :menu="$extraMenu"
                link-class="inline-flex h-8 items-center rounded-md px-3 text-sm font-medium text-vp-text-2 transition-colors hover:text-vp-brand-1"
            />
        </div>

        @if ($preview ?? false)
            <span class="rounded-md border border-dashed border-vp-divider px-2 py-1 text-xs text-vp-text-2">
                {{ __('voodbuilder::pro.grapesjs.blocks.site_header_preview') }}
            </span>
        @endif
    </div>
</header>
