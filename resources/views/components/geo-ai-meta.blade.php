@php
    use Voodflow\Vpress\Support\VpressSeo;

    $metaTags = VpressSeo::geoMetaTags();
@endphp

@foreach ($metaTags as $name => $content)
    @if (filled($content))
        <meta name="{{ $name }}" content="{{ $content }}">
    @endif
@endforeach
