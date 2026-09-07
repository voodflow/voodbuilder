<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Editor;

/**
 * Editor block defaults and event-scoping metadata registered by companions.
 */
final class EditorBlockConfigRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $defaults = [];

    /** @var array<string, true> */
    private array $requiresEventId = [];

    /** @var (callable(): ?int)|null */
    private $publishedEventIdResolver = null;

    /**
     * @param  array{
     *     defaults?: array<string, mixed>,
     *     requires_event_id?: bool,
     * }  $options
     */
    public function register(string $blockId, array $options = []): void
    {
        if (isset($options['defaults']) && is_array($options['defaults'])) {
            $this->defaults[$blockId] = $options['defaults'];
        }

        if (($options['requires_event_id'] ?? false) === true) {
            $this->requiresEventId[$blockId] = true;
        }
    }

    public function registerRequiresEventId(string ...$blockIds): void
    {
        foreach ($blockIds as $blockId) {
            $this->requiresEventId[$blockId] = true;
        }
    }

    public function registerPublishedEventIdResolver(callable $resolver): void
    {
        $this->publishedEventIdResolver = $resolver;
    }

    public function requiresEventId(string $blockId): bool
    {
        return isset($this->requiresEventId[$blockId]);
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultsFor(string $blockId): array
    {
        return $this->defaults[$blockId] ?? [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function allDefaults(): array
    {
        return $this->defaults;
    }

    public function resolvePublishedEventId(): ?int
    {
        if ($this->publishedEventIdResolver === null) {
            return null;
        }

        $id = ($this->publishedEventIdResolver)();

        return is_numeric($id) ? (int) $id : null;
    }
}
