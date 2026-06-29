<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Support\GrapesJs;

use Illuminate\Support\Str;

/**
 * Builds the Vpress section block catalog from the legacy source JSON.
 * Output blocks are renamed and use neutral placeholder copy (no third-party branding).
 */
final class SectionBlocksCatalogBuilder
{
    /** @var array<string, string> */
    private const CATEGORY_LABELS = [
        'blog' => 'Sections · Articles',
        'hero' => 'Sections · Hero',
        'content' => 'Sections · Content',
        'contact' => 'Sections · Contact',
        'cta' => 'Sections · CTA',
        'feature' => 'Sections · Features',
        'features' => 'Sections · Features',
        'gallery' => 'Sections · Gallery',
        'header' => 'Sections · Header',
        'footer' => 'Sections · Footer',
        'pricing' => 'Sections · Pricing',
        'statistics' => 'Sections · Stats',
        'statistic' => 'Sections · Stats',
        'steps' => 'Sections · Steps',
        'step' => 'Sections · Steps',
        'team' => 'Sections · Team',
        'testimonial' => 'Sections · Testimonials',
        'commerce' => 'Sections · Commerce',
        'ecommerce' => 'Sections · Shop',
    ];

    /** @var array<string, string> */
    private const TYPE_LABELS = [
        'blog' => 'Article grid',
        'hero' => 'Hero',
        'content' => 'Content band',
        'contact' => 'Contact',
        'cta' => 'Call to action',
        'feature' => 'Feature grid',
        'features' => 'Feature grid',
        'gallery' => 'Gallery',
        'header' => 'Header bar',
        'footer' => 'Footer band',
        'pricing' => 'Pricing table',
        'statistics' => 'Statistics',
        'statistic' => 'Statistics',
        'steps' => 'Process steps',
        'step' => 'Process steps',
        'team' => 'Team members',
        'testimonial' => 'Testimonial',
        'commerce' => 'Product cards',
        'ecommerce' => 'Product showcase',
    ];

    /** @var array<int, string> */
    private const VARIANT_NUMBERS = [
        'A' => '1',
        'B' => '2',
        'C' => '3',
        'D' => '4',
        'E' => '5',
    ];

    public static function sourcePath(): string
    {
        return dirname(__DIR__, 3).'/resources/grapesjs/tailblocks-blocks.json';
    }

    public static function outputPath(): string
    {
        return dirname(__DIR__, 3).'/resources/grapesjs/section-blocks.json';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function build(?string $sourcePath = null): array
    {
        $sourcePath ??= self::sourcePath();

        if (! is_file($sourcePath)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($sourcePath), true);

        if (! is_array($decoded)) {
            return [];
        }

        $blocks = [];

        foreach ($decoded as $definition) {
            if (! is_array($definition)) {
                continue;
            }

            $type = $this->resolveType($definition);
            $variant = $this->resolveVariant($definition);
            $number = self::VARIANT_NUMBERS[$variant] ?? '1';

            $blocks[] = [
                'id' => 'vb-'.$type.'-'.$number,
                'label' => (self::TYPE_LABELS[$type] ?? Str::headline($type)).' · '.$number,
                'category' => self::CATEGORY_LABELS[$type] ?? 'Sections',
                'content' => $this->sanitizeContent((string) ($definition['content'] ?? '')),
                'mode' => $definition['mode'] ?? 'adaptive',
            ];
        }

        return $blocks;
    }

    public function write(?string $outputPath = null): int
    {
        $outputPath ??= self::outputPath();
        $blocks = $this->build();

        file_put_contents(
            $outputPath,
            json_encode($blocks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n",
        );

        return count($blocks);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function resolveType(array $definition): string
    {
        $category = (string) ($definition['category'] ?? '');
        $id = (string) ($definition['id'] ?? '');

        if (preg_match('#/(\\w+)\\s*$#', $category, $matches)) {
            return Str::lower($matches[1]);
        }

        if (preg_match('#tailblocks-(\\w+)-#', $id, $matches)) {
            return Str::lower($matches[1]);
        }

        return 'content';
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    protected function resolveVariant(array $definition): string
    {
        $label = trim((string) ($definition['label'] ?? ''));

        if (preg_match('/\b([A-E])$/i', $label, $matches)) {
            return strtoupper($matches[1]);
        }

        if (preg_match('/([a-e])$/i', (string) ($definition['id'] ?? ''), $matches)) {
            return strtoupper($matches[1]);
        }

        return 'A';
    }

    protected function sanitizeContent(string $html): string
    {
        $replacements = [
            ' body-font' => '',
            'title-font' => 'font-semibold',
            '>CATEGORY<' => '>Category<',
            '>SUBTITLE<' => '>Subtitle<',
            '>Learn More<' => '>Read more<',
            '>Button<' => '>Submit<',
            '>Feedback<' => '>Get in touch<',
            '>Contact Us<' => '>Contact us<',
            'The Catalyzer' => 'Featured story title',
            'The 400 Blows' => 'Second story title',
            'Shooting Stars' => 'Third story title',
            'Photo booth fam kinfolk cold-pressed sriracha leggings jianbing microdosing tousled waistcoat.' => 'A short summary for this item. Replace with your own description.',
            'Whatever cardigan tote bag tumblr hexagon brooklyn asymmetrical gentrify.' => 'Introductory paragraph for this section. Edit to match your message.',
            'Post-ironic portland shabby chic echo park, banjo fashion axe' => 'Supporting line for your contact section.',
            'Chicharrones blog helvetica normcore iceland tousled brook viral artisan.' => 'Optional legal or helper note below the form.',
            'Holden Caulfield' => 'Author name',
            'Alper Kamu' => 'Contributor name',
            'Henry Letham' => 'Editor name',
            'UI DEVELOPER' => 'Role',
            'DESIGNER' => 'Role',
            'example@email.com' => 'hello@example.com',
            '123-456-7890' => '+1 234 567 890',
            '49 Smith St.<br/>Saint Cloud, MN 56301' => '123 Example Street<br/>Your City',
            'Photo booth tattooed prism, portland taiyaki hoodie neutra typewriter' => 'Street address line for your business.',
        ];

        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        return preg_replace('/\s{2,}/', ' ', $html) ?? $html;
    }
}
