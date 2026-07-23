@php
    /** @var array{shown: int, closed: int, cta_click: int, conversion_rate: float|null, close_rate: float|null, from: string|null, until: string|null, daily: list<array{date: string, shown: int, closed: int, cta_click: int}>} $stats */
@endphp

<div class="space-y-4">
    @if ($stats['from'] || $stats['until'])
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('voodbuilder::popups.stats.range', [
                'from' => $stats['from'] ?? '—',
                'until' => $stats['until'] ?? '—',
            ]) }}
        </p>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('voodbuilder::popups.stats.range_all') }}
        </p>
    @endif

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('voodbuilder::popups.stats.shown') }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
                {{ number_format($stats['shown']) }}
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('voodbuilder::popups.stats.closed') }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
                {{ number_format($stats['closed']) }}
            </div>
            @if ($stats['close_rate'] !== null)
                <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('voodbuilder::popups.stats.close_rate', ['rate' => $stats['close_rate']]) }}
                </div>
            @endif
        </div>
        <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('voodbuilder::popups.stats.cta_click') }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
                {{ number_format($stats['cta_click']) }}
            </div>
        </div>
        <div class="rounded-xl border border-gray-200 p-3 dark:border-gray-700">
            <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('voodbuilder::popups.stats.conversion') }}
            </div>
            <div class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">
                {{ $stats['conversion_rate'] !== null ? $stats['conversion_rate'].'%' : '—' }}
            </div>
        </div>
    </div>

    @if (count($stats['daily']) > 0)
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                    <tr>
                        <th class="px-3 py-2">{{ __('voodbuilder::popups.stats.day') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('voodbuilder::popups.stats.shown') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('voodbuilder::popups.stats.closed') }}</th>
                        <th class="px-3 py-2 text-right">{{ __('voodbuilder::popups.stats.cta_click') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($stats['daily'] as $row)
                        <tr>
                            <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $row['date'] }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['shown']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['closed']) }}</td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-950 dark:text-white">{{ number_format($row['cta_click']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-sm text-gray-500 dark:text-gray-400">
            {{ __('voodbuilder::popups.stats.empty') }}
        </p>
    @endif
</div>
