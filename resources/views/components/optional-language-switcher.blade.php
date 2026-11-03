@props([
    'variant' => 'inline',
    'labeled' => false,
])

@php
    use Voodflow\Voodbuilder\Support\NavProfileMenu;
    use Voodflow\Voodbuilder\Support\SiteLanguageSwitcher;

    $vtutsAvailable = NavProfileMenu::vtutsLanguageSwitcherVisible();
    $nativeAvailable = ! $vtutsAvailable && SiteLanguageSwitcher::visible();
@endphp

@if ($vtutsAvailable)
    @if ($variant === 'mobile-tool')
        <div {{ $attributes->class(['voodbuilder-mobile-nav__tool', 'voodbuilder-mobile-nav__tool--languages']) }}>
            @include('vtuts::components.language-switcher', ['variant' => 'inline'])
        </div>
    @elseif ($labeled)
        <div class="px-3.5 py-2">
            <div class="mb-1.5 text-[11px] font-semibold tracking-[0.08em] text-vp-text-3 uppercase">
                {{ __('vtuts::language_switcher.label') }}
            </div>
            @include('vtuts::components.language-switcher', ['variant' => $variant])
        </div>
        <div class="voodbuilder-dropdown-separator" aria-hidden="true"></div>
    @else
        @include('vtuts::components.language-switcher', ['variant' => $variant])
    @endif
@elseif ($nativeAvailable)
    @php
        $locales = SiteLanguageSwitcher::options();
        $current = SiteLanguageSwitcher::current();
        $label = __('voodbuilder::nav.languages');
    @endphp

    @if ($variant === 'dropdown' && $labeled)
        <div class="px-3.5 py-2">
            <div class="mb-1.5 text-[11px] font-semibold tracking-[0.08em] text-vp-text-3 uppercase">
                {{ $label }}
            </div>
            <div class="vtuts-language-switcher" data-voodbuilder-language-switcher>
                @foreach ($locales as $code => $localeLabel)
                    @if ($code === $current)
                        <span class="inline-flex rounded-md bg-vp-gray-soft px-2.5 py-1 text-xs font-semibold text-vp-brand-1" aria-current="true" title="{{ $localeLabel }}">
                            {{ strtoupper($code) }}
                        </span>
                    @elseif (SiteLanguageSwitcher::isAvailable($code))
                        <a
                            href="{{ SiteLanguageSwitcher::urlFor($code) }}"
                            class="inline-flex rounded-md px-2.5 py-1 text-xs font-medium text-vp-text-2 transition-colors hover:bg-vp-gray-soft hover:text-vp-brand-1"
                            hreflang="{{ $code }}"
                            lang="{{ $code }}"
                            title="{{ $localeLabel }}"
                            data-voodbuilder-language-option
                        >
                            {{ strtoupper($code) }}
                        </a>
                    @else
                        <span
                            class="inline-flex cursor-not-allowed rounded-md px-2.5 py-1 text-xs font-medium text-vp-text-3 opacity-50"
                            aria-disabled="true"
                            title="{{ __('voodbuilder::nav.language_unavailable', ['language' => $localeLabel]) }}"
                        >
                            {{ strtoupper($code) }}
                        </span>
                    @endif
                @endforeach
            </div>
        </div>
        <div class="voodbuilder-dropdown-separator" aria-hidden="true"></div>
    @elseif ($variant === 'mobile-tool')
        <div {{ $attributes->class(['voodbuilder-mobile-nav__tool', 'voodbuilder-mobile-nav__tool--languages']) }}>
            <nav aria-label="{{ $label }}" data-voodbuilder-language-switcher>
                <ul class="flex max-w-full flex-wrap items-center gap-1">
                    @foreach ($locales as $code => $localeLabel)
                        <li class="shrink-0">
                            @if ($code === $current)
                                <span class="inline-flex h-8 items-center rounded-md px-2 text-xs font-semibold text-vp-brand-1" aria-current="true" title="{{ $localeLabel }}">
                                    {{ strtoupper($code) }}
                                </span>
                            @elseif (SiteLanguageSwitcher::isAvailable($code))
                                <a
                                    href="{{ SiteLanguageSwitcher::urlFor($code) }}"
                                    class="inline-flex h-8 items-center rounded-md px-2 text-xs font-medium text-vp-text-2 transition-colors hover:text-vp-brand-1"
                                    hreflang="{{ $code }}"
                                    lang="{{ $code }}"
                                    title="{{ $localeLabel }}"
                                    data-voodbuilder-language-option
                                >
                                    {{ strtoupper($code) }}
                                </a>
                            @else
                                <span
                                    class="inline-flex h-8 cursor-not-allowed items-center rounded-md px-2 text-xs font-medium text-vp-text-3 opacity-50"
                                    aria-disabled="true"
                                    title="{{ __('voodbuilder::nav.language_unavailable', ['language' => $localeLabel]) }}"
                                >
                                    {{ strtoupper($code) }}
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>
    @else
        <nav
            aria-label="{{ $label }}"
            data-voodbuilder-language-switcher
            {{ $attributes->class(['vtuts-language-switcher flex items-center']) }}
        >
            <ul class="flex max-w-full flex-wrap items-center gap-1">
                @foreach ($locales as $code => $localeLabel)
                    <li class="shrink-0">
                        @if ($code === $current)
                            <span class="inline-flex h-8 items-center rounded-md px-2 text-xs font-semibold text-vp-brand-1" aria-current="true" title="{{ $localeLabel }}">
                                {{ strtoupper($code) }}
                            </span>
                        @elseif (SiteLanguageSwitcher::isAvailable($code))
                            <a
                                href="{{ SiteLanguageSwitcher::urlFor($code) }}"
                                class="inline-flex h-8 items-center rounded-md px-2 text-xs font-medium text-vp-text-2 transition-colors hover:text-vp-brand-1"
                                hreflang="{{ $code }}"
                                lang="{{ $code }}"
                                title="{{ $localeLabel }}"
                                data-voodbuilder-language-option
                            >
                                {{ strtoupper($code) }}
                            </a>
                        @else
                            <span
                                class="inline-flex h-8 cursor-not-allowed items-center rounded-md px-2 text-xs font-medium text-vp-text-3 opacity-50"
                                aria-disabled="true"
                                title="{{ __('voodbuilder::nav.language_unavailable', ['language' => $localeLabel]) }}"
                            >
                                {{ strtoupper($code) }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        </nav>
    @endif
@endif
