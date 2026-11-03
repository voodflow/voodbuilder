<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
     * @param  array<string, string>  $entries
     */
    private function makeZipArchive(array $entries): string
    {
        $directory = sys_get_temp_dir() . '/voodbuilder-test-fonts-' . Str::random(8);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $path = $directory . '/font-package.zip';
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
