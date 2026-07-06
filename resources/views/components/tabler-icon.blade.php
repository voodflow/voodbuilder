@props([
    'name' => null,
    'class' => 'h-5 w-5',
])

@php
    use Voodflow\Voodbuilder\Support\MenuTablerIcons;

    $path = filled($name) ? MenuTablerIcons::path((string) $name) : null;
@endphp

@if ($path)
    <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        stroke="currentColor"
        stroke-linecap="round"
        stroke-linejoin="round"
        stroke-width="2"
        viewBox="0 0 24 24"
        @class([$class])
        aria-hidden="true"
    >
        <path d="{{ $path }}" />
    </svg>
@endif
