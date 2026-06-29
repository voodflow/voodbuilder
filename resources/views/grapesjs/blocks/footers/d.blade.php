@php
    use Voodflow\Voodbuilder\Models\VoodbuilderSettings;

    $brandName = VoodbuilderSettings::brandName();
@endphp

<div class="container mx-auto px-5 py-8">
    <div class="flex flex-col items-center sm:flex-row">
        <div class="shrink-0" data-voodbuilder-brand></div>
        <p class="mt-4 text-sm text-vp-text-2 sm:ml-4 sm:mt-0 sm:border-l sm:border-vp-divider sm:py-2 sm:pl-4" data-voodbuilder-footer-copyright>
            &copy; {{ date('Y') }} {{ $brandName }}
        </p>
        <span class="mt-4 inline-flex justify-center gap-3 sm:ml-auto sm:mt-0 sm:justify-start" data-voodbuilder-footer-social>
            <a href="#" class="text-vp-text-2 transition-colors hover:text-vp-brand-1" aria-label="Facebook">
                <svg fill="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-5 w-5" viewBox="0 0 24 24"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path></svg>
            </a>
            <a href="#" class="text-vp-text-2 transition-colors hover:text-vp-brand-1" aria-label="Twitter">
                <svg fill="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" class="h-5 w-5" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path></svg>
            </a>
        </span>
    </div>
</div>
