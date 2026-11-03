<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources;

use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use Voodflow\Voodbuilder\Filament\Resources\CustomFontResource\Pages\CreateCustomFont;
use Voodflow\Voodbuilder\Filament\Resources\CustomFontResource\Pages\EditCustomFont;
use Voodflow\Voodbuilder\Filament\Resources\CustomFontResource\Pages\ListCustomFonts;
use Voodflow\Voodbuilder\Models\CustomFont;
use Voodflow\Voodbuilder\Support\Fonts\CustomFontInstaller;

/**
 * Filament resource: author-installed custom webfonts.
 */
class CustomFontResource extends Resource
{
    protected static ?string $model = CustomFont::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-language';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'voodbuilder/fonts';

    public static function getNavigationGroup(): ?string
    {
        return __('voodbuilder::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('voodbuilder::admin.navigation.custom_fonts');
    }

    public static function getModelLabel(): string
    {
        return __('voodbuilder::admin.custom_fonts.model.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('voodbuilder::admin.custom_fonts.model.plural');
    }

    /**
     * @return array<string, string>
     */
    public static function categoryOptions(): array
    {
        return [
            'sans-serif' => __('voodbuilder::admin.custom_fonts.categories.sans_serif'),
            'serif' => __('voodbuilder::admin.custom_fonts.categories.serif'),
            'monospace' => __('voodbuilder::admin.custom_fonts.categories.monospace'),
            'display' => __('voodbuilder::admin.custom_fonts.categories.display'),
            'handwriting' => __('voodbuilder::admin.custom_fonts.categories.handwriting'),
        ];
    }

    /**
     * @return list<string>
     */
    public static function acceptedFontMimeTypes(): array
    {
        return [
            'font/woff2',
            'font/woff',
            'font/ttf',
            'font/otf',
            'application/font-woff',
            'application/x-font-woff',
            'application/x-font-ttf',
            'application/x-font-otf',
            'application/vnd.ms-opentype',
            'application/font-sfnt',
            // Some browsers/OSes send fonts as a generic binary; the
            // `extensions` rule below still restricts the accepted formats.
            'application/octet-stream',
        ];
    }

    public static function form(Schema $schema): Schema
    {
        $disk = (string) config('voodbuilder.fonts.custom.disk', 'public');
        $directory = trim((string) config('voodbuilder.fonts.custom.directory', 'voodbuilder/fonts'), '/');

        return $schema
            ->columns(1)
            ->components([
                Section::make(__('voodbuilder::admin.custom_fonts.sections.font'))
                    ->schema([
                        TextInput::make('family')
                            ->label(__('voodbuilder::admin.custom_fonts.fields.family'))
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (?string $state, callable $set, ?CustomFont $record): void {
                                if ($record !== null) {
                                    return;
                                }

                                $set('slug', CustomFont::makeSlug((string) $state));
                            }),
                        TextInput::make('slug')
                            ->label(__('voodbuilder::admin.custom_fonts.fields.slug'))
                            ->helperText(__('voodbuilder::admin.custom_fonts.fields.slug_help'))
                            ->required()
                            ->maxLength(120)
                            ->alphaDash()
                            ->unique(ignoreRecord: true),
                        Select::make('category')
                            ->label(__('voodbuilder::admin.custom_fonts.fields.category'))
                            ->options(fn (): array => static::categoryOptions())
                            ->default('sans-serif')
                            ->native(false)
                            ->required(),
                        TextInput::make('stack')
                            ->label(__('voodbuilder::admin.custom_fonts.fields.stack'))
                            ->helperText(__('voodbuilder::admin.custom_fonts.fields.stack_help'))
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label(__('voodbuilder::admin.custom_fonts.fields.active'))
                            ->default(true),
                    ]),
                Section::make(__('voodbuilder::admin.custom_fonts.sections.files'))
                    ->description(__('voodbuilder::admin.custom_fonts.sections.files_help'))
                    ->schema([
                        FileUpload::make('font_files')
                            ->label(__('voodbuilder::admin.custom_fonts.fields.files'))
                            ->multiple()
                            ->storeFileNamesIn('font_file_names')
                            ->disk($disk)
                            ->directory(fn (Get $get): string => trim(
                                $directory . '/' . (Str::slug((string) $get('slug')) ?: 'uploads'),
                                '/',
                            ))
                            ->visibility('public')
                            ->previewable(false)
                            ->acceptedFileTypes(static::acceptedFontMimeTypes())
                            ->rules(['extensions:woff2,woff,ttf,otf'])
                            ->maxSize((int) config('voodbuilder.fonts.custom.max_size', 8192))
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->live()
                            ->afterStateUpdated(function (?array $state, callable $set, callable $get, ?CustomFont $record) use ($disk): void {
                                if ($record !== null || filled($get('family'))) {
                                    return;
                                }

                                $family = static::detectFamilyFromUploads((array) $state, $disk);

                                if ($family === null) {
                                    return;
                                }

                                $set('family', $family);
                                $set('slug', CustomFont::makeSlug($family));
                            })
                            ->helperText(__('voodbuilder::admin.custom_fonts.fields.files_help')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('family')
                    ->label(__('voodbuilder::admin.custom_fonts.fields.family'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label(__('voodbuilder::admin.custom_fonts.fields.slug'))
                    ->toggleable(),
                TextColumn::make('category')
                    ->label(__('voodbuilder::admin.custom_fonts.fields.category'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => static::categoryOptions()[(string) $state] ?? (string) $state),
                TextColumn::make('weights')
                    ->label(__('voodbuilder::admin.custom_fonts.fields.weights'))
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('files')
                    ->label(__('voodbuilder::admin.custom_fonts.fields.files'))
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => (string) count(is_array($state) ? $state : [])),
                IconColumn::make('is_active')
                    ->label(__('voodbuilder::admin.custom_fonts.fields.active'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label(__('voodbuilder::admin.custom_fonts.fields.updated_at'))
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('family')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ]);
    }

    /**
     * Resolve a family name from freshly uploaded (temporary) font files.
     *
     * @param  array<int|string, mixed>  $paths
     */
    public static function detectFamilyFromUploads(array $paths, string $disk): ?string
    {
        $files = [];

        foreach ($paths as $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }

            $absolute = static::resolveStoragePath($path, $disk);

            if ($absolute !== null) {
                $files[] = $absolute;
            }
        }

        return $files === [] ? null : CustomFontInstaller::detectFamilyFromFiles($files);
    }

    /**
     * Locate a file on the upload disk or the Livewire temporary disk.
     */
    public static function resolveStoragePath(string $path, string $disk): ?string
    {
        $candidates = array_filter([
            $disk,
            (string) config('livewire.temporary_file_upload.disk', ''),
            (string) config('filesystems.default', ''),
        ]);

        foreach (array_unique($candidates) as $candidate) {
            try {
                $storage = Storage::disk($candidate);

                if ($storage->exists($path)) {
                    return $storage->path($path);
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Turn FileUpload state into the stored face metadata.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withParsedFaces(array $data): array
    {
        $paths = $data['font_files'] ?? [];
        $paths = is_array($paths) ? array_values($paths) : [$paths];
        $originalNames = is_array($data['font_file_names'] ?? null) ? $data['font_file_names'] : [];

        $faces = CustomFontInstaller::facesFromPaths($paths, $originalNames);
        $family = trim((string) ($data['family'] ?? ''));
        $category = (string) ($data['category'] ?? 'sans-serif');

        $data['files'] = $faces;
        $data['weights'] = CustomFontInstaller::weightsFromFaces($faces);
        $data['slug'] = CustomFont::makeSlug((string) ($data['slug'] ?? $family));
        $data['stack'] = CustomFont::stackFor($family, $category, isset($data['stack']) ? (string) $data['stack'] : null);

        unset($data['font_files'], $data['font_file_names']);

        return $data;
    }

    /**
     * Hydrate the FileUpload with the stored paths and original names.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function withUploadedPaths(array $data): array
    {
        $faces = is_array($data['files'] ?? null) ? $data['files'] : [];
        $paths = [];
        $names = [];

        foreach ($faces as $face) {
            if (! is_array($face)) {
                continue;
            }

            $path = trim((string) ($face['path'] ?? ''));

            if ($path === '') {
                continue;
            }

            $paths[] = $path;
            $names[$path] = (string) ($face['name'] ?? basename($path));
        }

        $data['font_files'] = $paths;
        $data['font_file_names'] = $names;

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomFonts::route('/'),
            'create' => CreateCustomFont::route('/create'),
            'edit' => EditCustomFont::route('/{record}/edit'),
        ];
    }
}
