<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Voodflow\Voodbuilder\Filament\Resources\PopupResource\Pages\CreatePopup;
use Voodflow\Voodbuilder\Filament\Resources\PopupResource\Pages\EditPopup;
use Voodflow\Voodbuilder\Filament\Resources\PopupResource\Pages\ListPopups;
use Voodflow\Voodbuilder\Models\BuilderPopup;

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
                Section::make(__('voodbuilder::popups.sections.details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('voodbuilder::popups.fields.name'))
                            ->required()
                            ->maxLength(120),
                        Toggle::make('enabled')
                            ->label(__('voodbuilder::popups.fields.enabled'))
                            ->default(true),
                        TextInput::make('priority')
                            ->label(__('voodbuilder::popups.fields.priority'))
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(999),
                    ]),
                Section::make(__('voodbuilder::popups.sections.trigger'))
                    ->schema([
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
                        TextInput::make('rules.trigger.delay_seconds')
                            ->label(__('voodbuilder::popups.fields.delay_seconds'))
                            ->numeric()
                            ->default(3)
                            ->minValue(0)
                            ->maxValue(600)
                            ->visible(fn (Get $get): bool => $get('rules.trigger.type') === 'delay'),
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
                            ->visible(fn (Get $get): bool => $get('rules.trigger.type') === 'click'),
                    ]),
                Section::make(__('voodbuilder::popups.sections.frequency'))
                    ->schema([
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
                            ->live(),
                        TextInput::make('rules.frequency.days')
                            ->label(__('voodbuilder::popups.fields.frequency_days'))
                            ->numeric()
                            ->default(7)
                            ->minValue(1)
                            ->maxValue(365)
                            ->visible(fn (Get $get): bool => $get('rules.frequency.mode') === 'days'),
                    ]),
                Section::make(__('voodbuilder::popups.sections.targeting'))
                    ->schema([
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
                Section::make(__('voodbuilder::popups.sections.display'))
                    ->schema([
                        Select::make('rules.display.width')
                            ->label(__('voodbuilder::popups.fields.width'))
                            ->options([
                                'sm' => __('voodbuilder::popups.width.sm'),
                                'md' => __('voodbuilder::popups.width.md'),
                                'lg' => __('voodbuilder::popups.width.lg'),
                                'xl' => __('voodbuilder::popups.width.xl'),
                            ])
                            ->default('md'),
                        Toggle::make('rules.display.overlay')
                            ->label(__('voodbuilder::popups.fields.overlay'))
                            ->default(true),
                        Toggle::make('rules.display.close_on_overlay')
                            ->label(__('voodbuilder::popups.fields.close_on_overlay'))
                            ->default(true),
                        Toggle::make('rules.display.close_on_escape')
                            ->label(__('voodbuilder::popups.fields.close_on_escape'))
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('voodbuilder::popups.fields.name'))
                    ->searchable()
                    ->sortable(),
                IconColumn::make('enabled')
                    ->label(__('voodbuilder::popups.fields.enabled'))
                    ->boolean(),
                TextColumn::make('priority')
                    ->label(__('voodbuilder::popups.fields.priority'))
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('voodbuilder::popups.fields.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
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
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeFormData(array $data): array
    {
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

        $data['rules'] = $rules;

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function hydrateFormData(array $data): array
    {
        $rules = array_replace_recursive(BuilderPopup::defaultRules(), $data['rules'] ?? []);
        $rules['targeting']['logged_in'] = 'any';
        $rules['targeting']['page_path'] = '';

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
}
