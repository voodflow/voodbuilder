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
| **text** | CTA / Text link (`a`) | Replaces **label** (`data-voodbuilder-bind`). Can combine with a separate URL bind. |
| **url** | `a` / CTA | Sets `href` via `data-voodbuilder-bind-href` (preferred) or legacy `data-voodbuilder-bind`. Label stays static unless also text-bound. |
| **image** | `img` | Sets `src` (and `alt` when needed). |

Remove a binding: select element → **Clear dynamic binding** (✕).

---

## HTML contract

Saved markup uses `data-voodbuilder-bind` for content/label, and optionally `data-voodbuilder-bind-href` for an independent URL:

```html
<h1 data-voodbuilder-bind="vtuts.latest.title">[Latest tutorial: Title]</h1>
<a data-voodbuilder-bind-href="vtuts.latest.url" href="#">Read more</a>
<a data-voodbuilder-cta="true"
   data-voodbuilder-bind="users.item.name"
   data-voodbuilder-bind-href="users.item.profile_url"
   href="#">[Users: Name]</a>
<span data-voodbuilder-bind="users.auth.name" data-voodbuilder-hide-when-empty="1">[Users: Name]</span>
<span data-voodbuilder-bind="users.latest.name" data-voodbuilder-hide-when-empty="1">[Users: Name]</span>
<img data-voodbuilder-bind="vtuts.latest.image" src="…" alt="">
```

**Model Integration contexts:**

| Source | Meaning |
|--------|---------|
| `{alias}.auth` | Authenticated session user (`auth()->user()`). Only registered when the integration model is `Authenticatable`. |
| `{alias}.latest` | Newest DB record (`latest('id')`, with public/published scopes when present). |
| `{alias}.item` | Current row inside `data-voodbuilder-repeat="{alias}.list"`. |

**Rich Text dynamic tags:** Bricks-style menu — top group **User profile** inserts `{alias}.auth`; model groups (Users, Tutorials, …) insert `.latest` outside a list or `.item` inside that model’s repeat. Empty values are removed on the public page (`data-voodbuilder-hide-when-empty`).

Use-case guide (IT): [DYNAMIC_DATA_USE_CASES.md](./DYNAMIC_DATA_USE_CASES.md).

**Global text tags** (always on, not model bindings): `{current_year}`, `{brand_name}`, `{site_name}`, `{site_url}`, `{logged_username}` — see that doc. Footer copyright default is `© {current_year} {brand_name}`. Guest → empty `{logged_username}`.

**Block visibility:** use existing `data-voodbuilder-conditions` (logged-in, role, locale, path, dates, …) to show/hide whole sections — independent from field bindings.

Legacy (still supported): URL on `data-voodbuilder-bind` alone sets `href`.

Format: `{sourceId}.{fieldId}` — e.g. `vtuts.latest.introduction`.

The server **must not** leave unresolved `{{ }}` templates in HTML. Use `data-voodbuilder-bind` / `data-voodbuilder-bind-href` only.

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

### URL fields and `slug`

Fields guessed as URL (including `slug`) resolve through `BindingUrlResolver`:

1. `{field}Url` accessor when present
2. `getUrl()` on the model (recommended for list items such as tutorial series)
3. Stored scalar value when it already looks like a path or absolute URL

Binding `slug` on a **Read more** link therefore uses the model permalink, not the bare slug segment.

---

## List repeat (query loop)

Repeat a container for each record returned by a registered model integration list.

### HTML contract

```html
<div data-voodbuilder-repeat="vtuts.list" data-voodbuilder-repeat-limit="6" data-voodbuilder-repeat-offset="0" data-voodbuilder-repeat-sort="published_at" data-voodbuilder-repeat-sort-dir="desc">
  <article data-voodbuilder-repeat-item>
    <h2 data-voodbuilder-bind="vtuts.item.title">Title</h2>
    <a data-voodbuilder-bind="vtuts.item.url" href="#">Read more</a>
  </article>
  <p data-voodbuilder-repeat-empty>No tutorials published yet.</p>
</div>
```

| Attribute | Purpose |
|-----------|---------|
| `data-voodbuilder-repeat` | List key (`vtuts.list`, or a Model Integration `alias.list`) |
| `data-voodbuilder-repeat-limit` | Max items (1–24, default 6) |
| `data-voodbuilder-repeat-offset` | Skip the first N records (0–100). Use with a separate “Latest” binding or a second repeat (`limit=1`) for featured+list layouts |
| `data-voodbuilder-repeat-sort` | Optional sort column |
| `data-voodbuilder-repeat-sort-dir` | `asc` or `desc` |
| `data-voodbuilder-repeat-filter` | Optional JSON object of BelongsTo filters (`{"category":"3"}`) from Model Integration relations |
| `data-voodbuilder-repeat-item` | Marks the template node duplicated for each record |
| `data-voodbuilder-repeat-empty` | Optional fallback shown when the list returns zero records |

Inside the item template, bind fields with **List item** sources (not “Latest record”).

### Featured + sidebar list

For a block with one large story and a list of the next items, use **two** bindings (same sort):

1. Large card: bind fields with **Latest tutorial** / **Latest record**.
2. Sidebar container (`flex-col` with the small cards): open the **Dynamic** tab → **List repeat** → `Tutorials · Repeat list`, set **Skip first = 1**, **Items = 3**, sort `published_at` desc. Then select title/image/link **inside** the first card and bind with **List item (tutorial)**.

Do not put “Latest tutorial” on each sidebar row — that always resolves to the same newest record. Do not put “Latest” on the list container itself — use the List repeat panel.

**Legacy:** `vtuts.latest_list` still resolves as an alias of `vtuts.list`.

### Empty state

When the resolved list is empty:

1. The item template is removed from the output.
2. If a child has `data-voodbuilder-repeat-empty`, that markup is kept (bindings inside it are resolved).
3. Repeat attributes are stripped from the container.

Without an empty-state node, the container renders nothing.

Configure list repeat in the editor: select the grid/container → Inspector → **List repeat**.

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
