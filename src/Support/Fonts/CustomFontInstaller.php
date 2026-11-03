<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Fonts;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Voodflow\Voodbuilder\Models\CustomFont;
use ZipArchive;

/**
 * Install author-supplied webfonts from a file, a folder, or a ZIP archive.
 *
 * Faces are detected from the file name (weight/style), copied to the public
 * disk (or the configured custom-font disk) and registered in the database.
 */
final class CustomFontInstaller
{
    /**
     * @var list<string>
     */
    private const EXTENSIONS = ['woff2', 'woff', 'ttf', 'otf'];

    /**
     * @var array<string, int>
     */
    private const WEIGHT_KEYWORDS = [
        'extralight' => 200,
        'ultralight' => 200,
        'semibold' => 600,
        'demibold' => 600,
        'extrabold' => 800,
        'ultrabold' => 800,
        'hairline' => 100,
        'regular' => 400,
        'normal' => 400,
        'medium' => 500,
        'light' => 300,
        'black' => 900,
        'heavy' => 900,
        'bold' => 700,
        'book' => 400,
        'roman' => 400,
        'thin' => 100,
    ];

    /**
     * @var list<string>
     */
    private const FAMILY_NOISE = [
        'webfonts', 'webfont', 'web font', 'desktop', 'font files',
    ];

    public function installFromPath(
        string $source,
        ?string $family = null,
        string $category = 'sans-serif',
        ?string $stack = null,
        bool $active = true,
        ?string $originalName = null,
    ): CustomFont {
        if (is_dir($source)) {
            return $this->installFromFiles($this->fontFilesInDirectory($source), $family, $category, $stack, $active, $originalName);
        }

        if (! is_file($source)) {
            throw new InvalidArgumentException("Font source not found: {$source}");
        }

        if (self::extension($source) === 'zip') {
            return $this->installFromArchive($source, $family, $category, $stack, $active, $originalName);
        }

        return $this->installFromFiles([$source], $family, $category, $stack, $active, $originalName);
    }

    public function installFromArchive(
        string $zipPath,
        ?string $family = null,
        string $category = 'sans-serif',
        ?string $stack = null,
        bool $active = true,
        ?string $originalName = null,
    ): CustomFont {
        if (! is_file($zipPath)) {
            throw new InvalidArgumentException("Font archive not found: {$zipPath}");
        }

        [$directory, $files] = $this->extractArchive($zipPath);

        try {
            return $this->installFromFiles(
                $files,
                $family,
                $category,
                $stack,
                $active,
                $originalName ?? pathinfo($zipPath, PATHINFO_FILENAME),
            );
        } finally {
            foreach ($files as $file) {
                @unlink($file);
            }

            @rmdir($directory);
        }
    }

    /**
     * @param  list<string>  $sourceFiles
     */
    public function installFromFiles(
        array $sourceFiles,
        ?string $family = null,
        string $category = 'sans-serif',
        ?string $stack = null,
        bool $active = true,
        ?string $originalName = null,
    ): CustomFont {
        $sourceFiles = array_values(array_filter(
            $sourceFiles,
            static fn (string $file): bool => is_file($file) && self::isSupported($file),
        ));

        if ($sourceFiles === []) {
            throw new InvalidArgumentException('No WOFF2, WOFF, TTF, or OTF files were found.');
        }

        $family = trim((string) $family);

        if ($family === '') {
            $family = (string) self::detectFamilyFromFiles($sourceFiles, $originalName);
        }

        if ($family === '') {
            $family = 'Custom Font';
        }

        $slug = CustomFont::makeSlug($family);
        $diskName = (string) config('voodbuilder.fonts.custom.disk', 'public');
        $baseDirectory = trim((string) config('voodbuilder.fonts.custom.directory', 'voodbuilder/fonts'), '/');
        $directory = trim($baseDirectory . '/' . $slug, '/');
        $disk = Storage::disk($diskName);

        $faces = $this->copyFaces($sourceFiles, $disk, $directory);

        if ($faces === []) {
            throw new RuntimeException('Could not store the uploaded font files.');
        }

        $weights = self::weightsFromFaces($faces);
        $font = CustomFont::query()->where('slug', $slug)->first() ?? new CustomFont;

        $font->fill([
            'family' => $family,
            'slug' => $slug,
            'category' => $category,
            'stack' => CustomFont::stackFor($family, $category, $stack),
            'weights' => $weights,
            'files' => $faces,
            'is_active' => $active,
        ]);

        $font->save();

        return $font->refresh();
    }

