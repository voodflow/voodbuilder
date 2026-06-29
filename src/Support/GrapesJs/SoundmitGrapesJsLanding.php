<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Support\GrapesJs;

use Voodflow\Voodbuilder\Support\LandingBlockMedia;
use Voodflow\Voodbuilder\Support\VoodbuilderPaths;

/**
 * GrapesJS HTML payload mirroring the Soundmit exhibitor landing (soundmit.com).
 */
final class SoundmitGrapesJsLanding
{
    /**
     * @param  array{hero?: string, split?: string}  $assetPaths  Storage paths from SoundmitLandingAssets::install()
     * @return array{html: string, css: string, project: null}
     */
    public static function payload(array $assetPaths = []): array
    {
        $heroUrl = LandingBlockMedia::publicUrl($assetPaths['hero'] ?? '')
            ?? 'https://images.unsplash.com/photo-1511379938547-c1f69419868d?auto=format&fit=crop&w=1920&q=80';
        $splitUrl = LandingBlockMedia::publicUrl($assetPaths['split'] ?? '')
            ?? 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=1400&q=80';

        return [
            'html' => self::html($heroUrl, $splitUrl),
            'css' => self::css(),
            'project' => null,
        ];
    }

    protected static function html(string $heroUrl, string $splitUrl): string
    {
        $hero = e($heroUrl);
        $split = e($splitUrl);

        return <<<HTML
<section class="relative flex min-h-[70vh] items-center justify-center bg-gray-900 text-white body-font">
  <img src="{$hero}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-50" />
  <div class="absolute inset-0 bg-gray-900/65"></div>
  <div class="relative z-10 mx-auto max-w-4xl px-6 py-24 text-center">
    <p class="mb-4 text-sm font-semibold uppercase tracking-[0.2em] text-red-300">October 2 - 4, 2026 · Cremona, Italy</p>
    <h1 class="title-font mb-6 text-4xl font-bold tracking-tight sm:text-6xl">Soundmit 2026</h1>
    <p class="mx-auto mb-10 max-w-2xl text-lg leading-relaxed text-gray-200">Exhibit at the international show for synths, electronic instruments and studio technology inside Cremona Musica.</p>
    <div class="flex flex-wrap items-center justify-center gap-4">
      <a href="mailto:info@soundmit.com" class="inline-flex rounded-lg bg-red-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-red-700">Apply to exhibit</a>
      <a href="/pages/soundmit-2026" class="inline-flex rounded-lg border border-white/40 px-8 py-3 text-sm font-semibold text-white transition hover:bg-white/10">Visitor info</a>
    </div>
  </div>
</section>
<section class="bg-white py-20 body-font text-gray-600">
  <div class="container mx-auto px-6">
    <h2 class="title-font mb-12 text-center text-3xl font-bold text-gray-900">Why Exhibit at Soundmit?</h2>
    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-4">
      <div class="rounded-xl border border-gray-200 p-6"><h3 class="title-font mb-3 text-lg font-semibold text-gray-900">Qualified Global Audience</h3><p class="text-sm leading-relaxed">Meet 20,000+ visitors including European distributors, dealers, content creators and advanced users actively looking for new products.</p></div>
      <div class="rounded-xl border border-gray-200 p-6"><h3 class="title-font mb-3 text-lg font-semibold text-gray-900">High-Impact B2B/B2C Environment</h3><p class="text-sm leading-relaxed">Network with 400+ leading music companies in a curated area dedicated to electronic instruments, pro audio and studio solutions.</p></div>
      <div class="rounded-xl border border-gray-200 p-6"><h3 class="title-font mb-3 text-lg font-semibold text-gray-900">Focused Lead Generation</h3><p class="text-sm leading-relaxed">Present your synths, modules and studio gear to a community specifically interested in electronic sound and music production technology.</p></div>
      <div class="rounded-xl border border-gray-200 p-6"><h3 class="title-font mb-3 text-lg font-semibold text-gray-900">Cremona Musica Context</h3><p class="text-sm leading-relaxed">Associate your brand with Cremona Musica, where the world's finest instruments and cutting-edge technology share the same stage.</p></div>
    </div>
  </div>
</section>
<section class="border-y border-gray-200 bg-gray-50 py-16 body-font">
  <div class="container mx-auto grid gap-8 px-6 text-center md:grid-cols-3">
    <div><p class="title-font text-4xl font-bold text-red-600">20,000+</p><p class="mt-2 text-sm font-medium uppercase tracking-wide text-gray-500">Qualified Visitors</p></div>
    <div><p class="title-font text-4xl font-bold text-red-600">400+</p><p class="mt-2 text-sm font-medium uppercase tracking-wide text-gray-500">Exhibiting Brands</p></div>
    <div><p class="title-font text-4xl font-bold text-red-600">30+</p><p class="mt-2 text-sm font-medium uppercase tracking-wide text-gray-500">International Markets</p></div>
  </div>
</section>
<section class="bg-white py-20 body-font text-gray-600">
  <div class="container mx-auto flex flex-wrap items-center gap-12 px-6">
    <div class="lg:w-1/2">
      <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-red-600">Technology meets tradition</p>
      <h2 class="title-font mb-6 text-3xl font-bold text-gray-900">Technology Meets Tradition</h2>
      <p class="mb-4 leading-relaxed">After 16 years of shaping the music tech landscape, Soundmit brings its technological excellence to Cremona Musica, the world's most prestigious instrument exhibition.</p>
      <p class="mb-4 leading-relaxed">Selected as the innovative core of the Electric Sound Village, we represent the cutting edge: from boutique modular synths to professional studio outboard and software for music production.</p>
      <p class="mb-8 leading-relaxed">More than a trade show, Soundmit is a vibrant ecosystem where global brands, boutique creators and artists meet through live showcases, talks and high-level networking.</p>
      <a href="mailto:info@soundmit.com" class="inline-flex rounded-lg bg-red-600 px-6 py-3 text-sm font-semibold text-white hover:bg-red-700">Our story</a>
    </div>
    <div class="lg:w-1/2"><img src="{$split}" alt="" class="w-full rounded-2xl object-cover shadow-xl" /></div>
  </div>
</section>
<section class="bg-gray-50 py-20 body-font text-gray-600">
  <div class="container mx-auto max-w-3xl px-6">
    <h2 class="title-font mb-10 text-center text-3xl font-bold text-gray-900">Our story</h2>
    <ol class="space-y-8 border-l-2 border-red-200 pl-8">
      <li><p class="text-sm font-semibold uppercase tracking-wide text-red-600">2011 — Turin</p><p class="mt-1 leading-relaxed">Soundmit is born as a dedicated meeting point for electronic instruments and music technology.</p></li>
      <li><p class="text-sm font-semibold uppercase tracking-wide text-red-600">2015 — Growth</p><p class="mt-1 leading-relaxed">The show expands its international exhibitor base and live demo program.</p></li>
      <li><p class="text-sm font-semibold uppercase tracking-wide text-red-600">2020 — Innovation</p><p class="mt-1 leading-relaxed">New formats connect brands, creators and the professional audio community.</p></li>
      <li><p class="text-sm font-semibold uppercase tracking-wide text-red-600">2026 — Cremona</p><p class="mt-1 leading-relaxed">Soundmit moves to Cremona Musica, joining the world's most prestigious instrument fair.</p></li>
    </ol>
  </div>
</section>
<section class="bg-gray-900 py-16 text-center text-white body-font">
  <div class="container mx-auto px-6">
    <h2 class="title-font mb-4 text-2xl font-bold tracking-wide sm:text-3xl">SYNTHS · OUTBOARD · MODULAR · STUDIO GEAR · ELECTRONICS</h2>
    <p class="mx-auto max-w-3xl text-gray-300">Soundmit is the reference event for electronic sound: synthesizers, modular systems, studio outboard, controllers and music production software.</p>
  </div>
</section>
<section class="bg-red-600 py-20 text-center text-white body-font">
  <div class="container mx-auto px-6">
    <h2 class="title-font mb-3 text-3xl font-bold">October 2 - 4, 2026</h2>
    <p class="mb-8 text-lg text-red-100">Cremona Musica / Cremona</p>
    <a href="mailto:info@soundmit.com" class="inline-flex rounded-lg bg-white px-8 py-3 text-sm font-semibold text-red-700 hover:bg-red-50">Apply to exhibit</a>
  </div>
</section>
HTML;
    }

    protected static function css(): string
    {
        return '';
    }

    public static function writeUtilitiesCatalog(): void
    {
        $path = VoodbuilderPaths::packagePath().'/resources/grapesjs/soundmit-landing-catalog.html';
        $html = self::payload()['html'];

        file_put_contents(
            $path,
            '<!doctype html><html><body>'.$html.'</body></html>',
        );
    }
}
