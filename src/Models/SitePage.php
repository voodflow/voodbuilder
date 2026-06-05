<?php

declare(strict_types=1);

namespace Voodflow\Vpress\Models;

use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use RalphJSmit\Laravel\SEO\Support\HasSEO;
use RalphJSmit\Laravel\SEO\Support\SEOData;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Voodflow\Vpress\Support\RichContentBlockRegistry;

class SitePage extends Model implements HasRichContent
{
    use HasSEO;
    use HasSlug;
    use InteractsWithRichContent;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'layout',
        'is_home',
        'published',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_home' => 'boolean',
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
            return route('home');
        }

        return Route::has('vpress.pages.show')
            ? route('vpress.pages.show', $this->slug)
            : url('/pages/'.$this->slug);
    }

    public function renderedContent(): string
    {
        if (blank($this->content)) {
            return '';
        }

        return RichContentRenderer::make($this->content)
            ->customBlocks(app(RichContentBlockRegistry::class)->rendererBlocks())
            ->toHtml();
    }

    public function layoutView(): string
    {
        return match ($this->layout) {
            'home' => config('vpress.layouts.home', 'vpress::layouts.home'),
            'doc' => config('vpress.layouts.doc', 'vpress::layouts.doc'),
            default => config('vpress.layouts.page', 'vpress::layouts.page'),
        };
    }

    public function contentSection(): string
    {
        return match ($this->layout) {
            'home' => 'home',
            default => 'page',
        };
    }

    public function getDynamicSEOData(): SEOData
    {
        return new SEOData(
            title: $this->title,
            description: $this->excerptFromContent(),
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
