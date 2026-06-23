<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Models;

use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Voodflow\Vevents\Support\EventRichContentContext;
use Voodflow\Vpress\Enums\PageBuilder;
use Voodflow\Vpress\Support\GrapesJs\GrapesJsRenderer;
use Voodflow\Vpress\Support\RichContentBlockRegistry;
use Voodflow\Vpress\Support\SubThemeRegistry;
use Voodflow\Vpress\Support\SubThemeResolver;
use Voodflow\Vpress\Support\VpressUrls;

class SitePage extends Model implements HasRichContent
{
    use HasSEO;
    use HasSlug;
    use InteractsWithRichContent;

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

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getUrl(): string
    {
        if ($this->is_home) {
            return VpressUrls::home();
        }

        return Route::has('vpress.pages.show')
            ? route('vpress.pages.show', $this->slug)
            : url('/pages/'.$this->slug);
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
        if (! $this->usesGrapesJsBuilder()) {
            return null;
        }

        return app(GrapesJsRenderer::class)->css($this);
    }

    public function isLandingLayout(): bool
    {
        return $this->layout === 'landing' || $this->layout === 'home';
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
        return static::query()
            ->published()
            ->where('section', $section)
            ->where('section_home', false)
            ->when($exclude, fn (Builder $query) => $query->whereKeyNot($exclude->getKey()))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->get();
    }

    public static function sectionHomePage(string $section): ?self
    {
        return static::query()
            ->published()
            ->where('section', $section)
            ->where('section_home', true)
            ->first();
    }

    public function layoutView(): string
    {
        $layoutKey = match (true) {
            $this->isSectionHome() => 'section_index',
            $this->isSectionArticle() => 'article',
            $this->layout === 'landing' => 'landing',
            $this->layout === 'home' => 'home',
            $this->layout === 'doc' => 'doc',
            default => 'page',
        };

        $subThemeLayout = app(SubThemeRegistry::class)->layout($this->resolvedSubTheme(), $layoutKey);

        if ($subThemeLayout !== null) {
            return $subThemeLayout;
        }

        return match ($layoutKey) {
            'home' => config('vpress.layouts.home', 'vpress::layouts.home'),
            'landing' => config('vpress.layouts.landing', 'vpress::layouts.landing'),
            'doc' => config('vpress.layouts.doc', 'vpress::layouts.doc'),
            default => config('vpress.layouts.page', 'vpress::layouts.page'),
        };
    }

    public function contentSection(): string
    {
        return match ($this->layout) {
            'home' => 'home',
            'landing' => 'landing',
            default => 'page',
        };
    }

    public function getDynamicSEOData(): SEOData
    {
        return new SEOData(
            title: $this->title,
            description: $this->displayExcerpt(),
        );
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

    public static function homePage(): ?self
    {
        return static::query()
            ->published()
            ->where('is_home', true)
            ->first();
    }
}
