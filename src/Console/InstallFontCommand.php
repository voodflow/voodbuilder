<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Console;

use Illuminate\Console\Command;
use Throwable;
use Voodflow\Voodbuilder\Support\Fonts\CustomFontInstaller;

/**
 * Artisan command: install a custom webfont from a file, folder, or ZIP archive.
 */
class InstallFontCommand extends Command
{
    /**
     * @var list<string>
     */
    private const CATEGORIES = ['sans-serif', 'serif', 'monospace', 'display', 'handwriting'];

    protected $signature = 'voodbuilder:fonts:install
                            {source : Font file, folder, or ZIP archive (absolute, or relative to the project root)}
                            {--family= : Family name (auto-detected from the files when omitted)}
                            {--category=sans-serif : sans-serif, serif, monospace, display, handwriting}
                            {--stack= : Full CSS font stack (a safe fallback is appended when omitted)}
                            {--inactive : Register the font without enabling it in the catalog}';

    protected $description = 'Install a custom font from a WOFF2/WOFF/TTF/OTF file, folder, or ZIP archive';

    public function handle(CustomFontInstaller $installer): int
    {
        $source = trim((string) $this->argument('source'));

        if ($source !== '' && ! str_starts_with($source, '/')) {
            $source = base_path($source);
        }

        if ($source === '' || ! file_exists($source)) {
            $this->components->error("Font source not found: {$source}");

            return self::FAILURE;
        }

        $category = (string) $this->option('category');

        if (! in_array($category, self::CATEGORIES, true)) {
            $this->components->error('Invalid category. Use one of: ' . implode(', ', self::CATEGORIES) . '.');

            return self::FAILURE;
        }

        try {
            $font = $installer->installFromPath(
                $source,
                filled($this->option('family')) ? (string) $this->option('family') : null,
                $category,
                filled($this->option('stack')) ? (string) $this->option('stack') : null,
                ! (bool) $this->option('inactive'),
            );
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Installed font {$font->family} ({$font->slug}).");

        $this->table(['Property', 'Value'], [
            ['Family', $font->family],
            ['Identifier', $font->slug],
            ['Category', $font->category],
            ['Stack', $font->stack],
            ['Weights', implode(', ', $font->resolvedWeights())],
            ['Files', (string) count($font->faces())],
            ['Active', $font->is_active ? 'yes' : 'no'],
            ['Stylesheet', $font->stylesheetUrl() ?? '—'],
        ]);

        return self::SUCCESS;
    }
}
