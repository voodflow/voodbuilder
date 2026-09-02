<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Voodflow\Voodbuilder\Filament\Resources\MediaLibraryResource\Pages\ManageMediaLibrary;
use Voodflow\Voodbuilder\Models\MediaLibrary;
use Voodflow\Voodbuilder\Support\Editor\EditorMediaLibrary;
use Voodflow\Voodbuilder\Support\MediaCompanion;
use Voodflow\Voodbuilder\Support\PublicDiskUrl;

/**
 * Reusable site media (Spatie Media Library), listed as a library — not a wipe-prone FileUpload form.
 */
class MediaLibraryResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'voodbuilder/media-library';

    public static function getNavigationGroup(): ?string
    {
        return __('voodbuilder::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('voodbuilder::admin.navigation.media_library');
    }

    public static function getModelLabel(): string
    {
        return __('voodbuilder::admin.media_library.item_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('voodbuilder::admin.media_library.title');
    }

    /**
     * Also decides whether this appears in the sidebar, via Filament's default
     * shouldRegisterNavigation().
     *
     * The companion check has to live here rather than where the resource is registered.
     * VoodbuilderPlugin::register() used to make this call, but `Vmedia::activate()` runs
     * inside VmediaPlugin::register(), which the host lists after ours — so at that moment
     * the companion always looks absent, and the admin showed two "Media library" entries
     * pointing at different tables. By access time every plugin has registered.
     */
    public static function canAccess(): bool
    {
        if (MediaCompanion::ownsAdminLibrary()) {
            return false;
        }

        return (bool) config('voodbuilder.media_library.enabled', true)
            && (bool) config('voodbuilder.modules.media_library.enabled', true);
    }

    public static function getEloquentQuery(): Builder
    {
        $library = MediaLibrary::current();

        return parent::getEloquentQuery()
            ->where('model_type', $library->getMorphClass())
            ->where('model_id', $library->getKey())
            ->whereIn('collection_name', [
                MediaLibrary::COLLECTION_IMAGES,
                MediaLibrary::COLLECTION_VIDEOS,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                ImageColumn::make('preview')
                    ->label(__('voodbuilder::admin.media_library.preview'))
                    ->height(48)
                    ->width(48)
                    ->square()
                    ->state(function (Media $record): ?string {
                        if (str_starts_with((string) $record->mime_type, 'video/')) {
                            return null;
                        }

                        return PublicDiskUrl::fromPath((string) $record->getPathRelativeToRoot());
                    }),
                TextColumn::make('name')
                    ->label(__('voodbuilder::admin.media_library.name'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Media $record): string => (string) $record->file_name),
                TextColumn::make('collection_name')
                    ->label(__('voodbuilder::admin.media_library.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === MediaLibrary::COLLECTION_VIDEOS
                        ? __('voodbuilder::admin.media_library.videos')
                        : __('voodbuilder::admin.media_library.images'))
                    ->color(fn (string $state): string => $state === MediaLibrary::COLLECTION_VIDEOS ? 'warning' : 'success'),
                TextColumn::make('mime_type')
                    ->label(__('voodbuilder::admin.media_library.mime'))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('human_readable_size')
                    ->label(__('voodbuilder::admin.media_library.size'))
                    ->state(fn (Media $record): string => $record->human_readable_size),
                TextColumn::make('created_at')
                    ->label(__('voodbuilder::admin.media_library.uploaded_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('collection_name')
                    ->label(__('voodbuilder::admin.media_library.type'))
                    ->options([
                        MediaLibrary::COLLECTION_IMAGES => __('voodbuilder::admin.media_library.images'),
                        MediaLibrary::COLLECTION_VIDEOS => __('voodbuilder::admin.media_library.videos'),
                    ]),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('voodbuilder::admin.media_library.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Media $record): string => PublicDiskUrl::fromPath((string) $record->getPathRelativeToRoot()))
                    ->openUrlInNewTab(),
                DeleteAction::make()
                    ->successNotificationTitle(__('voodbuilder::admin.media_library.deleted')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('voodbuilder::admin.media_library.empty_heading'))
            ->emptyStateDescription(__('voodbuilder::admin.media_library.empty_body'))
            ->emptyStateActions([
                static::uploadAction(),
            ]);
    }

    public static function uploadAction(): Action
    {
        $imageMaxKb = (int) config('voodbuilder.editor.upload.max_size', 8192);
        $videoMaxKb = (int) config('voodbuilder.editor.upload.video_max_size', 51200);

        return Action::make('upload')
            ->label(__('voodbuilder::admin.media_library.upload'))
            ->icon('heroicon-o-arrow-up-tray')
            ->schema([
                FileUpload::make('files')
                    ->label(__('voodbuilder::admin.media_library.files'))
                    ->multiple()
                    ->required()
                    ->storeFiles(false)
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'image/webp',
                        'image/svg+xml',
                        'image/avif',
                        'video/mp4',
                        'video/webm',
                        'video/ogg',
                        'video/quicktime',
                        'video/x-m4v',
                    ])
                    ->rules([
                        File::types([
                            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif',
                            'mp4', 'webm', 'ogg', 'mov', 'm4v',
                        ])->max(max($imageMaxKb, $videoMaxKb)),
                    ])
                    ->helperText(__('voodbuilder::admin.media_library.upload_help')),
            ])
            ->action(function (array $data): void {
                $files = $data['files'] ?? [];

                if (! is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $file) {
                    if ($file instanceof TemporaryUploadedFile) {
                        EditorMediaLibrary::store($file);

                        continue;
                    }

                    if (! is_string($file) || $file === '') {
                        continue;
                    }

                    // Fallback if Livewire already stored a temp path on the default disk.
                    $disk = (string) (config('livewire.temporary_file_upload.disk') ?: config('filesystems.default'));
                    $absolute = Storage::disk($disk)->path($file);

                    if (! is_file($absolute)) {
                        continue;
                    }

                    MediaLibrary::current()
                        ->addMedia($absolute)
                        ->usingName(pathinfo($file, PATHINFO_FILENAME) ?: basename($file))
                        ->toMediaCollection(
                            str_starts_with((string) mime_content_type($absolute), 'video/')
                                ? MediaLibrary::COLLECTION_VIDEOS
                                : MediaLibrary::COLLECTION_IMAGES,
                        );
                }
            })
            ->successNotificationTitle(__('voodbuilder::admin.media_library.uploaded'));
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageMediaLibrary::route('/'),
        ];
    }
}