    /**
     * Build face metadata for files already stored on the disk (Filament uploads).
     *
     * @param  list<string>  $paths
     * @param  array<string, string>  $originalNames
     * @return list<array{path: string, name: string, weight: int, style: string, format: string}>
     */
    public static function facesFromPaths(array $paths, array $originalNames = []): array
    {
        $faces = [];

        foreach ($paths as $path) {
            $path = trim((string) $path);

            if ($path === '' || ! self::isSupported($path)) {
                continue;
            }

            $name = trim((string) ($originalNames[$path] ?? ''));
            $name = $name !== '' ? $name : basename($path);

            $faces[] = [
                'path' => $path,
                'name' => $name,
                'weight' => self::detectWeight($name),
                'style' => self::detectStyle($name),
                'format' => CustomFontStylesheet::normalizeFormat(self::extension($path) ?? ''),
            ];
        }

        return self::sortFaces($faces);
    }

    /**
     * @param  list<array{weight: int, style: string, format: string}>  $faces
     * @return list<int>
     */
    public static function weightsFromFaces(array $faces): array
    {
        $weights = array_values(array_unique(array_map(
            static fn (array $face): int => (int) $face['weight'],
            $faces,
        )));

        sort($weights);

        return $weights !== [] ? $weights : [400];
    }

    /**
     * Resolve a family name: embedded font metadata first, then file names.
     *
     * @param  list<string>  $files
     */
    public static function detectFamilyFromFiles(array $files, ?string $sourceName = null): ?string
    {
        foreach ($files as $file) {
            $record = FontNameReader::familyRecord($file);

            if ($record === null) {
                continue;
            }

            // nameID 1/16 are real family names ("Black Han Sans" must stay
            // intact); nameID 4/6 also carry style words that need stripping.
            $family = in_array($record['nameId'], FontNameReader::styleBearingNameIds(), true)
                ? self::detectFamily($record['value'])
                : self::sanitizeFamily($record['value']);

            if ($family !== '') {
                return $family;
            }
        }

        foreach ($files as $file) {
            $family = self::detectFamily(basename($file));

            if ($family !== '') {
                return $family;
            }
        }

        if ($sourceName !== null) {
            $family = self::detectFamily($sourceName);

            if ($family !== '') {
                return $family;
            }
        }

        return null;
    }

    public static function detectFamily(string $name): string
    {
        if (preg_match('/\.(?:woff2?|ttf|otf|eot|zip)$/i', $name) === 1) {
            $name = pathinfo($name, PATHINFO_FILENAME);
        }

        // Split camelCase words so "IBMPlexSans" and "WotfardRegular" read well.
        $name = preg_replace('/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', ' ', $name) ?? $name;
        $name = str_replace(['_', '-'], ' ', $name);
        $name = preg_replace('/\b(?:' . implode('|', self::FAMILY_NOISE) . ')\b/i', ' ', $name) ?? $name;
        $name = preg_replace('/\b(?:' . implode('|', array_keys(self::WEIGHT_KEYWORDS)) . ')\b/i', ' ', $name) ?? $name;
        $name = preg_replace('/\b(?:italic|oblique)\b/i', ' ', $name) ?? $name;
        $name = preg_replace('/\b(?:100|200|300|400|500|600|700|800|900)\b/', ' ', $name) ?? $name;
        $name = trim((string) preg_replace('/\s+/', ' ', $name));

        if ($name === '' || self::looksRandom($name)) {
            return '';
        }

        return ucwords($name);
    }

    /**
     * Keep an embedded family name as-is, dropping only hashed junk.
     */
    private static function sanitizeFamily(string $value): string
    {
        $value = trim((string) preg_replace('/\s+/', ' ', $value));

        return $value === '' || self::looksRandom($value) ? '' : $value;
    }

    /**
     * Uploads are stored under generated names (UUID-like strings). Detect them
     * so a hashed file name never becomes a font family label.
     */
    public static function looksRandom(string $name): bool
    {
        $name = trim($name);

        if ($name === '') {
            return false;
        }

        if (preg_match('/^[0-9a-f]{16,}$/i', $name) === 1) {
            return true;
        }

        $segments = (array) preg_split('/\s+/', $name);

        if (count($segments) < 3) {
            return preg_match('/^[a-z0-9]{24,}$/i', $name) === 1;
        }

        foreach ($segments as $segment) {
            if (preg_match('/[A-Za-z]{4,}/', (string) $segment) === 1) {
                return false;
            }
        }

        return true;
    }

    public static function detectWeight(string $name): int
    {
        $needle = strtolower($name);

        if (preg_match('/\b(100|200|300|400|500|600|700|800|900)\b/', $needle, $matches) === 1) {
            return (int) $matches[1];
        }

        foreach (self::WEIGHT_KEYWORDS as $keyword => $weight) {
            if (str_contains($needle, $keyword)) {
                return $weight;
            }
        }

        return 400;
    }

    public static function detectStyle(string $name): string
    {
        return preg_match('/\b(italic|oblique)\b/i', $name) === 1 ? 'italic' : 'normal';
    }

