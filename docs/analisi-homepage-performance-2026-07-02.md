# Analisi homepage — performance e qualità output builder

**Data:** 2 luglio 2026  
**URL analizzato:** `http://localhost:8006/#` (route `home`, SitePage id=19, sub-tema `seires`)  
**Ambiente:** container Docker `cosmolab-app-1` / `cosmolab-web-1`

---

## Metriche misurate

| Metrica | Valore |
|---|---|
| **TTFB (server)** | **~10,0–10,4 s** (costante su 3 richieste) |
| HTML totale | 341 KB |
| CSS inline | **223 KB** (un solo `<style>`) |
| Body senza CSS | ~109 KB |
| Componenti incollati | 8 tipi, 9 istanze nel DOM |
| Layout chrome | ~43 ms |

### Benchmark curl (3 run)

```
run 1: 10.401929s
run 2: 10.031188s
run 3: 10.410755s
```

Il collo di bottiglia è quasi tutto **PHP prima di inviare un byte al browser**.

### Profilo rendering (tinker nel container)

| Operazione | Tempo | Output |
|---|---|---|
| Query home page | 1 ms | SitePage id=19 |
| `renderedContent()` | 69 ms | 57 KB HTML |
| `cssForHtml()` | ~5.060 ms | — |
| `renderedStyles()` (singola) | ~7.205 ms | 223 KB CSS |
| `renderedStyles()` (doppia) | ~10.533 ms | — |
| Full view render | ~10.456 ms | — |
| Layout alone | 43 ms | — |

### Payload builder salvato in DB

| Campo | Dimensione |
|---|---|
| `builder_payload.html` | 51.916 bytes |
| `builder_payload.css` | 47.573 bytes |
| Riferimenti `data-voodbuilder-component` | 18 |

### CSS inline nella risposta HTTP

- 4 blocchi `<style>` totali
- **232.116 bytes** di CSS inline complessivo
- Blocco più grande: **223.022 bytes** (CSS componenti scoped)

---

## 1. Cosa rende lenta la pagina

### Causa principale: ricompilazione Tailwind a ogni richiesta

In `GrapesJsPastedComponentNormalizer::resolvedCssForStoredHtml()` il CSS **viene sempre ricompilato** via subprocess Node, anche se è già salvato nel DB:

**File:** `src/Support/GrapesJs/GrapesJsPastedComponentNormalizer.php`

```php
public static function resolvedCssForStoredHtml(string $html, ?string $storedCss): string
{
    $html = TailblocksThemeTokenMigrator::migrateHtml($html);
    $storedCss = filled($storedCss) ? trim((string) $storedCss) : '';
    $compiled = self::compileTailwindCss($html);  // ← sempre eseguito

    if ($compiled !== '') {
        $manualCss = self::manualCssFromStoredComponentCss($storedCss);
        $css = self::mergeCss($manualCss !== '' ? $manualCss : null, $compiled);
        // ...
```

`compileTailwindCss()` invoca `GrapesJsComponentTailwindCompiler::compile()` che lancia:

```bash
node packages/voodflow/voodbuilder/scripts/compile-component-tailwind.mjs
```

Ogni componente in libreria costa **~500–1.200 ms** di subprocess Node, **anche con CSS già presente** nel DB (es. 46 KB salvati). La homepage usa 8 componenti unici → ~5 s solo per `cssForHtml()`.

**Componenti in libreria:** 28 totali; tempo `resolvedCssForStoredHtml` per ciascuno: 472–1.243 ms.

### Causa secondaria: doppia chiamata nel Blade

**File:** `resources/views/pages/site-page.blade.php`

```blade
@if ($page->usesGrapesJsBuilder() && filled($page->renderedStyles()))
    <style>{!! $page->renderedStyles() !!}</style>
@endif
```

`renderedStyles()` viene invocato **due volte** (check `filled()` + output). Nessuna cache: la seconda chiamata rifà tutto il lavoro Node. Questo spiega perché la view completa (~10,5 s) ≈ 2× il costo del CSS singolo (~5,5 s).

### Payload pesante (dopo il TTFB)

- **223 KB di CSS inline** con selettori scoped tipo:
  ```css
  [data-vb-component-id="019f22c1-..."] .voodbuilder-pasted-component .flex { ... }
  ```
  Migliaia di regole duplicate nel markup.
- Il CSS contiene sintassi Tailwind v4 nested (`&:hover`, `@media` annidati) che aumenta parsing e dimensione.
- Script dev Laravel Boost (~180 righe) che intercetta `console.*` e fa POST a `/_boost/browser-logs`.
- Cookie consent da CDN esterno (`cdn.jsdelivr.net`).
- Livewire + Alpine sul layout anche per una pagina sostanzialmente statica.

### Pipeline di rendering (flusso)

