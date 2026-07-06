@props([
    'variant' => 'menu',
])

@php
    $buttonClass = match ($variant) {
        'mobile' => 'voodbuilder-mobile-nav__tool',
        default => 'flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-vp-text-1 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1',
    };
@endphp

<button
    type="button"
    @if ($variant === 'menu') role="menuitem" @endif
    data-theme-toggle
    data-theme-label-dark="{{ __('voodbuilder::nav.enable_dark_mode') }}"
    data-theme-label-light="{{ __('voodbuilder::nav.enable_light_mode') }}"
    {{ $attributes->merge(['class' => $buttonClass]) }}
    aria-pressed="false"
    aria-label="{{ __('voodbuilder::nav.enable_dark_mode') }}"
>
    <svg data-theme-icon="moon" class="voodbuilder-theme-toggle__icon h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
    </svg>
    <svg data-theme-icon="sun" class="voodbuilder-theme-toggle__icon h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true" hidden>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
    </svg>
    <span data-theme-toggle-label>{{ __('voodbuilder::nav.enable_dark_mode') }}</span>
</button>
