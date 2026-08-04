<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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
use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Filament\Actions\CloneChromeLayoutAction;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource\Pages\CreateChromeLayout;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource\Pages\EditChromeLayout;
use Voodflow\Voodbuilder\Filament\Resources\ChromeLayoutResource\Pages\ListChromeLayouts;
use Voodflow\Voodbuilder\Models\ChromeLayout;
use Voodflow\Voodbuilder\Support\ChromeLayoutContentWidth;
use Voodflow\Voodbuilder\Support\ChromeLayoutDefaults;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;
use Voodflow\Voodbuilder\Support\ContentChannelRegistry;

/**
 * Filament resource: Chrome Layout.
 */
class ChromeLayoutResource extends Resource
{
    protected static ?string $model = ChromeLayout::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'voodbuilder/layouts';

    public static function getNavigationGroup(): ?string
    {
        return __('voodbuilder::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('voodbuilder::chrome_layouts.navigation.label');
    }

    public static function getModelLabel(): string
    {
        return __('voodbuilder::chrome_layouts.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('voodbuilder::chrome_layouts.model.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make(__('voodbuilder::chrome_layouts.sections.details'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('voodbuilder::chrome_layouts.fields.name'))
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set, ?ChromeLayout $record): void {
                                if ($record !== null) {
                                    return;
                                }

                                $set('slug', Str::slug((string) $state));
                            }),
                        TextInput::make('slug')
                            ->label(__('voodbuilder::chrome_layouts.fields.slug'))
                            ->required()
                            ->maxLength(120)
                            ->unique(ignoreRecord: true)
                            ->alphaDash(),
                        Toggle::make('enabled')
                            ->label(__('voodbuilder::chrome_layouts.fields.enabled'))
                            ->default(true),
                        Toggle::make('is_default')
                            ->label(__('voodbuilder::chrome_layouts.fields.is_default'))
                            ->helperText(__('voodbuilder::chrome_layouts.fields.is_default_help'))
                            ->live()
                            ->afterStateUpdated(function (bool $state, callable $set): void {
                                if ($state) {
                                    $set('channel_ids', []);
                                }
                            }),
                        Select::make('channel_ids')
                            ->label(__('voodbuilder::chrome_layouts.fields.channels'))
                            ->multiple()
                            ->options(fn (): array => collect(app(ContentChannelRegistry::class)->all())
                                ->mapWithKeys(fn ($channel, $id) => [$id => $channel->label()])
                                ->all())
                            ->helperText(__('voodbuilder::chrome_layouts.fields.channels_help'))
                            ->visible(fn (Get $get): bool => ! $get('is_default'))
                            ->dehydrated(fn (Get $get): bool => ! $get('is_default')),
                        Select::make('content_width')
                            ->label(__('voodbuilder::chrome_layouts.fields.content_width'))
                            ->options(fn (): array => ChromeLayoutContentWidth::modeOptions())
                            ->default(ChromeLayoutContentWidth::MODE_FULL)
                            ->native(false)
                            ->live()
                            ->helperText(__('voodbuilder::chrome_layouts.fields.content_width_help')),
                        TextInput::make('content_max_width')
                            ->label(__('voodbuilder::chrome_layouts.fields.content_max_width'))
                            ->placeholder('72rem')
                            ->helperText(fn (Get $get): string => $get('content_width') === ChromeLayoutContentWidth::MODE_FULL
                                ? __('voodbuilder::chrome_layouts.fields.content_max_width_full_help')
                                : __('voodbuilder::chrome_layouts.fields.content_max_width_help'))
                            ->visible(fn (Get $get): bool => in_array(
                                $get('content_width'),
                                [ChromeLayoutContentWidth::MODE_CUSTOM, ChromeLayoutContentWidth::MODE_FULL],
                                true,
                            ))
                            ->dehydrateStateUsing(fn (?string $state): ?string => ChromeLayoutContentWidth::normalizeMaxWidth($state))
                            ->required(fn (Get $get): bool => $get('content_width') === ChromeLayoutContentWidth::MODE_CUSTOM),
                        Select::make('chrome_width')
                            ->label(__('voodbuilder::chrome_layouts.fields.chrome_width'))
                            ->options(fn (): array => ChromeLayoutContentWidth::chromeWidthOptions())
                            ->default(ChromeLayoutContentWidth::CHROME_FULL)
                            ->native(false)
                            ->helperText(__('voodbuilder::chrome_layouts.fields.chrome_width_help')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('voodbuilder::chrome_layouts.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('voodbuilder::chrome_layouts.fields.slug'))
                    ->toggleable(),
                IconColumn::make('enabled')
                    ->label(__('voodbuilder::chrome_layouts.fields.enabled'))
                    ->boolean(),
                IconColumn::make('is_default')
                    ->label(__('voodbuilder::chrome_layouts.fields.is_default'))
                    ->boolean(),
                TextColumn::make('channel_ids')
                    ->label(__('voodbuilder::chrome_layouts.fields.channels'))
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        // With ->badge(), Filament passes each array item (string id), not the whole array.
                        if (is_array($state)) {
                            return collect($state)
                                ->filter(fn ($id): bool => is_string($id) && $id !== '')
                                ->map(function (string $id): string {
                                    $channel = app(ContentChannelRegistry::class)->get($id);

                                    return $channel?->label() ?? $id;
                                })
                                ->implode(', ');
                        }

                        if (! is_string($state) || $state === '') {
                            return '';
                        }

                        $channel = app(ContentChannelRegistry::class)->get($state);

                        return $channel?->label() ?? $state;
                    })
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('content_width')
                    ->label(__('voodbuilder::chrome_layouts.fields.content_width'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match (ChromeLayoutContentWidth::normalizeMode((string) ($state ?? ''))) {
                        ChromeLayoutContentWidth::MODE_STANDARD => __('voodbuilder::chrome_layouts.fields.content_width_standard'),
                        ChromeLayoutContentWidth::MODE_CUSTOM => __('voodbuilder::chrome_layouts.fields.content_width_custom'),
                        default => __('voodbuilder::chrome_layouts.fields.content_width_full'),
                    })
                    ->toggleable(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('openVisualEditor')
                        ->label(__('voodbuilder::chrome_layouts.actions.open_visual_editor'))
                        ->icon('heroicon-o-paint-brush')
                        ->color('primary')
                        ->url(fn (ChromeLayout $record): string => route('voodbuilder.chrome-layouts.editor', [
                            'chromeLayout' => $record,
                            'edit' => 1,
                        ]))
                        ->openUrlInNewTab(),
                    CloneChromeLayoutAction::make(),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->iconButton()
                    ->tooltip(__('voodbuilder::admin.actions.actions')),
            ])
            ->recordActionsColumnLabel(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListChromeLayouts::route('/'),
            'create' => CreateChromeLayout::route('/create'),
            'edit' => EditChromeLayout::route('/{record}/edit'),
        ];
    }

    public static function defaultStarterHtml(): string
    {
        return ChromeLayoutDefaults::starterHtml();
    }
}
