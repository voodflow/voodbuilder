# GrapesJS field bindings

Connect any element in a GrapesJS layout to **live server data** using `data-voodbuilder-bind`. Values are resolved on every page view — nothing is baked into static HTML at save time (except placeholders for the editor).

---

## Editor workflow

1. Open a GrapesJS page with `?edit=1`.
2. Select an element (`h1`, `p`, `img`, `a`, `button`, …).
3. Click **Make dynamic** (🔗).
4. Choose **Data source** and **Field**.
5. Save the page.

| Binding type | Element | Behaviour |
|--------------|---------|-----------|
| **text** | `h1`–`h6`, `p`, `span`, … | Replaces inner text with live value. Not editable inline (content is dynamic). |
| **url** | `a` | Sets `href`. **Label text is editable** (double-click). |
| **url** | `button` | Auto-converts to `<a role="button">` (same classes). **Double-click edits the label.** |
| **image** | `img` | Sets `src` (and `alt` when needed). |

Remove a binding: select element → **Clear dynamic binding** (✕).

---

## HTML contract

Saved markup uses a single attribute:

```html
<h1 data-voodbuilder-bind="vtuts.latest.title">[Latest tutorial: Title]</h1>
<a data-voodbuilder-bind="vtuts.latest.url" href="#">Read more</a>
<button type="button" data-voodbuilder-bind="vtuts.latest.url">Start here</button>
<img data-voodbuilder-bind="vtuts.latest.image" src="…" alt="">
```

Format: `{sourceId}.{fieldId}` — e.g. `vtuts.latest.introduction`.

The server **must not** leave unresolved `{{ }}` templates in HTML. Use `data-voodbuilder-bind` only.

---

## Field types

Defined in `Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingField`:

| Type | PHP constant | Use for |
|------|--------------|---------|
| Text | `TYPE_TEXT` | Titles, names, dates as formatted strings, counts, comma-separated lists |
| URL | `TYPE_URL` | Links (`a`) and clickable buttons |
| Image | `TYPE_IMAGE` | `img` `src` |

Return `?string` from `resolve()`. Empty/null leaves the element unchanged on the public site.

---

## Register a binding source (plugin authors)

### 1. Implement the contract

```php
<?php

declare(strict_types=1);

namespace My\Package\Voodbuilder;

use Voodflow\Voodbuilder\Contracts\GrapesJsBindingSource;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingContext;
use Voodflow\Voodbuilder\Support\GrapesJs\Bindings\BindingField;

final class LatestItemBindingSource implements GrapesJsBindingSource
{
    public function id(): string
    {
        return 'mypackage.latest'; // stable ID; may contain dots
    }

    public function label(): string
    {
        return __('mypackage::bindings.latest.label');
    }

    public function package(): string
    {
        return 'mypackage'; // groups sources in the editor UI
    }

    public function packageLabel(): string
    {
        return __('mypackage::bindings.package');
    }

    public function fields(): array
    {
        return [
            new BindingField('title', __('mypackage::bindings.latest.fields.title'), BindingField::TYPE_TEXT),
            new BindingField('url', __('mypackage::bindings.latest.fields.url'), BindingField::TYPE_URL),
            new BindingField('image', __('mypackage::bindings.latest.fields.image'), BindingField::TYPE_IMAGE),
        ];
    }

    public function resolve(string $fieldId, BindingContext $context): ?string
    {
        $item = $this->resolveLatestItem($context);

        if ($item === null) {
            return null;
        }

        return match ($fieldId) {
            'title' => $item->title,
            'url' => $item->getUrl(),
            'image' => $item->imageUrl(),
            default => null,
        };
    }

    private function resolveLatestItem(BindingContext $context): ?object
    {
        // Use $context->locale, $context->page, etc.
        return null;
    }
}
```

### 2. Register in a ServiceProvider

```php
use Voodflow\Voodbuilder\Voodbuilder;

public function boot(): void
{
    if (! class_exists(Voodbuilder::class)) {
        return;
    }

    Voodbuilder::grapesJsBindingSource(new LatestItemBindingSource);
}
```

### 3. Add translations

Editor labels come from your package lang files (`__('mypackage::bindings…')`).

---

## Optional: legacy field IDs

If you rename a field in `fields()` but pages already store the old key, add:

```php
/**
 * @return list<string>
 */
public function legacyFieldIds(): array
{
    return ['old_field_name'];
}
```

Implement `resolve()` for legacy IDs too. The registry keeps validating old attributes; the editor UI maps aliases when needed (see `excerpt` → `introduction` in Vtuts).

---

## BindingContext

Passed to every `resolve()` call:

- **`locale`** — current page/request locale.
- **`page`** — `SitePage` being rendered (when available).

