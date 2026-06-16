<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Filament\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\CanUseDatabaseTransactions;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Throwable;
use Voodflow\Vpress\Contracts\PublicContentChannel;
use Voodflow\Vpress\Models\VpressSettings;
use Voodflow\Vpress\Support\ContentChannelRegistry;
use Voodflow\Vpress\Support\ContentChannelThemes;
use Voodflow\Vpress\Support\SubThemeRegistry;

/**
 * @property-read Schema $form
 */
class VpressContentChannelsPage extends Page
{
    use CanUseDatabaseTransactions;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'vpress/content-channels';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('vpress::admin.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return __('vpress::admin.navigation.content_channels');
    }

    public function getTitle(): string|Htmlable
    {
        return __('vpress::content_channels.page_title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'content_channel_sub_themes' => VpressSettings::get('content_channel_sub_themes', []),
        ]);
    }

    public function save(): void
    {
        try {
            $this->beginDatabaseTransaction();

            $data = $this->form->getState();

            VpressSettings::saveData([
                'content_channel_sub_themes' => $data['content_channel_sub_themes'] ?? [],
            ]);

            $this->commitDatabaseTransaction();

            Notification::make()
                ->title(__('vpress::content_channels.saved'))
                ->success()
                ->send();
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction() ?
                $this->rollBackDatabaseTransaction() :
                $this->commitDatabaseTransaction();
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $channels = app(ContentChannelRegistry::class)->all();

        if ($channels === []) {
            return $schema->components([
                Placeholder::make('no_channels')
                    ->hiddenLabel()
                    ->content(new HtmlString(__('vpress::content_channels.empty'))),
            ]);
        }

        $sections = [];

        foreach ($channels as $channel) {
            $sections[] = $this->channelSection($channel);
        }

        return $schema->components([
            Placeholder::make('intro')
                ->hiddenLabel()
                ->content(new HtmlString(__('vpress::content_channels.intro'))),
            ...$sections,
        ]);
    }

    protected function channelSection(PublicContentChannel $channel): Section
    {
        $channelId = $channel->id();
        $registry = app(SubThemeRegistry::class);
        $codeDefault = ContentChannelThemes::configuredDefaultFor($channelId);
        $defaultLabel = filled($codeDefault) && $registry->exists((string) $codeDefault)
            ? $registry->label((string) $codeDefault)
            : __('vpress::content_channels.no_default_theme');

        $routePatterns = implode(', ', $channel->routePatterns());

        return Section::make($channel->label())
            ->description($routePatterns !== '' ? $routePatterns : null)
            ->schema([
                Placeholder::make("channel_{$channelId}_default")
                    ->label(__('vpress::content_channels.package_default'))
                    ->content($defaultLabel),
                Select::make("content_channel_sub_themes.{$channelId}")
                    ->label(__('vpress::content_channels.visual_theme'))
                    ->options(fn (): array => ContentChannelThemes::selectOptions())
                    ->native(false)
                    ->helperText(__('vpress::content_channels.override_help')),
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
            ->id('vpress.content-channels-form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make([
                    Action::make('save')
                        ->label(__('vpress::content_channels.save'))
                        ->submit('save')
                        ->keyBindings(['mod+s']),
                ]),
            ]);
    }
}
