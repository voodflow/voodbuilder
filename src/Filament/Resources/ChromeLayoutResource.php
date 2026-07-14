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
use Voodflow\Voodbuilder\Support\ChromeLayoutDefaults;
use Voodflow\Voodbuilder\Support\ChromeLayoutResolver;
use Voodflow\Voodbuilder\Support\ContentChannelRegistry;

class ChromeLayoutResource extends Resource
{
    protected static ?string $model = ChromeLayout::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?int $navigationSort = 3;

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
                            ->helperText(__('voodbuilder::chrome_layouts.fields.is_default_help')),
                        Select::make('channel_ids')
                            ->label(__('voodbuilder::chrome_layouts.fields.channels'))
                            ->multiple()
                            ->options(fn (): array => collect(app(ContentChannelRegistry::class)->all())
                                ->mapWithKeys(fn ($channel, $id) => [$id => $channel->label()])
                                ->all())
                            ->helperText(__('voodbuilder::chrome_layouts.fields.channels_help')),
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
                    ->formatStateUsing(fn ($state): string => is_array($state) ? implode(', ', $state) : '')
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
