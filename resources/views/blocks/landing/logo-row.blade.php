@php
    use Voodflow\Vpress\Support\LandingBlockSupport;

    $logos = $config['logos'] ?? [];
    $shell = LandingBlockSupport::sectionShellClass($config, ['section_width' => 'contained', 'section_padding' => 'default']);
    $grayscale = (bool) ($config['grayscale'] ?? true);
@endphp

<section class="vp-landing-logos {{ $shell }}">
    <div class="mx-auto max-w-6xl">
        @if (! empty($config['heading']))
            <h2 class="mb-8 text-center text-sm font-bold uppercase tracking-[0.2em] text-vp-text-3">{{ $config['heading'] }}</h2>
        @endif

        <div class="grid grid-cols-2 items-center gap-8 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            @foreach ($logos as $logo)
                @if (filled($logo['image_url'] ?? null))
                    @if (filled($logo['url'] ?? null))
                        <a href="{{ $logo['url'] }}" class="flex items-center justify-center" @if ($logo['open_in_new_tab'] ?? false) target="_blank" rel="noopener noreferrer" @endif>
                            <img
                                src="{{ $logo['image_url'] }}"
                                alt="{{ $logo['name'] ?? '' }}"
                                class="max-h-12 w-full max-w-[8rem] object-contain {{ $grayscale ? 'opacity-70 grayscale transition hover:opacity-100 hover:grayscale-0' : '' }}"
                                loading="lazy"
                            >
                        </a>
                    @else
                        <div class="flex items-center justify-center">
                            <img
                                src="{{ $logo['image_url'] }}"
                                alt="{{ $logo['name'] ?? '' }}"
                                class="max-h-12 w-full max-w-[8rem] object-contain {{ $grayscale ? 'opacity-70 grayscale' : '' }}"
                                loading="lazy"
                            >
                        </div>
                    @endif
                @endif
            @endforeach
        </div>
    </div>
</section>