Use it to scope queries (locale, site, channel).

---

## HTTP APIs (auth + page-builder permission)

| Endpoint | Purpose |
|----------|---------|
| `GET /voodbuilder/grapesjs/bindings` | Catalog grouped by package (Make dynamic modal) |
| `GET /voodbuilder/grapesjs/bindings/preview/{sitePage}` | Live values for editor preview |

---

## Server rendering pipeline

On public view and in the editor (`?edit=1` initial HTML):

1. Load `builder_payload.html`
2. `GrapesJsBindingRenderer` finds `data-voodbuilder-bind`
3. `BindingRegistry` resolves each key via your `GrapesJsBindingSource`
4. DOM is updated (text / `href` / `src` / `onclick` on buttons)

On **save**, `GrapesJsBindingStorageNormalizer` strips live values back to placeholders so the database does not store stale copy.

---

## Image bindings (4 resolution levels)

Image fields (`BindingField::TYPE_IMAGE`) must resolve to a **browser-loadable URL**. Prefer relative `/storage/...` paths so URLs work across ports and reverse proxies.

| Level | Who | How |
|-------|-----|-----|
| **1 — Conventions** | Voodbuilder | `BindingMediaUrlResolver` tries Spatie Media Library collections, `{field}Url()` accessors, and public-disk paths automatically. |
| **2 — Binding source** | Plugin author | Return the final URL from `GrapesJsBindingSource::resolve()` (optionally via a package helper such as `MyMedia::url($record)`). |
| **3 — Editor proxy** | Voodbuilder | When previewing in the editor (`BindingContext::editorPreview`), non-public Spatie media is served via `GET /voodbuilder/grapesjs/media/{id}` (auth required). |
| **4 — Custom hook** | Plugin author | Register a resolver for exotic storage (CDN, signed URLs, WordPress attachments, …). |

### Level 2 example (recommended for third-party plugins)

```php
'image' => BindingMediaUrlResolver::resolve($item, 'image', $item->imageUrl(), $context),
```

### Level 4 example (exotic storage)

```php
use Voodflow\Voodbuilder\Voodbuilder;

Voodbuilder::registerBindingImageResolver(MyModel::class, 'image', function (MyModel $record, BindingContext $context): ?string {
    return MyPackageMedia::signedUrl($record);
});
```

Registered resolvers run **before** automatic convention matching. Use them only when level 1 is insufficient.

### Alt text

Bound `img` elements use the related record **title** (or `name`, `label`, …) for `alt` when available — see `BindingImageAltResolver`.

---

## Vtuts: `vtuts.latest`

Registered by `Voodflow\Vtuts\Support\VtutsGrapesJsBlocks`.

Resolves the **latest publicly listed tutorial** for the current locale (`published_at` desc).

| Field ID | Type | Description |
|----------|------|-------------|
| `title` | text | Tutorial title |
| `introduction` | text | Introduction (plain text, from admin field) |
| `url` | url | Tutorial permalink |
| `image` | image | Featured image URL |
| `slug` | text | URL slug |
| `difficulty` | text | `beginner` / `intermediate` / `advanced` |
| `video_url` | url | External video URL |
| `category` | text | Primary category name |
| `category_url` | url | Category archive URL |
| `category_breadcrumb` | text | `Parent › Child` path |
| `tags` | text | Tag names, comma-separated |
| `tag_urls` | text | Tag archive URLs, comma-separated (same order as `tags`) |
| `author` | text | Author display name |
| `author_avatar` | image | Author avatar URL |
| `published_date` | text | Localized date |
| `published_datetime` | text | Localized date + time |
| `readers_count` | text | View/read counter |

**Legacy:** `vtuts.latest.excerpt` still resolves to `introduction`.

### Categories vs tags

- Each tutorial has **one primary category** (`category`, `category_url`, `category_breadcrumb`).
- **Multiple labels** use **tags** (`tags`, `tag_urls`). For several clickable tag links in one layout, use a server block or duplicate elements — one binding returns one string.

---

## Testing

```php
$registry = app(BindingRegistry::class);
$html = '<h1 data-voodbuilder-bind="mypackage.latest.title">Placeholder</h1>';
$rendered = app(GrapesJsBindingRenderer::class)->render($html, $page);
```

Register your fake source on the registry in unit tests (see `GrapesJsBindingRendererTest` in voodbuilder).

---

## Related

- [GRAPESJS.md](./GRAPESJS.md) — page builder setup, blocks, Tailblocks
- `Voodbuilder::grapesJsBindingSource()` — registration helper
- `Voodbuilder::grapesJsServerBlock()` — when you need full HTML widgets instead of single fields
