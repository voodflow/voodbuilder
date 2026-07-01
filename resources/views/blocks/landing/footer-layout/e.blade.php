@php
    $columns = collect($menuColumns)->values()->map(fn (array $column, int $index): array => array_merge($column, ['index' => $index + 1]))->all();
@endphp

<footer class="text-gray-600 body-font vp-landing-footer-layout vp-landing-footer-layout--e">
    <div class="container px-5 py-24 mx-auto">
        @if ($columns !== [])
            <div class="flex flex-wrap md:text-left text-center order-first">
                @foreach ($columns as $column)
                    @include('voodbuilder::blocks.landing.partials.layout-column', ['column' => $column])
                @endforeach
            </div>
        @endif
    </div>

    <div class="bg-gray-100">
        <div class="container px-5 py-6 mx-auto flex items-center sm:flex-row flex-col">
            @include('voodbuilder::blocks.landing.partials.layout-brand', ['brandClass' => 'md:justify-start justify-center'])
            <div class="text-sm text-gray-500 sm:ml-6 sm:mt-0 mt-4">
                @include('voodbuilder::blocks.landing.partials.layout-copyright')
            </div>
        </div>
    </div>
</footer>
