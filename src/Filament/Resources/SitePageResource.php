<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
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
use Illuminate\Support\HtmlString;
use Voodflow\Vpress\Enums\PageBuilder;
use Voodflow\Vpress\Filament\Resources\SitePageResource\Pages\CreateSitePage;
use Voodflow\Vpress\Filament\Resources\SitePageResource\Pages\EditSitePage;
use Voodflow\Vpress\Filament\Resources\SitePageResource\Pages\ListSitePages;
use Voodflow\Vpress\Models\SitePage;
use Voodflow\Vpress\Support\RichContentBlockRegistry;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Support\ThemeBindings;

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

                                Select::make('builder')
                                    ->label(__('vpress::pro.fields.builder'))
                                    ->options(PageBuilder::options())
                                    ->default(PageBuilder::RichEditor)
                                    ->native(false)
                                    ->live()
                                    ->helperText(__('vpress::pro.helpers.builder'))
                                    ->columnSpanFull(),

                                RichEditor::make('content')
                                    ->label(__('Page content'))
                                    ->customBlocks(app(RichContentBlockRegistry::class)->editorGroups())
                                    ->toolbarButtons([
                                        ['bold', 'italic', 'strike', 'link'],
                                        ['h2', 'h3', 'blockquote', 'bulletList', 'orderedList'],
                                        ['customBlocks'],
                                    ])
                                    ->visible(fn (Get $get): bool => PageBuilder::matches($get('builder'), PageBuilder::RichEditor))
                                    ->columnSpanFull(),

                                Placeholder::make('grapesjs_frontend_hint')
                                    ->label(__('vpress::pro.fields.grapesjs_edit'))
                                    ->content(function (?SitePage $record): HtmlString {
                                        if ($record === null) {
                                            return new HtmlString(e(__('vpress::pro.helpers.grapesjs_save_first')));
                                        }

                                        $url = $record->getUrl();

                                        return new HtmlString(
                                            __('vpress::pro.helpers.grapesjs_frontend', ['url' => $url])
                                            .' <a class="text-primary-600 underline" href="'.e($url).'" target="_blank" rel="noopener">'
                                            .e(__('vpress::pro.actions.open_visual_editor'))
                                            .'</a>'
                                        );
                                    })
                                    ->visible(fn (Get $get): bool => PageBuilder::matches($get('builder'), PageBuilder::GrapesJs))
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
                                        'landing' => __('vpress::landing.layouts.landing'),
                                        'home' => __('Home (full width)'),
                                        'page' => __('Standard page'),
                                    ])
                                    ->default('page')
                                    ->native(false)
                                    ->helperText(fn (Get $get): ?string => $get('layout') === 'landing'
                                        ? __('vpress::landing.layouts.landing_help')
                                        : null)
                                    ->live()
                                    ->disabled(fn (?SitePage $record): bool => (bool) $record?->is_home),

                                Toggle::make('hide_site_footer')
                                    ->label(__('vpress::landing.layouts.hide_site_footer'))
                                    ->helperText(__('vpress::landing.layouts.hide_site_footer_help'))
                                    ->visible(fn (Get $get): bool => in_array($get('layout'), ['landing', 'home'], true)),

                                Toggle::make('hide_site_nav')
                                    ->label(__('vpress::landing.layouts.hide_site_nav'))
                                    ->helperText(__('vpress::landing.layouts.hide_site_nav_help'))
                                    ->visible(fn (Get $get): bool => in_array($get('layout'), ['landing', 'home'], true)),

                                Select::make('sub_theme')
                                    ->label(__('vpress::admin.fields.sub_theme'))
                                    ->options(fn (?SitePage $record): array => [
                                        '' => __('vpress::admin.fields.sub_theme_inherit'),
                                        ...ThemeBindings::sitePagesSelectOptions($record?->sub_theme),
                                    ])
                                    ->default(null)
                                    ->nullable()
                                    ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->native(false)
                                    ->helperText(__('vpress::admin.helpers.sub_theme_page')),
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
                TextColumn::make('builder')
                    ->label(__('vpress::pro.fields.builder'))
                    ->badge()
                    ->formatStateUsing(fn (PageBuilder|string|null $state): string => $state instanceof PageBuilder
                        ? $state->label()
                        : PageBuilder::tryFrom((string) $state)?->label() ?? (string) $state),
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
