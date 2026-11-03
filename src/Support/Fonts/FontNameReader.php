<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\Fonts;

/**
 * Minimal sfnt reader that extracts the family name from a font's `name` table.
 *
 * Supports TrueType/OpenType (TTF/OTF) and WOFF. WOFF2 stores its tables as
 * Brotli-compressed transformed data, so it is intentionally skipped and
 * callers fall back to the file name.
 */
final class FontNameReader
{
    /**
     * `name` table IDs, in order of preference: typographic family, family,
     * full name, then PostScript name. Some free/webfont builds leave the
     * family records empty, so the last two keep detection reliable.
     *
     * @var list<int>
     */
    private const FAMILY_NAME_IDS = [16, 1, 4, 6];

    private const MAX_TABLE_BYTES = 1048576;

    /**
     * Read the family name embedded in the font file.
     */
    public static function family(string $path): ?string
    {
        return self::familyRecord($path)['value'] ?? null;
    }

    /**
     * Read the best family candidate together with the `name` table ID it came
     * from, so callers know whether style words were part of the value.
     *
     * @return array{value: string, nameId: int}|null
     */
    public static function familyRecord(string $path): ?array
    {
        $table = self::readNameTable($path);

        if ($table === null) {
            return null;
        }

        $records = self::parseNameTable($table);

        foreach (self::FAMILY_NAME_IDS as $nameId) {
            foreach ($records[$nameId] ?? [] as $value) {
                $family = self::clean($value);

                if ($family !== null) {
                    return ['value' => $family, 'nameId' => $nameId];
                }
            }
        }

        return null;
    }

    /**
     * Name IDs that carry a style word (e.g. "Wotfard Regular Webfont") and
     * therefore still need cleaning by the installer.
     *
     * @return list<int>
     */
    public static function styleBearingNameIds(): array
    {
        return [4, 6];
    }

