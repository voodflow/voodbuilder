@php
    use Voodflow\Voodbuilder\Support\VoodbuilderSeo;

    $metaTags = VoodbuilderSeo::geoMetaTags();
@endphp

@foreach ($metaTags as $name => $content)
    @if (filled($content))
        <meta name="{{ $name }}" content="{{ $content }}">
    @endif
@endforeach
