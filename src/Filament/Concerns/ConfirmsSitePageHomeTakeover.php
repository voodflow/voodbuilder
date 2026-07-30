<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Support\Exceptions\Halt;
use Voodflow\Voodbuilder\Models\SitePage;
use Voodflow\Voodbuilder\Support\SitePageHome;

/**
 * Confirms Site Page Home Takeover trait.
 */
trait ConfirmsSitePageHomeTakeover
{
    protected bool $homeTakeoverConfirmed = false;

    public function confirmHomeTakeoverAction(): Action
    {
        return Action::make('confirmHomeTakeover')
            ->hidden()
            ->modalHeading(__('voodbuilder::admin.home_takeover.heading'))
            ->modalDescription(fn (): string => __('voodbuilder::admin.home_takeover.description', [
                'pages' => SitePageHome::conflictSummary($this->homeTakeoverCandidate()),
            ]))
            ->modalSubmitActionLabel(__('voodbuilder::admin.home_takeover.confirm'))
            ->action(function (): void {
                $this->homeTakeoverConfirmed = true;
                $this->proceedAfterHomeTakeoverConfirmation();
            });
    }

    protected function ensureHomeTakeoverConfirmed(): void
    {
        if ($this->homeTakeoverConfirmed) {
            $this->homeTakeoverConfirmed = false;

            return;
        }

        $data = $this->form->getState();

        if (! ($data['is_home'] ?? false)) {
            return;
        }

        if ($this->homeTakeoverCandidate()->is_home) {
            return;
        }

        if (SitePageHome::conflictingHomes($this->homeTakeoverCandidate())->isEmpty()) {
            return;
        }

        $this->mountAction('confirmHomeTakeover');

        throw new Halt;
    }

    protected function homeTakeoverCandidate(): SitePage
    {
        $data = $this->form->getState();

        if (isset($this->record) && $this->record instanceof SitePage && $this->record->exists) {
            $candidate = $this->record->newInstance($this->record->getAttributes());
            $candidate->exists = true;
            $candidate->forceFill([
                'is_home' => (bool) ($data['is_home'] ?? false),
                'locale' => (string) ($data['locale'] ?? $this->record->locale),
            ]);

            return $candidate;
        }

        return new SitePage($data);
    }

    abstract protected function proceedAfterHomeTakeoverConfirmation(): void;
}
