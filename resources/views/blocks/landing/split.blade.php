@php
    use Voodflow\Voodbuilder\Support\LandingBlockContent;
    use Voodflow\Voodbuilder\Support\LandingBlockSupport;
    use Voodflow\Voodbuilder\Support\ResolvableLinkSupport;

    $imageLeft = ($config['image_position'] ?? 'left') === 'left';
    $section = LandingBlockContent::section($config);
    $imageUrl = LandingBlockContent::imageUrl($config);
    $buttonUrl = ResolvableLinkSupport::resolve($config, 'button', 'button_url');
@endphp

<section class="vp-landing-split {{ $section['shell'] }}">
    <div class="mx-auto grid max-w-6xl gap-8 md:grid-cols-2 md:items-center">
        @if ($imageLeft)
            <div class="min-h-64 overflow-hidden rounded-2xl bg-vp-bg-alt">
                @if (filled($imageUrl))
                    <img src="{{ $imageUrl }}" alt="" class="h-full min-h-64 w-full object-cover" loading="lazy">
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
            @if (filled($buttonUrl))
                <div>
                    @include('voodbuilder::blocks.landing.partials.button', [
                        'label' => $config['button_label'] ?? __('voodbuilder::landing.learn_more'),
                        'url' => $buttonUrl,
                        'class' => 'bg-vp-brand-1 text-white hover:opacity-90',
                        'open_in_new_tab' => ResolvableLinkSupport::opensInNewTab($config, 'button'),
                    ])
                </div>
            @endif
        </div>

        @if (! $imageLeft)
            <div class="min-h-64 overflow-hidden rounded-2xl bg-vp-bg-alt">
                @if (filled($imageUrl))
                    <img src="{{ $imageUrl }}" alt="" class="h-full min-h-64 w-full object-cover" loading="lazy">
                @endif
            </div>
        @endif
    </div>
</section>
