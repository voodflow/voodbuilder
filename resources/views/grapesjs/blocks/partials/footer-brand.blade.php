@php
    $homeUrl = url('/');
@endphp

<a href="{{ $homeUrl }}" class="inline-flex items-center title-font font-medium text-vp-text-1">
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $brandName }}" class="h-10 w-10 rounded-full object-cover">
    @else
        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-vp-brand-1 p-2 text-white">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-6 w-6" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
            </svg>
        </span>
    @endif
    <span class="ml-3 text-xl">{{ $brandName }}</span>
</a>