    private static function readNameTable(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        try {
            $tag = (string) fread($handle, 4);

            return match ($tag) {
                'wOFF' => self::readWoffTable($handle),
                'wOF2', 'ttcf' => null,
                'OTTO', "\x00\x01\x00\x00", 'true', 'typ1' => self::readSfntTable($handle),
                default => null,
            };
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  resource  $handle  Positioned right after the 4-byte sfnt version.
     */
    private static function readSfntTable($handle): ?string
    {
        $header = (string) fread($handle, 8);

        if (strlen($header) < 8) {
            return null;
        }

        $count = self::u16($header, 0);
        $records = (string) fread($handle, $count * 16);

        if (strlen($records) < $count * 16) {
            return null;
        }

        for ($index = 0; $index < $count; $index++) {
            $offset = $index * 16;

            if (substr($records, $offset, 4) !== 'name') {
                continue;
            }

            return self::readAt($handle, self::u32($records, $offset + 8), self::u32($records, $offset + 12));
        }

        return null;
    }

    /**
     * @param  resource  $handle  Positioned right after the 4-byte wOFF signature.
     */
    private static function readWoffTable($handle): ?string
    {
        $header = (string) fread($handle, 40);

        if (strlen($header) < 40) {
            return null;
        }

        // Absolute header offset 12 holds numTables; the header is 44 bytes long.
        $count = self::u16($header, 8);
        $records = (string) fread($handle, $count * 20);

        if (strlen($records) < $count * 20) {
            return null;
        }

        for ($index = 0; $index < $count; $index++) {
            $offset = $index * 20;

            if (substr($records, $offset, 4) !== 'name') {
                continue;
            }

            $compressedLength = self::u32($records, $offset + 8);
            $originalLength = self::u32($records, $offset + 12);

            if ($compressedLength > self::MAX_TABLE_BYTES || $originalLength > self::MAX_TABLE_BYTES) {
                return null;
            }

            $contents = self::readAt($handle, self::u32($records, $offset + 4), $compressedLength);

            if ($contents === null) {
                return null;
            }

            if ($compressedLength < $originalLength) {
                $inflated = @gzuncompress($contents);

                return is_string($inflated) ? $inflated : null;
            }

            return $contents;
        }

        return null;
    }

    /**
     * @param  resource  $handle
     */
    private static function readAt($handle, int $offset, int $length): ?string
    {
        if ($length <= 0 || $length > self::MAX_TABLE_BYTES) {
            return null;
        }

        if (fseek($handle, $offset) !== 0) {
            return null;
        }

        $contents = (string) fread($handle, $length);

        return strlen($contents) === $length ? $contents : null;
    }

    /**
     * @return array<int, list<string>> Values keyed by name ID, best candidate first.
     */
    private static function parseNameTable(string $table): array
    {
        $length = strlen($table);

        if ($length < 6) {
            return [];
        }

        $count = self::u16($table, 2);
        $storage = self::u16($table, 4);

        /** @var array<int, list<array{priority: int, value: string}>> $candidates */
        $candidates = [];

        for ($index = 0; $index < $count; $index++) {
            $offset = 6 + ($index * 12);

            if ($offset + 12 > $length) {
                break;
            }

            $platform = self::u16($table, $offset);
            $encoding = self::u16($table, $offset + 2);
            $language = self::u16($table, $offset + 4);
            $nameId = self::u16($table, $offset + 6);
            $size = self::u16($table, $offset + 8);
            $start = self::u16($table, $offset + 10);

            if (! in_array($nameId, self::FAMILY_NAME_IDS, true) || $size === 0) {
                continue;
            }

            $raw = substr($table, $storage + $start, $size);

            if (strlen($raw) !== $size) {
                continue;
            }

            $value = self::decode($raw, $platform, $encoding);

            if ($value === null) {
                continue;
            }

            $candidates[$nameId][] = [
                'priority' => self::priority($platform, $language),
                'value' => $value,
            ];
        }

        $records = [];

        foreach ($candidates as $nameId => $entries) {
            usort($entries, static fn (array $a, array $b): int => $a['priority'] <=> $b['priority']);
            $records[$nameId] = array_column($entries, 'value');
        }

        return $records;
    }

    private static function decode(string $raw, int $platform, int $encoding): ?string
    {
        // Windows (3) and Unicode (0) platforms store UTF-16BE strings.
        if ($platform === 3 || $platform === 0) {
            return strlen($raw) % 2 === 0
                ? mb_convert_encoding($raw, 'UTF-8', 'UTF-16BE')
                : null;
        }

        // Macintosh (1) strings are treated as single-byte Latin text.
        if ($platform === 1) {
            return mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
        }

        return null;
    }

    private static function priority(int $platform, int $language): int
    {
        return match (true) {
            $platform === 3 && $language === 0x0409 => 0,
            $platform === 3 => 1,
            $platform === 0 => 2,
            $platform === 1 && $language === 0 => 3,
            default => 4,
        };
    }

    /**
     * Reject binary noise that occasionally slips out of broken name tables.
     */
    private static function clean(string $value): ?string
    {
        $value = (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);
        $value = trim($value);

        if ($value === '' || mb_strlen($value) > 120) {
            return null;
        }

        // A readable family name has at least a couple of letters; junk
        // records like ".\x7f" or " " are dropped here.
        if (preg_match_all('/\p{L}/u', $value) < 2) {
            return null;
        }

        if (preg_match('/[\x{FFFD}\x{0080}-\x{009F}]/u', $value) === 1) {
            return null;
        }

        return $value;
    }

    private static function u16(string $bytes, int $offset): int
    {
        return strlen($bytes) >= $offset + 2
            ? (unpack('n', substr($bytes, $offset, 2))[1] ?? 0)
            : 0;
    }

    private static function u32(string $bytes, int $offset): int
    {
        return strlen($bytes) >= $offset + 4
            ? (unpack('N', substr($bytes, $offset, 4))[1] ?? 0)
            : 0;
    }
}
