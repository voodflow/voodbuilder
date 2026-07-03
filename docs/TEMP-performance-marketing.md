# VoodBuilder Performance — Draft Marketing Brief

> **Status:** Temporary draft for marketing and sales enablement.  
> **Last updated:** July 2026  
> **Audience:** Prospects, partners, technical evaluators  
> **Internal reference:** `docs/analisi-homepage-performance-2026-07-02.md`

---

## One-line pitch

**VoodBuilder compiles visual component styles once in the editor — never on your visitors’ page loads.**

---

## Why this matters

Page speed is not a nice-to-have for a visual page builder. It is the product.

When a marketing homepage takes **10 seconds** before the browser receives the first byte, every downstream metric suffers: bounce rate, SEO rankings, ad quality scores, and trust. A page builder that looks great in the editor but ships slowly in production fails the most important test — the one your customers never see.

VoodBuilder is designed around a simple rule:

| Context | Tailwind / CSS compilation |
|---|---|
| **Editor** (authors, admins) | Compile when saving or when content actually changes |
| **Public site** (visitors, crawlers) | **Never** compile at request time — serve pre-built CSS from the database |

That separation is what turns a demo-grade builder into production-grade infrastructure.

---

## The problem we found (and fixed)

### Symptom

On a real project homepage (GrapesJS builder, 8 unique library components, 9 DOM instances):

| Metric | Measured value |
|---|---|
| **TTFB** (time to first byte) | **~10.0–10.4 s** (stable across 3 runs) |
| Total HTML | 341 KB |
| Inline CSS | 223 KB |
| Layout chrome alone | ~43 ms |
| HTML content render | ~69 ms |

Almost the entire delay happened **before PHP sent a single byte** — not in the browser, not on the network edge.

### Root cause

The render pipeline called **Node + Tailwind JIT** for every library component on **every HTTP request**, even when compiled CSS was already stored in the database.

Each component cost roughly **500–1,200 ms** of subprocess time. Eight components on one page ≈ **5+ seconds** for CSS alone. A Blade template bug doubled that work by calling `renderedStyles()` twice per page view.

```
Visitor requests homepage
  → PHP loads page from DB          (~1 ms)
  → Renders HTML                    (~69 ms)
  → For each component:
      → Spawn Node
      → Run Tailwind compile        (~600 ms each)   ← wrong place
  → Scope CSS per instance
  → Send response                   (~10 s total TTFB)
```

This is the wrong architecture for a public site: **build time work was disguised as runtime work.**

---

## The VoodBuilder approach: compile once, publish fast

### Design principles

1. **Compile at authoring time**  
   When a component is created, imported, or updated in the editor, Tailwind CSS is generated and persisted.

2. **Checksum-based invalidation**  
   Each library component stores an `html_checksum` (SHA-256 of normalized HTML). Recompilation happens only when HTML changes or stored CSS is missing — not on every page view.

3. **Two explicit code paths**
   - **`publishedCssForStoredHtml()`** — public pages. Reads stored CSS. **No Node. No JIT.**
   - **`resolvedCssForStoredHtml()`** — editor / admin API. May compile when checksum is stale.

4. **Request-level memoization**  
   `renderedStyles()` is computed once per HTTP request (and the Blade view no longer invokes it twice).

### Target architecture

```
Author saves component in editor
  → Normalize HTML
  → Compile Tailwind (once)
  → Store css + html_checksum in DB

Visitor requests homepage
  → PHP loads page + components from DB
  → publishedCssForStoredHtml() per component   (microseconds)
  → Scope CSS per instance id
  → Send response                               (target: <200 ms TTFB)
```

### Expected impact (same homepage, post-fix)

| Metric | Before | After (expected) |
|---|---|---|
| TTFB | ~10 s | **< 200 ms** |
| `renderedStyles()` | ~7 s (single call) | **< 100 ms** |
| Node subprocesses per page view | 8+ | **0** |

*Figures from lab profiling on Docker; production numbers depend on hardware, DB latency, and page complexity.*

---

## What you can say in marketing (approved framing)

- **“Styles are built when you publish, not when your users browse.”**  
  Visitors get static-speed delivery from pre-computed CSS.

- **“Visual editing without the serverless-tax of on-the-fly CSS generation.”**  
  No hidden Node farm required at scale.

- **“Component library CSS is versioned with a checksum.”**  
  Safe invalidation without wasteful full recompiles.

- **“Sub-200 ms server response is the design target for builder-driven homepages.”**  
  Backed by measured before/after profiling, not hand-waving.

### What to avoid claiming (until measured in prod)

- Exact millisecond numbers on customer infrastructure  
- “Fastest page builder” superlatives without benchmark context  
- Lighthouse scores (not yet profiled in this document)

---

## How to measure performance (reproducible playbook)

Use these steps to demo or validate performance in any VoodBuilder + Laravel environment.

