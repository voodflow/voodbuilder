<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Voodflow\Voodbuilder\Enums\PopupAnalyticsEvent;
use Voodflow\Voodbuilder\Filament\Actions\ViewPopupStatsAction;
use Voodflow\Voodbuilder\Filament\Resources\PopupResource\Pages\CreatePopup;
use Voodflow\Voodbuilder\Filament\Resources\PopupResource\Pages\EditPopup;
use Voodflow\Voodbuilder\Filament\Resources\PopupResource\Pages\ListPopups;
use Voodflow\Voodbuilder\Models\BuilderPopup;
use Voodflow\Voodbuilder\Support\GrapesJs\Popups\PopupRulesEvaluator;
use Voodflow\Vtuts\Support\Locales;

class PopupResource extends Resource
{
    protected static ?string $model = BuilderPopup::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'voodbuilder/popups';

    public static function getNavigationGroup(): ?string
    {
        return __('voodbuilder::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('voodbuilder::popups.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('voodbuilder::popups.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('voodbuilder::popups.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('popup_configurator')
                    ->persistTabInQueryString('popup-tab')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make(__('voodbuilder::popups.editor.tab_general'))
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Grid::make(12)->schema([
                                    TextInput::make('name')
                                        ->label(__('voodbuilder::popups.fields.name'))
                                        ->required()
                                        ->maxLength(120)
                                        ->columnSpan(6),
                                    Select::make('locale')
                                        ->label(__('voodbuilder::popups.fields.locale'))
                                        ->options(fn (): array => ['' => __('voodbuilder::popups.fields.locale_all')] + (class_exists(Locales::class) ? Locales::options() : ['en' => 'English']))
                                        ->helperText(__('voodbuilder::popups.fields.locale_help'))
                                        ->native(false)
                                        ->columnSpan(4),
                                    TextInput::make('priority')
                                        ->label(__('voodbuilder::popups.fields.priority'))
                                        ->numeric()
                                        ->default(0)
                                        ->minValue(0)
                                        ->maxValue(999)
                                        ->helperText(__('voodbuilder::popups.fields.priority_help'))
                                        ->columnSpan(2),
                                    Textarea::make('description')
                                        ->label(__('voodbuilder::popups.fields.description'))
                                        ->rows(2)
                                        ->maxLength(2000)
                                        ->helperText(__('voodbuilder::popups.fields.description_help'))
                                        ->columnSpanFull(),
                                    Toggle::make('enabled')
                                        ->label(__('voodbuilder::popups.fields.enabled'))
                                        ->default(true)
                                        ->inline(false)
                                        ->columnSpan(2),
                                    Toggle::make('paused')
                                        ->label(__('voodbuilder::popups.fields.paused'))
                                        ->default(false)
                                        ->inline(false)
                                        ->columnSpan(2),
                                ]),
                            ]),
                        Tab::make(__('voodbuilder::popups.editor.tab_triggers'))
                            ->icon('heroicon-o-bolt')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('rules.trigger.type')
                                        ->label(__('voodbuilder::popups.fields.trigger_type'))
                                        ->options([
                                            'load' => __('voodbuilder::popups.triggers.load'),
                                            'delay' => __('voodbuilder::popups.triggers.delay'),
                                            'scroll' => __('voodbuilder::popups.triggers.scroll'),
                                            'exit_intent' => __('voodbuilder::popups.triggers.exit_intent'),
                                            'click' => __('voodbuilder::popups.triggers.click'),
                                        ])
                                        ->default('delay')
                                        ->required()
                                        ->live(),
                                    Select::make('rules.frequency.mode')
                                        ->label(__('voodbuilder::popups.fields.frequency_mode'))
                                        ->options([
                                            'always' => __('voodbuilder::popups.frequency.always'),
                                            'once' => __('voodbuilder::popups.frequency.once'),
                                            'session' => __('voodbuilder::popups.frequency.session'),
                                            'days' => __('voodbuilder::popups.frequency.days'),
                                        ])
                                        ->default('session')
                                        ->required()
                                        ->live()
                                        ->helperText(__('voodbuilder::popups.fields.frequency_storage_help')),
                                    TextInput::make('rules.trigger.delay_seconds')
                                        ->label(__('voodbuilder::popups.fields.delay_seconds'))
                                        ->numeric()
                                        ->default(3)
                                        ->minValue(0)
                                        ->maxValue(600)
                                        ->visible(fn (Get $get): bool => $get('rules.trigger.type') === 'delay'),
                                    TextInput::make('rules.frequency.days')
                                        ->label(__('voodbuilder::popups.fields.frequency_days'))
                                        ->numeric()
                                        ->default(7)
                                        ->minValue(1)
                                        ->maxValue(365)
                                        ->helperText(__('voodbuilder::popups.fields.frequency_days_help'))
                                        ->visible(fn (Get $get): bool => $get('rules.frequency.mode') === 'days'),
                                    TextInput::make('rules.trigger.scroll_percent')
                                        ->label(__('voodbuilder::popups.fields.scroll_percent'))
                                        ->numeric()
                                        ->default(50)
                                        ->minValue(1)
                                        ->maxValue(100)
                                        ->visible(fn (Get $get): bool => $get('rules.trigger.type') === 'scroll'),
                                    TextInput::make('rules.trigger.click_selector')
                                        ->label(__('voodbuilder::popups.fields.click_selector'))
                                        ->placeholder('#open-promo')
                                        ->maxLength(200)
                                        ->visible(fn (Get $get): bool => $get('rules.trigger.type') === 'click')
                                        ->columnSpanFull(),
                                ]),
                            ]),
                        Tab::make(__('voodbuilder::popups.editor.tab_targeting'))
                            ->icon('heroicon-o-viewfinder-circle')
                            ->schema([
                                Grid::make(2)->schema([
                                    Select::make('rules.targeting.logged_in')
                                        ->label(__('voodbuilder::popups.fields.logged_in'))
                                        ->options([
                                            'any' => __('voodbuilder::popups.targeting.any'),
                                            'yes' => __('voodbuilder::popups.targeting.logged_in'),
                                            'no' => __('voodbuilder::popups.targeting.logged_out'),
                                        ])
                                        ->default('any'),
                                    TextInput::make('rules.targeting.page_path')
                                        ->label(__('voodbuilder::popups.fields.page_path'))
                                        ->placeholder('/blog')
                                        ->helperText(__('voodbuilder::popups.fields.page_path_help'))
                                        ->maxLength(255),
                                ]),
                            ]),
                        Tab::make(__('voodbuilder::popups.editor.tab_schedule'))
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Grid::make(3)->schema([
                                    DateTimePicker::make('rules.schedule.start_at')
                                        ->label(__('voodbuilder::popups.fields.start_at'))
                                        ->seconds(false),
                                    DateTimePicker::make('rules.schedule.end_at')
                                        ->label(__('voodbuilder::popups.fields.end_at'))
                                        ->seconds(false)
                                        ->rule('after_or_equal:rules.schedule.start_at'),
                                    Select::make('rules.schedule.timezone')
                                        ->label(__('voodbuilder::popups.fields.timezone'))
                                        ->options(self::timezoneOptions())
                                        ->searchable()
                                        ->default(fn (): string => (string) config('app.timezone', 'UTC'))
                                        ->helperText(__('voodbuilder::popups.fields.timezone_help')),
                                ]),
                                CheckboxList::make('rules.schedule.weekly_days')
                                    ->label(__('voodbuilder::popups.fields.weekly_days'))
                                    ->options([
                                        'monday' => __('voodbuilder::popups.schedule.monday'),
                                        'tuesday' => __('voodbuilder::popups.schedule.tuesday'),
                                        'wednesday' => __('voodbuilder::popups.schedule.wednesday'),
                                        'thursday' => __('voodbuilder::popups.schedule.thursday'),
                                        'friday' => __('voodbuilder::popups.schedule.friday'),
                                        'saturday' => __('voodbuilder::popups.schedule.saturday'),
                                        'sunday' => __('voodbuilder::popups.schedule.sunday'),
                                    ])
                                    ->columns(4)
                                    ->bulkToggleable()
                                    ->columnSpanFull(),
                            ]),
                        Tab::make(__('voodbuilder::popups.editor.tab_appearance'))
                            ->icon('heroicon-o-square-3-stack-3d')
                            ->schema([
                                Grid::make(4)->schema([
                                    Select::make('rules.display.width')
                                        ->label(__('voodbuilder::popups.fields.width'))
                                        ->options([
                                            'sm' => __('voodbuilder::popups.width.sm'),
                                            'md' => __('voodbuilder::popups.width.md'),
                                            'lg' => __('voodbuilder::popups.width.lg'),
                                            'xl' => __('voodbuilder::popups.width.xl'),
                                        ])
                                        ->default('md')
                                        ->columnSpan(1),
                                    Toggle::make('rules.display.overlay')
                                        ->label(__('voodbuilder::popups.fields.overlay'))
                                        ->default(true)
                                        ->inline(false),
                                    Toggle::make('rules.display.close_on_overlay')
                                        ->label(__('voodbuilder::popups.fields.close_on_overlay'))
                                        ->default(true)
                                        ->inline(false),
                                    Toggle::make('rules.display.close_on_escape')
                                        ->label(__('voodbuilder::popups.fields.close_on_escape'))
                                        ->default(true)
                                        ->inline(false),
                                ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount([
                'events as views_count' => fn (Builder $events): Builder => $events
                    ->where('event', PopupAnalyticsEvent::Shown->value),
                'events as cta_count' => fn (Builder $events): Builder => $events
                    ->where('event', PopupAnalyticsEvent::CtaClick->value),
            ]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('voodbuilder::popups.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (BuilderPopup $record): ?string => $record->description),
                TextColumn::make('locale')
                    ->label(__('voodbuilder::popups.fields.locale'))
                    ->formatStateUsing(function (?string $state): string {
                        if ($state === null || $state === '') {
                            return __('voodbuilder::popups.fields.locale_all');
                        }

                        if (class_exists(Locales::class)) {
                            return Locales::options()[$state] ?? strtoupper($state);
                        }

                        return strtoupper($state);
                    })
                    ->sortable(),
                IconColumn::make('enabled')
                    ->label(__('voodbuilder::popups.fields.enabled'))
                    ->boolean(),
                IconColumn::make('paused')
                    ->label(__('voodbuilder::popups.fields.paused'))
                    ->boolean(),
                TextColumn::make('views_count')
                    ->label(__('voodbuilder::popups.fields.views'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cta_count')
                    ->label(__('voodbuilder::popups.fields.cta_clicks'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('priority')
                    ->label(__('voodbuilder::popups.fields.priority'))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('voodbuilder::popups.fields.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                ViewPopupStatsAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPopups::route('/'),
            'create' => CreatePopup::route('/create'),
            'edit' => EditPopup::route('/{record}/edit'),
        ];
    }

    /**
     * Merge editor/API payload onto the current popup state.
     *
     * Avoids array_replace_recursive on list fields like weekly_days, which
     * merges by numeric index and leaves stale weekdays behind.
     *
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public static function mergeEditorPayload(array $current, array $incoming): array
    {
        $merged = array_replace($current, $incoming);

        if (! array_key_exists('rules', $incoming) || ! is_array($incoming['rules'])) {
            return $merged;
        }

        $currentRules = is_array($current['rules'] ?? null) ? $current['rules'] : [];
        $merged['rules'] = array_replace_recursive($currentRules, $incoming['rules']);

        $incomingSchedule = $incoming['rules']['schedule'] ?? null;

        if (is_array($incomingSchedule) && array_key_exists('weekly_days', $incomingSchedule)) {
            $merged['rules']['schedule']['weekly_days'] = is_array($incomingSchedule['weekly_days'])
                ? array_values($incomingSchedule['weekly_days'])
                : [];
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeFormData(array $data): array
    {
        $description = trim((string) ($data['description'] ?? ''));
        $data['description'] = $description !== '' ? $description : null;

        $locale = strtolower(trim((string) ($data['locale'] ?? '')));
        $data['locale'] = ($locale !== '' && $locale !== '*') ? $locale : null;

        $rules = array_replace_recursive(BuilderPopup::defaultRules(), $data['rules'] ?? []);
        $targetingSets = [];

        $loggedIn = (string) ($rules['targeting']['logged_in'] ?? 'any');

        if ($loggedIn === 'yes') {
            $targetingSets[] = [
                'conditions' => [
                    ['key' => 'user_logged_in', 'compare' => '==', 'value' => '1'],
                ],
            ];
        } elseif ($loggedIn === 'no') {
            $targetingSets[] = [
                'conditions' => [
                    ['key' => 'user_logged_in', 'compare' => '==', 'value' => '0'],
                ],
            ];
        }

        $pagePath = trim((string) ($rules['targeting']['page_path'] ?? ''));

        if ($pagePath !== '') {
            $targetingSets[] = [
                'conditions' => [
                    ['key' => 'page_path', 'compare' => 'contains', 'value' => $pagePath],
                ],
            ];
        }

        unset($rules['targeting']['logged_in'], $rules['targeting']['page_path']);

        $rules['targeting']['match'] = 'all';
        $rules['targeting']['sets'] = $targetingSets;

        $weeklyDays = PopupRulesEvaluator::normalizeWeeklyDays($rules['schedule'] ?? []);
        $rules['schedule']['weekly_days'] = $weeklyDays;
        $rules['schedule']['weekly_day'] = $weeklyDays[0] ?? '';
        $rules['schedule']['weekly_start_time'] = '';
        $rules['schedule']['weekly_end_time'] = '';

        if (blank($rules['schedule']['timezone'] ?? null)) {
            $rules['schedule']['timezone'] = (string) config('app.timezone', 'UTC');
        }

        $data['rules'] = $rules;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrateFormData(array $data): array
    {
        $data['locale'] = filled($data['locale'] ?? null) ? (string) $data['locale'] : '';
        $data['description'] = (string) ($data['description'] ?? '');

        $rules = array_replace_recursive(BuilderPopup::defaultRules(), $data['rules'] ?? []);
        $rules['targeting']['logged_in'] = 'any';
        $rules['targeting']['page_path'] = '';
        $rules['schedule']['weekly_days'] = PopupRulesEvaluator::normalizeWeeklyDays($rules['schedule'] ?? []);

        if (blank($rules['schedule']['timezone'] ?? null)) {
            $rules['schedule']['timezone'] = (string) config('app.timezone', 'UTC');
        }

        foreach ($rules['targeting']['sets'] ?? [] as $set) {
            foreach ($set['conditions'] ?? [] as $condition) {
                $key = (string) ($condition['key'] ?? '');

                if ($key === 'user_logged_in') {
                    $rules['targeting']['logged_in'] = in_array($condition['value'] ?? null, ['1', 1, true, 'true'], true)
                        ? 'yes'
                        : 'no';
                }

                if ($key === 'page_path') {
                    $rules['targeting']['page_path'] = (string) ($condition['value'] ?? '');
                }
            }
        }

        $data['rules'] = $rules;

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public static function timezoneOptions(): array
    {
        $identifiers = timezone_identifiers_list();
        $options = [];

        foreach ($identifiers as $identifier) {
            $options[$identifier] = $identifier;
        }

        return $options;
    }
}
