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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
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
use Voodflow\Vpress\Support\SitePageSection;
use Voodflow\Vpress\Support\SubThemeRegistry;

class SitePageResource extends Resource
{
    protected static ?string $model = SitePage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-window';

    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return __('vpress::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('vpress::admin.navigation.pages');
    }

    protected static ?string $modelLabel = 'Page';

    protected static ?string $slug = 'vpress/pages';

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

                                Textarea::make('excerpt')
                                    ->label(__('vpress::admin.fields.excerpt'))
                                    ->rows(3)
                                    ->maxLength(500)
                                    ->helperText(__('vpress::admin.helpers.excerpt'))
                                    ->columnSpanFull(),

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

                                Select::make('sub_theme')
                                    ->label(__('vpress::admin.fields.sub_theme'))
                                    ->options(fn (): array => [
                                        '' => __('vpress::admin.fields.sub_theme_inherit'),
                                        ...app(SubThemeRegistry::class)->options(),
                                    ])
                                    ->default(null)
                                    ->nullable()
                                    ->native(false)
                                    ->helperText(__('vpress::admin.helpers.sub_theme_page')),

                                Select::make('section')
                                    ->label(__('vpress::admin.fields.section'))
                                    ->options(fn (): array => [
                                        '' => __('vpress::admin.fields.section_none'),
                                        SitePageSection::BLOG => __('vpress::demo.blog.title'),
                                        SitePageSection::NEWS => __('vpress::demo.news.title'),
                                    ])
                                    ->nullable()
                                    ->native(false)
                                    ->live(),

                                Toggle::make('section_home')
                                    ->label(__('vpress::admin.fields.section_home'))
                                    ->helperText(__('vpress::admin.helpers.section_home'))
                                    ->visible(fn (Get $get): bool => filled($get('section'))),
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
                TextColumn::make('sub_theme')
                    ->label(__('vpress::admin.fields.sub_theme'))
                    ->formatStateUsing(fn (?string $state): string => filled($state)
                        ? app(SubThemeRegistry::class)->label($state)
                        : __('vpress::admin.fields.sub_theme_inherit'))
                    ->badge()
                    ->color(fn (?string $state): string => filled($state) ? 'info' : 'gray'),
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
