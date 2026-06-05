<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Voodflow\Vpress\Filament\Resources\SitePageResource\Pages\CreateSitePage;
use Voodflow\Vpress\Filament\Resources\SitePageResource\Pages\EditSitePage;
use Voodflow\Vpress\Filament\Resources\SitePageResource\Pages\ListSitePages;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\RichContentBlockRegistry;

class SitePageResource extends Resource
{
    protected static ?string $model = SitePage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-window';

    protected static string|\UnitEnum|null $navigationGroup = 'Site';

    protected static ?int $navigationSort = 0;

    protected static ?string $navigationLabel = 'Pages';

    protected static ?string $modelLabel = 'Page';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true),

                                TextInput::make('slug')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->disabled(fn (?SitePage $record): bool => (bool) $record?->is_home),

                                RichEditor::make('content')
                                    ->label(__('Page content'))
                                    ->customBlocks(app(RichContentBlockRegistry::class)->editorGroups())
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'strike', 'link'],
                                        ['h2', 'h3', 'blockquote', 'bulletList', 'orderedList'],
                                        ['customBlocks'],
                                    ])
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),

                Group::make()
                    ->schema([
                        Section::make('Publish')
                            ->afterHeader([
                                Action::make('view')
                                    ->icon('heroicon-o-eye')
                                    ->color('gray')
                                    ->url(fn (SitePage $record): string => $record->getUrl())
                                    ->openUrlInNewTab()
                                    ->visible(fn (?SitePage $record): bool => $record?->published ?? false),
                            ])
                            ->schema([
                                Toggle::make('published')
                                    ->label(__('Published'))
                                    ->default(false),

                                DateTimePicker::make('published_at'),

                                Toggle::make('is_home')
                                    ->label(__('Home page'))
                                    ->helperText(__('Only one page can be the home page.'))
                                    ->disabled(fn (?SitePage $record): bool => (bool) $record?->is_home)
                                    ->dehydrated(),

                                Select::make('layout')
                                    ->options([
                                        'home' => __('Home (full width)'),
                                        'page' => __('Standard page'),
                                    ])
                                    ->default('page')
                                    ->native(false)
                                    ->disabled(fn (?SitePage $record): bool => (bool) $record?->is_home),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('layout')->badge(),
                IconColumn::make('is_home')->label(__('Home'))->boolean(),
                IconColumn::make('published')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->hidden(fn (SitePage $record): bool => $record->is_home),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('title');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSitePages::route('/'),
            'create' => CreateSitePage::route('/create'),
            'edit' => EditSitePage::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }
}