    public static function extension(string $path): ?string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, [...self::EXTENSIONS, 'zip'], true) ? $extension : null;
    }

    public static function isSupported(string $path): bool
    {
        return in_array(self::extension($path), self::EXTENSIONS, true);
    }

    /**
     * @param  list<string>  $sourceFiles
     * @return list<array{path: string, name: string, weight: int, style: string, format: string}>
     */
    private function copyFaces(array $sourceFiles, Filesystem $disk, string $directory): array
    {
        $staging = sys_get_temp_dir() . '/voodbuilder-fonts-' . Str::random(12);

        if (! is_dir($staging) && ! mkdir($staging, 0777, true) && ! is_dir($staging)) {
            throw new RuntimeException('Could not create a temporary font directory.');
        }

        /** @var list<array{path: string, name: string, weight: int, style: string, format: string, staged: string}> $staged */
        $staged = [];
        $usedNames = [];

        try {
            foreach ($sourceFiles as $source) {
                $originalName = basename($source);
                $extension = self::extension($source) ?? 'woff2';
                $destination = self::destinationName($originalName, $extension, $usedNames);
                $usedNames[$destination] = true;
                $stagedPath = $staging . '/' . $destination;

                if (! copy($source, $stagedPath)) {
                    throw new RuntimeException("Could not read font file: {$source}");
                }

                $staged[] = [
                    'path' => trim($directory . '/' . $destination, '/'),
                    'name' => $originalName,
                    'weight' => self::detectWeight($originalName),
                    'style' => self::detectStyle($originalName),
                    'format' => CustomFontStylesheet::normalizeFormat($extension),
                    'staged' => $stagedPath,
                ];
            }

            $disk->deleteDirectory($directory);

            foreach ($staged as $face) {
                $contents = file_get_contents($face['staged']);

                if (! is_string($contents) || ! $disk->put($face['path'], $contents)) {
                    throw new RuntimeException('Could not write the font file to the configured disk.');
                }
            }

            return self::sortFaces(array_map(
                static fn (array $face): array => [
                    'path' => $face['path'],
                    'name' => $face['name'],
                    'weight' => $face['weight'],
                    'style' => $face['style'],
                    'format' => $face['format'],
                ],
                $staged,
            ));
        } finally {
            foreach ($staged as $face) {
                @unlink($face['staged']);
            }

            @rmdir($staging);
        }
    }

    /**
     * @param  array<string, true>  $usedNames
     */
    private static function destinationName(string $originalName, string $extension, array $usedNames): string
    {
        $base = Str::slug(pathinfo($originalName, PATHINFO_FILENAME));

        if ($base === '') {
            $base = 'font-' . self::detectWeight($originalName) . '-' . self::detectStyle($originalName);
        }

        $name = $base . '.' . $extension;
        $counter = 2;

        while (isset($usedNames[$name])) {
            $name = $base . '-' . $counter . '.' . $extension;
            $counter++;
        }

        return $name;
    }

    /**
     * @return list<string>
     */
    private function fontFilesInDirectory(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && self::isSupported($file->getPathname())) {
                $files[] = $file->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * Extract font entries from a ZIP, flattening folders and ignoring macOS metadata.
     *
     * @return array{0: string, 1: list<string>}
     */
    private function extractArchive(string $zipPath): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required to install a font archive.');
        }

        $zip = new ZipArchive;

        if ($zip->open($zipPath) !== true) {
            throw new RuntimeException("Could not open font archive: {$zipPath}");
        }

        $directory = sys_get_temp_dir() . '/voodbuilder-fonts-' . Str::random(12);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            $zip->close();

            throw new RuntimeException('Could not create a temporary font directory.');
        }

        $files = [];
        $usedNames = [];

        try {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = (string) $zip->getNameIndex($index);
                $base = basename($entry);

                if ($base === '' || str_starts_with($base, '._') || str_contains($entry, '__MACOSX')) {
                    continue;
                }

                if (! self::isSupported($base)) {
                    continue;
                }

                $extension = self::extension($base) ?? 'woff2';
                $name = self::destinationName($base, $extension, $usedNames);
                $usedNames[$name] = true;
                $contents = $zip->getFromIndex($index);

                if (! is_string($contents) || $contents === '') {
                    continue;
                }

                $path = $directory . '/' . $name;

                if (file_put_contents($path, $contents) === false) {
                    continue;
                }

                $files[] = $path;
            }
        } finally {
            $zip->close();
        }

        if ($files === []) {
            @rmdir($directory);

            throw new InvalidArgumentException('The archive does not contain any WOFF2, WOFF, TTF, or OTF files.');
        }

        return [$directory, $files];
    }

    /**
     * @template T of array{weight: int, style: string, format: string}
     *
     * @param  list<T>  $faces
     * @return list<T>
     */
    private static function sortFaces(array $faces): array
    {
        usort($faces, static function (array $a, array $b): int {
            return [$a['weight'], $a['style'], CustomFontStylesheet::formatPriority($a['format'])]
                <=> [$b['weight'], $b['style'], CustomFontStylesheet::formatPriority($b['format'])];
        });

        return $faces;
    }
}
