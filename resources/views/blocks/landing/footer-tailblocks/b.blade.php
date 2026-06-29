@php
    $columns = collect($menuColumns)->values()->map(fn (array $column, int $index): array => array_merge($column, ['index' => $index + 1]))->all();
@endphp

<footer class="text-gray-600 body-font vp-landing-footer-tailblocks vp-landing-footer-tailblocks--b">
    <div class="container px-5 py-24 mx-auto flex md:items-center lg:items-start md:flex-row md:flex-nowrap flex-wrap flex-col">
        <div class="w-64 shrink-0 md:mx-0 mx-auto text-center md:text-left md:mt-0 mt-10">
            @include('voodbuilder::blocks.landing.partials.tailblocks-brand', ['brandClass' => 'md:justify-start justify-center'])
        </div>

        @if ($columns !== [])
            <div class="grow flex flex-wrap md:pr-20 -mb-10 md:text-left text-center order-first">
                @foreach ($columns as $column)
                    @include('voodbuilder::blocks.landing.partials.tailblocks-column', ['column' => $column])
                @endforeach
            </div>
        @endif
    </div>

    <div class="bg-gray-100">
        <div class="container mx-auto py-4 px-5 flex flex-wrap flex-col sm:flex-row items-center">
            @include('voodbuilder::blocks.landing.partials.tailblocks-copyright')
        </div>
    </div>
</footer>
