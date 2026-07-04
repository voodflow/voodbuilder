@props([
    'variant' => 'inline',
    'labeled' => false,
])

@php
    $languageSwitcherAvailable = class_exists(\Voodflow\Vtuts\Support\LocaleSwitcher::class)
        && \Voodflow\Vtuts\Support\LocaleSwitcher::visible()
        && app()->providerIsLoaded(\Voodflow\Vtuts\VtutsServiceProvider::class)
        && view()->exists('vtuts::components.language-switcher');
@endphp

@if ($languageSwitcherAvailable)
    @if ($variant === 'mobile-tool')
        <div {{ $attributes->class(['voodbuilder-mobile-nav__tool']) }}>
            @include('vtuts::components.language-switcher', ['variant' => 'inline'])
        </div>
    @elseif ($labeled)
        <div class="px-3 py-2">
            <div class="mb-1.5 text-[11px] font-semibold tracking-[0.08em] text-vp-text-3 uppercase">
                {{ __('vtuts::language_switcher.label') }}
            </div>
            @include('vtuts::components.language-switcher', ['variant' => $variant])
        </div>
        <div class="my-1 h-px bg-vp-divider" aria-hidden="true"></div>
    @else
        @include('vtuts::components.language-switcher', ['variant' => $variant])
    @endif
@endif
