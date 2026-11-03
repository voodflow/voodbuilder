<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Voodflow\Voodbuilder\Filament\Resources\CustomFontResource;
use Voodflow\Voodbuilder\Models\CustomFont;
use Voodflow\Voodbuilder\Support\Fonts\CustomFontInstaller;
use Voodflow\Voodbuilder\Support\Fonts\CustomFontStylesheet;
use Voodflow\Voodbuilder\Support\Fonts\FontStylesheets;
use Voodflow\Voodbuilder\Tests\TestCase;
use Voodflow\Voodbuilder\Voodbuilder;
use ZipArchive;

class CustomFontTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        Storage::disk('public')->deleteDirectory('voodbuilder/fonts');

        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        $this->tempFiles = [];

        parent::tearDown();
    }

    public function test_install_from_single_file_registers_a_custom_font(): void
    {
        $path = $this->makeFontFile('Wotfard-Regular.woff2');

        $font = app(CustomFontInstaller::class)->installFromPath($path, 'Wotfard');

        $this->assertInstanceOf(CustomFont::class, $font);
        $this->assertSame('wotfard', $font->slug);
        $this->assertSame([400], $font->resolvedWeights());

        $disk = Storage::disk('public');
        $this->assertTrue($disk->exists('voodbuilder/fonts/wotfard/font.css'));

        $css = (string) $disk->get('voodbuilder/fonts/wotfard/font.css');
        $this->assertStringContainsString('@font-face', $css);
        $this->assertStringContainsString("font-family: 'Wotfard';", $css);
        $this->assertStringContainsString("format('woff2')", $css);

        $entry = Voodbuilder::fonts()->get('wotfard');

        $this->assertNotNull($entry);
        $this->assertSame('custom', $entry->provider);
        $this->assertSame('Wotfard', $entry->family);
        $this->assertSame([400], $entry->weights);
        $this->assertSame($font->stylesheetUrl(), $entry->meta['css_url']);
    }

    public function test_weight_and_style_are_detected_from_file_names(): void
    {
        $regular = $this->makeFontFile('Acme-Regular.woff2');
        $bold = $this->makeFontFile('Acme-Bold-700.woff2');
        $italic = $this->makeFontFile('Acme-Italic.woff2');

        $font = app(CustomFontInstaller::class)->installFromFiles([$regular, $bold, $italic], 'Acme');

        $this->assertSame([400, 700], $font->resolvedWeights());

        $faces = collect($font->faces());
        $this->assertSame(3, $faces->count());
        $this->assertTrue($faces->contains(fn (array $face): bool => $face['weight'] === 700 && $face['style'] === 'normal'));
        $this->assertTrue($faces->contains(fn (array $face): bool => $face['weight'] === 400 && $face['style'] === 'italic'));

        $css = (string) Storage::disk('public')->get($font->stylesheetPath());
        $this->assertStringContainsString('font-weight: 700;', $css);
        $this->assertStringContainsString('font-style: italic;', $css);
    }

    public function test_install_from_zip_archive_extracts_webfonts(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The zip extension is not available.');
        }

        $zipPath = $this->makeZipArchive([
            'Wotfard_Regular/Wotfard_Regular_Webfont/wotfard-regular-webfont.woff2' => 'FAKE-WOFF2',
            'Wotfard_Regular/Wotfard_Regular_Webfont/wotfard-regular-webfont.ttf' => 'FAKE-TTF',
            'Wotfard_Regular/Wotfard_Regular_Desktop/Wotfard_desktop_license.pdf' => 'LICENSE',
            '__MACOSX/Wotfard_Regular/._wotfard-regular-webfont.woff2' => 'JUNK',
        ]);

        $font = app(CustomFontInstaller::class)->installFromPath($zipPath, 'Wotfard');

        $this->assertSame('wotfard', $font->slug);
        $this->assertSame([400], $font->resolvedWeights());
        $this->assertCount(2, $font->faces());

        $formats = array_column($font->faces(), 'format');
        $this->assertContains('woff2', $formats);
        $this->assertContains('truetype', $formats);
    }

    public function test_family_name_is_detected_from_the_file_name(): void
    {
        $path = $this->makeFontFile('wotfard-regular-webfont.woff2');

        $font = app(CustomFontInstaller::class)->installFromPath($path);

        $this->assertSame('Wotfard', $font->family);
        $this->assertSame('wotfard', $font->slug);
    }

    public function test_family_name_is_read_from_the_font_metadata(): void
    {
        $path = $this->makeTrueTypeFile('acme-grotesk-regular.ttf', [1 => 'Wotfard', 16 => 'Wotfard']);

        $font = app(CustomFontInstaller::class)->installFromPath($path);

        $this->assertSame('Wotfard', $font->family);
        $this->assertSame('wotfard', $font->slug);
    }

    public function test_style_words_in_a_family_name_are_kept(): void
    {
        $path = $this->makeTrueTypeFile('acme-regular.ttf', [1 => 'Black Han Sans']);

        $font = app(CustomFontInstaller::class)->installFromPath($path);

        $this->assertSame('Black Han Sans', $font->family);
    }

    public function test_broken_family_records_fall_back_to_the_full_name(): void
    {
        // Webfont builds often leave nameID 1/16 empty or garbled.
        $path = $this->makeTrueTypeFile('acme-regular.ttf', [1 => '.', 4 => 'Wotfard Regular Webfont']);

        $font = app(CustomFontInstaller::class)->installFromPath($path);

        $this->assertSame('Wotfard', $font->family);
    }

    public function test_family_name_is_read_from_woff_metadata(): void
    {
        $path = $this->makeWoffFile('acme-grotesk-regular.woff', [1 => 'Wotfard Extra Grotesk Display Super Family']);

        $font = app(CustomFontInstaller::class)->installFromPath($path);

        $this->assertSame('Wotfard Extra Grotesk Display Super Family', $font->family);
    }

    public function test_hashed_upload_names_fall_back_to_the_font_files(): void
    {
        if (! class_exists(ZipArchive::class)) {
            $this->markTestSkipped('The zip extension is not available.');
        }

        $zipPath = $this->makeZipArchive([
            'wotfard-regular-webfont.woff2' => 'FAKE-WOFF2',
            'wotfard-regular.otf' => 'FAKE-OTF',
        ], '4bf-l-qa-lh-zcb-a-l-ki8-m-r-vx-p9c-td5-qw-b5ilh-oh-a-x-rh1.zip');

        $font = app(CustomFontInstaller::class)->installFromPath($zipPath);

        $this->assertSame('Wotfard', $font->family);
        $this->assertSame('wotfard', $font->slug);
    }

    public function test_hashed_names_are_never_used_as_a_family_name(): void
    {
        $this->assertSame('', CustomFontInstaller::detectFamily('4bf-l-qa-lh-zcb-a-l-ki8-m-r-vx-p9c-td5-qw-b5ilh-oh-a-x-rh1'));
        $this->assertSame('', CustomFontInstaller::detectFamily('a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0'));
        $this->assertSame('IBM Plex Sans', CustomFontInstaller::detectFamily('IBMPlexSans-Bold'));
    }

    public function test_family_is_detected_from_uploaded_paths(): void
    {
        $source = $this->makeTrueTypeFile('acme-grotesk-regular.ttf', [1 => 'Wotfard']);
        $path = 'voodbuilder/fonts/tmp/wotfard-regular.ttf';

        Storage::disk('public')->put($path, (string) file_get_contents($source));

        $this->assertSame(
            'Wotfard',
            CustomFontResource::detectFamilyFromUploads([$path], 'public'),
        );
    }

    public function test_stylesheets_include_custom_font_css_url(): void
    {
        $path = $this->makeFontFile('Wotfard-Regular.woff2');
        $font = app(CustomFontInstaller::class)->installFromPath($path, 'Wotfard');

        $urls = FontStylesheets::urlsFor(['wotfard']);

        $this->assertContains($font->stylesheetUrl(), $urls);
        $this->assertSame([], FontStylesheets::urlsFor(['not-installed']));
    }

    public function test_inactive_font_is_not_registered_in_the_catalog(): void
    {
        $path = $this->makeFontFile('Dormant-Regular.woff2');

        $font = app(CustomFontInstaller::class)->installFromPath($path, 'Dormant', active: false);

        $this->assertFalse($font->is_active);
        $this->assertNull(Voodbuilder::fonts()->get('dormant'));
    }

    public function test_deleting_a_font_removes_its_files_and_catalog_entry(): void
    {
        $path = $this->makeFontFile('Temporary-Regular.woff2');
        $font = app(CustomFontInstaller::class)->installFromPath($path, 'Temporary');

        $this->assertNotNull(Voodbuilder::fonts()->get('temporary'));

        $font->delete();

        $this->assertNull(Voodbuilder::fonts()->get('temporary'));
        $this->assertFalse(Storage::disk('public')->exists($font->stylesheetPath()));
    }

    public function test_css_stack_uses_a_category_fallback(): void
    {
        $this->assertSame(
            "'Acme', ui-serif, Georgia, serif",
            CustomFont::stackFor('Acme', 'serif'),
        );

        $this->assertSame(
            'Georgia, serif',
            CustomFont::stackFor('Acme', 'serif', 'Georgia, serif'),
        );

        $this->assertNull(CustomFontStylesheet::urlForSlug('missing-font'));
    }

    private function makeFontFile(string $name, string $contents = 'FAKEFONT'): string
    {
        $directory = sys_get_temp_dir() . '/voodbuilder-test-fonts-' . Str::random(8);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $path = $directory . '/' . $name;
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    /**
     * Build a minimal, readable TrueType file that only carries a `name` table.
     *
     * @param  array<int, string>  $names  name IDs mapped to their value.
     */
    private function makeTrueTypeFile(string $name, array $names): string
    {
        $nameTable = $this->buildNameTable($names);
        $offset = 12 + 16;

        $contents = pack('N', 0x00010000)
            . pack('n4', 1, 16, 0, 0)
            . 'name'
            . pack('N', 0)
            . pack('N', $offset)
            . pack('N', strlen($nameTable))
            . $nameTable;

        return $this->makeFontFile($name, $contents);
    }

    /**
     * Build a minimal WOFF 1.0 file with a zlib-compressed `name` table.
     *
     * @param  array<int, string>  $names  name IDs mapped to their value.
     */
    private function makeWoffFile(string $name, array $names): string
    {
        $nameTable = $this->buildNameTable($names);
        $compressed = (string) gzcompress($nameTable);
        $offset = 44 + 20;
        $length = $offset + strlen($compressed);

        $contents = 'wOFF'
            . pack('N', 0x00010000)
            . pack('N', $length)
            . pack('n2', 1, 0)
            . pack('N', 12 + strlen($nameTable))
            . pack('n2', 1, 0)
            . pack('N', 0)
            . pack('N', 0)
            . pack('N', 0)
            . pack('N', 0)
            . pack('N', 0)
            . 'name'
            . pack('N', $offset)
            . pack('N', strlen($compressed))
            . pack('N', strlen($nameTable))
            . pack('N', 0)
            . $compressed;

        return $this->makeFontFile($name, $contents);
    }

    /**
     * @param  array<int, string>  $entries
     */
    private function buildNameTable(array $entries): string
    {
        $storage = '';
        $records = '';

        foreach ($entries as $nameId => $value) {
            $encoded = (string) mb_convert_encoding($value, 'UTF-16BE', 'UTF-8');
            $records .= pack('n6', 3, 1, 0x0409, $nameId, strlen($encoded), strlen($storage));
            $storage .= $encoded;
        }

        return pack('n3', 0, count($entries), 6 + (12 * count($entries))) . $records . $storage;
    }

    /**
     * @param  array<string, string>  $entries
     */
    private function makeZipArchive(array $entries, string $archiveName = 'font-package.zip'): string
    {
        $directory = sys_get_temp_dir() . '/voodbuilder-test-fonts-' . Str::random(8);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $path = $directory . '/' . $archiveName;
        $zip = new ZipArchive;

        $this->assertTrue($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));

        foreach ($entries as $name => $contents) {
            $zip->addFromString($name, $contents);
        }

        $zip->close();
        $this->tempFiles[] = $path;

        return $path;
    }
}
