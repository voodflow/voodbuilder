<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Fonts;

/**
 * Extensible font catalog. Core ships Fontsource (~50). Plugins call register().
 */
final class FontCatalog
{
    /** @var array<string, FontDefinition> */
    private array $fonts = [];

    /** @var array<string, callable(FontDefinition): void> */
    private array $loaders = [];

    private bool $coreBooted = false;

    public function bootCore(): void
    {
        if ($this->coreBooted) {
            return;
        }

        $this->coreBooted = true;

        $path = dirname(__DIR__, 3).'/resources/fonts/core-catalog.json';

        if (! is_file($path)) {
            return;
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        if (! is_array($decoded)) {
            return;
        }

        foreach ($decoded as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            try {
                $this->register(FontDefinition::fromArray($entry));
            } catch (\InvalidArgumentException) {
                continue;
            }
        }
    }

    public function register(FontDefinition $font): void
    {
        $this->fonts[$font->id] = $font;
    }

    /**
     * @param  list<array<string, mixed>|FontDefinition>  $fonts
     */
    public function registerMany(array $fonts): void
    {
        foreach ($fonts as $font) {
            if ($font instanceof FontDefinition) {
                $this->register($font);

                continue;
            }

            if (is_array($font)) {
                $this->register(FontDefinition::fromArray($font));
            }
        }
    }

    /**
     * Optional custom loader hook for non-fontsource providers (e.g. Bunny plugin).
     *
     * @param  callable(FontDefinition): void  $loader
     */
    public function registerProviderLoader(string $provider, callable $loader): void
    {
        $this->loaders[trim($provider)] = $loader;
    }

    public function providerLoader(string $provider): ?callable
    {
        return $this->loaders[trim($provider)] ?? null;
    }

    public function get(string $id): ?FontDefinition
    {
        return $this->fonts[$id] ?? null;
    }

    public function findByStack(string $stack): ?FontDefinition
    {
        $needle = trim($stack);

        foreach ($this->fonts as $font) {
            if ($font->stack === $needle || $font->family === $needle) {
                return $font;
            }
        }

        return null;
    }

    /**
     * @return list<FontDefinition>
     */
    public function all(): array
    {
        $fonts = array_values($this->fonts);

        usort($fonts, static fn (FontDefinition $a, FontDefinition $b): int => strcasecmp($a->family, $b->family));

        return $fonts;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toEditorPayload(): array
    {
        return array_map(
            static fn (FontDefinition $font): array => $font->toArray(),
            $this->all(),
        );
    }

    /**
     * @return list<array{id: string, label: string}>
     */
    public function styleManagerOptions(): array
    {
        $options = [
            ['id' => 'Arial, Helvetica, sans-serif', 'label' => 'Arial'],
            ['id' => 'Georgia, serif', 'label' => 'Georgia'],
            ['id' => "'Courier New', Courier, monospace", 'label' => 'Courier New'],
            ['id' => 'system-ui, sans-serif', 'label' => 'System UI'],
        ];

        foreach ($this->all() as $font) {
            $options[] = $font->toStyleManagerOption();
        }

        return $options;
    }

    /**
     * Detect catalog font ids referenced in CSS / HTML.
     *
     * @return list<string>
     */
    public function detectUsedIds(string $cssOrHtml): array
    {
        $used = [];

        foreach ($this->fonts as $font) {
            $family = preg_quote($font->family, '/');

            if (preg_match('/font-family\s*:\s*[^;]*'.$family.'/i', $cssOrHtml) === 1) {
                $used[] = $font->id;
            }
        }

        return array_values(array_unique($used));
    }

    /**
     * Required npm packages for Fontsource core (+ already registered fontsource fonts).
     *
     * @return array<string, string>
     */
    public function requiredFontsourcePackages(string $version = '^5.2.8'): array
    {
        $packages = [];

        foreach ($this->fonts as $font) {
            if ($font->provider !== 'fontsource' || $font->package === null) {
                continue;
            }

            $packages[$font->package] = $version;
        }

        ksort($packages);

        return $packages;
    }
}
