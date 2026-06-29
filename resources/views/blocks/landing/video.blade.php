@php
    use Voodflow\Voodbuilder\Support\LandingBlockSupport;

    $embedUrl = $embedUrl ?? null;
    $shell = LandingBlockSupport::sectionShellClass($config);
@endphp

<section class="vp-landing-video {{ $shell }}">
    <div class="mx-auto max-w-5xl">
        @if (! empty($config['title']))
            <h2 class="mb-6 text-center text-3xl font-bold text-vp-text-1">{{ $config['title'] }}</h2>
        @endif

        @if ($embedUrl)
            <div class="vp-landing-video__frame">
                <iframe
                    src="{{ $embedUrl }}"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowfullscreen
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"
                    title="{{ $config['title'] ?? 'Video' }}"
                ></iframe>
            </div>
        @else
            <div class="vp-landing-video__placeholder">
                <p>{{ __('voodbuilder::landing.video_unavailable') }}</p>
            </div>
        @endif

        @if (! empty($config['caption']))
            <p class="mt-4 text-center text-sm text-vp-text-2">{{ $config['caption'] }}</p>
        @endif
    </div>
</section>
