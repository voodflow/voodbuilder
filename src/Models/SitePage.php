<?php

declare(strict_types=1);

namespace Voodflow\Voodbuilder\Models;

use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use RalphJSmit\Laravel\SEO\Support\AlternateTag;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Voodflow\Vevents\Support\EventRichContentContext;
use Voodflow\Voodbuilder\Enums\PageBuilder;
use Voodflow\Voodbuilder\Support\GrapesJs\GrapesJsRenderer;
use Voodflow\Voodbuilder\Support\RichContentBlockRegistry;
use Voodflow\Voodbuilder\Support\SitePageResolver;
use Voodflow\Voodbuilder\Support\SubThemeRegistry;
use Voodflow\Voodbuilder\Support\SubThemeResolver;
use Voodflow\Voodbuilder\Support\VoodbuilderUrls;
use Voodflow\Vtuts\Support\Locales;

class SitePage extends Model implements HasRichContent
{
    use HasSEO;
    use HasSlug;
    use InteractsWithRichContent;

    public const LAYOUT_AUTO = 'auto';

    /** @var array{resolved?: bool, styles?: ?string} */
    private array $renderedStylesCache = [];

    protected $fillable = [
        'title',
        'slug',
        'content',
        'builder',
        'builder_payload',
        'layout',
        'hide_site_footer',
        'hide_site_nav',
        'sub_theme',
        'section',
        'excerpt',
        'section_home',
        'is_home',
        'locale',
        'translation_group_id',
        'published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'builder' => PageBuilder::class,
            'builder_payload' => 'array',
            'is_home' => 'boolean',
            'hide_site_footer' => 'boolean',
            'hide_site_nav' => 'boolean',
            'section_home' => 'boolean',
            'published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected function setUpRichContent(): void
    {
        $this->registerRichContent('content')
            ->customBlocks(app(RichContentBlockRegistry::class)->editorGroups());
    }

    protected static function booted(): void
    {
        static::creating(function (SitePage $page): void {
            if (blank($page->locale)) {
                $page->locale = class_exists(Locales::class) ? Locales::default() : 'en';
            }

            if (blank($page->translation_group_id)) {
                $page->translation_group_id = (string) Str::uuid();
            }
        });
    }

    /** @return HasMany<SitePage, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(self::class, 'translation_group_id', 'translation_group_id')
            ->whereKeyNot($this->getKey());
    }

    public function translationFor(string $locale): ?self
    {
        if ($this->locale === $locale) {
            return $this;
        }

        if (blank($this->translation_group_id)) {
            return null;
        }

        return static::query()
            ->where('translation_group_id', $this->translation_group_id)
            ->where('locale', $locale)
            ->first();
    }

    /** @return list<string> */
    public function translationLocaleCodes(): array
    {
        if (blank($this->translation_group_id)) {
            return [strtoupper((string) $this->locale)];
        }

        return static::query()
            ->where('translation_group_id', $this->translation_group_id)
            ->orderBy('locale')
            ->pluck('locale')
            ->map(fn (string $locale): string => strtoupper($locale))
            ->values()
            ->all();
    }

    /** @return list<string> */
    public function otherTranslationLocaleCodes(): array
    {
        if (blank($this->translation_group_id)) {
            return [];
        }

        return static::query()
            ->where('translation_group_id', $this->translation_group_id)
            ->where('locale', '!=', $this->locale)
            ->orderBy('locale')
            ->pluck('locale')
            ->map(fn (string $locale): string => strtoupper($locale))
            ->values()
            ->all();
    }

    public static function currentLocale(): string
    {
        return SitePageResolver::preferredLocale();
    }

    public function getSlugOptions(): SlugOptions
    {
        $options = SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();

        if (SitePageResolver::hasLocalizationColumns()) {
            $options->extraScope(fn ($builder) => $builder->where('locale', $this->locale));
        }

        return $options;
    }

    public function getUrl(): string
    {
        return VoodbuilderUrls::page($this);
    }

    public function usesGrapesJsBuilder(): bool
    {
        return ($this->builder ?? PageBuilder::RichEditor) === PageBuilder::GrapesJs;
    }

    public function renderedContent(): string
    {
        if ($this->usesGrapesJsBuilder()) {
            return app(GrapesJsRenderer::class)->render($this);
        }

        if (blank($this->content)) {
            return '';
        }

        $content = $this->content;

        if (class_exists(EventRichContentContext::class)) {
            $eventId = EventRichContentContext::resolveEventIdForPageSlug((string) $this->slug);

            if ($eventId !== null) {
                $content = EventRichContentContext::injectEventId($content, $eventId);
            }
        }

        return RichContentRenderer::make($content)
            ->customBlocks(app(RichContentBlockRegistry::class)->rendererBlocks())
            ->toHtml();
    }

    public function renderedStyles(): ?string
    {
        if (array_key_exists('resolved', $this->renderedStylesCache)) {
            return $this->renderedStylesCache['styles'];
        }

        if (! $this->usesGrapesJsBuilder()) {
            $this->renderedStylesCache = ['resolved' => true, 'styles' => null];

            return null;
        }

        $styles = app(GrapesJsRenderer::class)->css($this);
        $this->renderedStylesCache = ['resolved' => true, 'styles' => $styles];

        return $styles;
    }

    public function renderedScripts(): ?string
    {
        if (! $this->usesGrapesJsBuilder()) {
            return null;
        }

        return app(GrapesJsRenderer::class)->js($this);
    }

    public function usesAutomaticLayout(): bool
    {
        return $this->layout === self::LAYOUT_AUTO || blank($this->layout);
    }

    public function usesFullWidthLayout(): bool
    {
        if ($this->is_home && $this->layout !== 'page') {
            return true;
        }

        return in_array($this->layout, ['home', 'landing', 'full_width'], true);
    }

    public function isLandingLayout(): bool
    {
        return $this->usesFullWidthLayout();
    }

    public function shouldHideSiteFooter(): bool
    {
        return (bool) $this->hide_site_footer;
    }

    public function shouldHideSiteNav(): bool
    {
        return (bool) $this->hide_site_nav;
    }

    public function usesLandingCanvas(): bool
    {
        return $this->isLandingLayout();
    }

    public function resolvedSubTheme(): string
    {
        return SubThemeResolver::forPage($this);
    }

    public function isSectionHome(): bool
    {
        return (bool) $this->section_home;
    }

    public function isSectionArticle(): bool
    {
        return filled($this->section) && ! $this->section_home;
    }

    public function displayExcerpt(): string
    {
        if (filled($this->excerpt)) {
            return (string) $this->excerpt;
        }

        return $this->excerptFromContent();
    }

    /** @return Collection<int, SitePage> */
    public static function sectionArticles(string $section, ?self $exclude = null): Collection
    {
        $locale = self::currentLocale();

        return static::query()
            ->published()
            ->where('locale', $locale)
            ->where('section', $section)
            ->where('section_home', false)
            ->when($exclude, fn (Builder $query) => $query->whereKeyNot($exclude->getKey()))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();
    }

    public static function sectionHomePage(string $section): ?self
    {
        $locale = self::currentLocale();

        return static::query()
            ->published()
            ->where('locale', $locale)
            ->where('section', $section)
            ->where('section_home', true)
            ->first();
    }

    public function layoutView(): string
    {
        if ($this->usesFullWidthLayout()) {
            $subThemeLayout = app(SubThemeRegistry::class)->resolveLayout(
                $this->resolvedSubTheme(),
                'full_width',
                ['landing', 'home'],
            );

            if ($subThemeLayout !== null) {
                return $subThemeLayout;
            }

            return config('voodbuilder.layouts.full_width', 'voodbuilder::layouts.full-width');
        }

        $layoutKey = $this->resolvedLayoutKey();

        $subThemeLayout = app(SubThemeRegistry::class)->resolveLayout(
            $this->resolvedSubTheme(),
            $layoutKey,
            $this->layoutKeyAlternates($layoutKey),
        );

        if ($subThemeLayout !== null) {
            return $subThemeLayout;
        }

        return $this->fallbackLayoutView($layoutKey);
    }

    public function contentSection(): string
    {
        return $this->usesFullWidthLayout() ? 'full_width' : 'page';
    }

    protected function resolvedLayoutKey(): string
    {
        return match (true) {
            $this->isSectionHome() => 'section_index',
            $this->isSectionArticle() => 'article',
            $this->usesFullWidthLayout() => 'full_width',
            $this->layout === 'doc' => 'doc',
            default => 'page',
        };
    }

    protected function storedLayoutKey(): string
    {
        if ($this->usesAutomaticLayout()) {
            return $this->is_home ? 'home' : 'page';
        }

        return (string) $this->layout;
    }

    /**
     * @return list<string>
     */
    protected function layoutKeyAlternates(string $layoutKey): array
    {
        return match ($layoutKey) {
            'full_width' => ['landing', 'home'],
            'home' => ['landing', 'full_width'],
            'page' => ['landing', 'full_width'],
            default => [],
        };
    }

    protected function fallbackLayoutView(string $layoutKey): string
    {
        return match ($layoutKey) {
            'full_width', 'home', 'landing' => config('voodbuilder.layouts.full_width', 'voodbuilder::layouts.full-width'),
            'doc' => config('voodbuilder.layouts.doc', 'voodbuilder::layouts.doc'),
            default => config('voodbuilder.layouts.page', 'voodbuilder::layouts.page'),
        };
    }

    public function getDynamicSEOData(): SEOData
    {
        return new SEOData(
            title: $this->title,
            description: $this->displayExcerpt(),
            alternates: $this->seoAlternates(),
        );
    }

    /** @return list<AlternateTag>|null */
    protected function seoAlternates(): ?array
    {
        if (! SitePageResolver::localizationEnabled() || blank($this->translation_group_id)) {
            return null;
        }

        $alternates = [];

        foreach (static::query()
            ->where('translation_group_id', $this->translation_group_id)
            ->published()
            ->get() as $translation) {
            $alternates[] = new AlternateTag(
                hreflang: (string) $translation->locale,
                href: VoodbuilderUrls::page($translation),
            );
        }

        return $alternates === [] ? null : $alternates;
    }

    protected function excerptFromContent(): string
    {
        $html = strip_tags($this->renderedContent());

        return str($html)->squish()->limit(160)->toString();
    }

    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query
            ->where('published', true)
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public static function homePage(?string $locale = null): ?self
    {
        $locale ??= self::currentLocale();

        $page = static::query()
            ->published()
            ->where('is_home', true)
            ->where('locale', $locale)
            ->first();

        if ($page !== null) {
            return $page;
        }

        if (! class_exists(Locales::class) || $locale === Locales::default()) {
            return null;
        }

        $defaultHome = static::query()
            ->published()
            ->where('is_home', true)
            ->where('locale', Locales::default())
            ->first();

        return $defaultHome?->translationFor($locale);
    }
}
