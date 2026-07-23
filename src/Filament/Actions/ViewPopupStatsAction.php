<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\HtmlString;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\GrapesJs\Popups\PopupAnalytics;

class ViewPopupStatsAction
{
    public static function make(): Action
    {
        return Action::make('viewStats')
            ->label(__('voodbuilder::popups.actions.view_stats'))
            ->icon('heroicon-o-chart-bar')
            ->color('gray')
            ->slideOver()
            ->modalHeading(fn (BuilderPopup $record): string => __('voodbuilder::popups.stats.heading', [
                'name' => $record->name,
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('voodbuilder::popups.stats.close'))
            ->fillForm(fn (): array => [
                'period' => '30d',
                'from' => now()->subDays(29)->toDateString(),
                'until' => now()->toDateString(),
            ])
            ->schema([
                Select::make('period')
                    ->label(__('voodbuilder::popups.stats.period'))
                    ->options([
                        '7d' => __('voodbuilder::popups.stats.periods.7d'),
                        '30d' => __('voodbuilder::popups.stats.periods.30d'),
                        '90d' => __('voodbuilder::popups.stats.periods.90d'),
                        'all' => __('voodbuilder::popups.stats.periods.all'),
                        'custom' => __('voodbuilder::popups.stats.periods.custom'),
                    ])
                    ->default('30d')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $range = app(PopupAnalytics::class)->resolvePeriod((string) $state);

                        $set('from', $range['from']?->toDateString());
                        $set('until', $range['until']?->toDateString());
                    }),
                DatePicker::make('from')
                    ->label(__('voodbuilder::popups.stats.from'))
                    ->native(false)
                    ->live()
                    ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                DatePicker::make('until')
                    ->label(__('voodbuilder::popups.stats.until'))
                    ->native(false)
                    ->live()
                    ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                Placeholder::make('summary')
                    ->hiddenLabel()
                    ->content(function (Get $get, BuilderPopup $record): HtmlString {
                        $range = app(PopupAnalytics::class)->resolvePeriod(
                            (string) ($get('period') ?? '30d'),
                            $get('from'),
                            $get('until'),
                        );

                        $stats = app(PopupAnalytics::class)->summarize(
                            $record,
                            $range['from'],
                            $range['until'],
                        );

                        return new HtmlString(
                            view('voodbuilder::filament.popup-stats', ['stats' => $stats])->render()
                        );
                    }),
            ]);
    }
}
