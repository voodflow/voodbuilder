@php
    $columns = collect($menuColumns)->values()->map(fn (array $column, int $index): array => array_merge($column, ['index' => $index + 1]))->all();
@endphp

<footer class="text-gray-600 body-font vp-landing-footer-layout vp-landing-footer-layout--c">
    <div class="container px-5 py-24 mx-auto">
        @if ($columns !== [])
            <div class="flex flex-wrap md:text-left text-center -mb-10 -mx-4">
                @foreach ($columns as $column)
                    @include('voodbuilder::blocks.landing.partials.layout-column', [
                        'column' => $column,
                        'columnClass' => 'lg:w-1/4 md:w-1/2 w-full px-4',
                    ])
                @endforeach
            </div>
        @endif
    </div>

    <div class="bg-gray-100">
        <div class="container mx-auto py-4 px-5 flex flex-wrap flex-col sm:flex-row items-center">
            @include('voodbuilder::blocks.landing.partials.layout-copyright')
        </div>
    </div>
</footer>