### Prerequisites

- App running (Docker or local)
- Homepage built with GrapesJS (`SitePage` with `builder = grapesjs`)
- At least one page using library components (`data-voodbuilder-component`)

### 1. TTFB — server response time (curl)

Measures how long PHP takes before the first byte reaches the client. This is the metric that exposed our 10 s bottleneck.

```bash
# Run 3 times; results should be stable
for i in 1 2 3; do
  curl -s -o /dev/null -w "run $i: %{time_starttransfer}s (TTFB)\n" "https://your-site.example/"
done
```

**Healthy target for a component-rich homepage:** TTFB **< 300 ms** on local Docker, **< 500 ms** on modest production hardware.

### 2. CSS render cost — Laravel tinker

Isolates the style pipeline from layout, network, and browser.

```bash
php artisan tinker --execute '
$page = \Voodflow\Voodbuilder\Models\SitePage::homePage("en");
$t = microtime(true);
$styles = $page->renderedStyles();
echo "renderedStyles: " . round((microtime(true) - $t) * 1000) . " ms\n";
echo "CSS bytes: " . strlen((string) $styles) . "\n";
'
```

**Healthy target:** **< 100 ms** with stored component CSS (zero Node compiles).

### 3. Inline CSS payload size

Large inline CSS affects parse time in the browser after TTFB is fixed.

```bash
curl -s "https://your-site.example/" | python3 -c "
import re, sys
html = sys.stdin.read()
styles = re.findall(r'<style[^>]*>(.*?)</style>', html, re.S)
print('style blocks:', len(styles))
print('inline CSS bytes:', sum(len(s) for s in styles))
"
```

Use this to track future optimizations (external CSS files, deduplication).

### 4. Browser DevTools — Network tab

1. Open the public homepage (not `?edit=1`).
2. Network → disable cache → hard reload.
3. Inspect the **document** request:
   - **Waiting (TTFB)** should match curl within normal variance.
4. Confirm there are **no** requests to internal compile endpoints on page load.

### 5. Editor vs public — sanity check

| URL | Node / compile expected? |
|---|---|
| `/?edit=1` (authenticated editor) | Yes, when saving components or loading stale catalog entries |
| `/` (public homepage) | **No** |

If public pages trigger `compile-component-tailwind.mjs`, something is misconfigured.

### 6. Automated tests (CI / regression guard)

```bash
php artisan test --filter=GrapesJsPastedComponentNormalizerTest
php artisan test --filter=GrapesJsPhaseOneRenderersTest
php artisan test --filter=SitePageGrapesJsTest
```

Key assertions:

- `publishedCssForStoredHtml()` never expands missing utilities from HTML at runtime  
- `GrapesJsComponentCssRenderer` uses the publish path  
- `renderedStyles()` is memoized per request

---

## Suggested demo script (5 minutes)

1. **Show the problem (historical)** — slide or terminal recording: 10 s TTFB, explain Node-per-component.  
2. **Show the fix** — same `curl` loop, sub-second TTFB.  
3. **Show the architecture** — “compile in editor, checksum, serve from DB.”  
4. **Open DevTools** — single document request, no compile API calls.  
5. **Open editor** — explain compilation still happens where it belongs: when authors change content.

---

## Roadmap (honest next steps)

These are **not** blockers for the compile-once fix, but they improve payload and polish:

| Priority | Improvement | Benefit |
|---|---|---|
| High | External versioned CSS asset instead of 200+ KB inline | Faster browser parse, better caching |
| High | Strip `data-gjs-*` editor attributes at publish | Cleaner HTML, smaller DOM |
| Medium | Deduplicate scoped CSS rules across instances | Smaller payload |
| Medium | Redis / file cache for full page CSS hash | Extra margin at very high traffic |
| Low | Disable dev-only browser loggers in production | Minor noise reduction |

---

## Glossary

| Term | Meaning |
|---|---|
| **TTFB** | Time To First Byte — server processing + first network packet |
| **Component library** | Reusable blocks stored in `voodbuilder_components` |
| **Checksum** | `html_checksum` — fingerprint of component HTML; invalidates CSS when content changes |
| **Publish path** | Code that serves public pages; must not invoke Tailwind JIT |
| **Editor path** | Authoring APIs and GrapesJS UI; may compile when content is new or stale |

---

## Document lifecycle

This file is **temporary**. Before public marketing use:

- [ ] Re-run benchmarks on production-like hardware and replace “expected” with measured “after” numbers  
- [ ] Add 1–2 screenshots (Network tab, curl output)  
- [ ] Legal / product review of claims  
- [ ] Move to public docs or landing page copy  
- [ ] Delete or archive this `TEMP-` draft

---

*Questions or updated benchmarks: update `docs/analisi-homepage-performance-2026-07-02.md` (internal, Italian) and sync the headline numbers here.*