```
Request /
  → HomeController
    → SitePage::homePage("en")
    → view site-page.blade.php
      → renderedContent()  [69ms]  → GrapesJsRenderer::render()
      → renderedStyles()   [×2]    → GrapesJsRenderer::css()
          → GrapesJsComponentCssRenderer::cssForHtml()
              → per ogni BuilderComponent:
                  → resolvedCssForStoredHtml()  → Node Tailwind compile (~600ms each)
                  → GrapesJsComponentInstanceCssScoper::scopeCssToComponentInstance()
  → ~10s TTFB
  → 341KB HTML + 223KB CSS inline
```

### File coinvolti nel collo di bottiglia

| File | Ruolo |
|---|---|
| `src/Http/Controllers/HomeController.php` | Entry point homepage |
| `src/Models/SitePage.php` | `renderedContent()`, `renderedStyles()` |
| `src/Support/GrapesJs/GrapesJsRenderer.php` | `render()`, `css()` |
| `src/Support/GrapesJs/GrapesJsComponentCssRenderer.php` | `cssForHtml()` — loop componenti |
| `src/Support/GrapesJs/GrapesJsPastedComponentNormalizer.php` | `resolvedCssForStoredHtml()` — ricompila sempre |
| `src/Support/GrapesJs/GrapesJsComponentTailwindCompiler.php` | Subprocess Node |
| `scripts/compile-component-tailwind.mjs` | JIT Tailwind v4 |
| `src/Support/GrapesJs/GrapesJsComponentInstanceCssScoper.php` | Scope CSS per istanza |
| `resources/views/pages/site-page.blade.php` | Doppia chiamata `renderedStyles()` |

---

## 2. Qualità del codice generato dal builder

### Punti di forza

- Pipeline strutturata: sanitizzazione HTML, migrazione token tema (`vp-brand-*`), scoping CSS per istanza, binding dinamici (`data-voodbuilder-bind`).
- HTML semanticamente ragionevole: `<section>`, `aria-hidden` su decorazioni, `alt` su alcune immagini.
- Integrazione tema: bridge CSS per token Preline/semantic (`text-primary`, `bg-vp-brand-1`).
- Componenti riutilizzabili dalla libreria con override per istanza.
- SVG ID uniquification, strip script su import, media slot per video embed.

### Problemi nel markup pubblicato

| Problema | Evidenza |
|---|---|
| **Metadati editor non rimossi** | ~525 attributi `data-gjs-*` (`data-gjs-type`, `data-gjs-tagname`, `data-gjs-name`…) |
| **Shell vuote** | `<header></header><footer><div></div></footer>` all'inizio del contenuto |
| **Componenti annidati male** | Istanze `voodbuilder-component-rendered` una dentro l'altra |
| **Link placeholder** | `href="#"` su CTA ("Get started today") |
| **Colori legacy** | `focus-visible:outline-indigo-500`, `bg-gray-900` accanto a token `vp-*` |
| **Attributi corrotti** | `ratiodefault=""`, `data-gjs-resizable="{"` |
| **Commenti d'import** | `<!-- Include this script tag or install @tailwindplus/elements -->` |
| **Mix classi** | Tailwind + semantiche Preline (`text-muted-foreground`, `text-primary`) |
| **SEO** | Doppio `<title>`, meta description = blob di testo concatenato senza punteggiatura |

### Componenti sulla homepage (8 unici, 9 istanze DOM)

| Component ID | Istanze |
|---|---|
| `019f233e-9b3e-7216-adce-515bab82243b` | 2 |
| `019f234a-ddda-711b-8ab8-849dd48634a2` | 1 |
| `019f2344-a1d8-717d-a843-3e4bb93eabc5` | 1 |
| `019f2346-71a7-7167-813d-6642aaf67aec` | 1 |
| `019f22c1-5c00-717d-b21a-50924a6311d1` | 1 |
| `019f234f-02b0-737f-b9dc-8362f488aa75` | 1 |
| `019f2323-4b80-72d6-8f21-c00b118e847a` | 1 |
| `019f22c2-f879-7033-88dc-081a8789fa3e` | 1 |

### Verdetto qualità

| Area | Voto | Note |
|---|---|---|
| Architettura builder | Buona | Pipeline modulare, tema, componenti, binding |
| Output HTML pubblicato | Medio-basso | Metadati editor, placeholder, nesting |
| Performance server | **Critica** | ~10 s TTFB per bug architetturale CSS |
| Performance client | Medio | 341 KB HTML + parsing CSS massiccio |

L'**architettura del builder è solida**, ma l'**output di publish non è ancora "production-ready"** — sembra HTML dell'editor con una passata CSS pesante, non HTML pulito ottimizzato.

---

## 3. Cosa andrebbe migliorato

### Critico — performance server

1. **Non ricompilare Tailwind al render** se HTML del componente non è cambiato: usare `$storedCss` + eventuale hash dell'HTML. La compilazione Node va fatta solo in import/salvataggio componente.
   - Modificare `resolvedCssForStoredHtml()` per saltare `compileTailwindCss()` quando il CSS stored è valido.
2. **Fix Blade**: una sola chiamata, es.:
   ```blade
   @php $grapesJsStyles = $page->renderedStyles(); @endphp
   @if ($page->usesGrapesJsBuilder() && filled($grapesJsStyles))
       <style>{!! $grapesJsStyles !!}</style>
   @endif
   ```
