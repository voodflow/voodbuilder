<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View as SchemaView;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Throwable;
use Voodflow\Voodbuilder\Models\MediaLibrary;

/**
 * Admin media library powered by Filament Spatie Media Library File Upload.
 *
 * Assets are reusable across the Editor AssetManager (frontend picker).
 *
 * @property-read Schema $form
 */
class MediaLibraryPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'voodbuilder/media-library';

    public ?MediaLibrary $record = null;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('voodbuilder::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('voodbuilder::admin.navigation.media_library');
    }

    public static function canAccess(): bool
    {
        return (bool) config('voodbuilder.media_library.enabled', true)
            && (bool) config('voodbuilder.modules.media_library.enabled', true)
            && class_exists(SpatieMediaLibraryFileUpload::class);
    }

    public function mount(): void
    {
        $this->record = MediaLibrary::current();
        $this->form->fill();
    }

    public function getRecord(): ?Model
    {
        return $this->record;
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $this->form->getState();
            $this->form->model($this->record)->saveRelationships();

            $this->commitDatabaseTransaction();

            Notification::make()
                ->title(__('voodbuilder::admin.media_library.saved'))
                ->success()
                ->send();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->record)
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $imageMaxKb = (int) config('voodbuilder.editor.upload.max_size', 8192);
        $videoMaxKb = (int) config('voodbuilder.editor.upload.video_max_size', 51200);
        $disk = (string) config('voodbuilder.editor.upload.disk', 'public');

        return $schema
            ->components([
                SchemaView::make('voodbuilder::filament.media-library-intro'),
                Section::make(__('voodbuilder::admin.media_library.images'))
                    ->description(__('voodbuilder::admin.media_library.images_help'))
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->label(__('voodbuilder::admin.media_library.images'))
                            ->collection(MediaLibrary::COLLECTION_IMAGES)
                            ->disk($disk)
                            ->multiple()
                            ->reorderable()
                            ->image()
                            ->visibility('public')
                            ->maxSize($imageMaxKb)
                            ->panelLayout('grid')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
                Section::make(__('voodbuilder::admin.media_library.videos'))
                    ->description(__('voodbuilder::admin.media_library.videos_help'))
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('videos')
                            ->label(__('voodbuilder::admin.media_library.videos'))
                            ->collection(MediaLibrary::COLLECTION_VIDEOS)
                            ->disk($disk)
                            ->multiple()
                            ->reorderable()
                            ->acceptedFileTypes([
                                'video/mp4',
                                'video/webm',
                                'video/ogg',
                                'video/quicktime',
                                'video/x-m4v',
                            ])
                            ->visibility('public')
                            ->maxSize($videoMaxKb)
                            ->panelLayout('grid')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getFormContentComponent(),
        ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('media-library-form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label(__('voodbuilder::admin.media_library.save'))
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                ]),
            ]);
    }

    public function getTitle(): string|Htmlable
    {
        return __('voodbuilder::admin.media_library.title');
    }
}
