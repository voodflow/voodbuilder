@props([
    'name' => null,
    'class' => 'h-5 w-5',
])

@php
    use Voodflow\Voodbuilder\Support\MenuTablerIcons;

    $parsed = filled($name) ? MenuTablerIcons::parse((string) $name) : ['name' => '', 'style' => MenuTablerIcons::STYLE_OUTLINE];
    $inner = $parsed['name'] !== ''
        ? MenuTablerIcons::inner($parsed['name'], $parsed['style'])
        : null;
    $isFilled = $parsed['style'] === MenuTablerIcons::STYLE_FILLED;
@endphp

@if ($inner)
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        @if ($isFilled)
            fill="currentColor"
            stroke="none"
        @else
            fill="none"
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
        @endif
        @class([$class])
        aria-hidden="true"
    >{!! $inner !!}</svg>
@endif