3. **Cache del CSS risolto** per pagina (request cache minimo; in produzione Redis/file con invalidazione al save).
4. **Pre-calcolare CSS scoped** al salvataggio pagina/componente, non a ogni GET.

**Impatto atteso:** TTFB da **~10 s → <200 ms** (layout 43 ms + content 69 ms + CSS da cache).

### Alto — payload e frontend

5. Spostare il CSS componenti in **file statico versionato** (`/build/pages/home-{hash}.css`) invece di 223 KB inline.
6. **Deduplicare** CSS per `component_id` (già parzialmente fatto, ma lo scoping genera comunque blocchi enormi).
7. **Strip attributi GrapesJS** in fase di publish (`data-gjs-*`, classi `voodbuilder-gjs-*` non necessarie).
   - Aggiungere normalizer in `GrapesJsRenderer::html()` o pipeline publish.

### Medio — qualità output

8. Validazione al save: rimuovere shell vuote, link `#`, attributi malformati.
9. Completare migrazione colori legacy (`indigo-*` → `vp-brand-*`) — parzialmente gestito da `TailblocksThemeTokenMigrator`.
10. Impedire annidamento illecito di componenti libreria nell'editor.
11. Migliorare SEO: un solo `<title>`, description leggibile.

### Basso — dev experience

12. Disabilitare browser logger Boost in produzione.
13. Self-hostare cookie consent o caricarlo `defer`.

---

## 4. Contesto tecnico per l'agente

### Route e controller

- Route: `GET /` → `Voodflow\Voodbuilder\Http\Controllers\HomeController`
- Config: `config/voodbuilder.php` → `home.route_enabled`, sub-tema `seires`
- View: `voodbuilder::pages.site-page` → layout da `$page->layoutView()`
- Sub-tema attivo sulla pagina: `seires` (`data-voodbuilder-sub-theme="seires"`)

### Modello dati

- `SitePage` con `builder_payload` (JSON: `html`, `css`, `js`, `project`)
- `BuilderComponent` (tabella `voodbuilder_components`) — libreria componenti riutilizzabili
- CSS componente salvato in `BuilderComponent.css` ma **ignorato al render** se `compileTailwindCss()` ha successo

### Test esistenti rilevanti

```bash
php artisan test --compact --filter=GrapesJsComponentTailwindCompilerTest
php artisan test --compact --filter=GrapesJsPastedComponentNormalizerTest
php artisan test --compact --filter=GrapesJsComponentInstanceCssScoperTest
php artisan test --compact --filter=SitePageGrapesJsTest
```

### Comandi utili per verificare fix

```bash
# Profilo rendering (nel container)
docker exec cosmolab-app-1 php artisan tinker --execute '
$page = \Voodflow\Voodbuilder\Models\SitePage::homePage("en");
$t = microtime(true);
$page->renderedStyles();
echo round((microtime(true)-$t)*1000) . "ms\n";
'

# Benchmark TTFB
for i in 1 2 3; do
  curl -s -o /dev/null -w "run $i: %{time_total}s\n" "http://localhost:8006/"
done

# Dimensione CSS inline
curl -s "http://localhost:8006/" | python3 -c "
import re, sys
html = sys.stdin.read()
styles = re.findall(r'<style[^>]*>(.*?)</style>', html, re.S)
print('inline CSS bytes:', sum(len(s) for s in styles))
"
```

---

## 5. Task suggeriti per l'agente

### Task 1 — Fix doppia chiamata Blade (quick win)

- File: `resources/views/pages/site-page.blade.php`
- Assegnare `renderedStyles()` a variabile, usarla una sola volta
- Verificare: TTFB dovrebbe dimezzarsi (~5 s invece di ~10 s)

### Task 2 — Skip ricompilazione Tailwind al render (fix principale)

- File: `src/Support/GrapesJs/GrapesJsPastedComponentNormalizer.php`
- In `resolvedCssForStoredHtml()`: se `$storedCss` contiene già CSS compilato per `.voodbuilder-pasted-component` e l'HTML non è cambiato, usare quello senza chiamare Node
- Opzionale: hash HTML (`md5($html)`) salvato insieme al CSS per invalidazione
- Aggiungere/aggiornare test in `GrapesJsPastedComponentNormalizerTest`
- Verificare: `renderedStyles()` singola < 100 ms

### Task 3 — Cache request-level del CSS pagina

- File: `src/Support/GrapesJs/GrapesJsRenderer.php` o `SitePage.php`
- Memoizzare risultato di `css()` per istanza `SitePage` nella stessa request
- Verificare: doppia chiamata = 0 ms aggiuntivi

### Task 4 — Strip attributi GrapesJS al publish

- Nuovo normalizer o estensione pipeline in `GrapesJsRenderer::html()`
- Rimuovere `data-gjs-*`, `data-gjs-tagname`, attributi editor
- Test con HTML di esempio dalla homepage

---

_Riferimento sessione analisi: 2 luglio 2026. Vedi anche `docs/recap-2026-07-01.md` per il contesto sulle component library e fix colori._
