<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Resources\CustomFontResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Throwable;
use Voodflow\Voodbuilder\Filament\Resources\CustomFontResource;
use Voodflow\Voodbuilder\Support\Fonts\CustomFontInstaller;

/**
 * List custom fonts + install a font package (ZIP / single font file).
 */
class ListCustomFonts extends ListRecords
{
    protected static string $resource = CustomFontResource::class;

    protected function getHeaderActions(): array
    {
        $disk = (string) config('voodbuilder.fonts.custom.disk', 'public');
        $directory = trim((string) config('voodbuilder.fonts.custom.directory', 'voodbuilder/fonts'), '/');

        return [
            Action::make('installPackage')
                ->label(__('voodbuilder::admin.custom_fonts.actions.install'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->modalHeading(__('voodbuilder::admin.custom_fonts.actions.install'))
                ->modalDescription(__('voodbuilder::admin.custom_fonts.actions.install_help'))
                ->modalSubmitActionLabel(__('voodbuilder::admin.custom_fonts.actions.install_submit'))
                ->schema([
                    FileUpload::make('archive')
                        ->label(__('voodbuilder::admin.custom_fonts.fields.archive'))
                        ->helperText(__('voodbuilder::admin.custom_fonts.fields.archive_help'))
                        ->disk($disk)
                        ->directory(trim($directory . '/packages', '/'))
                        ->visibility('public')
                        ->storeFiles()
                        ->storeFileNamesIn('archive_names')
                        ->previewable(false)
                        ->acceptedFileTypes([
                            'application/zip',
                            'application/x-zip-compressed',
                            'application/x-zip',
                            'multipart/x-zip',
                            ...CustomFontResource::acceptedFontMimeTypes(),
                        ])
                        ->rules(['extensions:zip,woff2,woff,ttf,otf'])
                        ->maxSize((int) config('voodbuilder.fonts.custom.max_size', 8192))
                        ->required(),
                    TextInput::make('family')
                        ->label(__('voodbuilder::admin.custom_fonts.fields.family'))
                        ->helperText(__('voodbuilder::admin.custom_fonts.fields.family_auto_help'))
                        ->maxLength(120),
                    Select::make('category')
                        ->label(__('voodbuilder::admin.custom_fonts.fields.category'))
                        ->options(fn (): array => CustomFontResource::categoryOptions())
                        ->default('sans-serif')
                        ->native(false)
                        ->required(),
                ])
                ->action(function (array $data) use ($disk): void {
                    $value = $data['archive'] ?? null;
                    $path = is_array($value) ? (string) (reset($value) ?: '') : (string) $value;
                    $path = trim($path);

                    if ($path === '') {
                        Notification::make()
                            ->danger()
                            ->title(__('voodbuilder::admin.custom_fonts.actions.install_failed'))
                            ->body(__('voodbuilder::admin.custom_fonts.actions.no_upload'))
                            ->send();

                        return;
                    }

                    $names = is_array($data['archive_names'] ?? null) ? $data['archive_names'] : [];
                    $originalName = trim((string) ($names[$path] ?? ''));

                    try {
                        $font = app(CustomFontInstaller::class)->installFromPath(
                            Storage::disk($disk)->path($path),
                            filled($data['family'] ?? null) ? (string) $data['family'] : null,
                            (string) ($data['category'] ?? 'sans-serif'),
                            originalName: $originalName !== '' ? $originalName : null,
                        );

                        Notification::make()
                            ->success()
                            ->title(__('voodbuilder::admin.custom_fonts.actions.installed'))
                            ->body($font->family . ' · ' . $font->slug)
                            ->send();
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title(__('voodbuilder::admin.custom_fonts.actions.install_failed'))
                            ->body($exception->getMessage())
                            ->send();
                    } finally {
                        Storage::disk($disk)->delete($path);
                    }
                }),
            CreateAction::make(),
        ];
    }
}
