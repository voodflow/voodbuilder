@php
    use Voodflow\Vpress\Support\LandingBlockContent;

    $narrow = ($config['width'] ?? 'wide') === 'narrow';
    $centered = ($config['text_align'] ?? 'left') === 'center';
    $section = LandingBlockContent::section($config, [], 'light');
@endphp

<section
    class="vp-landing-text {{ $section['shell'] }} {{ $section['corners'] }} {{ $section['appearance']['class'] }}"
    @if ($section['appearance']['style'] !== '') style="{{ $section['appearance']['style'] }}" @endif
>
    <div class="mx-auto {{ $narrow ? 'max-w-3xl' : 'max-w-5xl' }} {{ $centered ? 'text-center' : 'text-left' }}">
        @if (! empty($config['eyebrow']))
            <p class="mb-3 text-sm font-semibold uppercase tracking-wide text-vp-brand-1">{{ $config['eyebrow'] }}</p>
        @endif
        @if (! empty($config['heading']))
            <h2 @class(['text-3xl font-bold', 'text-white' => $section['onDark'], 'text-vp-text-1' => ! $section['onDark']])>{{ $config['heading'] }}</h2>
        @endif
        @if (! empty($config['intro']))
            <p @class(['mt-4 text-lg', 'text-white/80' => $section['onDark'], 'text-vp-text-2' => ! $section['onDark']])>{{ $config['intro'] }}</p>
        @endif
        @if (! empty($config['body']))
            <div @class(['mt-6 space-y-4 text-base leading-relaxed', 'text-white/75' => $section['onDark'], 'text-vp-text-2' => ! $section['onDark']])>{!! nl2br(e($config['body'])) !!}</div>
        @endif
    </div>
</section>
