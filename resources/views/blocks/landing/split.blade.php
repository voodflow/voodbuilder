@php
    use Voodflow\Vpress\Support\LandingBlockSupport;

    $imageLeft = ($config['image_position'] ?? 'left') === 'left';
    $shell = LandingBlockSupport::sectionShellClass($config);
@endphp

<section class="vp-landing-split {{ $shell }}">
    <div class="mx-auto grid max-w-6xl gap-8 md:grid-cols-2 md:items-center">
        @if ($imageLeft)
            <div class="min-h-64 overflow-hidden rounded-2xl bg-vp-bg-alt">
                @if (! empty($config['image_url']))
                    <img src="{{ $config['image_url'] }}" alt="" class="h-full min-h-64 w-full object-cover" loading="lazy">
                @endif
            </div>
        @endif

        <div class="flex flex-col gap-4">
            @if (! empty($config['eyebrow']))
                <p class="text-sm font-semibold uppercase tracking-wide text-vp-brand-1">{{ $config['eyebrow'] }}</p>
            @endif
            @if (! empty($config['heading']))
                <h2 class="text-3xl font-bold text-vp-text-1">{{ $config['heading'] }}</h2>
            @endif
            @if (! empty($config['body']))
                <div class="text-base leading-relaxed text-vp-text-2">{!! nl2br(e($config['body'])) !!}</div>
            @endif
            @if (! empty($config['button_url']))
                <div>
                    <a href="{{ $config['button_url'] }}" class="inline-flex rounded-lg bg-vp-brand-1 px-6 py-3 text-sm font-semibold text-white hover:opacity-90">
                        {{ $config['button_label'] ?? __('vpress::landing.learn_more') }}
                    </a>
                </div>
            @endif
        </div>

        @if (! $imageLeft)
            <div class="min-h-64 overflow-hidden rounded-2xl bg-vp-bg-alt">
                @if (! empty($config['image_url']))
                    <img src="{{ $config['image_url'] }}" alt="" class="h-full min-h-64 w-full object-cover" loading="lazy">
                @endif
            </div>
        @endif
    </div>
</section>
