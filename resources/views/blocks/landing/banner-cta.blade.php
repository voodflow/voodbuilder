@php
    use Voodflow\Vpress\Support\LandingBlockContent;
    use Voodflow\Vpress\Support\LandingBlockSupport;
    use Voodflow\Vpress\Support\ResolvableLinkSupport;

    $section = LandingBlockContent::section($config, ['section_width' => 'contained']);
    $buttonUrl = ResolvableLinkSupport::resolve($config, 'button', 'button_url');
@endphp

<section
    class="vp-landing-banner-cta {{ $section['shell'] }} {{ $section['corners'] }} {{ $section['appearance']['class'] }}"
    @if ($section['appearance']['style'] !== '') style="{{ $section['appearance']['style'] }}" @endif
>
    <div class="mx-auto flex w-full max-w-4xl flex-col gap-4 px-6 py-12 md:px-10 md:py-14 {{ $section['align'] }}">
        @if (! empty($config['heading']))
            <h2 class="text-3xl font-bold md:text-4xl">{{ $config['heading'] }}</h2>
        @endif

        @if (! empty($config['subheading']))
            <p class="max-w-2xl text-lg opacity-90">{{ $config['subheading'] }}</p>
        @endif

        @if (filled($buttonUrl))
            <div class="mt-2 flex flex-wrap gap-3 {{ $config['text_align'] === 'left' ? 'justify-start' : 'justify-center' }}">
                @include('vpress::blocks.landing.partials.button', [
                    'label' => $config['button_label'] ?? __('vpress::landing.learn_more'),
                    'url' => $buttonUrl,
                    'class' => LandingBlockSupport::primaryButtonClass((string) ($config['button_style'] ?? 'solid'), $section['onDark']),
                    'open_in_new_tab' => ResolvableLinkSupport::opensInNewTab($config, 'button'),
                ])
            </div>
        @endif
    </div>
</section>
