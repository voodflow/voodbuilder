<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\RichContent\Landing;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Voodflow\Voodbuilder\Filament\Forms\LandingBlockForm;
use Voodflow\Voodbuilder\Filament\Forms\ResolvableLinkForm;
use Voodflow\Voodbuilder\Support\ResolvableLinkSupport;
use Voodflow\Voodbuilder\Support\RichContentBlockPreview;

/**
 * Rich content / landing block: Landing Contact Cta Block.
 */
class LandingContactCtaBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'landing_contact_cta';
    }

    public static function getLabel(): string
    {
        return __('voodbuilder::landing.blocks.contact_cta');
    }

    public static function configureEditorAction(Action $action): Action
    {
        return ResolvableLinkForm::configureAction(
            $action->schema([
                Textarea::make('intro')
                    ->label(__('voodbuilder::landing.contact.intro'))
                    ->rows(4)
                    ->columnSpanFull(),
                TextInput::make('highlight_phrase')
                    ->label(__('voodbuilder::landing.contact.highlight_phrase'))
                    ->maxLength(120),
                Select::make('display_style')
                    ->label(__('voodbuilder::landing.contact.display_style'))
                    ->options([
                        'email' => __('voodbuilder::landing.contact.display_email'),
                        'button' => __('voodbuilder::landing.contact.display_button'),
                    ])
                    ->default('email')
                    ->live(),
                TextInput::make('contact_email')
                    ->label(__('voodbuilder::landing.contact.email'))
                    ->email()
                    ->visible(fn (callable $get): bool => $get('display_style') === 'email'),
                TextInput::make('button_label')
                    ->label(__('voodbuilder::landing.fields.primary_button_label'))
                    ->visible(fn (callable $get): bool => $get('display_style') === 'button'),
                ...ResolvableLinkForm::fields('button', [
                    'type_label' => __('voodbuilder::landing.contact.link_target'),
                    'visible' => fn (Get $get): bool => $get('display_style') === 'button',
                ]),
                ...LandingBlockForm::backgroundFields('dark'),
                ...LandingBlockForm::sectionLayoutFields('bleed', 'large'),
            ]),
            ['button'],
        );
    }

    public static function toPreviewHtml(array $config): string
    {
        return RichContentBlockPreview::render('voodbuilder::blocks.preview-placeholder', [
            'title' => $config['contact_email'] ?? $config['button_label'] ?? static::getLabel(),
        ]);
    }

    public static function toHtml(array $config, array $data): string
    {
        return view('voodbuilder::blocks.landing.contact-cta', [
            'config' => $config,
            'actionUrl' => self::resolveActionUrl($config),
            'actionLabel' => self::resolveActionLabel($config),
        ])->render();
    }

    /** @param  array<string, mixed>  $config */
    public static function resolveActionUrl(array $config): ?string
    {
        if (($config['display_style'] ?? 'email') === 'email') {
            $email = $config['contact_email'] ?? null;

            return filled($email) ? 'mailto:'.(string) $email : null;
        }

        return ResolvableLinkSupport::resolve($config, 'button', 'button_url');
    }

    /** @param  array<string, mixed>  $config */
    public static function resolveActionLabel(array $config): ?string
    {
        if (($config['display_style'] ?? 'email') === 'email') {
            return filled($config['contact_email'] ?? null) ? (string) $config['contact_email'] : null;
        }

        return filled($config['button_label'] ?? null)
            ? (string) $config['button_label']
            : __('voodbuilder::landing.learn_more');
    }
}
