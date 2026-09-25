<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Layout Integration typography becomes an override of site Settings typography:
 * the previous save path stored 'inter' and the full default type scale even when
 * nothing was chosen, which froze companion pages off the site fonts.
 */
return new class extends Migration
{
    private const TABLE = 'voodbuilder_chrome_layouts';

    /** @var array<string, array{size: string, weight: string, leading: string}> */
    private const LEGACY_DEFAULT_TYPE_SCALE = [
        'h1' => ['size' => '3xl', 'weight' => '600', 'leading' => '1.25'],
        'h2' => ['size' => '2xl', 'weight' => '600', 'leading' => '1.375'],
        'h3' => ['size' => 'xl', 'weight' => '600', 'leading' => '1.375'],
        'h4' => ['size' => 'lg', 'weight' => '600', 'leading' => '1.375'],
        'p' => ['size' => 'base', 'weight' => '400', 'leading' => '1.625'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            if (! Schema::hasColumn(self::TABLE, 'reading_heading_font')) {
                $table->string('reading_heading_font', 120)->nullable()->after('reading_font');
            }
        });

        $hasBodySize = Schema::hasColumn(self::TABLE, 'reading_font_size');

        DB::table(self::TABLE)->orderBy('id')->each(function (object $layout) use ($hasBodySize): void {
            $font = trim((string) ($layout->reading_font ?? ''));
            $scale = $this->inheritUntouchedScale($layout->reading_type_scale ?? null);
            $bodySize = $hasBodySize ? trim((string) ($layout->reading_font_size ?? '')) : '';

            if ($bodySize !== '' && $bodySize !== 'base' && ($scale['p']['size'] ?? null) === null) {
                $scale['p']['size'] = $bodySize;
            }

            DB::table(self::TABLE)->where('id', $layout->id)->update([
                'reading_font' => $font === '' || $font === 'inter' ? null : $font,
                'reading_type_scale' => $this->hasOverrides($scale) ? json_encode($scale) : null,
            ]);
        });

        Schema::table(self::TABLE, function (Blueprint $table): void {
            foreach (['reading_font_size', 'reading_sidebar_font', 'reading_sidebar_type_scale'] as $column) {
                if (Schema::hasColumn(self::TABLE, $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLE)) {
            return;
        }

        Schema::table(self::TABLE, function (Blueprint $table): void {
            if (! Schema::hasColumn(self::TABLE, 'reading_font_size')) {
                $table->string('reading_font_size', 16)->nullable()->after('reading_font');
            }

            if (! Schema::hasColumn(self::TABLE, 'reading_sidebar_font')) {
                $table->string('reading_sidebar_font', 120)->nullable()->after('reading_font_size');
            }

            if (! Schema::hasColumn(self::TABLE, 'reading_sidebar_type_scale')) {
                $table->json('reading_sidebar_type_scale')->nullable();
            }

            if (Schema::hasColumn(self::TABLE, 'reading_heading_font')) {
                $table->dropColumn('reading_heading_font');
            }
        });
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function inheritUntouchedScale(mixed $raw): array
    {
        $stored = is_string($raw) ? json_decode($raw, true) : $raw;
        $stored = is_array($stored) ? $stored : [];
        $scale = [];

        foreach (self::LEGACY_DEFAULT_TYPE_SCALE as $element => $defaults) {
            $row = is_array($stored[$element] ?? null) ? $stored[$element] : [];
            $scale[$element] = ['size' => null, 'sizeMd' => null, 'sizeLg' => null, 'weight' => null, 'leading' => null];

            foreach ($defaults as $prop => $default) {
                $value = isset($row[$prop]) ? trim((string) $row[$prop]) : '';

                if ($value !== '' && $value !== $default) {
                    $scale[$element][$prop] = $value;
                }
            }
        }

        return $scale;
    }

    /**
     * @param  array<string, array<string, string|null>>  $scale
     */
    private function hasOverrides(array $scale): bool
    {
        foreach ($scale as $row) {
            foreach ($row as $value) {
                if ($value !== null) {
                    return true;
                }
            }
        }

        return false;
    }
};
