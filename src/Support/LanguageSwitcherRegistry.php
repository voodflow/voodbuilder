<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support;

use Closure;

/**
 * Switcher registry: lets a companion package replace the header language switcher.
 *
 * VoodBuilder ships its own page-translation switcher ({@see SiteLanguageSwitcher});
 * a package that owns richer locale routing (tutorials, docs, events) registers its
 * own view here and wins. VoodBuilder never names that package.
 */
final class LanguageSwitcherRegistry
{
    /**
     * @var array<int, array{id: string, view: string, visible: (Closure(): bool)|null, order: int}>
     */
    private array $providers = [];

    public function register(string $id, string $view, ?callable $visible = null, int $order = 0): self
    {
        $this->providers = array_values(array_filter(
            $this->providers,
            static fn (array $provider): bool => $provider['id'] !== $id,
        ));

        $this->providers[] = [
            'id' => $id,
            'view' => $view,
            'visible' => $visible !== null ? Closure::fromCallable($visible) : null,
            'order' => $order,
        ];

        return $this;
    }

    public function forget(string $id): self
    {
        $this->providers = array_values(array_filter(
            $this->providers,
            static fn (array $provider): bool => $provider['id'] !== $id,
        ));

        return $this;
    }

    public function reset(): void
    {
        $this->providers = [];
    }

    /**
     * View of the highest-priority visible provider, or null to use the built-in one.
     */
    public function activeView(): ?string
    {
        $visible = array_values(array_filter(
            $this->providers,
            static function (array $provider): bool {
                if ($provider['visible'] === null) {
                    return true;
                }

                try {
                    return (bool) ($provider['visible'])();
                } catch (\Throwable) {
                    return false;
                }
            },
        ));

        if ($visible === []) {
            return null;
        }

        usort($visible, static fn (array $a, array $b): int => $b['order'] <=> $a['order']);

        return $visible[0]['view'];
    }

    public function hasVisibleProvider(): bool
    {
        return $this->activeView() !== null;
    }

    public function isVisible(): bool
    {
        return $this->hasVisibleProvider() || SiteLanguageSwitcher::visible();
    }
}
